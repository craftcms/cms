<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\utilities;

use Craft;
use craft\base\Utility;
use Craft\Cms\Utility\Events\RegisterCacheOptions;
use Craft\Cms\Utility\Events\RegisterTagOptions;
use craft\events\RegisterCacheOptionsEvent;
use yii\base\Event;

/**
 * ClearCaches represents a ClearCaches dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 6.0.0. [[\Craft\Cms\Utility\Utilities\ClearCaches]] should be used instead.
 */
class ClearCaches extends Utility
{
    /**
     * @event RegisterCacheOptionsEvent The event that is triggered when registering cache options.
     *
     * Each option added to [[RegisterCacheOptionsEvent::$options]] should be an array that has the following keys:
     *
     * - `key` – An identifying key for the cache option.
     * - `label` – A human-facing label for the cache option.
     * - `action` – Either the path to a folder that should be cleared, or a callable that should handle the cache clearing.
     * - `info` _(optional)_ – A description of the cache option.
     *
     * @see cacheOptions()
     */
    public const EVENT_REGISTER_CACHE_OPTIONS = 'registerCacheOptions';

    /**
     * @event RegisterCacheOptionsEvent The event that is triggered when registering cache tag invalidation options.
     *
     * Each option added to [[RegisterCacheOptionsEvent::$options]] should be an array that has the following keys:
     *
     * - `tag` – The cache tag name that sholud be cleared.
     * - `label` – A human-facing label for the cache tag option.
     *
     * @see tagOptions()
     * @since 3.5.0
     */
    public const EVENT_REGISTER_TAG_OPTIONS = 'registerTagOptions';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft\Cms\Utility\Utilities\ClearCaches::displayName();
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return Craft\Cms\Utility\Utilities\ClearCaches::id();
    }

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        return Craft\Cms\Utility\Utilities\ClearCaches::icon();
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        return Craft\Cms\Utility\Utilities\ClearCaches::contentHtml();
    }

    /**
     * Returns all cache options
     *
     * @return array
     */
    public static function cacheOptions(): array
    {
        return Craft\Cms\Utility\Utilities\ClearCaches::cacheOptions();
    }

    /**
     * Returns all cache tag invalidation options.
     *
     * @return array
     * @since 3.5.0
     */
    public static function tagOptions(): array
    {
        return Craft\Cms\Utility\Utilities\ClearCaches::tagOptions();
    }

    public static function registerEvents(): void
    {
        // Fire a 'registerCacheOptions' event
        if (Event::hasHandlers(self::class, self::EVENT_REGISTER_CACHE_OPTIONS)) {
            \Illuminate\Support\Facades\Event::listen(RegisterCacheOptions::class, function(RegisterCacheOptionsEvent $event) {
                $yiiEvent = new RegisterCacheOptionsEvent(['options' => $event->options]);
                Event::trigger(self::class, self::EVENT_REGISTER_CACHE_OPTIONS, $yiiEvent);

                $event->options = $yiiEvent->options;

                if ($yiiEvent->handled) {
                    return false;
                }
            });
        }

        // Fire a 'registerTagOptions' event
        if (Event::hasHandlers(self::class, self::EVENT_REGISTER_TAG_OPTIONS)) {
            \Illuminate\Support\Facades\Event::listen(RegisterTagOptions::class, function(RegisterCacheOptionsEvent $event) {
                $yiiEvent = new RegisterCacheOptionsEvent(['options' => $event->options]);
                Event::trigger(self::class, self::EVENT_REGISTER_TAG_OPTIONS, $yiiEvent);

                $event->options = $yiiEvent->options;

                if ($yiiEvent->handled) {
                    return false;
                }
            });
        }
    }
}
