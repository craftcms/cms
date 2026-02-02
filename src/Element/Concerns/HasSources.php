<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use craft\events\RegisterElementFieldLayoutsEvent;
use craft\events\RegisterElementSourcesEvent;
use craft\models\FieldLayout;
use CraftCms\Cms\Support\Facades\Fields;
use yii\base\Event;

/**
 * HasSources provides element source management functionality.
 *
 * This trait contains methods for defining, finding, and working with element sources,
 * as well as managing field layouts associated with sources.
 *
 * @internal
 */
trait HasSources
{
    /**
     * @event RegisterElementSourcesEvent The event that is triggered when registering the available sources for the element type.
     */
    public const EVENT_REGISTER_SOURCES = 'registerSources';

    /**
     * @see sources()
     */
    private static array $sources = [];

    /**
     * {@inheritdoc}
     */
    public static function multiPageSources(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public static function sources(string $context): array
    {
        if (! isset(self::$sources[static::class][$context])) {
            // Memoize the results immediately, in case sources() gets called again via the event
            self::$sources[static::class][$context] = static::defineSources($context);

            // Fire a 'registerSources' event
            if (Event::hasHandlers(static::class, self::EVENT_REGISTER_SOURCES)) {
                $event = new RegisterElementSourcesEvent([
                    'context' => $context,
                    'sources' => self::$sources[static::class][$context],
                ]);
                Event::trigger(static::class, self::EVENT_REGISTER_SOURCES, $event);
                self::$sources[static::class][$context] = $event->sources;
            }
        }

        return self::$sources[static::class][$context];
    }

    /**
     * Defines the sources that elements of this type may belong to.
     *
     * @param  string  $context  The context ('index', 'modal', 'field', or 'settings').
     * @return array The sources.
     *
     * @see sources()
     */
    protected static function defineSources(string $context): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function findSource(string $sourceKey, ?string $context): ?array
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public static function sourcePath(string $sourceKey, string $stepKey, ?string $context): ?array
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public static function modifyCustomSource(array $config): array
    {
        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public static function fieldLayouts(?string $source): array
    {
        $fieldLayouts = static::defineFieldLayouts($source);

        if (! Event::hasHandlers(static::class, self::EVENT_REGISTER_FIELD_LAYOUTS)) {
            return $fieldLayouts;
        }

        $event = new RegisterElementFieldLayoutsEvent([
            'source' => $source,
            'fieldLayouts' => $fieldLayouts,
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_FIELD_LAYOUTS, $event);

        return $event->fieldLayouts;
    }

    /**
     * Defines the field layouts associated with elements for a given source.
     *
     * @param  string|null  $source  The selected source's key, or `null` if all known field layouts should be returned
     * @return FieldLayout[] The associated field layouts
     *
     * @see fieldLayouts()
     * @since 3.5.0
     */
    protected static function defineFieldLayouts(?string $source): array
    {
        // Default to all the field layouts associated with this element type
        return Fields::getLayoutsByType(static::class)->all();
    }
}
