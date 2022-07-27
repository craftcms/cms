<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\garnish;

use craft\helpers\Json;
use craft\web\AssetBundle;
use craft\web\assets\elementresizedetector\ElementResizeDetectorAsset;
use craft\web\assets\jquerytouchevents\JqueryTouchEventsAsset;
use craft\web\assets\velocity\VelocityAsset;
use craft\web\View;
use yii\web\JqueryAsset;

/**
 * Garnish asset bundle.
 */
class GarnishAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $jsOptions = [
        'position' => View::POS_HEAD,
    ];

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = __DIR__ . '/dist';
        $this->depends = [
            ElementResizeDetectorAsset::class,
            JqueryAsset::class,
            JqueryTouchEventsAsset::class,
            VelocityAsset::class,
        ];

        $this->js = [
            'garnish.js',
        ];

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        // Instantiate the UiLayerManager
        $js = <<<JS
Garnish.uiLayerManager = new Garnish.UiLayerManager();

// deprecated
Garnish.shortcutManager = Garnish.uiLayerManager;
Garnish.escManager = new Garnish.EscManager();
JS;
        $view->registerJs($js, View::POS_HEAD);
    }
}
