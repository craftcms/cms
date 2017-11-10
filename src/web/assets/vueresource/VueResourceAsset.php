<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\web\assets\vueresource;

use craft\web\AssetBundle;

/**
 * Asset bundle for the Plugin Store page
 */
class VueResourceAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@npm/vue-resource/dist';

        $this->js = [
            'vue-resource'.$this->dotJs()
        ];

        parent::init();
    }
}
