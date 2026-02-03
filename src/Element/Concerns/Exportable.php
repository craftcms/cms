<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use craft\elements\exporters\Expanded;
use craft\elements\exporters\Raw;
use craft\events\RegisterElementExportersEvent;
use yii\base\Event;

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
     * @event RegisterElementExportersEvent The event that is triggered when registering the available exporters for the element type.
     *
     * @since 3.4.0
     */
    public const EVENT_REGISTER_EXPORTERS = 'registerExporters';

    /**
     * Returns the available element exporters for a given source.
     *
     * @param  string  $source  The selected source's key
     * @return array The available element exporters
     *
     * @since 3.4.0
     */
    public static function exporters(string $source): array
    {
        $exporters = static::defineExporters($source);

        // Fire a 'registerExporters' event
        if (Event::hasHandlers(static::class, self::EVENT_REGISTER_EXPORTERS)) {
            $event = new RegisterElementExportersEvent([
                'source' => $source,
                'exporters' => $exporters,
            ]);
            Event::trigger(static::class, self::EVENT_REGISTER_EXPORTERS, $event);

            return $event->exporters;
        }

        return $exporters;
    }

    /**
     * Defines the available element exporters for a given source.
     *
     * @param  string  $source  The selected source's key
     * @return array The available element exporters
     *
     * @see exporters()
     * @since 3.4.0
     */
    protected static function defineExporters(string $source): array
    {
        return [
            Raw::class,
            Expanded::class,
        ];
    }
}
