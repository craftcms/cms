<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\utilities;

use Craft;
use craft\base\Utility;
use craft\base\Volume;
use craft\helpers\Html;
use craft\web\assets\assetindexes\AssetIndexesAsset;

/**
 * AssetIndexes represents a AssetIndexes dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetIndexes extends Utility
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Asset Indexes');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'asset-indexes';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        return Craft::getAlias('@app/icons/photo.svg');
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        /** @var Volume[] $volumes */
        $volumes = Craft::$app->getVolumes()->getAllVolumes();
        $volumeOptions = [];

        foreach ($volumes as $volume) {
            $volumeOptions[] = [
                'label' => Html::encode($volume->name),
                'value' => $volume->id
            ];
        }

        $view = Craft::$app->getView();
        $checkboxSelectHtml = $view->renderTemplate('_includes/forms/checkboxSelect', [
            'name' => 'volumes',
            'options' => $volumeOptions,
            'showAllOption' => true,
            'values' => '*',
        ]);

        $view->registerAssetBundle(AssetIndexesAsset::class);
        $view->registerJs('new Craft.AssetIndexesUtility(\'asset-indexes\');');

        return $view->renderTemplate('_components/utilities/AssetIndexes', [
            'checkboxSelectHtml' => $checkboxSelectHtml,
        ]);
    }
}
