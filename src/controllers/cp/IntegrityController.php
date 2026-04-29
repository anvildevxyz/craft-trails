<?php

declare(strict_types=1);

namespace anvildev\trails\controllers\cp;

use anvildev\trails\dto\IntegrityRunSummary;
use anvildev\trails\Trails;
use Craft;
use yii\web\Response;

class IntegrityController extends BaseCpController
{
    protected function requiredPermission(): string
    {
        return 'trails-viewLogs';
    }

    public function actionIndex(): Response
    {
        $lastRun = Craft::$app->getCache()->get('trails:integrity:lastRun');
        return $this->renderTemplate('trails/integrity/index', [
            'lastRun' => $lastRun ?: null,
        ]);
    }

    public function actionCertificate(): Response
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        $dateFrom = $request->getBodyParam('dateFrom');
        $dateTo = $request->getBodyParam('dateTo');
        $format = $request->getBodyParam('format', 'json');

        if (empty($dateFrom) || empty($dateTo)) {
            Craft::$app->getSession()->setError(Craft::t('trails', 'Please specify a date range.'));
            return $this->redirect('trails/integrity');
        }

        $generated = Trails::getInstance()->certificate->generate($dateFrom, $dateTo, (string) $format);

        $response = Craft::$app->getResponse();
        $response->headers->set('Content-Type', $generated['contentType']);
        $response->headers->set('Content-Disposition', 'attachment; filename="trails-certificate-' . date('Y-m-d') . '.' . $generated['extension'] . '"');
        $response->content = $generated['content'];
        return $response;
    }

    public function actionVerify(): Response
    {
        $this->requirePostRequest();
        $trails = Trails::getInstance();

        $logResult = $trails->audit->verifyAllLogs();
        $merkleResult = $trails->merkle->verifyAllRoots();
        $anchorResult = $trails->anchor->verifyAll();

        $payload = IntegrityRunSummary::fromResults(
            at: time(),
            logResult: $logResult,
            merkleResult: $merkleResult,
            anchorResult: $anchorResult,
        );
        Craft::$app->getCache()->set('trails:integrity:lastRun', $payload, 7 * 24 * 60 * 60);

        if ($payload['overallStatus'] === 'passed') {
            $msg = Craft::t('trails', 'All {total} logs verified OK.', ['total' => $payload['total']]);
        } else {
            $msg = Craft::t(
                'trails',
                'Verification complete. {tampered} tampered, {merkle} merkle root(s) failed, {anchors} anchor(s) failed.',
                [
                    'tampered' => count($payload['tampered']),
                    'merkle' => $payload['merkleRootsFailed'],
                    'anchors' => $payload['anchorsFailed'],
                ]
            );
        }
        Craft::$app->getSession()->setNotice($msg);
        return $this->redirectToPostedUrl();
    }
}
