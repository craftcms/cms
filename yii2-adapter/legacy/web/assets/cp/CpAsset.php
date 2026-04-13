<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\cp;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\animationblocker\AnimationBlockerAsset;
use craft\web\assets\axios\AxiosAsset;
use craft\web\assets\d3\D3Asset;
use craft\web\assets\datepickeri18n\DatepickerI18nAsset;
use craft\web\assets\fabric\FabricAsset;
use craft\web\assets\fileupload\FileUploadAsset;
use craft\web\assets\garnish\GarnishAsset;
use craft\web\assets\iframeresizer\IframeResizerAsset;
use craft\web\assets\jquerypayment\JqueryPaymentAsset;
use craft\web\assets\jquerytouchevents\JqueryTouchEventsAsset;
use craft\web\assets\jqueryui\JqueryUiAsset;
use craft\web\assets\picturefill\PicturefillAsset;
use craft\web\assets\selectize\SelectizeAsset;
use craft\web\assets\tailwindreset\TailwindResetAsset;
use craft\web\assets\theme\ThemeAsset;
use craft\web\assets\velocity\VelocityAsset;
use craft\web\assets\xregexp\XregexpAsset;
use craft\web\View;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Update\Updates;
use CraftCms\Cms\View\CpBootstrap;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Yii2Adapter\DeprecatedConcepts;
use yii\web\JqueryAsset;
use function CraftCms\Cms\t;

/**
 * Asset bundle for the control panel
 */
class CpAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $depends = [
        TailwindResetAsset::class,
        AnimationBlockerAsset::class,
        AxiosAsset::class,
        D3Asset::class,
        GarnishAsset::class,
        JqueryAsset::class,
        JqueryTouchEventsAsset::class,
        JqueryUiAsset::class,
        JqueryPaymentAsset::class,
        DatepickerI18nAsset::class,
        SelectizeAsset::class,
        VelocityAsset::class,
        FileUploadAsset::class,
        XregexpAsset::class,
        FabricAsset::class,
        IframeResizerAsset::class,
        ThemeAsset::class,
        PicturefillAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public $css = [
        'css/cp.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'cp.js',
    ];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            HtmlStack::icons(app(CpBootstrap::class)->icons());
        }

        // Define the Craft object
        $craftJson = Json::encode($this->_craftData());
        $js = <<<JS
window.Craft = $craftJson;
JS;
        HtmlStack::js($js, Position::Head);
    }

    private function _craftData(): array
    {
        $data = app(CpBootstrap::class)->craftData([], []);

        if (!array_key_exists('userId', $data)) {
            return $data;
        }

        $upToDate = Cms::isInstalled() && !app(Updates::class)->areMigrationsPending();
        $data['editableCategoryGroups'] = $upToDate ? $this->_editableCategoryGroups() : [];

        return $data;
    }

    private function _editableCategoryGroups(): array
    {
        $groups = [];

        if (!DeprecatedConcepts::supportsCategories()) {
            return $groups;
        }

        foreach (Craft::$app->getCategories()->getEditableGroups() as $group) {
            $groups[] = [
                'handle' => $group->handle,
                'id' => (int)$group->id,
                'name' => t($group->name, category: 'site'),
                'uid' => $group->uid,
            ];
        }

        return $groups;
    }
}
