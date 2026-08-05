<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use CraftCms\Cms\Element\Contracts\ElementExporterInterface;
use CraftCms\Cms\Element\Events\ElementExportersResolving;
use CraftCms\Cms\Element\Exporters\Expanded;
use CraftCms\Cms\Element\Exporters\Raw;

/**
 * Exportable provides element export functionality.
 *
 * This trait contains methods for registering and retrieving element exporters,
 * allowing elements to be exported in different formats (Raw, Expanded, or custom exporters).
 *
 * @internal
 */
trait Exportable
{
    /**
     * Returns the available element exporters for a given source.
     *
     * @param  string  $source  The selected source's key
     * @return array<ElementExporterInterface|class-string<ElementExporterInterface>|array{type:class-string<ElementExporterInterface>}> The available element exporters
     */
    public static function exporters(string $source): array
    {
        event($event = new ElementExportersResolving(
            elementType: static::class,
            source: $source,
            exporters: static::defineExporters($source),
        ));

        return $event->exporters;
    }

    /**
     * Defines the available element exporters for a given source.
     *
     * @param  string  $source  The selected source's key
     * @return array<ElementExporterInterface|class-string<ElementExporterInterface>|array{type:class-string<ElementExporterInterface>}> The available element exporters
     *
     * @see exporters()
     */
    protected static function defineExporters(string $source): array
    {
        return [
            Raw::class,
            Expanded::class,
        ];
    }
}
