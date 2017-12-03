<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\web\assets\vue;

use Craft;
use craft\helpers\ChartHelper;
use craft\helpers\Json;
use craft\web\AssetBundle;
use craft\web\View;

/**
 * Vue asset bundle.
 */
class VueAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@lib';

        $this->js = [
            'vue/vue.js',
            'vue-router/vue-router.js',
            'vuex/vuex.js',
            'axios/axios.js',
        ];

        parent::init();
    }
}
