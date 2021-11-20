<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\tests;

use craft\web\assets\cp\CpAsset;
use craft\web\assets\qunit\QunitAsset;
use yii\web\AssetBundle;

/**
 * Asset bundle for the Tests page
 */
class TestsAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = __DIR__ . '/dist';

        $this->depends = [
            CpAsset::class,
            QunitAsset::class,
        ];

        $this->js = [
            'tests.min.js',
        ];

        parent::init();
    }
}
