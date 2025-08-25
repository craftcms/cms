<?php

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use craft\helpers\App;
use craft\helpers\Html;
use craft\i18n\Locale;
use craft\models\Volume;
use craft\web\assets\assetindexes\AssetIndexesAsset;
use CraftCms\Cms\Utility\Events\ListVolumes;
use CraftCms\Cms\Utility\Utility;
use Illuminate\Support\Facades\Event;

/**
 * AssetIndexes represents a AssetIndexes dashboard widget.
 *
 * @since 6.0.0
 */
final class AssetIndexes extends Utility
{
    /**
     * {@inheritdoc}
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Asset Indexes');
    }

    /**
     * {@inheritdoc}
     */
    public static function id(): string
    {
        return 'asset-indexes';
    }

    /**
     * {@inheritdoc}
     */
    public static function icon(): string
    {
        return 'image';
    }

    /**
     * Returns all the available volumes for indexing.
     *
     * @return Volume[]
     */
    public static function volumes(): array
    {
        $volumes = Craft::$app->getVolumes()->getAllVolumes();

        if (Event::hasListeners(ListVolumes::class)) {
            $event = new ListVolumes($volumes);
            Event::dispatch($event);

            return $event->volumes;
        }

        return $volumes;
    }

    /**
     * {@inheritdoc}
     */
    public static function contentHtml(): string
    {
        $volumeOptions = [];

        foreach (self::volumes() as $volume) {
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
            'isEphemeral' => App::isEphemeral(),
        ]);
    }
}
