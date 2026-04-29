<?php

namespace anvildev\trails\tests\Unit\Services;

use anvildev\trails\services\AuditService;
use anvildev\trails\tests\Support\TestCase;

class AuditServiceVerifyAllTest extends TestCase
{
    public function testVerifyAllIntegrityMethodExists(): void
    {
        $this->assertTrue(method_exists(AuditService::class, 'verifyAllIntegrity'));
    }

    public function testVerifyAllIntegrityAcceptsOptionalCallback(): void
    {
        $method = new \ReflectionMethod(AuditService::class, 'verifyAllIntegrity');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertTrue($params[0]->isOptional());
        $this->assertTrue($params[0]->allowsNull());
    }
}
