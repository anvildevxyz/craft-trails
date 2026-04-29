<?php

namespace anvildev\trails\tests\Unit\Export;

use anvildev\trails\export\CsvStreamWriter;
use anvildev\trails\tests\Support\TestCase;

class CsvStreamWriterTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempFile = tempnam(sys_get_temp_dir(), 'csv_test_');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
        parent::tearDown();
    }

    public function testWriteHeaderAndRows(): void
    {
        $writer = new CsvStreamWriter($this->tempFile);
        $writer->open();
        $writer->writeRow(['name' => 'Alice', 'age' => '30']);
        $writer->writeRow(['name' => 'Bob', 'age' => '25']);
        $writer->close();

        $lines = array_filter(explode("\n", file_get_contents($this->tempFile)), fn($l) => $l !== '');
        $this->assertCount(3, $lines, 'Expected 3 lines: header + 2 data rows');

        $rows = array_values($lines);
        $this->assertSame('name,age', $rows[0]);
        $this->assertSame('Alice,30', $rows[1]);
        $this->assertSame('Bob,25', $rows[2]);
    }

    public function testCsvInjectionPrevention(): void
    {
        $writer = new CsvStreamWriter($this->tempFile);
        $writer->open();
        $writer->writeRow(['formula' => '=SUM(A1:A10)', 'safe' => 'hello']);
        $writer->close();

        $content = file_get_contents($this->tempFile);
        $lines = array_values(array_filter(explode("\n", $content), fn($l) => $l !== ''));

        // Data row (index 1) should have the formula prefixed with a single quote
        $this->assertStringContainsString("'=SUM(A1:A10)", $lines[1]);
        $this->assertStringContainsString('hello', $lines[1]);
    }

    public function testEmptyExport(): void
    {
        $writer = new CsvStreamWriter($this->tempFile);
        $writer->open();
        $writer->close();

        $this->assertFileExists($this->tempFile);
    }

    public function testGetContentType(): void
    {
        $writer = new CsvStreamWriter($this->tempFile);
        $this->assertSame('text/csv', $writer->getContentType());
    }

    public function testGetExtension(): void
    {
        $writer = new CsvStreamWriter($this->tempFile);
        $this->assertSame('csv', $writer->getExtension());
    }
}
