<?php

declare(strict_types=1);

namespace anvildev\trails\tests\Unit\Helpers;

use anvildev\trails\helpers\DiffRenderer;
use anvildev\trails\tests\Support\TestCase;

class DiffRendererTest extends TestCase
{
    public function testCompareReturnsEmptyForIdenticalArrays(): void
    {
        $result = DiffRenderer::compare(['title' => 'Hello'], ['title' => 'Hello']);
        $this->assertCount(1, $result);
        $this->assertSame('unchanged', $result[0]['change']);
    }

    public function testCompareDetectsModifiedFields(): void
    {
        $result = DiffRenderer::compare(['title' => 'Old'], ['title' => 'New']);
        $this->assertCount(1, $result);
        $this->assertSame('modified', $result[0]['change']);
        $this->assertSame('title', $result[0]['key']);
        $this->assertSame('Old', $result[0]['oldValue']);
        $this->assertSame('New', $result[0]['newValue']);
    }

    public function testCompareDetectsAddedFields(): void
    {
        $result = DiffRenderer::compare(['title' => 'Hello'], ['title' => 'Hello', 'subtitle' => 'World']);
        $added = array_values(array_filter($result, fn($r) => $r['change'] === 'added'));
        $this->assertCount(1, $added);
        $this->assertSame('subtitle', $added[0]['key']);
        $this->assertSame('World', $added[0]['newValue']);
    }

    public function testCompareDetectsRemovedFields(): void
    {
        $result = DiffRenderer::compare(['title' => 'Hello', 'subtitle' => 'World'], ['title' => 'Hello']);
        $removed = array_values(array_filter($result, fn($r) => $r['change'] === 'removed'));
        $this->assertCount(1, $removed);
        $this->assertSame('subtitle', $removed[0]['key']);
        $this->assertSame('World', $removed[0]['oldValue']);
    }

    public function testCompareFlattensNestedKeysWithDotPath(): void
    {
        $old = ['seo' => ['title' => 'Old Title', 'description' => 'Desc']];
        $new = ['seo' => ['title' => 'New Title', 'description' => 'Desc']];
        $result = DiffRenderer::compare($old, $new);
        $modified = array_values(array_filter($result, fn($r) => $r['change'] === 'modified'));
        $this->assertCount(1, $modified);
        $this->assertSame('seo.title', $modified[0]['key']);
    }

    public function testCompareHandlesNullOldValue(): void
    {
        $result = DiffRenderer::compare([], ['title' => 'Hello']);
        $this->assertCount(1, $result);
        $this->assertSame('added', $result[0]['change']);
    }

    public function testCompareHandlesNullNewValue(): void
    {
        $result = DiffRenderer::compare(['title' => 'Hello'], []);
        $this->assertCount(1, $result);
        $this->assertSame('removed', $result[0]['change']);
    }

    public function testCompareHandlesScalarValues(): void
    {
        $result = DiffRenderer::compare(['count' => 5], ['count' => 10]);
        $this->assertSame('modified', $result[0]['change']);
        $this->assertSame(5, $result[0]['oldValue']);
        $this->assertSame(10, $result[0]['newValue']);
    }

    public function testCompareHandlesBooleans(): void
    {
        $result = DiffRenderer::compare(['enabled' => true], ['enabled' => false]);
        $this->assertSame('modified', $result[0]['change']);
    }

    public function testCompareHandlesMixedTypes(): void
    {
        $result = DiffRenderer::compare(['value' => '5'], ['value' => 5]);
        // Loose comparison would call these equal; strict says modified.
        $this->assertSame('modified', $result[0]['change']);
    }

    public function testCompareSkipsUnchangedWhenFlagged(): void
    {
        $result = DiffRenderer::compare(
            ['a' => 1, 'b' => 2],
            ['a' => 1, 'b' => 3],
            skipUnchanged: true
        );
        $this->assertCount(1, $result);
        $this->assertSame('modified', $result[0]['change']);
        $this->assertSame('b', $result[0]['key']);
    }

    public function testCompareReturnsEmptyForBothEmpty(): void
    {
        $this->assertSame([], DiffRenderer::compare([], []));
    }

    public function testCompareHandlesDeeplyNestedArrays(): void
    {
        $old = ['a' => ['b' => ['c' => 'old']]];
        $new = ['a' => ['b' => ['c' => 'new']]];
        $result = DiffRenderer::compare($old, $new, skipUnchanged: true);
        $this->assertCount(1, $result);
        $this->assertSame('a.b.c', $result[0]['key']);
        $this->assertSame('modified', $result[0]['change']);
    }

    public function testCompareHandlesListArraysAsValues(): void
    {
        // Indexed arrays (lists) are treated as scalar values, not flattened further
        $old = ['tags' => ['php', 'craft']];
        $new = ['tags' => ['php', 'craft', 'yii']];
        $result = DiffRenderer::compare($old, $new, skipUnchanged: true);
        $this->assertCount(1, $result);
        $this->assertSame('tags', $result[0]['key']);
        $this->assertSame('modified', $result[0]['change']);
    }

    public function testCompareHandlesNullValues(): void
    {
        $old = ['title' => 'Hello', 'subtitle' => null];
        $new = ['title' => 'Hello', 'subtitle' => 'World'];
        $result = DiffRenderer::compare($old, $new, skipUnchanged: true);
        $this->assertCount(1, $result);
        $this->assertSame('subtitle', $result[0]['key']);
        $this->assertSame('modified', $result[0]['change']);
        $this->assertNull($result[0]['oldValue']);
        $this->assertSame('World', $result[0]['newValue']);
    }
}
