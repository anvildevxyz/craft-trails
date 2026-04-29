<?php

namespace anvildev\trails\tests\Unit\Query;

use anvildev\trails\query\AuditQuery;
use anvildev\trails\tests\Support\TestCase;
use craft\base\ElementInterface;

class AuditQueryTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Fluent chaining
    // -------------------------------------------------------------------------

    public function testFluentChainingReturnsSelf(): void
    {
        $element = $this->createMock(ElementInterface::class);
        $element->method('getId')->willReturn(5);

        $query = (new AuditQuery())
            ->event('element.saved')
            ->category('entries')
            ->user(3)
            ->ipAddress('192.168.1.1')
            ->element('craft\\elements\\Entry', 7)
            ->after('2026-01-01')
            ->before('2026-12-31')
            ->search('hello')
            ->orderBy('dateCreated', 'asc')
            ->limit(25)
            ->cursor('abc123');

        $this->assertInstanceOf(AuditQuery::class, $query);
    }

    // -------------------------------------------------------------------------
    // toCriteria
    // -------------------------------------------------------------------------

    public function testBuildsCriteriaFromFluentMethods(): void
    {
        $query = (new AuditQuery())
            ->event('element.saved')
            ->category('entries')
            ->user(3)
            ->ipAddress('192.168.1.1')
            ->element('craft\\elements\\Entry', 7)
            ->after('2026-01-01')
            ->before('2026-12-31')
            ->search('hello');

        $criteria = $query->toCriteria();

        $this->assertSame('element.saved', $criteria['event']);
        $this->assertSame('entries', $criteria['category']);
        $this->assertSame(3, $criteria['userId']);
        $this->assertSame('192.168.1.1', $criteria['ipAddress']);
        $this->assertSame('craft\\elements\\Entry', $criteria['elementType']);
        $this->assertSame(7, $criteria['elementId']);
        $this->assertSame('2026-01-01', $criteria['dateFrom']);
        $this->assertSame('2026-12-31', $criteria['dateTo']);
        $this->assertSame('hello', $criteria['search']);
    }

    // -------------------------------------------------------------------------
    // limit
    // -------------------------------------------------------------------------

    public function testDefaultLimit(): void
    {
        $this->assertSame(50, (new AuditQuery())->getLimit());
    }

    public function testCustomLimit(): void
    {
        $query = (new AuditQuery())->limit(25);

        $this->assertSame(25, $query->getLimit());
    }

    public function testLimitCapsAt1000(): void
    {
        $query = (new AuditQuery())->limit(5000);

        $this->assertSame(AuditQuery::MAX_LIMIT, $query->getLimit());
        $this->assertSame(1000, $query->getLimit());
    }

    // -------------------------------------------------------------------------
    // forElement shorthand
    // -------------------------------------------------------------------------

    public function testForElementShorthand(): void
    {
        $element = $this->createMock(ElementInterface::class);
        $element->method('getId')->willReturn(99);

        $query = (new AuditQuery())->forElement($element);
        $criteria = $query->toCriteria();

        $this->assertSame(get_class($element), $criteria['elementType']);
        $this->assertSame(99, $criteria['elementId']);
    }

    // -------------------------------------------------------------------------
    // cursor
    // -------------------------------------------------------------------------

    public function testCursorStored(): void
    {
        $query = (new AuditQuery())->cursor('abc123');

        $this->assertSame('abc123', $query->getCursor());
    }

    public function testCursorDefaultsToNull(): void
    {
        $this->assertNull((new AuditQuery())->getCursor());
    }

    // -------------------------------------------------------------------------
    // toCriteria excludes nulls
    // -------------------------------------------------------------------------

    public function testToCriteriaExcludesNullValues(): void
    {
        $query = (new AuditQuery())->event('user.login');
        $criteria = $query->toCriteria();

        $this->assertArrayHasKey('event', $criteria);
        $this->assertArrayNotHasKey('category', $criteria);
        $this->assertArrayNotHasKey('userId', $criteria);
        $this->assertArrayNotHasKey('elementType', $criteria);
        $this->assertArrayNotHasKey('elementId', $criteria);
        $this->assertArrayNotHasKey('ipAddress', $criteria);
        $this->assertArrayNotHasKey('dateFrom', $criteria);
        $this->assertArrayNotHasKey('dateTo', $criteria);
        $this->assertArrayNotHasKey('search', $criteria);
    }
}
