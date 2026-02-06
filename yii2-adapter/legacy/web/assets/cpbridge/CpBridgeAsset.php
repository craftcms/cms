<?php

namespace craft\web\assets\cpbridge;

use craft\web\AssetBundle;
use yii\web\JqueryAsset;

class CpBridgeAsset extends AssetBundle
{
    /**
     * {@inheritdoc}
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * {@inheritdoc}
     */
    public $depends = [
        // CpAsset::class,
        // TailwindResetAsset::class,
        // AnimationBlockerAsset::class,
        // AxiosAsset::class,
        // D3Asset::class,
        // GarnishAsset::class,
        JqueryAsset::class,
        // JqueryTouchEventsAsset::class,
        // JqueryUiAsset::class,
        // JqueryPaymentAsset::class,
        // DatepickerI18nAsset::class,
        // SelectizeAsset::class,
        // VelocityAsset::class,
        // FileUploadAsset::class,
        // XregexpAsset::class,
        // FabricAsset::class,
        // IframeResizerAsset::class,
        // ThemeAsset::class,
    ];

    /**
     * {@inheritdoc}
     */
    public $css = [
        'css/cp-bridge.css',
    ];

    /**
     * {@inheritdoc}
     */
    public $js = [
        ['cp-bridge.js', 'type' => 'module'],
    ];
}
