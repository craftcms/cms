<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\conditionbuilder;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\vue\VueAsset;

/**
 * Asset bundle for admin tables
 */
class ConditionBuilderAsset extends AssetBundle
{
    /**
     * @var bool
     */
    private $useDevServer = true;

    /**
     * @var bool
     */
    private $devServerBaseUrl = 'http://localhost:8080/';

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__ . '/dist/';

        $this->depends = [
            CpAsset::class,
            VueAsset::class,
        ];

        if ($this->useDevServer) {
            $this->js = [
                $this->devServerBaseUrl . 'app.js',
            ];
        } else {
            $this->css = [
                'css/chunk-vendors.css',
                'css/app.css',
            ];

            $this->js = [
                'js/chunk-vendors.js',
                'js/app.js',
            ];
        }

        parent::init();
    }
}
