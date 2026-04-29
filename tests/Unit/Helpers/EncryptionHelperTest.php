<?php

namespace anvildev\trails\tests\Unit\Helpers;

use anvildev\trails\helpers\EncryptionHelper;
use anvildev\trails\tests\Support\TestCase;

class EncryptionHelperTest extends TestCase
{
    public function testEncryptAndDecryptRoundTrip(): void
    {
        $this->requiresCraft();
        $original = 'user@example.com';
        $encrypted = EncryptionHelper::encrypt($original);
        $this->assertNotEquals($original, $encrypted);
        $this->assertEquals($original, EncryptionHelper::decrypt($encrypted));
    }

    public function testEncryptReturnsNullForNull(): void
    {
        $this->assertNull(EncryptionHelper::encrypt(null));
    }

    public function testDecryptReturnsNullForNull(): void
    {
        $this->assertNull(EncryptionHelper::decrypt(null));
    }

    public function testEncryptReturnsEmptyStringForEmptyString(): void
    {
        $this->assertSame('', EncryptionHelper::encrypt(''));
    }

    public function testDecryptReturnsOriginalIfNotEncrypted(): void
    {
        $this->requiresCraft();
        $plain = 'plain-text-value';
        $result = EncryptionHelper::decrypt($plain);
        $this->assertEquals($plain, $result);
    }

    public function testHashSessionIdProduces64CharHex(): void
    {
        $this->requiresCraft();
        $sessionId = 'abc123session';
        $hashed = EncryptionHelper::hashSessionId($sessionId);
        $this->assertEquals(64, strlen($hashed));
        $this->assertNotEquals($sessionId, $hashed);
    }

    public function testHashSessionIdIsConsistent(): void
    {
        $this->requiresCraft();
        $sessionId = 'test-session-id';
        $hash1 = EncryptionHelper::hashSessionId($sessionId);
        $hash2 = EncryptionHelper::hashSessionId($sessionId);
        $this->assertEquals($hash1, $hash2);
    }

    public function testHashSessionIdReturnsNullForNull(): void
    {
        $this->assertNull(EncryptionHelper::hashSessionId(null));
    }

    public function testHashSessionIdReturnsNullForEmptyString(): void
    {
        $this->assertNull(EncryptionHelper::hashSessionId(''));
    }
}
