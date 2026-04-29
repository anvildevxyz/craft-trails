<?php

declare(strict_types=1);

namespace anvildev\trails\console\controllers;

use anvildev\trails\Trails;
use craft\console\Controller;
use yii\console\ExitCode;

class ApiController extends Controller
{
    public string $name = '';
    public string $scopes = 'trails:read';
    public int $days = 365;

    public function options($actionID): array
    {
        $options = parent::options($actionID);
        if ($actionID === 'issue-token') {
            $options[] = 'name';
            $options[] = 'scopes';
            $options[] = 'days';
        }
        return $options;
    }

    /**
     * Issue a Trails API bearer token for integrations.
     *
     * Example:
     *  php craft trails/api/issue-token --name=SIEM --scopes=trails:read,trails:write --days=90
     */
    public function actionIssueToken(): int
    {
        if ($this->name === '') {
            $this->stderr("Error: --name is required.\n");
            return ExitCode::USAGE;
        }

        $scopes = array_values(array_filter(array_map('trim', explode(',', $this->scopes))));
        if ($scopes === []) {
            $scopes = ['trails:read'];
        }

        $issued = Trails::getInstance()->apiAuth->issueToken($this->name, $scopes, $this->days > 0 ? $this->days : null);

        $this->stdout("Token created.\n");
        $this->stdout("  tokenId: " . $issued['tokenId'] . "\n");
        $this->stdout("  token: " . $issued['token'] . "\n");
        $this->stdout("Store this token securely now; it cannot be retrieved again.\n");
        return ExitCode::OK;
    }
}
