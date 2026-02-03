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
use craft\events\RegisterElementActionsEvent;
use craft\events\RegisterElementFieldLayoutsEvent;
use craft\events\RegisterElementSourcesEvent;
use craft\events\RegisterPreviewTargetsEvent;
use CraftCms\Cms\Element\Events\DefineCacheTags;
use CraftCms\Cms\Element\Events\RegisterActions;
use CraftCms\Cms\Element\Events\RegisterFieldLayouts;
use CraftCms\Cms\Element\Events\RegisterPreviewTargets;
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

    /**
     * @event RegisterPreviewTargetsEvent The event that is triggered when registering the element's preview targets.
     *
     * @see getPreviewTargets()
     * @since 3.2.0
     * @deprecated 6.0.0 Use {@see RegisterPreviewTargets} instead.
     */
    public const EVENT_REGISTER_PREVIEW_TARGETS = 'registerPreviewTargets';

    /**
     * @event RegisterElementActionsEvent The event that is triggered when registering the available bulk actions for the element type.
     *
     * @see actions()
     * @since 3.0.0
     * @deprecated 6.0.0 Use {@see RegisterActions} instead.
     */
    public const EVENT_REGISTER_ACTIONS = 'registerActions';

    /**
     * @event RegisterElementExportersEvent The event that is triggered when registering the available exporters for the element type.
     *
     * @see exporters()
     * @since 3.4.0
     * @deprecated 6.0.0 Use {@see RegisterExporters} instead.
     */
    public const EVENT_REGISTER_EXPORTERS = 'registerExporters';

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

        Event::listen(function(RegisterPreviewTargets $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!is_a($event->element, $class)) {
                    continue;
                }

                if (!YiiEvent::hasHandlers($class, self::EVENT_REGISTER_PREVIEW_TARGETS)) {
                    continue;
                }

                $yiiEvent = new RegisterPreviewTargetsEvent([
                    'sender' => $event->element,
                    'previewTargets' => $event->previewTargets,
                ]);

                YiiEvent::trigger($class, self::EVENT_REGISTER_PREVIEW_TARGETS, $yiiEvent);

                $event->previewTargets = $yiiEvent->previewTargets;
            }
        });

        Event::listen(function(RegisterActions $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if ($class !== $event->elementType && !is_subclass_of($event->elementType, $class)) {
                    continue;
                }

                if (!YiiEvent::hasHandlers($class, self::EVENT_REGISTER_ACTIONS)) {
                    continue;
                }

                $yiiEvent = new RegisterElementActionsEvent([
                    'source' => $event->source,
                    'actions' => $event->actions,
                ]);

                YiiEvent::trigger($class, self::EVENT_REGISTER_ACTIONS, $yiiEvent);

                $event->actions = $yiiEvent->actions;
            }
        });

        Event::listen(function(RegisterExporters $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if ($class !== $event->elementType && !is_subclass_of($event->elementType, $class)) {
                    continue;
                }

                if (!YiiEvent::hasHandlers($class, self::EVENT_REGISTER_EXPORTERS)) {
                    continue;
                }

                $yiiEvent = new RegisterElementExportersEvent([
                    'source' => $event->source,
                    'exporters' => $event->exporters,
                ]);

                YiiEvent::trigger($class, self::EVENT_REGISTER_EXPORTERS, $yiiEvent);

                $event->exporters = $yiiEvent->exporters;
            }
        });
    }
}
