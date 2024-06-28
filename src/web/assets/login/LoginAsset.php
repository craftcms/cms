<?php

namespace craft\web\assets\login;

use craft\web\AssetBundle;

/**
 * Login asset bundle
 */
class LoginAsset extends AssetBundle
{
    public $css = [];
    public $depends = [];
    public $js = [
        'Login.js',
    ];
    public $sourcePath = __DIR__ . '/dist';
}
