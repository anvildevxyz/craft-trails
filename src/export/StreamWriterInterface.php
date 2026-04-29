<?php

declare(strict_types=1);

namespace anvildev\trails\export;

interface StreamWriterInterface
{
    public function open(): void;

    public function writeRow(array $row): void;

    public function close(): void;

    public function getContentType(): string;

    public function getExtension(): string;
}
