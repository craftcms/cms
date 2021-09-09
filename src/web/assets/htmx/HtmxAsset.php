<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\htmx;

use craft\web\assets\cp\CpAsset;
use craft\web\View;
use yii\web\AssetBundle;

/**
 * Sortable asset bundle.
 */
class HtmxAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $depends = [
        CpAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = '@lib/htmx';

        $this->css = [];

        $this->js = [
            'htmx.js',
        ];

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        $js = <<<JS
// Anytime Htmx does a server request, add standard Craft headers (includes CSRF)
htmx.on('htmx:configRequest', function(evt) {
    evt.detail.headers = {...evt.detail.headers, ...Craft._actionHeaders()};
});

// Anytime Htmx does a swap, look for html in templates to be added to head or foot in CP
htmx.on('htmx:afterSwap', function(evt) {
    const content = evt.detail.elt;
    const headHtmls = content.querySelectorAll("template.hx-head-html");
    const footHtmls = content.querySelectorAll("template.hx-foot-html");

    for (var i = 0; i < headHtmls.length; i++) {
        var headHtml = headHtmls[i].innerHTML;
        console.log('Appending', headHtml);
        Craft.appendHeadHtml(headHtml);
    }
    
    for (var i = 0; i < footHtmls.length; i++) {
        var footHtml = footHtmls[i].innerHTML;
        console.log('Appending', footHtml);
        Craft.appendHeadHtml(footHtml);
    }
    
    Craft.initUiElements(content);
});
JS;
        $view->registerJs($js, View::POS_END);
    }
}
