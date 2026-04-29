<?php

declare(strict_types=1);

namespace anvildev\trails\anchors;

final class VerificationResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly int $exitCode,
        public readonly string $stdout,
        public readonly string $stderr,
    ) {
    }
}
