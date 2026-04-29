<?php

/**
 * PHPUnit Bootstrap File
 */

define('CRAFT_TESTS_PATH', __DIR__);
define('CRAFT_VENDOR_PATH', dirname(__DIR__) . '/vendor');
define('CRAFT_BASE_PATH', dirname(__DIR__));

// Load Composer autoloader
require_once CRAFT_VENDOR_PATH . '/autoload.php';

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

// Load Yii
require_once CRAFT_VENDOR_PATH . '/yiisoft/yii2/Yii.php';

// Load Craft class (extends Yii, provides Craft::t() etc.)
require_once CRAFT_VENDOR_PATH . '/craftcms/cms/src/Craft.php';

// Create a minimal Yii application for testing
$config = [
    'id' => 'trails-test',
    'basePath' => CRAFT_BASE_PATH,
    'vendorPath' => CRAFT_VENDOR_PATH,
    'components' => [
        'i18n' => [
            'class' => 'yii\i18n\I18N',
            'translations' => [
                'trails' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => CRAFT_BASE_PATH . '/src/translations',
                ],
            ],
        ],
    ],
];

new yii\console\Application($config);
