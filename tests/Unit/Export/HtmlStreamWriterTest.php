<?php

namespace anvildev\trails\tests\Unit\Export;

use anvildev\trails\export\HtmlStreamWriter;
use anvildev\trails\tests\Support\TestCase;

class HtmlStreamWriterTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempFile = tempnam(sys_get_temp_dir(), 'html_test_');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
        parent::tearDown();
    }

    public function testWriteProducesValidHtml(): void
    {
        $writer = new HtmlStreamWriter($this->tempFile);
        $writer->open();
        $writer->writeRow(['name' => 'Alice', 'age' => '30']);
        $writer->writeRow(['name' => 'Bob', 'age' => '25']);
        $writer->close();

        $content = file_get_contents($this->tempFile);

        $this->assertStringContainsString('<html', $content);
        $this->assertStringContainsString('</html>', $content);
        $this->assertStringContainsString('<table', $content);
        $this->assertStringContainsString('Alice', $content);
        $this->assertStringContainsString('Bob', $content);
    }

    public function testHtmlEscaping(): void
    {
        $writer = new HtmlStreamWriter($this->tempFile);
        $writer->open();
        $writer->writeRow(['payload' => '<script>alert("xss")</script>']);
        $writer->close();

        $content = file_get_contents($this->tempFile);

        $this->assertStringNotContainsString('<script>', $content);
        $this->assertStringContainsString('&lt;script&gt;', $content);
    }

    public function testEmptyExportHasStructure(): void
    {
        $writer = new HtmlStreamWriter($this->tempFile);
        $writer->open();
        $writer->close();

        $content = file_get_contents($this->tempFile);

        $this->assertStringContainsString('<html', $content);
        $this->assertStringContainsString('<head', $content);
        $this->assertStringContainsString('<body', $content);
        $this->assertStringContainsString('</html>', $content);
    }

    public function testGetContentType(): void
    {
        $writer = new HtmlStreamWriter($this->tempFile);
        $this->assertSame('text/html', $writer->getContentType());
    }

    public function testGetExtension(): void
    {
        $writer = new HtmlStreamWriter($this->tempFile);
        $this->assertSame('html', $writer->getExtension());
    }
}
