<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use craft\web\assets\updates\UpdatesAsset;
use CraftCms\Cms\Support\Facades\AssetRegistry;
use CraftCms\Cms\Updates\Updates as UpdatesService;
use CraftCms\Cms\Utility\Utility;
use Override;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * Updates represents a Updates dashboard widget.
 */
final class Updates extends Utility
{
    #[Override]
    public static function displayName(): string
    {
        return t('Updates');
    }

    #[Override]
    public static function id(): string
    {
        return 'updates';
    }

    #[Override]
    public static function icon(): string
    {
        return 'certificate';
    }

    #[Override]
    public static function badgeCount(): int
    {
        return app(UpdatesService::class)->totalAvailableUpdates();
    }

    #[Override]
    public static function contentHtml(): string
    {
        Craft::$app->getView()->registerAssetBundle(UpdatesAsset::class);

        AssetRegistry::js('new Craft.UpdatesUtility();');

        return template('_components/utilities/Updates');
    }
}
