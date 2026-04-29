<?php

namespace anvildev\trails\tests\Unit\Jobs;

use anvildev\trails\jobs\ShipLogJob;
use anvildev\trails\tests\Support\TestCase;

class ShipLogJobTest extends TestCase
{
    public function testJobCanBeInstantiated(): void
    {
        $job = new ShipLogJob();
        $this->assertInstanceOf(ShipLogJob::class, $job);
    }

    public function testJobExtendsBaseJob(): void
    {
        $job = new ShipLogJob();
        $this->assertInstanceOf(\craft\queue\BaseJob::class, $job);
    }

    public function testDefaultMaxTries(): void
    {
        $job = new ShipLogJob();
        $this->assertSame(5, $job->maxTries);
    }

    public function testDefaultProvider(): void
    {
        $job = new ShipLogJob();
        $this->assertSame('webhook', $job->provider);
    }

    public function testPayloadIsArray(): void
    {
        $job = new ShipLogJob(['payload' => ['test' => 'data']]);
        $this->assertSame(['test' => 'data'], $job->payload);
    }

    public function testHasShipToWebhookMethod(): void
    {
        $method = new \ReflectionMethod(ShipLogJob::class, 'shipToWebhook');
        $this->assertTrue($method->isPrivate());
    }
}
