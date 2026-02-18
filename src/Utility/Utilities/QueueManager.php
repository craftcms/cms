<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use craft\web\assets\queuemanager\QueueManagerAsset;
use CraftCms\Cms\Utility\Utility;
use Override;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * Queue manager is a utility used for managing jobs in the Queue.
 *
  @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 */
final class QueueManager extends Utility
{
    #[Override]
    public static function displayName(): string
    {
        return t('Queue Manager');
    }

    #[Override]
    public static function id(): string
    {
        return 'queue-manager';
    }

    #[Override]
    public static function icon(): string
    {
        return 'play';
    }

    #[Override]
    public static function toolbarHtml(): string
    {
        return template('_components/utilities/QueueManager/toolbar');
    }

    #[Override]
    public static function footerHtml(): string
    {
        return template('_components/utilities/QueueManager/footer');
    }

    #[Override]
    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(QueueManagerAsset::class);

        return template('_components/utilities/QueueManager/content');
    }
}
