<?php

declare(strict_types=1);
namespace craft\web\assets\jquery;

use craft\web\InternalAssetBundle;

class JqueryAsset extends InternalAssetBundle
{
    public $sourcePath = __DIR__ . '/dist/';

    public $js = [
        'jquery.js',
    ];
}
