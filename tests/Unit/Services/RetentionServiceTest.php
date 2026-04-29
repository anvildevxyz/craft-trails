<?php

namespace anvildev\trails\tests\Unit\Services;

use anvildev\trails\services\RetentionService;
use anvildev\trails\tests\Support\TestCase;

class RetentionServiceTest extends TestCase
{
    public function testServiceCanBeInstantiated(): void
    {
        $service = new RetentionService();

        $this->assertInstanceOf(RetentionService::class, $service);
    }

    public function testCleanupMethodExists(): void
    {
        $service = new RetentionService();

        $this->assertTrue(method_exists($service, 'cleanupOldLogs'));
    }

    public function testGetRetentionStatsMethodExists(): void
    {
        $service = new RetentionService();

        $this->assertTrue(method_exists($service, 'getRetentionStats'));
    }
}
