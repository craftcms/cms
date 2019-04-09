<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\vue;

use craft\web\AssetBundle;

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
            'vue/vue' . $this->dotJs(),
            'vue-router/vue-router.js',
            'vuex/vuex.js',
            'axios/axios.js',
            'vue-autosuggest/vue-autosuggest.js',
        ];

        parent::init();
    }
}
