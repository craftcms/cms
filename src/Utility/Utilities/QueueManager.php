<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use craft\web\assets\queuemanager\QueueManagerAsset;
use CraftCms\Cms\Utility\Utility;

use function CraftCms\Cms\t;

/**
 * Queue manager is a utility used for managing jobs in the Queue.
 *
  @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 */
final class QueueManager extends Utility
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function displayName(): string
    {
        return t('Queue Manager');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function id(): string
    {
        return 'queue-manager';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function icon(): string
    {
        return 'play';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function toolbarHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('_components/utilities/QueueManager/toolbar.twig');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function footerHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('_components/utilities/QueueManager/footer.twig');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(QueueManagerAsset::class);

        return $view->renderTemplate('_components/utilities/QueueManager/content.twig');
    }
}
