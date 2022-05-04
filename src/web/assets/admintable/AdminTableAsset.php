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
    private bool $useDevServer = false;

    /**
     * @var string
     */
    private string $devServerBaseUrl = 'http://localhost:8082/';

    /**
     * @inheritdoc
     */
    public function init(): void
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
                'css/app.css',
            ];

            $this->js = [
                'js/app.js',
            ];
        }

        parent::init();
    }
}
