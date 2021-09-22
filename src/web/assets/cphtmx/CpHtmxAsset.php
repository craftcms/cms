<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\cphtmx;

use craft\web\assets\cp\CpAsset;
use craft\web\assets\htmx\HtmxAsset;
use yii\web\AssetBundle;

/**
 * Sortable asset bundle.
 */
class CpHtmxAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $depends = [
        CpAsset::class,
        HtmxAsset::class
    ];

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->css = [];

        $this->js = [
            'js/CpHtmx.js',
        ];

        parent::init();
    }
}
