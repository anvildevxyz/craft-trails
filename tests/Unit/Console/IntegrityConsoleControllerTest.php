<?php

namespace anvildev\trails\tests\Unit\Console;

use anvildev\trails\tests\Support\TestCase;

class IntegrityConsoleControllerTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents(
            __DIR__ . '/../../../src/console/controllers/IntegrityController.php'
        );
    }

    public function testHasOutputProperty(): void
    {
        $this->assertMatchesRegularExpression(
            '/public string \$output\s*=/',
            $this->source(),
            'Console controller must declare a public string $output property so callers can pass --output=PATH.'
        );
    }

    public function testCertificateActionRegistersOutputOption(): void
    {
        $source = $this->source();

        // Look for the certificate-action options block and confirm 'output' is registered.
        $this->assertMatchesRegularExpression(
            "/if \(\\\$actionID === 'certificate'\)\s*\{[^}]*\\\$options\[\] = 'output';/s",
            $source,
            "The certificate action must register 'output' in options() so --output=PATH is accepted."
        );
    }

    public function testCertificateUsesOutputPathWhenProvided(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            '$this->output',
            $source,
            'Certificate action must read from $this->output to honour --output=PATH.'
        );
    }
}
