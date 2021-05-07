<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\authentication\authenticatorcode;

use craft\web\AssetBundle;
use craft\web\assets\login\LoginAsset;
use craft\web\View;

/**
 * Asset bundle for the email code auth step
 */
class AuthenticatorCodeAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $depends = [
        LoginAsset::class
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'AuthenticatorCode.min.js',
    ];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view)
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $view->registerTranslations('app', [
                'Please enter a verification code',
            ]);
        }
    }
}
