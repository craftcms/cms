<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\base\Event as YiiEvent;
use craft\events\DefineValueEvent;
use craft\events\RegisterElementFieldLayoutsEvent;
use craft\events\RegisterElementSourcesEvent;
use CraftCms\Cms\Element\Events\DefineCacheTags;
use CraftCms\Cms\Element\Events\RegisterFieldLayouts;
use CraftCms\Cms\Element\Events\RegisterSources;
use Illuminate\Support\Facades\Event;

/**
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Element} instead.
 */
abstract class Element extends \CraftCms\Cms\Element\Element
{
    /**
     * @event DefineValueEvent The event that is triggered when defining the cache tags that should be cleared when
     * this element is saved.
     *
     * @see getCacheTags()
     * @since 4.1.0
     * @deprecated 6.0.0 Use {@see DefineCacheTags} instead.
     */
    public const EVENT_DEFINE_CACHE_TAGS = 'defineCacheTags';

    /**
     * @event RegisterElementSourcesEvent The event that is triggered when registering the available sources for the element type.
     *
     * @see sources()
     * @deprecated 6.0.0 Use {@see RegisterSources} instead.
     */
    public const EVENT_REGISTER_SOURCES = 'registerSources';

    /**
     * @event RegisterElementFieldLayoutsEvent The event that is triggered when registering all of the field layouts
     * associated with elements from a given source.
     *
     * @see fieldLayouts()
     * @since 3.5.0
     * @deprecated 6.0.0 Use {@see RegisterFieldLayouts} instead.
     */
    public const EVENT_REGISTER_FIELD_LAYOUTS = 'registerFieldLayouts';

    public static function registerEvents(): void
    {
        // Find all classes that extend Element
        $classes = get_declared_classes();
        $elementClasses = [];
        foreach ($classes as $class) {
            if (is_subclass_of($class, self::class)) {
                $elementClasses[] = $class;
            }
        }

        Event::listen(function(DefineCacheTags $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!YiiEvent::hasHandlers($class, self::EVENT_DEFINE_CACHE_TAGS)) {
                    continue;
                }

                $yiiEvent = new DefineValueEvent([
                    'sender' => $event->element,
                    'value' => $event->tags,
                ]);

                YiiEvent::trigger($class, self::EVENT_DEFINE_CACHE_TAGS, $yiiEvent);

                $event->tags = $yiiEvent->value;
            }
        });

        Event::listen(function(RegisterSources $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if ($class !== $event->elementType && !is_subclass_of($event->elementType, $class)) {
                    continue;
                }

                if (!YiiEvent::hasHandlers($class, self::EVENT_REGISTER_SOURCES)) {
                    continue;
                }

                $yiiEvent = new RegisterElementSourcesEvent([
                    'context' => $event->context,
                    'sources' => $event->sources,
                ]);

                YiiEvent::trigger($class, self::EVENT_REGISTER_SOURCES, $yiiEvent);

                $event->sources = $yiiEvent->sources;
            }
        });

        Event::listen(function(RegisterFieldLayouts $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if ($class !== $event->elementType && !is_subclass_of($event->elementType, $class)) {
                    continue;
                }

                if (!YiiEvent::hasHandlers($class, self::EVENT_REGISTER_FIELD_LAYOUTS)) {
                    continue;
                }

                $yiiEvent = new RegisterElementFieldLayoutsEvent([
                    'source' => $event->source,
                    'fieldLayouts' => $event->fieldLayouts,
                ]);

                YiiEvent::trigger($class, self::EVENT_REGISTER_FIELD_LAYOUTS, $yiiEvent);

                $event->fieldLayouts = $yiiEvent->fieldLayouts;
            }
        });
    }
}
