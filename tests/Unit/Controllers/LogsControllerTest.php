<?php

namespace anvildev\trails\tests\Unit\Controllers;

use anvildev\trails\controllers\cp\LogsController;
use anvildev\trails\tests\Support\TestCase;

class LogsControllerTest extends TestCase
{
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(LogsController::class));
    }

    public function testHasActionViewMethod(): void
    {
        $this->assertTrue(method_exists(LogsController::class, 'actionView'));
    }

    public function testHasActionIndexMethod(): void
    {
        $this->assertTrue(method_exists(LogsController::class, 'actionIndex'));
    }
}
