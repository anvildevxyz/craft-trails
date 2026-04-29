<?php

namespace anvildev\trails\tests\Unit\Jobs;

use anvildev\trails\jobs\RetentionJob;
use anvildev\trails\tests\Support\TestCase;

class RetentionJobTest extends TestCase
{
    public function testJobCanBeInstantiated(): void
    {
        $job = new RetentionJob();
        $this->assertInstanceOf(RetentionJob::class, $job);
    }

    public function testJobExtendsBaseJob(): void
    {
        $job = new RetentionJob();
        $this->assertInstanceOf(\craft\queue\BaseJob::class, $job);
    }
}
