<?php

declare(strict_types=1);

namespace anvildev\trails\export;

class HtmlStreamWriter implements StreamWriterInterface
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

        fwrite($this->handle, <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Audit Trail Report</title>
<style>
body { font-family: sans-serif; margin: 2rem; color: #333; }
h1 { font-size: 1.5rem; margin-bottom: 1rem; }
table { border-collapse: collapse; width: 100%; }
th, td { border: 1px solid #ccc; padding: 0.5rem 0.75rem; text-align: left; }
th { background-color: #f0f0f0; font-weight: bold; }
tr:nth-child(even) { background-color: #fafafa; }
</style>
</head>
<body>
<h1>Audit Trail Report</h1>
<table>
HTML);
    }

    public function writeRow(array $row): void
    {
        if ($this->handle === null) {
            throw new \RuntimeException('Writer is not open. Call open() first.');
        }

        if (!$this->headerWritten) {
            fwrite($this->handle, '<tr>');
            foreach (array_keys($row) as $key) {
                fwrite($this->handle, '<th>' . htmlspecialchars((string) $key, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</th>');
            }
            fwrite($this->handle, "</tr>\n");
            $this->headerWritten = true;
        }

        fwrite($this->handle, '<tr>');
        foreach ($row as $value) {
            fwrite($this->handle, '<td>' . htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</td>');
        }
        fwrite($this->handle, "</tr>\n");
    }

    public function close(): void
    {
        if ($this->handle !== null) {
            fwrite($this->handle, "</table>\n</body>\n</html>");
            fclose($this->handle);
            $this->handle = null;
        }
    }

    public function getContentType(): string
    {
        return 'text/html';
    }

    public function getExtension(): string
    {
        return 'html';
    }
}
