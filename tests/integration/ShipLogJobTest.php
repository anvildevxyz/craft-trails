<?php

namespace anvildev\trails\tests\integration;

use anvildev\trails\jobs\ShipLogJob;
use craft\test\TestCase;

class ShipLogJobTest extends TestCase
{
    public function testExecuteWithEmptyEndpointReturnsEarly(): void
    {
        $job = new ShipLogJob([
            'endpoint' => '',
            'provider' => 'webhook',
            'payload' => ['event' => 'test'],
        ]);

        // Should not throw — empty endpoint is handled gracefully
        $mockQueue = $this->createMock(\yii\queue\Queue::class);
        $job->execute($mockQueue);

        // If we got here without exception, the early return worked
        $this->assertTrue(true);
    }
}
