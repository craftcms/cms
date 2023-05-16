<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\utilities;

use Craft;
use craft\base\Utility;
use craft\events\ListVolumesEvent;
use craft\helpers\Html;
use craft\i18n\Locale;
use craft\models\Volume;
use craft\web\assets\assetindexes\AssetIndexesAsset;
use yii\base\Event;

/**
 * AssetIndexes represents a AssetIndexes dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class AssetIndexes extends Utility
{
    /**
     * @event ListVolumesEvent The event that is triggered when listing the available volumes to index.
     * @since 4.4.0
     */
    public const EVENT_LIST_VOLUMES = 'listVolumes';

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
    public static function iconPath(): ?string
    {
        return Craft::getAlias('@appicons/photo.svg');
    }

    /**
     * Returns all of the available volumes for indexing.
     *
     * @return Volume[]
     * @since 4.4.6
     */
    public static function volumes(): array
    {
        // Fire a 'listVolumes' event
        $event = new ListVolumesEvent([
            'volumes' => Craft::$app->getVolumes()->getAllVolumes(),
        ]);
        Event::trigger(self::class, self::EVENT_LIST_VOLUMES, $event);
        return $event->volumes;
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        $volumeOptions = [];

        foreach (static::volumes() as $volume) {
            $volumeOptions[] = [
                'label' => Html::encode($volume->name),
                'value' => $volume->id,
            ];
        }

        $view = Craft::$app->getView();
        $checkboxSelectHtml = $view->renderTemplate('_includes/forms/checkboxSelect.twig', [
            'class' => 'first',
            'name' => 'volumes',
            'options' => $volumeOptions,
            'showAllOption' => true,
            'values' => '*',
        ]);

        $view->registerAssetBundle(AssetIndexesAsset::class);
        $dateFormat = Craft::$app->getLocale()->getDateTimeFormat('short', Locale::FORMAT_PHP);

        $existingIndexingSessions = Craft::$app->getAssetIndexer()->getExistingIndexingSessions();

        return $view->renderTemplate('_components/utilities/AssetIndexes.twig', [
            'existingSessions' => $existingIndexingSessions,
            'checkboxSelectHtml' => $checkboxSelectHtml,
            'dateFormat' => $dateFormat,
        ]);
    }
}
