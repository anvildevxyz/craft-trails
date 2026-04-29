<?php

declare(strict_types=1);

namespace anvildev\trails\services;

use anvildev\trails\records\ApiTokenRecord;
use Craft;
use craft\base\Component;
use craft\helpers\DateTimeHelper;

class ApiAuthService extends Component
{
    /**
     * @return array{
     *   type:string,
     *   rateLimitKey:string,
     *   canViewPii:bool,
     *   scopes:string[],
     *   userId?:int,
     *   tokenId?:int
     * }|null
     */
    public function authenticateRequest(): ?array
    {
        $user = Craft::$app->getUser()->getIdentity();
        if ($user !== null) {
            $isAdmin = (bool) ($user->admin ?? false);
            $canManage = $this->userCan($user, 'trails-manageSettings');
            $canViewLogs = $this->userCan($user, 'trails-viewLogs');
            $canView = $isAdmin || $canManage;
            if (!$isAdmin && !$canViewLogs) {
                return null;
            }

            return [
                'type' => 'cp-user',
                'rateLimitKey' => 'user:' . (string) $user->id,
                'canViewPii' => $canView,
                'scopes' => ['*'],
                'userId' => (int) $user->id,
            ];
        }

        $header = Craft::$app->getRequest()->getHeaders()->get('Authorization');
        if (!is_string($header) || !preg_match('/^\s*Bearer\s+(.+?)\s*$/i', $header, $m)) {
            return null;
        }

        $rawToken = trim($m[1]);
        if ($rawToken === '') {
            return null;
        }

        $token = $this->findTokenByRawToken($rawToken);
        if ($token === null || !$this->isActive($token)) {
            return null;
        }

        $token->lastUsedAt = DateTimeHelper::toDateTime(time())->format('Y-m-d H:i:s');
        $token->save(false);

        return [
            'type' => 'api-token',
            'rateLimitKey' => 'token:' . (string) $token->id,
            'canViewPii' => false,
            'scopes' => $this->normalizeScopes($token->scopes),
            'tokenId' => (int) $token->id,
        ];
    }

    public function tokenAllows(array $authContext, string $scope): bool
    {
        $scopes = $authContext['scopes'] ?? [];
        if (in_array('*', $scopes, true)) {
            return true;
        }
        return in_array($scope, $scopes, true);
    }

    /**
     * @return array{token:string,tokenId:int}
     */
    public function issueToken(string $name, array $scopes = ['*'], ?int $daysValid = null, ?int $createdByUserId = null): array
    {
        $plain = bin2hex(random_bytes(32));
        $record = new ApiTokenRecord();
        $record->name = $name;
        $record->tokenHash = $this->hashToken($plain);
        $record->scopes = json_encode(array_values(array_unique($scopes)), JSON_UNESCAPED_SLASHES);
        $record->createdByUserId = $createdByUserId;
        $record->lastUsedAt = null;
        $record->revokedAt = null;
        $record->expiresAt = $daysValid !== null
            ? gmdate('Y-m-d H:i:s', time() + max(1, $daysValid) * 86400)
            : null;
        $record->save(false);

        return ['token' => $plain, 'tokenId' => (int) $record->id];
    }

    public function revokeToken(int $tokenId): bool
    {
        $record = ApiTokenRecord::findOne($tokenId);
        if ($record === null) {
            return false;
        }
        $record->revokedAt = gmdate('Y-m-d H:i:s');
        return (bool) $record->save(false);
    }

    private function hashToken(string $raw): string
    {
        return hash_hmac('sha256', $raw, Craft::$app->getConfig()->getGeneral()->securityKey);
    }

    private function findTokenByRawToken(string $rawToken): ?ApiTokenRecord
    {
        $hash = $this->hashToken($rawToken);
        /** @var ApiTokenRecord|null $record */
        $record = ApiTokenRecord::find()->where(['tokenHash' => $hash])->one();
        return $record;
    }

    private function isActive(ApiTokenRecord $token): bool
    {
        if ($token->revokedAt !== null) {
            return false;
        }
        if ($token->expiresAt === null) {
            return true;
        }
        $expires = strtotime((string) $token->expiresAt);
        return $expires !== false && $expires >= time();
    }

    private function userCan(object $user, string $permission): bool
    {
        if (!method_exists($user, 'can')) {
            return false;
        }
        return (bool) $user->can($permission);
    }

    /**
     * @return string[]
     */
    private function normalizeScopes(?string $rawScopes): array
    {
        if ($rawScopes === null || $rawScopes === '') {
            return [];
        }
        $decoded = json_decode($rawScopes, true);
        if (!is_array($decoded)) {
            return [];
        }
        $scopes = array_values(array_filter(array_map(static fn($v) => is_string($v) ? trim($v) : '', $decoded)));
        return $scopes;
    }
}
