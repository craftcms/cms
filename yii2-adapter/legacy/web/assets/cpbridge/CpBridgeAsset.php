<?php

namespace craft\web\assets\cpbridge;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class CpBridgeAsset extends AssetBundle
{
    public $depends = [
        CpAsset::class,
    ];

    public $sourcePath = __DIR__;

    public $css = [
        'css/cp-bridge.css',
    ];
}
