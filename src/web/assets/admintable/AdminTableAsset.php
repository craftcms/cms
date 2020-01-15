<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\admintable;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\vue\VueAsset;

/**
 * Asset bundle for admin tables
 */
class AdminTableAsset extends AssetBundle
{
    /**
     * @var bool
     */
    private $useDevServer = false;

    /**
     * @var bool
     */
    private $devServerBaseUrl = 'http://localhost:8082/';

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
