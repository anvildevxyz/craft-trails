<?php

declare(strict_types=1);

namespace anvildev\trails\export;

class CsvStreamWriter implements StreamWriterInterface
{
    /** @var resource|null */
    private $handle = null;

    private bool $headerWritten = false;

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
        $this->headerWritten = false;
    }

    public function writeRow(array $row): void
    {
        if ($this->handle === null) {
            throw new \RuntimeException('Writer is not open. Call open() first.');
        }

        if (!$this->headerWritten) {
            fputcsv($this->handle, array_keys($row), separator: ',', enclosure: '"', escape: '\\');
            $this->headerWritten = true;
        }

        $escaped = array_map([$this, 'escapeCsvField'], array_values($row));
        fputcsv($this->handle, $escaped, separator: ',', enclosure: '"', escape: '\\');
    }

    public function close(): void
    {
        if ($this->handle !== null) {
            fclose($this->handle);
            $this->handle = null;
        }
    }

    public function getContentType(): string
    {
        return 'text/csv';
    }

    public function getExtension(): string
    {
        return 'csv';
    }

    private function escapeCsvField(mixed $value): string
    {
        $str = (string) $value;
        if ($str !== '' && preg_match('/^[=+\-@\t\r]/', $str)) {
            return "'" . $str;
        }
        return $str;
    }
}
