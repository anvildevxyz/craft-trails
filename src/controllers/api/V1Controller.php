<?php

declare(strict_types=1);

namespace anvildev\trails\controllers\api;

use anvildev\trails\dto\AuditLogEntry;
use anvildev\trails\helpers\EncryptionHelper;
use anvildev\trails\helpers\RateLimiter;
use anvildev\trails\query\AuditQuery;
use anvildev\trails\records\ExportRecord;
use anvildev\trails\Trails;
use Craft;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * REST API v1 controller for programmatic access to Trails audit data.
 *
 * All actions require the `trails-viewLogs` permission and are subject to
 * rate limiting based on the Authorization header token.
 */
class V1Controller extends Controller
{
    /** @var array{type:string,rateLimitKey:string,canViewPii:bool,scopes:string[]}|null */
    private ?array $authContext = null;

    /**
     * Allow anonymous requests so bearer token auth can work without a CP session.
     * Authentication is enforced explicitly in beforeAction().
     */
    protected array|bool|int $allowAnonymous = true;

    /**
     * Disable CSRF validation for REST API calls (clients use bearer tokens,
     * not session cookies, so CSRF is not relevant here).
     */
    public $enableCsrfValidation = false;

    private const RATE_LIMIT_WINDOW = 60; // seconds
    private const MAX_CERTIFICATE_RANGE_DAYS = 90;

    // =========================================================================
    // Lifecycle
    // =========================================================================

    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requireAcceptsJson();

        $settings = Trails::getInstance()->getSettings();
        if (!$settings->enableApi) {
            $this->asJson([
                'error' => 'Not Found',
                'message' => 'Trails API is disabled.',
            ])->setStatusCode(404)->send();
            Craft::info('Trails API request rejected because API is disabled.', 'trails');
            return false;
        }

        $auth = Trails::getInstance()->apiAuth->authenticateRequest();
        if ($auth === null) {
            $this->asJson([
                'error' => 'Unauthorized',
                'message' => 'Provide a valid Craft session with trails-viewLogs permission or a valid bearer token.',
            ])->setStatusCode(401)->send();
            Craft::warning('Trails API request rejected due to missing/invalid auth.', 'trails');
            return false;
        }
        $this->authContext = $auth;

        $scope = $this->requiredScopeForAction((string) $action->id);
        if (!Trails::getInstance()->apiAuth->tokenAllows($auth, $scope)) {
            throw new ForbiddenHttpException('API token does not allow this action.');
        }

        $limit = max(0, (int) $settings->apiRateLimit);
        if ($limit > 0) {
            $result = RateLimiter::check($auth['rateLimitKey'], $limit, self::RATE_LIMIT_WINDOW);

            // Always attach rate-limit headers so clients can track their quota.
            $headers = Craft::$app->getResponse()->getHeaders();
            foreach (RateLimiter::formatHeaders($limit, $result['remaining'], $result['resetAt']) as $name => $value) {
                $headers->set($name, $value);
            }

            if (!$result['allowed']) {
                $this->asJson([
                    'error' => 'Too Many Requests',
                    'message' => 'Rate limit exceeded. Please slow down.',
                    'retryAfter' => $result['resetAt'] - time(),
                ])->setStatusCode(429)->send();
                Craft::warning('Trails API rate limit exceeded for key: ' . $auth['rateLimitKey'], 'trails');
                return false;
            }
        }

        Craft::info('Trails API authenticated via ' . $auth['type'], 'trails');

        return true;
    }

    // =========================================================================
    // Actions
    // =========================================================================

    /**
     * GET /trails/api/v1/logs
     *
     * Returns a cursor-paginated list of audit log entries.
     *
     * Query params: event, category, userId, after, before, search, limit, cursor
     */
    public function actionLogs(): Response
    {
        $request = Craft::$app->getRequest();

        $query = new AuditQuery();

        $event = $request->getQueryParam('event');
        if (is_string($event) && $event !== '') {
            $query->event($event);
        }

        $category = $request->getQueryParam('category');
        if (is_string($category) && $category !== '') {
            $query->category($category);
        }

        $userId = $request->getQueryParam('userId');
        if ($userId !== null) {
            $query->user((int) $userId);
        }

        $after = $request->getQueryParam('after');
        if (is_string($after) && $after !== '') {
            $query->after($after);
        }

        $before = $request->getQueryParam('before');
        if (is_string($before) && $before !== '') {
            $query->before($before);
        }

        $search = $request->getQueryParam('search');
        if (is_string($search) && $search !== '') {
            $query->search($search);
        }

        $limit = $request->getQueryParam('limit');
        if ($limit !== null) {
            $query->limit((int) $limit);
        }

        $cursor = $request->getQueryParam('cursor');
        if (is_string($cursor) && $cursor !== '') {
            $query->cursor($cursor);
        }

        $result = $query->get();

        return $this->asJson([
            'data' => array_map(static fn($entry) => $entry->toArray(), $result->items),
            'pagination' => [
                'nextCursor' => $result->nextCursor,
                'totalCount' => $result->totalCount,
                'hasMore' => $result->hasMore(),
            ],
        ]);
    }

    /**
     * GET /trails/api/v1/logs/<id>
     *
     * Returns a single audit log entry with decoded payload fields and decrypted email.
     */
    public function actionLog(): Response
    {
        $request = Craft::$app->getRequest();
        $id = (int) $request->getRequiredQueryParam('id');

        $record = Trails::getInstance()->audit->getLogById($id);

        if ($record === null) {
            return $this->asJson(['error' => 'Not Found', 'message' => "Log {$id} not found."])
                ->setStatusCode(404);
        }

        $entry = AuditLogEntry::fromRecord($record);
        $data = $entry->toArray();
        $data['metadata'] = $entry->decodedMetadata();
        $data['oldValue'] = $entry->decodedOldValue();
        $data['newValue'] = $entry->decodedNewValue();

        $canViewPii = (bool) ($this->authContext['canViewPii'] ?? false);
        if ($canViewPii) {
            $data['userEmail'] = EncryptionHelper::decrypt($entry->userEmail);
        } else {
            $data['userEmail'] = $entry->userEmail ? '[redacted]' : null;
            $data['ipAddress'] = $data['ipAddress'] ? '[redacted]' : null;
            $data['userAgent'] = $data['userAgent'] ? '[redacted]' : null;
            $data['sessionId'] = null;
        }

        return $this->asJson($data);
    }

    /**
     * GET /trails/api/v1/logs/<id>/proof
     *
     * Returns a Merkle inclusion proof for the specified log entry.
     */
    public function actionProof(): Response
    {
        $request = Craft::$app->getRequest();
        $id = (int) $request->getRequiredQueryParam('id');

        $record = Trails::getInstance()->audit->getLogById($id);

        if ($record === null) {
            return $this->asJson(['error' => 'Not Found', 'message' => "Log {$id} not found."])
                ->setStatusCode(404);
        }

        if ($record->chainPosition === null) {
            return $this->asJson([
                'error' => 'Not Found',
                'message' => "Log {$id} has no chain position; proof unavailable.",
            ])->setStatusCode(404);
        }

        $proof = Trails::getInstance()->merkle->getInclusionProof($record->chainPosition);

        if ($proof === null) {
            return $this->asJson([
                'error' => 'Not Found',
                'message' => "No Merkle proof available for log {$id}.",
            ])->setStatusCode(404);
        }

        return $this->asJson($proof->toArray());
    }

    /**
     * GET /trails/api/v1/integrity
     *
     * Returns the result of the last integrity check run, or null if never run.
     */
    public function actionIntegrity(): Response
    {
        $lastRun = Craft::$app->getCache()->get('trails:integrity:lastRun');

        return $this->asJson([
            'lastRun' => $lastRun !== false ? $lastRun : null,
        ]);
    }

    /**
     * POST /trails/api/v1/certificate
     *
     * Generates an integrity certificate for the given date range.
     *
     * Body params: dateFrom (required), dateTo (required), format (json|pdf, default json)
     */
    public function actionCertificate(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $from = $this->parseCertificateDate($request->getRequiredBodyParam('dateFrom'), 'dateFrom');
        $to = $this->parseCertificateDate($request->getRequiredBodyParam('dateTo'), 'dateTo');
        if ($from > $to) {
            throw new BadRequestHttpException('dateFrom must be before or equal to dateTo.');
        }
        $rangeSeconds = $to->getTimestamp() - $from->getTimestamp();
        if ($rangeSeconds > (self::MAX_CERTIFICATE_RANGE_DAYS * 86400)) {
            throw new BadRequestHttpException('Certificate range is too large. Maximum is 90 days.');
        }

        $format = $request->getBodyParam('format', 'json');

        // Normalise format to a known value.
        if (!in_array($format, ['json', 'pdf'], true)) {
            $format = 'json';
        }

        $dateFrom = $from->format('Y-m-d H:i:s');
        $dateTo = $to->format('Y-m-d H:i:s');
        $generated = Trails::getInstance()->certificate->generate($dateFrom, $dateTo, (string) $format);
        $filename = 'trails-certificate-' . date('Y-m-d') . '.' . $generated['extension'];

        $response = Craft::$app->getResponse();
        $response->headers->set('Content-Type', $generated['contentType']);
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->content = $generated['content'];

        return $response;
    }

    /**
     * POST /trails/api/v1/export
     *
     * Starts a background export job.
     *
     * Body params: format (csv|json|html, default csv), criteria (object), redactPii (bool)
     */
    public function actionExport(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $format = $request->getBodyParam('format', 'csv');
        if (!in_array($format, ['csv', 'json', 'html'], true)) {
            $format = 'csv';
        }

        $criteriaParam = $request->getBodyParam('criteria', []);
        $criteria = is_array($criteriaParam) ? $criteriaParam : [];

        $canViewPii = (bool) ($this->authContext['canViewPii'] ?? false);
        $redactPii = $canViewPii ? (bool)$request->getBodyParam('redactPii', false) : true;

        $criteria['_apiOwnerKey'] = $this->currentOwnerKey();
        $export = Trails::getInstance()->export->startBackgroundExport($format, $criteria, $redactPii);

        return $this->asJson([
            'exportId' => $export->id,
            'status' => $export->status,
            'format' => $export->format,
        ]);
    }

    /**
     * GET /trails/api/v1/export/status
     *
     * Returns the status of a background export. If status is 'complete' and
     * download=1 is passed, sends the file directly.
     *
     * Query params: id (required), download (optional, 1 to trigger file download)
     */
    public function actionExportStatus(): Response
    {
        $request = Craft::$app->getRequest();
        $id = (int) $request->getRequiredQueryParam('id');

        $export = Trails::getInstance()->export->getExport($id);

        if (!$export) {
            return $this->asJson(['error' => 'Not Found', 'message' => "Export {$id} not found."])
                ->setStatusCode(404);
        }

        if (!$this->canAccessExport($export)) {
            return $this->asJson(['error' => 'Not Found', 'message' => "Export {$id} not found."])
                ->setStatusCode(404);
        }

        $download = (int)$request->getQueryParam('download', 0) === 1;
        if ($download && !Trails::getInstance()->apiAuth->tokenAllows((array) $this->authContext, 'trails:write')) {
            throw new ForbiddenHttpException('API token does not allow this action.');
        }

        if ($export->status === 'complete' && $download) {
            $contentTypes = [
                'csv' => 'text/csv',
                'json' => 'application/json',
                'html' => 'text/html',
            ];
            $contentType = $contentTypes[$export->format] ?? 'application/octet-stream';

            $response = Craft::$app->getResponse();
            // Keep the explicit download mime type. Without FORMAT_RAW, Yii may
            // re-apply FORMAT_HTML defaults and clients see JSON as text/html.
            $response->format = Response::FORMAT_RAW;
            $response->headers->set('Content-Type', $contentType);
            $response->headers->set('Content-Disposition', 'attachment; filename="audit-trail-export-' . $export->id . '.' . $export->format . '"');
            $response->content = Trails::getInstance()->export->getExportContent($export);
            return $response;
        }

        $downloadUrl = null;
        if ($export->status === 'complete') {
            $downloadUrl = \craft\helpers\UrlHelper::actionUrl('trails/api/v1/export/status', ['id' => $export->id, 'download' => 1]);
        }

        return $this->asJson([
            'id' => $export->id,
            'status' => $export->status,
            'progress' => $export->progress,
            'totalRecords' => $export->totalRecords,
            'format' => $export->format,
            'downloadUrl' => $downloadUrl,
        ]);
    }

    /**
     * GET /trails/api/v1/summary
     *
     * Returns an activity summary with per-day breakdown.
     *
     * Query params: days (default 7, clamped to 1–90)
     */
    public function actionSummary(): Response
    {
        $request = Craft::$app->getRequest();

        $days = (int) $request->getQueryParam('days', 7);
        $days = max(1, min(90, $days));

        $audit = Trails::getInstance()->audit;

        return $this->asJson([
            'activity' => $audit->getActivitySummary($days),
            'dailyActivity' => $audit->getDailyActivity($days),
        ]);
    }

    private function requiredScopeForAction(string $actionId): string
    {
        return match ($actionId) {
            'certificate', 'export' => 'trails:write',
            default => 'trails:read',
        };
    }

    private function parseCertificateDate(mixed $value, string $field): \DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            throw new BadRequestHttpException("{$field} must be a non-empty date string.");
        }

        $raw = trim($value);
        $formats = ['Y-m-d', 'Y-m-d H:i:s', \DateTimeInterface::ATOM];
        foreach ($formats as $format) {
            $dt = \DateTimeImmutable::createFromFormat($format, $raw, new \DateTimeZone('UTC'));
            if ($dt instanceof \DateTimeImmutable) {
                return $dt;
            }
        }

        throw new BadRequestHttpException("{$field} must be in Y-m-d, Y-m-d H:i:s, or ISO-8601 format.");
    }

    private function currentOwnerKey(): string
    {
        $auth = (array) $this->authContext;
        if (($auth['type'] ?? null) === 'cp-user') {
            return 'user:' . (string) ((int) ($auth['userId'] ?? 0));
        }
        return 'token:' . (string) ((int) ($auth['tokenId'] ?? 0));
    }

    private function canAccessExport(ExportRecord $export): bool
    {
        $criteria = json_decode((string) ($export->criteria ?? ''), true);
        $ownerKey = is_array($criteria) ? ($criteria['_apiOwnerKey'] ?? null) : null;
        if (is_string($ownerKey) && $ownerKey !== '') {
            return hash_equals($ownerKey, $this->currentOwnerKey());
        }

        $auth = (array) $this->authContext;
        if (($auth['type'] ?? null) === 'cp-user') {
            return (int) ($auth['userId'] ?? 0) > 0 && (int) $export->userId === (int) ($auth['userId'] ?? 0);
        }

        return false;
    }
}
