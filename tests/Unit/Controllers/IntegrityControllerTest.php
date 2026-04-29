<?php

namespace anvildev\trails\tests\Unit\Controllers;

use anvildev\trails\controllers\cp\IntegrityController;
use anvildev\trails\dto\IntegrityRunSummary;
use anvildev\trails\tests\Support\TestCase;

class IntegrityControllerTest extends TestCase
{
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(IntegrityController::class));
    }

    public function testHasActionVerifyMethod(): void
    {
        $this->assertTrue(method_exists(IntegrityController::class, 'actionVerify'));
    }

    public function testActionVerifyReferencesRunSummaryDto(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../../src/controllers/cp/IntegrityController.php'
        );

        $this->assertStringContainsString(
            IntegrityRunSummary::class,
            $source,
            'IntegrityController should depend on IntegrityRunSummary so the cache payload is enriched with merkle and anchor counts.'
        );
    }

    public function testActionVerifyCallsAllThreeIntegrityServices(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../../src/controllers/cp/IntegrityController.php'
        );

        $this->assertStringContainsString('audit->verifyAllLogs', $source);
        $this->assertStringContainsString('merkle->verifyAllRoots', $source);
        $this->assertStringContainsString('anchor->verifyAll', $source);
    }
}
