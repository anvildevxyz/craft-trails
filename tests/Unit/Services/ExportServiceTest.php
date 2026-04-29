<?php

namespace anvildev\trails\tests\Unit\Services;

use anvildev\trails\services\ExportService;
use anvildev\trails\tests\Support\TestCase;

class ExportServiceTest extends TestCase
{
    public function testExportToHtmlMethodExists(): void
    {
        $service = new ExportService();

        $this->assertTrue(method_exists($service, 'exportToHtml'));
    }

    public function testExportToCsvMethodExists(): void
    {
        $service = new ExportService();

        $this->assertTrue(method_exists($service, 'exportToCsv'));
    }

    public function testExportToJsonMethodExists(): void
    {
        $service = new ExportService();

        $this->assertTrue(method_exists($service, 'exportToJson'));
    }

    public function testExportToPdfMethodDoesNotExist(): void
    {
        $service = new ExportService();

        $this->assertFalse(method_exists($service, 'exportToPdf'));
    }

    // =========================================================================
    // escapeCsvField() — CSV injection prevention
    // =========================================================================

    private function invokeEscapeCsvField(mixed $value): string
    {
        $method = new \ReflectionMethod(ExportService::class, 'escapeCsvField');
        $method->setAccessible(true);
        return $method->invoke(new ExportService(), $value);
    }

    public function testEscapeCsvFieldPrefixesEqualsSign(): void
    {
        $this->assertSame("'=SUM(A1)", $this->invokeEscapeCsvField('=SUM(A1)'));
    }

    public function testEscapeCsvFieldPrefixesPlusSign(): void
    {
        $this->assertSame("'+1", $this->invokeEscapeCsvField('+1'));
    }

    public function testEscapeCsvFieldPrefixesMinusSign(): void
    {
        $this->assertSame("'-foo", $this->invokeEscapeCsvField('-foo'));
    }

    public function testEscapeCsvFieldPrefixesAtSign(): void
    {
        $this->assertSame("'@SUM", $this->invokeEscapeCsvField('@SUM'));
    }

    public function testEscapeCsvFieldPrefixesTab(): void
    {
        $this->assertSame("'\tcmd", $this->invokeEscapeCsvField("\tcmd"));
    }

    public function testEscapeCsvFieldPrefixesCarriageReturn(): void
    {
        $this->assertSame("'\rcmd", $this->invokeEscapeCsvField("\rcmd"));
    }

    public function testEscapeCsvFieldLeavesNormalFieldUnchanged(): void
    {
        $this->assertSame('John Doe', $this->invokeEscapeCsvField('John Doe'));
    }

    public function testEscapeCsvFieldLeavesEmptyFieldUnchanged(): void
    {
        $this->assertSame('', $this->invokeEscapeCsvField(''));
    }
}
