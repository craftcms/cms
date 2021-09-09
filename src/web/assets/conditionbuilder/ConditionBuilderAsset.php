<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\conditionbuilder;

use craft\web\assets\cp\CpAsset;
use craft\web\assets\htmx\HtmxAsset;
use craft\web\assets\sortable\SortableAsset;
use craft\web\View;
use yii\web\AssetBundle;

/**
 * Condition Builder asset bundle.
 */
class ConditionBuilderAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $depends = [
        HtmxAsset::class,
        SortableAsset::class
    ];

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->css = [];

        $this->js = [
            'js/ConditionBuilder.js',
        ];

        parent::init();
    }
}
