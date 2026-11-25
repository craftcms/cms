<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Asset\Concerns;

use craft\base\ElementEventConstants;
use craft\base\Event as YiiEvent;
use craft\elements\Asset;
use craft\events\AssetEvent;
use craft\events\DefineAssetUrlEvent;
use craft\events\GenerateTransformEvent;
use craft\models\ImageTransform as LegacyImageTransform;
use CraftCms\Cms\Asset\Elements\Asset as CoreAsset;
use CraftCms\Cms\Asset\Enums\FileKind;
use CraftCms\Cms\Asset\Events\AssetFileHandling;
use CraftCms\Cms\Asset\Events\AssetUrlDefined;
use CraftCms\Cms\Asset\Events\AssetUrlResolving;
use CraftCms\Cms\Asset\Events\TransformGenerated;
use CraftCms\Cms\Asset\Events\TransformGenerating;
use CraftCms\Cms\Asset\Validation\AssetRules;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageHelper;
use Deprecated;
use Illuminate\Support\Facades\Event;
use TypeError;

/**
 * @internal
 * @deprecated 6.0.0
 * @phpstan-ignore trait.unused
 */
trait LegacyConstants
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

    /**
     * @since 5.12.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Enums\FileKind} instead.
     */
    public const string KIND_MARKDOWN = FileKind::Markdown->value;

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
     * @deprecated 6.0.0 Use {@see AssetFileHandling} instead.
     */
    public const string EVENT_BEFORE_HANDLE_FILE = 'beforeHandleFile';

    /**
     * @event GenerateTransformEvent The event that is triggered before an asset’s transform URL is determined.
     * @deprecated 6.0.0 Use {@see TransformGenerating} instead.
     */
    public const string EVENT_BEFORE_GENERATE_TRANSFORM = 'beforeGenerateTransform';

    /**
     * @event GenerateTransformEvent The event that is triggered after an asset’s transform driver returns the transform URL.
     * @deprecated 6.0.0 Use {@see TransformGenerated} instead.
     */
    public const string EVENT_AFTER_GENERATE_TRANSFORM = 'afterGenerateTransform';

    public static function registerEvents(): void
    {
        Event::listen(function(AssetUrlResolving $event) {
            if (YiiEvent::hasHandlers(Asset::class, self::EVENT_BEFORE_DEFINE_URL)) {
                $yiiEvent = new DefineAssetUrlEvent([
                    'transform' => $event->transform,
                    'asset' => $event->asset,
                    'sender' => $event->asset,
                ]);

                YiiEvent::trigger(Asset::class, self::EVENT_BEFORE_DEFINE_URL, $yiiEvent);

                $event->url = $yiiEvent->url;
                $event->handled = $yiiEvent->handled;
            }
        });

        Event::listen(function(AssetUrlDefined $event) {
            if (YiiEvent::hasHandlers(Asset::class, self::EVENT_DEFINE_URL)) {
                $yiiEvent = new DefineAssetUrlEvent([
                    'transform' => $event->transform,
                    'asset' => $event->asset,
                    'url' => $event->url,
                    'sender' => $event->asset,
                ]);

                YiiEvent::trigger(Asset::class, self::EVENT_DEFINE_URL, $yiiEvent);

                $event->url = $yiiEvent->url;
                $event->handled = $yiiEvent->handled;
            }
        });

        Event::listen(function(TransformGenerating $event) {
            if (
                YiiEvent::hasHandlers(Asset::class, self::EVENT_BEFORE_GENERATE_TRANSFORM) &&
                self::triggersLegacyTransformEvents($event->asset)
            ) {
                $yiiEvent = new GenerateTransformEvent([
                    'transform' => self::legacyTransform($event->transform),
                    'asset' => $event->asset,
                    'url' => $event->url,
                    'sender' => $event->asset,
                ]);

                YiiEvent::trigger(Asset::class, self::EVENT_BEFORE_GENERATE_TRANSFORM, $yiiEvent);

                $event->url = $yiiEvent->url;
            }
        });

        Event::listen(function(TransformGenerated $event) {
            if (
                YiiEvent::hasHandlers(Asset::class, self::EVENT_AFTER_GENERATE_TRANSFORM) &&
                self::triggersLegacyTransformEvents($event->asset)
            ) {
                $yiiEvent = new GenerateTransformEvent([
                    'transform' => self::legacyTransform($event->transform),
                    'asset' => $event->asset,
                    'url' => $event->url,
                    'sender' => $event->asset,
                ]);

                YiiEvent::trigger(Asset::class, self::EVENT_AFTER_GENERATE_TRANSFORM, $yiiEvent);
            }
        });

        Event::listen(function(AssetFileHandling $event) {
            if (YiiEvent::hasHandlers(Asset::class, self::EVENT_BEFORE_HANDLE_FILE)) {
                $yiiEvent = new AssetEvent([
                    'asset' => $event->asset,
                    'isNew' => $event->isNew,
                ]);

                YiiEvent::trigger(Asset::class, self::EVENT_BEFORE_HANDLE_FILE, $yiiEvent);
            }
        });
    }

    /**
     * Returns the transform that legacy transform event handlers get.
     *
     * @param ImageTransform|string|array<string, mixed>|null $transform
     */
    private static function legacyTransform(ImageTransform|string|array|null $transform): ?LegacyImageTransform
    {
        if ($transform === null || $transform instanceof LegacyImageTransform) {
            return $transform;
        }

        try {
            return match (true) {
                $transform instanceof ImageTransform && $transform->id !== null => new LegacyImageTransform([
                    ...$transform->getConfig(),
                    'id' => $transform->id,
                    'uid' => $transform->uid,
                    'parameterChangeTime' => $transform->parameterChangeTime,
                ]),
                $transform instanceof ImageTransform => new LegacyImageTransform()->setInlineParameters($transform->getParameters()),
                is_array($transform) => new LegacyImageTransform()->setInlineParameters($transform),
                default => null,
            };
        } catch (TypeError) {
            return null;
        }
    }

    /**
     * Returns whether a transform of the asset should trigger the legacy generate transform events.
     *
     * Like Craft 5, only image files do, and on site requests, GIFs and SVGs only do when the `transformGifs` or
     * `transformSvgs` config setting allows it. This checks the extension rather than the MIME type, because getting
     * the MIME type of an asset with a transform runs the transform.
     */
    private static function triggersLegacyTransformEvents(CoreAsset $asset): bool
    {
        $extension = strtolower($asset->getExtension());

        if (!ImageHelper::canManipulateAsImage($extension)) {
            return false;
        }

        if (!request()->isSiteRequest()) {
            return true;
        }

        return match ($extension) {
            'gif' => Cms::config()->transformGifs,
            'svg' => Cms::config()->transformSvgs,
            default => true,
        };
    }
}
