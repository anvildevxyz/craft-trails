<?php

namespace anvildev\trails\tests\Unit\Query;

use anvildev\trails\query\Cursor;
use anvildev\trails\tests\Support\TestCase;

class CursorTest extends TestCase
{
    public function testEncodeAndDecode(): void
    {
        $dateCreated = '2024-01-15 10:30:00';
        $id = 42;

        $token = Cursor::encode($dateCreated, $id);
        $result = Cursor::decode($token);

        $this->assertNotNull($result);
        $this->assertSame($dateCreated, $result['dateCreated']);
        $this->assertSame($id, $result['id']);
    }

    public function testDecodeReturnsNullForInvalidToken(): void
    {
        $result = Cursor::decode('!!!not-valid-base64!!!');
        $this->assertNull($result);
    }

    public function testDecodeReturnsNullForEmptyString(): void
    {
        $result = Cursor::decode('');
        $this->assertNull($result);
    }

    public function testDecodeReturnsNullForNull(): void
    {
        $result = Cursor::decode(null);
        $this->assertNull($result);
    }

    public function testDecodeReturnsNullForMalformedJson(): void
    {
        // Valid base64url of non-JSON string
        $token = rtrim(strtr(base64_encode('not-json-at-all'), '+/', '-_'), '=');
        $result = Cursor::decode($token);
        $this->assertNull($result);
    }

    public function testDecodeReturnsNullForMissingFields(): void
    {
        // Valid base64url of JSON with dateCreated ('d') but no id ('i')
        $json = json_encode(['d' => '2024-01-15 10:30:00']);
        $token = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $result = Cursor::decode($token);
        $this->assertNull($result);
    }

    public function testRoundTripPreservesValues(): void
    {
        $cases = [
            ['2024-01-01 00:00:00', 1],
            ['2024-06-15 12:34:56', 999999],
            ['2099-12-31 23:59:59', 2147483647],
        ];

        foreach ($cases as [$dateCreated, $id]) {
            $token = Cursor::encode($dateCreated, $id);
            $result = Cursor::decode($token);

            $this->assertNotNull($result, "Round-trip failed for dateCreated={$dateCreated}, id={$id}");
            $this->assertSame($dateCreated, $result['dateCreated']);
            $this->assertSame($id, $result['id']);
        }
    }

    public function testTokenIsUrlSafe(): void
    {
        $token = Cursor::encode('2024-03-20 08:00:00', 123);

        $this->assertStringNotContainsString('+', $token);
        $this->assertStringNotContainsString('/', $token);
        $this->assertStringNotContainsString('=', $token);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $token);
    }
}
