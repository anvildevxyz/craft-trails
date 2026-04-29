<?php

namespace anvildev\trails\controllers\cp;

use anvildev\trails\Trails;
use Craft;
use yii\web\Response;

class ExportController extends BaseCpController
{
    protected function requiredPermission(): string
    {
        return 'trails-exportLogs';
    }

    public function actionIndex(): Response
    {
        $audit = Trails::getInstance()->audit;
        $recentExports = \anvildev\trails\records\ExportRecord::find()
            ->where(['userId' => Craft::$app->getUser()->getId()])
            ->orderBy(['dateCreated' => SORT_DESC])
            ->limit(5)
            ->all();
        return $this->renderTemplate('trails/export/index', [
            'eventTypes' => $audit->getEventTypes(),
            'categories' => $audit->getCategories(),
            'users' => \craft\elements\User::find()->all(),
            'recentExports' => $recentExports,
        ]);
    }

    public function actionDownload(): Response
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        $dateFrom = $request->getBodyParam('dateFrom');
        $dateTo = $request->getBodyParam('dateTo');
        $criteria = [
            'dateFrom' => is_string($dateFrom) ? $dateFrom : null,
            'dateTo' => is_string($dateTo) ? $dateTo : null,
            'event' => $request->getBodyParam('event'),
            'category' => $request->getBodyParam('category'),
            'userId' => $request->getBodyParam('userId'),
        ];

        if (!empty($criteria['dateFrom']) && !strtotime($criteria['dateFrom'])) {
            Craft::$app->getSession()->setError(Craft::t('trails', 'Invalid "from" date format.'));
            return $this->redirect('trails/export');
        }
        if (!empty($criteria['dateTo']) && !strtotime($criteria['dateTo'])) {
            Craft::$app->getSession()->setError(Craft::t('trails', 'Invalid "to" date format.'));
            return $this->redirect('trails/export');
        }
        if (!empty($criteria['dateFrom']) && !empty($criteria['dateTo']) && strtotime($criteria['dateFrom']) > strtotime($criteria['dateTo'])) {
            Craft::$app->getSession()->setError(Craft::t('trails', '"From" date must be before "to" date.'));
            return $this->redirect('trails/export');
        }

        $formats = [
            'csv' => ['exportToCsv', 'text/csv'],
            'json' => ['exportToJson', 'application/json'],
            'html' => ['exportToHtml', 'text/html'],
        ];

        $format = $request->getBodyParam('format', 'csv');
        [$method, $contentType] = $formats[$format] ?? $formats['csv'];

        // Check if this should be a background export
        $exportService = Trails::getInstance()->export;
        if ($exportService->shouldRunInBackground($criteria)) {
            $currentUser = Craft::$app->getUser()->getIdentity();
            $canViewPii = $currentUser && ($currentUser->admin || $currentUser->can('trails-manageSettings'));
            $redactPii = $canViewPii ? (bool)$request->getBodyParam('redactPii', false) : true;

            $exportService->startBackgroundExport($format, $criteria, $redactPii);
            Craft::$app->getSession()->setNotice(
                Craft::t('trails', 'Export started in the background. You can download it from this page when complete.')
            );
            return $this->redirect('trails/export');
        }

        $currentUser = Craft::$app->getUser()->getIdentity();
        $canViewPii = $currentUser && ($currentUser->admin || $currentUser->can('trails-manageSettings'));
        // Non-PII users always get redaction regardless of the request param
        $redactPii = $canViewPii ? (bool)$request->getBodyParam('redactPii', false) : true;

        $response = Craft::$app->getResponse();
        // Without FORMAT_RAW, Yii's Response::prepare() overwrites the
        // Content-Type we set below based on the default FORMAT_HTML, so CSV
        // and JSON downloads arrive as "text/html" to the client.
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('Content-Disposition', 'attachment; filename="audit-trail-export-' . date('Y-m-d-His') . '.' . $format . '"');
        $response->content = Trails::getInstance()->export->$method($criteria, $redactPii);
        return $response;
    }

    public function actionStatus(): Response
    {
        $id = (int) Craft::$app->getRequest()->getRequiredQueryParam('id');
        $export = Trails::getInstance()->export->getExport($id);

        if (!$export) {
            return $this->asJson(['error' => 'Export not found']);
        }

        return $this->asJson([
            'id' => $export->id,
            'status' => $export->status,
            'progress' => $export->progress,
            'totalRecords' => $export->totalRecords,
            'format' => $export->format,
        ]);
    }

    /**
     * Download a previously-completed background export by id. Enforces:
     * - the export must belong to the current user (or current user is admin),
     * - status must be 'complete',
     * - the file must still exist on disk (it's pruned after dateExpires).
     */
    public function actionFile(): Response
    {
        $id = (int) Craft::$app->getRequest()->getRequiredQueryParam('id');
        $export = Trails::getInstance()->export->getExport($id);

        if ($export === null) {
            throw new \yii\web\NotFoundHttpException('Export not found.');
        }

        $currentUser = Craft::$app->getUser()->getIdentity();
        if ($currentUser === null) {
            throw new \yii\web\ForbiddenHttpException();
        }
        if (!$currentUser->admin && (int) $export->userId !== (int) $currentUser->id) {
            throw new \yii\web\ForbiddenHttpException('You can only download your own exports.');
        }

        if ($export->status !== 'complete') {
            Craft::$app->getSession()->setError(
                Craft::t('trails', 'Export is not ready yet (status: {status}).', ['status' => $export->status])
            );
            return $this->redirect('trails/export');
        }

        if (!$export->filePath || !file_exists($export->filePath)) {
            throw new \yii\web\NotFoundHttpException('Export file no longer exists on disk.');
        }

        $contentTypes = [
            'csv' => 'text/csv',
            'json' => 'application/json',
            'html' => 'text/html',
        ];
        $contentType = $contentTypes[$export->format] ?? 'application/octet-stream';
        $datePart = $export->dateCreated ? date('Y-m-d', strtotime((string) $export->dateCreated)) : date('Y-m-d');
        $filename = "audit-trail-export-{$export->id}-{$datePart}.{$export->format}";

        return Craft::$app->getResponse()->sendFile($export->filePath, $filename, [
            'mimeType' => $contentType,
        ]);
    }
}
