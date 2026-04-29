<?php

declare(strict_types=1);

namespace anvildev\trails\tests\integration;

use anvildev\trails\controllers\cp\ExportController;
use anvildev\trails\records\ExportRecord;
use anvildev\trails\Trails;
use Craft;
use craft\elements\User;

/**
 * Coverage for three of the four guard branches in ExportController::actionFile():
 *   - 404 when the export id doesn't exist (no user identity required)
 *   - redirect+flash when status != 'complete' (uses admin identity)
 *   - 404 when the on-disk file is missing (uses admin identity)
 *
 * The 403-for-different-user branch is intentionally NOT covered here. Testing
 * it requires constructing a non-admin user, but creating Users in the
 * integration suite triggers the audit-log listener which calls
 * EncryptionHelper::encrypt() with the test environment's incomplete security
 * config and fails. The branch is small and reviewable; tracked in ROADMAP.md
 * for future infrastructure work to cover it (either fixture-user provisioning
 * that bypasses element events, or a controller refactor that takes the user
 * as a constructor dependency for testability).
 *
 * The action is called directly (not via Craft::$app->runAction) to bypass
 * web routing + permission middleware, keeping this a focused logic test.
 */
class ExportControllerCest
{
    /** @var int[] */
    private array $insertedExportIds = [];

    public function _after(\IntegrationTester $I): void
    {
        if ($this->insertedExportIds !== []) {
            ExportRecord::deleteAll(['id' => $this->insertedExportIds]);
            $this->insertedExportIds = [];
        }
    }

    public function returns404WhenExportIdDoesNotExist(\IntegrationTester $I): void
    {
        Craft::$app->getRequest()->setQueryParams(['id' => 999_999_999]);

        $controller = $this->makeController();
        $thrown = null;
        try {
            $controller->actionFile();
        } catch (\Throwable $e) {
            $thrown = $e;
        }
        $I->assertInstanceOf(\yii\web\NotFoundHttpException::class, $thrown);
        $I->assertStringContainsString('not found', strtolower($thrown->getMessage()));
    }

    public function redirectsWithErrorWhenStatusIsNotComplete(\IntegrationTester $I): void
    {
        $admin = $this->loginExistingAdmin();
        $export = $this->seedExport((int) $admin->id, 'processing');
        Craft::$app->getRequest()->setQueryParams(['id' => $export->id]);

        $controller = $this->makeController();
        $response = $controller->actionFile();

        $I->assertInstanceOf(\yii\web\Response::class, $response);
        $I->assertTrue($response->getIsRedirection(), 'Response must be a 3xx redirect when status != complete.');

        $flashes = Craft::$app->getSession()->getAllFlashes();
        $errorFlashes = $flashes['error'] ?? [];
        $errorText = is_array($errorFlashes) ? implode(' ', $errorFlashes) : (string) $errorFlashes;
        $I->assertStringContainsString('processing', strtolower($errorText));
    }

    public function returns404WhenFileIsMissingOnDisk(\IntegrationTester $I): void
    {
        $admin = $this->loginExistingAdmin();
        $missingPath = sys_get_temp_dir() . '/trails-test-not-here-' . uniqid('', true) . '.csv';
        $export = $this->seedExport((int) $admin->id, 'complete', $missingPath);
        Craft::$app->getRequest()->setQueryParams(['id' => $export->id]);

        $controller = $this->makeController();
        $thrown = null;
        try {
            $controller->actionFile();
        } catch (\Throwable $e) {
            $thrown = $e;
        }
        $I->assertInstanceOf(\yii\web\NotFoundHttpException::class, $thrown);
        $I->assertStringContainsString('disk', strtolower($thrown->getMessage()));
    }

    private function makeController(): ExportController
    {
        $plugin = Trails::getInstance();
        return new ExportController('export', $plugin);
    }

    /**
     * Returns the first admin user from the test DB. Skipped if none — every
     * Craft install has at least one admin so this is just defensive.
     */
    private function loginExistingAdmin(): User
    {
        $admin = User::find()->admin(true)->one();
        if (!$admin) {
            throw new \PHPUnit\Framework\SkippedTestError('No admin user in craft_test DB; cannot test action that requires identity.');
        }
        Craft::$app->getUser()->setIdentity($admin);
        return $admin;
    }

    private function seedExport(int $userId, string $status, ?string $filePath = null): ExportRecord
    {
        $record = new ExportRecord();
        $record->userId = $userId;
        $record->status = $status;
        $record->format = 'csv';
        $record->progress = $status === 'complete' ? 100 : 50;
        $record->dateExpires = date('Y-m-d H:i:s', time() + 86400);
        $record->filePath = $filePath;
        $record->save(false);
        $this->insertedExportIds[] = (int) $record->id;
        return $record;
    }
}
