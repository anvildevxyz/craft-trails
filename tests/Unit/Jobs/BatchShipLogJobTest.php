<?php

namespace anvildev\trails\tests\Unit\Jobs;

use anvildev\trails\jobs\BatchShipLogJob;
use anvildev\trails\tests\Support\TestCase;

class BatchShipLogJobTest extends TestCase
{
    public function testJobCanBeInstantiated(): void
    {
        $job = new BatchShipLogJob();
        $this->assertInstanceOf(BatchShipLogJob::class, $job);
    }

    public function testJobExtendsBaseJob(): void
    {
        $job = new BatchShipLogJob();
        $this->assertInstanceOf(\craft\queue\BaseJob::class, $job);
    }

    public function testAcceptsMultiplePayloads(): void
    {
        $job = new BatchShipLogJob([
            'payloads' => [['id' => 1], ['id' => 2], ['id' => 3]],
        ]);
        $this->assertCount(3, $job->payloads);
    }

    public function testDefaultProviderIsWebhook(): void
    {
        $job = new BatchShipLogJob();
        $this->assertSame('webhook', $job->provider);
    }
}
