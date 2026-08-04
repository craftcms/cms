<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\utilities;

use craft\base\Utility;
use craft\events\ListVolumesEvent;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Utility\Events\AssetIndexVolumesResolving;
use Illuminate\Support\Facades\Event as EventFacade;
use yii\base\Event;

/**
 * AssetIndexes represents a AssetIndexes dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 6.0.0. [[\CraftCms\Cms\Utility\Utilities\AssetIndexes]] should be used instead.
 */
class AssetIndexes extends Utility
{
    /**
     * @event ListVolumesEvent The event that is triggered when listing the available volumes to index.
     * @since 4.4.0
     */
    public const EVENT_LIST_VOLUMES = 'listVolumes';

    /**
     * {@inheritdoc}
     */
    public static function displayName(): string
    {
        return \CraftCms\Cms\Utility\Utilities\AssetIndexes::displayName();
    }

    /**
     * {@inheritdoc}
     */
    public static function id(): string
    {
        return \CraftCms\Cms\Utility\Utilities\AssetIndexes::id();
    }

    /**
     * {@inheritdoc}
     */
    public static function icon(): ?string
    {
        return \CraftCms\Cms\Utility\Utilities\AssetIndexes::icon();
    }

    /**
     * Returns all the available volumes for indexing.
     *
     * @return Volume[]
     */
    public static function volumes(): array
    {
        return \CraftCms\Cms\Utility\Utilities\AssetIndexes::volumes();
    }

    /**
     * {@inheritdoc}
     */
    public static function contentHtml(): string
    {
        return \CraftCms\Cms\Utility\Utilities\AssetIndexes::contentHtml();
    }

    public static function registerEvents(): void
    {
        EventFacade::listen(AssetIndexVolumesResolving::class, function(AssetIndexVolumesResolving $event) {
            $yiiEvent = new ListVolumesEvent(['volumes' => $event->volumes]);

            Event::trigger(self::class, self::EVENT_LIST_VOLUMES, $yiiEvent);

            $event->volumes = $yiiEvent->volumes;

            if ($yiiEvent->handled) {
                return false;
            }
        });
    }
}
