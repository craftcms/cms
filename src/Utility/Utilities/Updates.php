<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use craft\web\assets\updates\UpdatesAsset;
use CraftCms\Cms\Updates\Updates as UpdatesService;
use CraftCms\Cms\Utility\Utility;

use function CraftCms\Cms\t;

/**
 * Updates represents a Updates dashboard widget.
 */
final class Updates extends Utility
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function displayName(): string
    {
        return t('Updates');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function id(): string
    {
        return 'updates';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function icon(): string
    {
        return 'certificate';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function badgeCount(): int
    {
        return resolve(UpdatesService::class)->totalAvailableUpdates();
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(UpdatesAsset::class);
        $view->registerJs('new Craft.UpdatesUtility();');

        return $view->renderTemplate('_components/utilities/Updates.twig');
    }
}
