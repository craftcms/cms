<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use CraftCms\Cms\Element\Events\RegisterFieldLayouts;
use CraftCms\Cms\Element\Events\RegisterSources;
use CraftCms\Cms\Support\Facades\Fields;

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
     * @see sources()
     */
    private static array $sources = [];

    public static function multiPageSources(): bool
    {
        return false;
    }

    public static function sources(string $context): array
    {
        if (! isset(self::$sources[static::class][$context])) {
            // Memoize the results immediately, in case sources() gets called again via the event
            self::$sources[static::class][$context] = static::defineSources($context);

            event($event = new RegisterSources(static::class, $context, self::$sources[static::class][$context]));
            self::$sources[static::class][$context] = $event->sources;
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

    public static function findSource(string $sourceKey, ?string $context): ?array
    {
        return null;
    }

    public static function sourcePath(string $sourceKey, string $stepKey, ?string $context): ?array
    {
        return null;
    }

    public static function modifyCustomSource(array $config): array
    {
        return $config;
    }

    public static function fieldLayouts(?string $source): array
    {
        $fieldLayouts = static::defineFieldLayouts($source);

        event($event = new RegisterFieldLayouts(static::class, $source, $fieldLayouts));

        return $event->fieldLayouts;
    }

    /**
     * Defines the field layouts associated with elements for a given source.
     *
     * @param  string|null  $source  The selected source's key, or `null` if all known field layouts should be returned
     * @return \CraftCms\Cms\FieldLayout\FieldLayout[] The associated field layouts
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
