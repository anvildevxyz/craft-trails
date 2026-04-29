<?php

declare(strict_types=1);

namespace anvildev\trails\tests\Unit\Services;

use anvildev\trails\services\MerkleService;
use anvildev\trails\tests\Support\TestCase;

/**
 * Unit tests for MerkleService static/pure methods only.
 * DB-dependent methods (computeBatch, getInclusionProof, verifyAllRoots) are not tested here.
 */
class MerkleServiceTest extends TestCase
{
    // =========================================================================
    // hashLeaf()
    // =========================================================================

    public function testHashLeaf(): void
    {
        $hash = MerkleService::hashLeaf('hello');

        // SHA-256 always produces a 64-character hex string.
        $this->assertSame(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash);

        // Deterministic.
        $this->assertSame($hash, MerkleService::hashLeaf('hello'));

        // Different input → different output.
        $this->assertNotSame($hash, MerkleService::hashLeaf('world'));
    }

    // =========================================================================
    // hashPair()
    // =========================================================================

    public function testHashPair(): void
    {
        $hash = MerkleService::hashPair('aaa', 'bbb');

        $this->assertSame(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash);

        // Order matters — hashPair(a,b) ≠ hashPair(b,a).
        $this->assertNotSame($hash, MerkleService::hashPair('bbb', 'aaa'));

        // Leaf hash ≠ pair hash even with identical bytes (different prefix).
        $this->assertNotSame(
            MerkleService::hashLeaf('data'),
            MerkleService::hashPair('data', 'data')
        );
    }

    // =========================================================================
    // computeRoot()
    // =========================================================================

    public function testComputeRootEmptyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MerkleService::computeRoot([]);
    }

    public function testComputeRootSingleLeaf(): void
    {
        $root = MerkleService::computeRoot(['a']);

        // With a single leaf the root equals the leaf hash.
        $this->assertSame(MerkleService::hashLeaf('a'), $root);
    }

    public function testComputeRootTwoLeaves(): void
    {
        $root = MerkleService::computeRoot(['a', 'b']);

        $expected = MerkleService::hashPair(
            MerkleService::hashLeaf('a'),
            MerkleService::hashLeaf('b')
        );

        $this->assertSame($expected, $root);
    }

    public function testComputeRootFourLeaves(): void
    {
        $hashes = ['a', 'b', 'c', 'd'];
        $root1 = MerkleService::computeRoot($hashes);
        $root2 = MerkleService::computeRoot($hashes);

        // Must be deterministic.
        $this->assertSame($root1, $root2);
        $this->assertSame(64, strlen($root1));

        // Manual computation:
        //   level 0: [H(a), H(b), H(c), H(d)]
        //   level 1: [HP(H(a),H(b)), HP(H(c),H(d))]
        //   level 2: [HP(level1[0], level1[1])]
        $ha = MerkleService::hashLeaf('a');
        $hb = MerkleService::hashLeaf('b');
        $hc = MerkleService::hashLeaf('c');
        $hd = MerkleService::hashLeaf('d');

        $hab = MerkleService::hashPair($ha, $hb);
        $hcd = MerkleService::hashPair($hc, $hd);
        $expected = MerkleService::hashPair($hab, $hcd);

        $this->assertSame($expected, $root1);
    }

    public function testComputeRootOddCountDuplicatesLastLeaf(): void
    {
        // 3 leaves must NOT equal 4 leaves (last-node duplication != extra distinct node).
        $root3 = MerkleService::computeRoot(['a', 'b', 'c']);
        $root4 = MerkleService::computeRoot(['a', 'b', 'c', 'd']);

        $this->assertNotSame($root3, $root4);

        // Verify manual computation for 3 leaves:
        //   level 0: [H(a), H(b), H(c)]
        //   level 1: [HP(H(a),H(b)), HP(H(c),H(c))]  ← H(c) duplicated
        //   level 2: [HP(level1[0], level1[1])]
        $ha = MerkleService::hashLeaf('a');
        $hb = MerkleService::hashLeaf('b');
        $hc = MerkleService::hashLeaf('c');

        $hab = MerkleService::hashPair($ha, $hb);
        $hcc = MerkleService::hashPair($hc, $hc); // duplicated
        $expected = MerkleService::hashPair($hab, $hcc);

        $this->assertSame($expected, $root3);
    }

    // =========================================================================
    // generateProof() + verifyProof()
    // =========================================================================

    public function testGenerateProofAndVerify(): void
    {
        $hashes = ['a', 'b', 'c', 'd'];
        $proof = MerkleService::generateProof($hashes, 2);

        // Correct shape.
        $this->assertArrayHasKeys(['leafHash', 'rootHash', 'path', 'leafIndex', 'treeSize'], $proof);
        $this->assertSame(2, $proof['leafIndex']);
        $this->assertSame(4, $proof['treeSize']);
        $this->assertSame(MerkleService::hashLeaf('c'), $proof['leafHash']);
        $this->assertSame(MerkleService::computeRoot($hashes), $proof['rootHash']);

        // Path has one entry per tree level above the leaves (log2(4) = 2 steps).
        $this->assertCount(2, $proof['path']);

        // Each path entry has the required keys and a valid position value.
        foreach ($proof['path'] as $step) {
            $this->assertArrayHasKey('hash', $step);
            $this->assertArrayHasKey('position', $step);
            $this->assertContains($step['position'], ['left', 'right']);
        }

        // Proof should verify.
        $this->assertTrue(
            MerkleService::verifyProof(
                $proof['leafHash'],
                $proof['path'],
                $proof['rootHash'],
                $proof['leafIndex']
            )
        );
    }

    public function testVerifyProofValid(): void
    {
        $hashes = ['x', 'y', 'z', 'w'];

        foreach (range(0, 3) as $index) {
            $proof = MerkleService::generateProof($hashes, $index);
            $this->assertTrue(
                MerkleService::verifyProof(
                    $proof['leafHash'],
                    $proof['path'],
                    $proof['rootHash'],
                    $proof['leafIndex']
                ),
                "Proof failed for leaf index {$index}"
            );
        }
    }

    public function testVerifyProofInvalidWithWrongRoot(): void
    {
        $hashes = ['a', 'b', 'c', 'd'];
        $proof = MerkleService::generateProof($hashes, 1);

        $this->assertFalse(
            MerkleService::verifyProof(
                $proof['leafHash'],
                $proof['path'],
                str_repeat('0', 64), // tampered root
                $proof['leafIndex']
            )
        );
    }

    public function testVerifyProofInvalidWithWrongLeaf(): void
    {
        $hashes = ['a', 'b', 'c', 'd'];
        $proof = MerkleService::generateProof($hashes, 1);

        $this->assertFalse(
            MerkleService::verifyProof(
                MerkleService::hashLeaf('TAMPERED'), // wrong leaf
                $proof['path'],
                $proof['rootHash'],
                $proof['leafIndex']
            )
        );
    }

    public function testProofForEachLeaf(): void
    {
        // 8 leaves — perfectly balanced binary tree.
        $hashes = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
        $root = MerkleService::computeRoot($hashes);

        foreach (range(0, 7) as $index) {
            $proof = MerkleService::generateProof($hashes, $index);

            $this->assertSame($root, $proof['rootHash'], "Root mismatch at index {$index}");
            $this->assertTrue(
                MerkleService::verifyProof(
                    $proof['leafHash'],
                    $proof['path'],
                    $root,
                    $proof['leafIndex']
                ),
                "Proof failed at index {$index}"
            );
        }
    }

    public function testProofForOddSizedTree(): void
    {
        // 5 leaves — verifies correct handling at every level with odd counts.
        $hashes = ['a', 'b', 'c', 'd', 'e'];
        $root = MerkleService::computeRoot($hashes);

        foreach (range(0, 4) as $index) {
            $proof = MerkleService::generateProof($hashes, $index);

            $this->assertSame($root, $proof['rootHash'], "Root mismatch at index {$index}");
            $this->assertTrue(
                MerkleService::verifyProof(
                    $proof['leafHash'],
                    $proof['path'],
                    $root,
                    $proof['leafIndex']
                ),
                "Proof failed at index {$index}"
            );
        }
    }

    public function testGenerateProofOutOfBoundsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MerkleService::generateProof(['a', 'b', 'c'], 5);
    }

    public function testGenerateProofNegativeIndexThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MerkleService::generateProof(['a', 'b', 'c'], -1);
    }

    public function testLargeTree(): void
    {
        // 256 leaves — stress test determinism and proof correctness.
        $hashes = [];
        for ($i = 0; $i < 256; $i++) {
            $hashes[] = "record-{$i}";
        }

        $root = MerkleService::computeRoot($hashes);

        foreach ([0, 127, 255] as $index) {
            $proof = MerkleService::generateProof($hashes, $index);

            $this->assertSame($root, $proof['rootHash'], "Root mismatch at index {$index}");
            $this->assertSame(256, $proof['treeSize']);
            $this->assertTrue(
                MerkleService::verifyProof(
                    $proof['leafHash'],
                    $proof['path'],
                    $root,
                    $proof['leafIndex']
                ),
                "Proof failed at index {$index}"
            );
        }
    }
}
