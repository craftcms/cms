<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\utilities;

use Craft;
use craft\base\Utility;
use craft\base\Volume;
use yii\base\Exception;

/**
 * AssetIndexes represents a AssetIndexes dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
        $iconPath = Craft::getAlias('@app/icons/photo.svg');

        if ($iconPath === false) {
            throw new Exception('There was a problem getting the icon path.');
        }

        return $iconPath;
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        /** @var Volume[] $volumes */
        $volumes = Craft::$app->getVolumes()->getAllVolumes();
        $sourceOptions = [];

        foreach ($volumes as $volume) {
            $sourceOptions[] = [
                'label' => $volume->name,
                'value' => $volume->id
            ];
        }

        $view = Craft::$app->getView();
        $checkboxSelectHtml = $view->renderTemplate('_includes/forms/checkboxSelect', [
            'name' => 'sources',
            'options' => $sourceOptions
        ]);

        $view->registerJsResource('js/AssetIndexesUtility.js');
        $view->registerJs('new Craft.AssetIndexesUtility(\'asset-indexes\');');

        return $view->renderTemplate('_components/utilities/AssetIndexes', [
            'checkboxSelectHtml' => $checkboxSelectHtml,
        ]);
    }
}
