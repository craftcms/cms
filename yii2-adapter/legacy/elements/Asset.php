<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use craft\base\Event as YiiEvent;
use craft\events\AssetEvent;
use craft\events\DefineAssetUrlEvent;
use craft\events\GenerateTransformEvent;
use CraftCms\Cms\Asset\Events\AfterGenerateTransform;
use CraftCms\Cms\Asset\Events\BeforeDefineAssetUrl;
use CraftCms\Cms\Asset\Events\BeforeGenerateTransform;
use CraftCms\Cms\Asset\Events\BeforeHandleFile;
use CraftCms\Cms\Asset\Events\DefineAssetUrl;
use Illuminate\Support\Facades\Event;

/**
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Elements\Asset} instead.
 */
class Asset extends \CraftCms\Cms\Asset\Elements\Asset
{
    // Events
    // -------------------------------------------------------------------------

    /**
     * @event AssetEvent The event that is triggered before an asset is uploaded to volume.
     */
    public const string EVENT_BEFORE_HANDLE_FILE = 'beforeHandleFile';

    /**
     * @event GenerateTransformEvent The event that is triggered before a transform is generated for an asset.
     */
    public const string EVENT_BEFORE_GENERATE_TRANSFORM = 'beforeGenerateTransform';

    /**
     * @event GenerateTransformEvent The event that is triggered after a transform is generated for an asset.
     */
    public const string EVENT_AFTER_GENERATE_TRANSFORM = 'afterGenerateTransform';

    /**
     * @event DefineAssetUrlEvent The event that is triggered before defining the asset’s URL.
     *
     * @see getUrl()
     */
    public const string EVENT_BEFORE_DEFINE_URL = 'beforeDefineUrl';

    /**
     * @event DefineAssetUrlEvent The event that is triggered when defining the asset’s URL.
     *
     * @see getUrl()
     */
    public const string EVENT_DEFINE_URL = 'defineUrl';

    public static function registerEvents(): void
    {
        Event::listen(function(BeforeDefineAssetUrl $event) {
            if (YiiEvent::hasHandlers(self::class, self::EVENT_BEFORE_DEFINE_URL)) {
                $yiiEvent = new DefineAssetUrlEvent([
                    'transform' => $event->transform,
                    'asset' => $event->asset,
                    'sender' => $event->asset,
                ]);

                YiiEvent::trigger(self::class, self::EVENT_BEFORE_DEFINE_URL, $yiiEvent);

                $event->url = $yiiEvent->url;
                $event->handled = $yiiEvent->handled;
            }
        });

        Event::listen(function(DefineAssetUrl $event) {
            if (YiiEvent::hasHandlers(self::class, self::EVENT_DEFINE_URL)) {
                $yiiEvent = new DefineAssetUrlEvent([
                    'transform' => $event->transform,
                    'asset' => $event->asset,
                    'sender' => $event->asset,
                ]);

                YiiEvent::trigger(self::class, self::EVENT_DEFINE_URL, $yiiEvent);

                $event->url = $yiiEvent->url;
                $event->handled = $yiiEvent->handled;
            }
        });

        Event::listen(function(BeforeGenerateTransform $event) {
            if (YiiEvent::hasHandlers(self::class, self::EVENT_BEFORE_GENERATE_TRANSFORM)) {
                $yiiEvent = new GenerateTransformEvent([
                    'transform' => $event->transform,
                    'asset' => $event->asset,
                    'url' => $event->url,
                    'sender' => $event->asset,
                ]);

                YiiEvent::trigger(self::class, self::EVENT_BEFORE_GENERATE_TRANSFORM, $yiiEvent);

                $event->url = $yiiEvent->url;
            }
        });

        Event::listen(function(AfterGenerateTransform $event) {
            if (YiiEvent::hasHandlers(self::class, self::EVENT_AFTER_GENERATE_TRANSFORM)) {
                $yiiEvent = new GenerateTransformEvent([
                    'transform' => $event->transform,
                    'asset' => $event->asset,
                    'url' => $event->url,
                    'sender' => $event->asset,
                ]);

                YiiEvent::trigger(self::class, self::EVENT_AFTER_GENERATE_TRANSFORM, $yiiEvent);
            }
        });

        Event::listen(function(BeforeHandleFile $event) {
            if (YiiEvent::hasHandlers(self::class, self::EVENT_BEFORE_HANDLE_FILE)) {
                $yiiEvent = new AssetEvent([
                    'asset' => $event->asset,
                    'isNew' => $event->isNew,
                ]);

                YiiEvent::trigger(self::class, self::EVENT_BEFORE_HANDLE_FILE, $yiiEvent);
            }
        });
    }
}
