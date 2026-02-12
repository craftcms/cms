<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use craft\helpers\App;
use craft\models\Volume;
use craft\web\assets\assetindexes\AssetIndexesAsset;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Translation\Locale;
use CraftCms\Cms\Utility\Events\ListVolumes;
use CraftCms\Cms\Utility\Utility;
use Override;

use function CraftCms\Cms\t;

/**
 * AssetIndexes represents a AssetIndexes dashboard widget.
 */
final class AssetIndexes extends Utility
{
    #[Override]
    public static function displayName(): string
    {
        return t('Asset Indexes');
    }

    #[Override]
    public static function id(): string
    {
        return 'asset-indexes';
    }

    #[Override]
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

        event($event = new ListVolumes($volumes));

        return $event->volumes;
    }

    #[Override]
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
        $dateFormat = I18N::getLocale()->getDateTimeFormat('short', Locale::FORMAT_PHP);

        $existingIndexingSessions = Craft::$app->getAssetIndexer()->getExistingIndexingSessions();

        return $view->renderTemplate('_components/utilities/AssetIndexes.twig', [
            'existingSessions' => $existingIndexingSessions,
            'checkboxSelectHtml' => $checkboxSelectHtml,
            'dateFormat' => $dateFormat,
            'isEphemeral' => App::isEphemeral(),
        ]);
    }
}
