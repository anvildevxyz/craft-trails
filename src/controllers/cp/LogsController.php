<?php

namespace anvildev\trails\controllers\cp;

use anvildev\trails\helpers\EncryptionHelper;
use anvildev\trails\Trails;
use Craft;
use yii\web\Response;

class LogsController extends BaseCpController
{
    protected function requiredPermission(): string
    {
        return 'trails-viewLogs';
    }

    public function actionIndex(): Response
    {
        $request = Craft::$app->getRequest();

        $dateFromParam = $request->getQueryParam('dateFrom');
        $dateToParam = $request->getQueryParam('dateTo');
        $dateFrom = is_string($dateFromParam) ? $dateFromParam : '';
        $dateTo = is_string($dateToParam) ? $dateToParam : '';

        // Parse the user-supplied YYYY-MM-DD as UTC to match how dateCreated is
        // stored (Craft writes timestamps in UTC). A timezone-naive parse would
        // skew the day boundary by the server's local offset.
        $utc = new \DateTimeZone('UTC');
        $dateFromObj = \DateTime::createFromFormat('!Y-m-d', $dateFrom, $utc);
        $dateToObj = \DateTime::createFromFormat('!Y-m-d', $dateTo, $utc);

        $criteria = array_filter([
            'event' => $request->getQueryParam('event'),
            'category' => $request->getQueryParam('category'),
            'userId' => $request->getQueryParam('userId'),
            'dateFrom' => $dateFromObj ? $dateFromObj->format('Y-m-d 00:00:00') : null,
            'dateTo' => $dateToObj ? $dateToObj->format('Y-m-d 23:59:59') : null,
            'search' => $request->getQueryParam('search'),
            'limit' => min((int)($request->getQueryParam('pageSize') ?: 50), 100),
            'offset' => max(0, (int)$request->getQueryParam('offset', 0)),
        ], fn($v) => $v !== null && $v !== '');

        $audit = Trails::getInstance()->audit;

        // Only expose the user list to viewers who can already see PII — otherwise
        // a low-trust auditor could enumerate every Craft account via the filter UI.
        $currentUser = Craft::$app->getUser()->getIdentity();
        $canViewPii = $currentUser && ($currentUser->admin || $currentUser->can('trails-manageSettings'));
        $users = $canViewPii ? \craft\elements\User::find()->all() : [];

        return $this->renderTemplate('trails/logs/index', [
            'logs' => $audit->getLogs($criteria),
            'totalLogs' => $audit->countLogs($criteria),
            'eventTypes' => $audit->getEventTypes(),
            'categories' => $audit->getCategories(),
            'summary' => $audit->getActivitySummary(7),
            'users' => $users,
            'criteria' => $criteria,
            'dateFromRaw' => $dateFrom,
            'dateToRaw' => $dateTo,
            'pageSize' => min((int)($request->getQueryParam('pageSize') ?: 50), 100),
        ]);
    }

    public function actionView(int $logId): Response
    {
        $audit = Trails::getInstance()->audit;
        $log = $audit->getLogById($logId);

        if (!$log) {
            throw new \yii\web\NotFoundHttpException('Log not found');
        }

        $decryptedEmail = EncryptionHelper::decrypt($log->userEmail);

        $metadata = $log->metadata ? json_decode($log->metadata, true) : null;
        $oldValue = $log->oldValue ? json_decode($log->oldValue, true) : null;
        $newValue = $log->newValue ? json_decode($log->newValue, true) : null;

        $diff = null;
        if (is_array($oldValue) || is_array($newValue)) {
            $diff = \anvildev\trails\helpers\DiffRenderer::compare(
                is_array($oldValue) ? $oldValue : [],
                is_array($newValue) ? $newValue : [],
                skipUnchanged: true
            );
        }

        $elementUrl = null;
        if ($log->elementId && $log->elementType && class_exists($log->elementType)) {
            $element = Craft::$app->getElements()->getElementById(
                $log->elementId,
                $log->elementType
            );
            $elementUrl = $element?->getCpEditUrl();
        }

        // Chain navigation
        $prevLog = $nextLog = null;
        if ($log->chainPosition) {
            $prevLog = \anvildev\trails\records\AuditLogRecord::find()
                ->where(['chainPosition' => $log->chainPosition - 1])
                ->one();
            $nextLog = \anvildev\trails\records\AuditLogRecord::find()
                ->where(['chainPosition' => $log->chainPosition + 1])
                ->one();
        }

        $currentUser = Craft::$app->getUser()->getIdentity();
        $canViewPii = $currentUser && ($currentUser->admin || $currentUser->can('trails-manageSettings'));

        return $this->renderTemplate('trails/logs/view', [
            'log' => $log,
            'decryptedEmail' => $decryptedEmail,
            'metadata' => $metadata,
            'oldValue' => $oldValue,
            'newValue' => $newValue,
            'diff' => $diff,
            'hashValid' => $audit->verifyLogIntegrity($log),
            'elementUrl' => $elementUrl,
            'canViewPii' => $canViewPii,
            'prevLog' => $prevLog,
            'nextLog' => $nextLog,
        ]);
    }
}
