<?php

namespace anvildev\trails\tests\Unit\Dto;

use anvildev\trails\dto\MerkleProof;
use anvildev\trails\tests\Support\TestCase;

class MerkleProofTest extends TestCase
{
    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    private function makePath(): array
    {
        return [
            ['hash' => 'abc123', 'position' => 'left'],
            ['hash' => 'def456', 'position' => 'right'],
            ['hash' => 'ghi789', 'position' => 'left'],
        ];
    }

    private function makeProof(): MerkleProof
    {
        return new MerkleProof(
            leafHash: 'leafhash_abcdef',
            rootHash: 'roothash_123456',
            path: $this->makePath(),
            leafIndex: 3,
            treeSize: 8,
            verified: true,
        );
    }

    // ---------------------------------------------------------------------------
    // testConstruction
    // ---------------------------------------------------------------------------

    public function testConstruction(): void
    {
        $proof = $this->makeProof();

        $this->assertSame('leafhash_abcdef', $proof->leafHash);
        $this->assertSame('roothash_123456', $proof->rootHash);
        $this->assertSame($this->makePath(), $proof->path);
        $this->assertSame(3, $proof->leafIndex);
        $this->assertSame(8, $proof->treeSize);
        $this->assertTrue($proof->verified);
    }

    public function testConstructionWithFalseVerified(): void
    {
        $proof = new MerkleProof(
            leafHash: 'leaf',
            rootHash: 'root',
            path: [],
            leafIndex: 0,
            treeSize: 1,
            verified: false,
        );

        $this->assertFalse($proof->verified);
        $this->assertSame([], $proof->path);
        $this->assertSame(0, $proof->leafIndex);
        $this->assertSame(1, $proof->treeSize);
    }

    // ---------------------------------------------------------------------------
    // testToArray
    // ---------------------------------------------------------------------------

    public function testToArray(): void
    {
        $proof = $this->makeProof();
        $array = $proof->toArray();

        $this->assertArrayHasKey('leafHash', $array);
        $this->assertArrayHasKey('rootHash', $array);
        $this->assertArrayHasKey('path', $array);
        $this->assertArrayHasKey('leafIndex', $array);
        $this->assertArrayHasKey('treeSize', $array);
        $this->assertArrayHasKey('verified', $array);

        $this->assertSame('leafhash_abcdef', $array['leafHash']);
        $this->assertSame('roothash_123456', $array['rootHash']);
        $this->assertSame($this->makePath(), $array['path']);
        $this->assertSame(3, $array['leafIndex']);
        $this->assertSame(8, $array['treeSize']);
        $this->assertTrue($array['verified']);

        $this->assertCount(6, $array);
    }

    // ---------------------------------------------------------------------------
    // Structural guards
    // ---------------------------------------------------------------------------

    public function testPropertiesAreReadonly(): void
    {
        $proof = $this->makeProof();
        $reflection = new \ReflectionClass($proof);

        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue(
                $property->isReadOnly(),
                "Property \${$property->getName()} should be readonly"
            );
        }
    }

    public function testClassIsFinal(): void
    {
        $reflection = new \ReflectionClass(MerkleProof::class);

        $this->assertTrue($reflection->isFinal(), 'MerkleProof must be a final class');
    }
}
