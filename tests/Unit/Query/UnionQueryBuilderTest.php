<?php

declare(strict_types=1);

namespace anvildev\trails\tests\Unit\Query;

use anvildev\trails\query\UnionQueryBuilder;
use anvildev\trails\tests\Support\TestCase;

/**
 * Unit tests for UnionQueryBuilder static methods.
 *
 * Only the pure static methods are tested here — execute() requires a live DB
 * and is covered by integration tests.
 */
class UnionQueryBuilderTest extends TestCase
{
    // -------------------------------------------------------------------------
    // buildWhereClauses
    // -------------------------------------------------------------------------

    public function testBuildWhereClausesFromCriteria(): void
    {
        $criteria = [
            'category'    => 'auth',
            'userId'      => 42,
            'elementType' => 'craft\\elements\\Entry',
            'elementId'   => 99,
            'ipAddress'   => '127.0.0.1',
        ];

        $conditions = UnionQueryBuilder::buildWhereClauses($criteria);

        $this->assertContains(['category'    => 'auth'],                    $conditions);
        $this->assertContains(['userId'      => 42],                        $conditions);
        $this->assertContains(['elementType' => 'craft\\elements\\Entry'],  $conditions);
        $this->assertContains(['elementId'   => 99],                        $conditions);
        $this->assertContains(['ipAddress'   => '127.0.0.1'],               $conditions);
    }

    public function testBuildWhereClausesWithWildcardEvent(): void
    {
        $criteria = ['event' => 'element.*'];

        $conditions = UnionQueryBuilder::buildWhereClauses($criteria);

        // Wildcard must produce a prefix LIKE without auto-wrapping (false as 4th element).
        $this->assertCount(1, $conditions);
        $this->assertSame(['like', 'event', 'element.', false], $conditions[0]);
    }

    public function testBuildWhereClausesWithExactEvent(): void
    {
        $criteria = ['event' => 'element.save'];

        $conditions = UnionQueryBuilder::buildWhereClauses($criteria);

        $this->assertCount(1, $conditions);
        $this->assertContains(['event' => 'element.save'], $conditions);
    }

    public function testBuildWhereClausesWithDateRange(): void
    {
        $criteria = [
            'dateFrom' => '2024-01-01 00:00:00',
            'dateTo'   => '2024-12-31 23:59:59',
        ];

        $conditions = UnionQueryBuilder::buildWhereClauses($criteria);

        $this->assertCount(2, $conditions);
        $this->assertContains(['>=', 'dateCreated', '2024-01-01 00:00:00'], $conditions);
        $this->assertContains(['<=', 'dateCreated', '2024-12-31 23:59:59'], $conditions);
    }

    public function testBuildWhereClausesWithSearch(): void
    {
        $criteria = ['search' => 'login'];

        $conditions = UnionQueryBuilder::buildWhereClauses($criteria);

        $this->assertCount(1, $conditions);

        $orCondition = $conditions[0];

        // First element must be 'or'.
        $this->assertSame('or', $orCondition[0]);

        // Must contain LIKE branches for all three columns.
        $this->assertContains(['like', 'event',        'login'], $orCondition);
        $this->assertContains(['like', 'elementTitle', 'login'], $orCondition);
        $this->assertContains(['like', 'userName',     'login'], $orCondition);
    }

    public function testBuildWhereClausesSkipsEmptyValues(): void
    {
        $criteria = [
            'category'    => '',      // empty string
            'userId'      => null,    // null
            'elementId'   => 0,       // zero
            'ipAddress'   => null,
            'event'       => '',
            'dateFrom'    => null,
            'dateTo'      => '',
            'search'      => '',
        ];

        $conditions = UnionQueryBuilder::buildWhereClauses($criteria);

        $this->assertSame([], $conditions, 'All empty/null/zero values must be skipped.');
    }

    public function testBuildWhereClausesSkipsPartialEmpty(): void
    {
        $criteria = [
            'category' => 'auth',
            'userId'   => null,       // should be skipped
            'search'   => '',         // should be skipped
        ];

        $conditions = UnionQueryBuilder::buildWhereClauses($criteria);

        $this->assertCount(1, $conditions);
        $this->assertContains(['category' => 'auth'], $conditions);
    }

    // -------------------------------------------------------------------------
    // buildCursorCondition
    // -------------------------------------------------------------------------

    public function testBuildCursorCondition(): void
    {
        $dateCreated = '2024-06-15 12:00:00';
        $id          = 123;

        $condition = UnionQueryBuilder::buildCursorCondition($dateCreated, $id);

        // Top-level operator must be 'or'.
        $this->assertSame('or', $condition[0]);

        // Branch 1: strict date comparison.
        $this->assertContains(['<', 'dateCreated', $dateCreated], $condition);

        // Branch 2: same date, lower id.
        $andBranch = ['and', ['dateCreated' => $dateCreated], ['<', 'id', $id]];
        $this->assertContains($andBranch, $condition);
    }

    public function testBuildCursorConditionStructure(): void
    {
        $condition = UnionQueryBuilder::buildCursorCondition('2024-01-01 00:00:00', 1);

        // Must have exactly 3 elements: 'or' + two branches.
        $this->assertCount(3, $condition);
        $this->assertSame('or', $condition[0]);

        // Second element must be a simple array with operator '<'.
        $this->assertIsArray($condition[1]);
        $this->assertSame('<', $condition[1][0]);

        // Third element must be an AND array with two sub-conditions.
        $this->assertIsArray($condition[2]);
        $this->assertSame('and', $condition[2][0]);
        $this->assertCount(3, $condition[2]); // 'and' + 2 sub-conditions
    }
}
