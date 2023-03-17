<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\mfa;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\View;

/**
 * Authentication bundle for MFA
 */
class MfaAsset extends AssetBundle
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
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'mfa.js',
    ];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $view->registerTranslations('app', [
                'MFA settings saved.',
                'MFA setup removed.',
                'No alternative MFA methods available.',
                'Registration failed:',
                'Security key registered.',
                'Sign in using a security key',
                'Starting registration',
                'Starting verification',
                'This browser does not support WebAuth.',
                'Use a password to login',
                'Use a security key to login',
                'Waiting for elevated session',
            ]);
        }
    }
}
