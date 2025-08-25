<?php

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use craft\web\assets\updates\UpdatesAsset;
use CraftCms\Cms\Utility\Utility;

/**
 * Updates represents a Updates dashboard widget.
 *

 * @since 6.0.0
 */
final class Updates extends Utility
{
    /**
     * {@inheritdoc}
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Updates');
    }

    /**
     * {@inheritdoc}
     */
    public static function id(): string
    {
        return 'updates';
    }

    /**
     * {@inheritdoc}
     */
    public static function icon(): string
    {
        return 'certificate';
    }

    /**
     * {@inheritdoc}
     */
    public static function badgeCount(): int
    {
        return Craft::$app->getUpdates()->getTotalAvailableUpdates();
    }

    /**
     * {@inheritdoc}
     */
    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(UpdatesAsset::class);
        $view->registerJs('new Craft.UpdatesUtility();');

        return $view->renderTemplate('_components/utilities/Updates.twig');
    }
}
