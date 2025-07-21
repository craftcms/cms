<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace Craft\Cms\Utility\Utilities;

use Craft;
use Craft\Cms\Utility\Utility;
use craft\web\assets\updates\UpdatesAsset;

/**
 * Updates represents a Updates dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 6.0.0
 */
class Updates extends Utility
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
    public static function icon(): ?string
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
