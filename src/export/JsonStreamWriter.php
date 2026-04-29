<?php

declare(strict_types=1);

namespace anvildev\trails\export;

class JsonStreamWriter implements StreamWriterInterface
{
    /** @var resource|null */
    private $handle = null;

    private bool $firstRow = true;

    public function __construct(private readonly string $filePath)
    {
    }

    public function open(): void
    {
        $handle = fopen($this->filePath, 'w');
        if ($handle === false) {
            throw new \RuntimeException('Could not open file for writing: ' . $this->filePath);
        }
        $this->handle = $handle;
        $this->firstRow = true;
        fwrite($this->handle, "[\n");
    }

    public function writeRow(array $row): void
    {
        if ($this->handle === null) {
            throw new \RuntimeException('Writer is not open. Call open() first.');
        }

        $json = json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($this->firstRow) {
            fwrite($this->handle, $json);
            $this->firstRow = false;
        } else {
            fwrite($this->handle, ",\n" . $json);
        }
    }

    public function close(): void
    {
        if ($this->handle !== null) {
            fwrite($this->handle, "\n]");
            fclose($this->handle);
            $this->handle = null;
        }
    }

    public function getContentType(): string
    {
        return 'application/json';
    }

    public function getExtension(): string
    {
        return 'json';
    }
}
