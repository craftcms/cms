<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use craft\base\ElementEventConstants;
use craft\base\Event as YiiEvent;
use craft\events\AssetEvent;
use craft\events\DefineAssetUrlEvent;
use craft\events\GenerateTransformEvent;
use CraftCms\Cms\Asset\Enums\FileKind;
use CraftCms\Cms\Asset\Events\AfterGenerateTransform;
use CraftCms\Cms\Asset\Events\AssetFileHandling;
use CraftCms\Cms\Asset\Events\AssetUrlDefined;
use CraftCms\Cms\Asset\Events\AssetUrlResolving;
use CraftCms\Cms\Asset\Events\TransformGenerating;
use CraftCms\Cms\Asset\Validation\AssetRules;
use CraftCms\Cms\Element\Validation\ElementRules;
use Illuminate\Support\Facades\Event;

/**
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Elements\Asset} instead.
 */
class Asset extends \CraftCms\Cms\Asset\Elements\Asset
{
    use ElementEventConstants;

    public const string SCENARIO_DEFAULT = ElementRules::SCENARIO_DEFAULT;

    public const string SCENARIO_ESSENTIALS = ElementRules::SCENARIO_ESSENTIALS;

    public const string SCENARIO_LIVE = ElementRules::SCENARIO_LIVE;

    public const string SCENARIO_MOVE = AssetRules::SCENARIO_MOVE;

    public const string SCENARIO_FILEOPS = AssetRules::SCENARIO_FILEOPS;

    public const string SCENARIO_INDEX = AssetRules::SCENARIO_INDEX;

    public const string SCENARIO_CREATE = AssetRules::SCENARIO_CREATE;

    public const string SCENARIO_REPLACE = AssetRules::SCENARIO_REPLACE;

    /** @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Enums\FileKind} instead. */
    public const string KIND_ACCESS = FileKind::Access->value;

    /** @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Enums\FileKind} instead. */
    public const string KIND_AUDIO = FileKind::Audio->value;

    /** @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Enums\FileKind} instead. */
    public const string KIND_CAPTIONS_SUBTITLES = FileKind::CaptionsSubtitles->value;

    /** @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Enums\FileKind} instead. */
    public const string KIND_COMPRESSED = FileKind::Compressed->value;

    /** @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Enums\FileKind} instead. */
    public const string KIND_EXCEL = FileKind::Excel->value;

    /** @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Enums\FileKind} instead. */
    public const string KIND_FLASH = FileKind::Flash->value;

    /** @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Enums\FileKind} instead. */
    public const string KIND_HTML = FileKind::Html->value;

    /** @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Enums\FileKind} instead. */
    public const string KIND_ILLUSTRATOR = FileKind::Illustrator->value;

    /** @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Enums\FileKind} instead. */
    public const string KIND_IMAGE = FileKind::Image->value;

    /** @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Enums\FileKind} instead. */
    public const string KIND_JAVASCRIPT = FileKind::Javascript->value;

    /** @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Enums\FileKind} instead. */
    public const string KIND_JSON = FileKind::Json->value;

    /** @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Enums\FileKind} instead. */
    public const string KIND_PDF = FileKind::Pdf->value;

    /** @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Enums\FileKind} instead. */
    public const string KIND_PHOTOSHOP = FileKind::Photoshop->value;

    /** @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Enums\FileKind} instead. */
    public const string KIND_PHP = FileKind::Php->value;

    /** @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Enums\FileKind} instead. */
    public const string KIND_POWERPOINT = FileKind::Powerpoint->value;

    /** @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Enums\FileKind} instead. */
    public const string KIND_TEXT = FileKind::Text->value;

    /** @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Enums\FileKind} instead. */
    public const string KIND_VIDEO = FileKind::Video->value;

    /** @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Enums\FileKind} instead. */
    public const string KIND_WORD = FileKind::Word->value;

    /** @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Enums\FileKind} instead. */
    public const string KIND_XML = FileKind::Xml->value;

    /** @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Enums\FileKind} instead. */
    public const string KIND_UNKNOWN = FileKind::Unknown->value;

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

    public static function registerEvents(): void
    {
        Event::listen(function(AssetUrlResolving $event) {
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

        Event::listen(function(AssetUrlDefined $event) {
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

        Event::listen(function(TransformGenerating $event) {
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

        Event::listen(function(AssetFileHandling $event) {
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
