<?php

namespace anvildev\trails\web\assets\cp;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset as CraftCpAsset;

class CpAsset extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = __DIR__ . '/dist';
        $this->depends = [CraftCpAsset::class];
        $this->css = ['trails.css'];
        $this->js = ['trails-htmx.js'];

        parent::init();
    }

    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        $view->registerJsFile(
            'https://unpkg.com/htmx.org@1.9.12',
            ['position' => \yii\web\View::POS_HEAD]
        );
        $view->registerJsFile(
            'https://unpkg.com/alpinejs@3.14.3/dist/cdn.min.js',
            ['position' => \yii\web\View::POS_HEAD, 'defer' => true]
        );
    }
}
