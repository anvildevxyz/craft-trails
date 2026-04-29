<?php

namespace anvildev\trails\tests\Support;

use Mockery;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base Test Case for Trails plugin tests
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Skip test if Craft CMS is not fully initialized.
     */
    protected function requiresCraft(): void
    {
        if (!\Yii::$app instanceof \craft\console\Application
            && !\Yii::$app instanceof \craft\web\Application) {
            $this->markTestSkipped('Requires full Craft CMS initialization');
        }
    }

    /**
     * Tear down after each test
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Assert that an array has all specified keys
     */
    protected function assertArrayHasKeys(array $keys, array $array, string $message = ''): void
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array, $message);
        }
    }
}
