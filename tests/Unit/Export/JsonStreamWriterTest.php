<?php

namespace anvildev\trails\tests\Unit\Export;

use anvildev\trails\export\JsonStreamWriter;
use anvildev\trails\tests\Support\TestCase;

class JsonStreamWriterTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempFile = tempnam(sys_get_temp_dir(), 'json_test_');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
        parent::tearDown();
    }

    public function testWriteProducesValidJson(): void
    {
        $writer = new JsonStreamWriter($this->tempFile);
        $writer->open();
        $writer->writeRow(['name' => 'Alice', 'age' => 30]);
        $writer->writeRow(['name' => 'Bob', 'age' => 25]);
        $writer->close();

        $content = file_get_contents($this->tempFile);
        $decoded = json_decode($content, true);

        $this->assertNotNull($decoded, 'JSON should be valid');
        $this->assertCount(2, $decoded, 'Should have 2 rows');
        $this->assertSame('Alice', $decoded[0]['name']);
        $this->assertSame('Bob', $decoded[1]['name']);
    }

    public function testEmptyProducesEmptyArray(): void
    {
        $writer = new JsonStreamWriter($this->tempFile);
        $writer->open();
        $writer->close();

        $content = file_get_contents($this->tempFile);
        $decoded = json_decode($content, true);

        $this->assertNotNull($decoded, 'JSON should be valid');
        $this->assertSame([], $decoded);
    }

    public function testSingleRowProducesValidJson(): void
    {
        $writer = new JsonStreamWriter($this->tempFile);
        $writer->open();
        $writer->writeRow(['event' => 'login', 'user' => 'admin']);
        $writer->close();

        $content = file_get_contents($this->tempFile);
        $decoded = json_decode($content, true);

        $this->assertNotNull($decoded, 'JSON should be valid');
        $this->assertCount(1, $decoded);
        $this->assertSame('login', $decoded[0]['event']);
    }

    public function testGetContentType(): void
    {
        $writer = new JsonStreamWriter($this->tempFile);
        $this->assertSame('application/json', $writer->getContentType());
    }

    public function testGetExtension(): void
    {
        $writer = new JsonStreamWriter($this->tempFile);
        $this->assertSame('json', $writer->getExtension());
    }
}
