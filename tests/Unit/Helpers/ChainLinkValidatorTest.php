<?php

namespace anvildev\trails\tests\Unit\Helpers;

use anvildev\trails\helpers\ChainLinkValidator;
use anvildev\trails\tests\Support\TestCase;

class ChainLinkValidatorTest extends TestCase
{
    public function testGenesisAcceptsNullPrevHash(): void
    {
        $result = ChainLinkValidator::validate(
            chainPosition: 1,
            prevHash: null,
            expectedPrevHash: null,
        );

        $this->assertSame('ok', $result['status']);
        $this->assertStringContainsString('genesis', $result['message']);
    }

    public function testNullPrevHashAfterGenesisIsChainGap(): void
    {
        $result = ChainLinkValidator::validate(
            chainPosition: 2106,
            prevHash: null,
            expectedPrevHash: null,
        );

        $this->assertSame('failed', $result['status']);
        $this->assertStringContainsString('chain gap', $result['message']);
    }

    public function testMatchingPrevHashYieldsLinkedMessage(): void
    {
        $result = ChainLinkValidator::validate(
            chainPosition: 2044,
            prevHash: 'v3:abc',
            expectedPrevHash: 'v3:abc',
        );

        $this->assertSame('ok', $result['status']);
        $this->assertStringContainsString('linked', $result['message']);
        $this->assertStringContainsString('2043', $result['message']);
    }

    public function testMismatchedPrevHashIsFailure(): void
    {
        $result = ChainLinkValidator::validate(
            chainPosition: 2044,
            prevHash: 'v3:abc',
            expectedPrevHash: 'v3:xyz',
        );

        $this->assertSame('failed', $result['status']);
        $this->assertStringContainsString('mismatch', $result['message']);
    }

    public function testMissingPredecessorIsFailure(): void
    {
        $result = ChainLinkValidator::validate(
            chainPosition: 2044,
            prevHash: 'v3:abc',
            expectedPrevHash: null,
        );

        $this->assertSame('failed', $result['status']);
        $this->assertStringContainsString('not found', $result['message']);
    }
}
