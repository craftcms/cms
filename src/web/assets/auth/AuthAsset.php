<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\auth;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\View;

/**
 * Authentication bundle for 2FA
 */
class AuthAsset extends AssetBundle
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
        'auth.js',
    ];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $view->registerTranslations('app', [
                '2FA settings saved.',
                '2FA setup removed.',
                'Are you sure you want to delete this setup?',
                'Are you sure you want to delete ‘{credentialName}‘ security key?',
                'Are you sure you want to generate new recovery codes? All current codes will stop working.',
                'In this browser, you can only use a security key with an external (roaming) authenticator like Yubikey or Titan Key.',
                'No alternative 2FA methods available.',
                'Please enter a name for the security key',
                'Recovery codes generated.',
                'Registration failed:',
                'Security key registered.',
                'Sign in using a security key',
                'Starting registration',
                'Starting verification',
                'This browser does not support WebAuthn.',
                'Use a security key',
                'Waiting for elevated session',
                'We couldn’t find your credentials. This can happen e.g. if you deleted them via the Control Panel.',
            ]);
        }
    }
}
