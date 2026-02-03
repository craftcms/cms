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
use craft\events\ElementIndexTableAttributeEvent;
use craft\events\RegisterElementActionsEvent;
use craft\events\RegisterElementCardAttributesEvent;
use craft\events\RegisterElementDefaultCardAttributesEvent;
use craft\events\RegisterElementDefaultTableAttributesEvent;
use craft\events\RegisterElementExportersEvent;
use craft\events\RegisterElementFieldLayoutsEvent;
use craft\events\RegisterElementSearchableAttributesEvent;
use craft\events\RegisterElementSortOptionsEvent;
use craft\events\RegisterElementSourcesEvent;
use craft\events\RegisterElementTableAttributesEvent;
use craft\events\RegisterPreviewTargetsEvent;
use craft\events\RenderElementEvent;
use CraftCms\Cms\Element\Events\DefineCacheTags;
use CraftCms\Cms\Element\Events\PrepQueryForTableAttribute;
use CraftCms\Cms\Element\Events\RegisterActions;
use CraftCms\Cms\Element\Events\RegisterCardAttributes;
use CraftCms\Cms\Element\Events\RegisterDefaultCardAttributes;
use CraftCms\Cms\Element\Events\RegisterDefaultTableAttributes;
use CraftCms\Cms\Element\Events\RegisterExporters;
use CraftCms\Cms\Element\Events\RegisterFieldLayouts;
use CraftCms\Cms\Element\Events\RegisterPreviewTargets;
use CraftCms\Cms\Element\Events\RegisterSearchableAttributes;
use CraftCms\Cms\Element\Events\RegisterSortOptions;
use CraftCms\Cms\Element\Events\RegisterSources;
use CraftCms\Cms\Element\Events\RegisterTableAttributes;
use CraftCms\Cms\Element\Events\Render;
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

    /**
     * @event RenderElementEvent The event that is triggered before an element is rendered.
     *
     * @see render()
     * @since 5.7.5
     * @deprecated 6.0.0 Use {@see Render} instead.
     */
    public const EVENT_RENDER = 'render';

    /**
     * @event DefineAttributeKeywordsEvent The event that is triggered when defining the search keywords for an element attribute.
     *
     * @see getSearchKeywords()
     * @since 3.5.0
     * @deprecated 6.0.0 Use {@see DefineKeywords} instead.
     */
    public const EVENT_DEFINE_KEYWORDS = 'defineKeywords';

    /**
     * @event RegisterElementSortOptionsEvent The event that is triggered when registering the sort options for the element type.
     *
     * @see sortOptions()
     * @since 3.0.0
     * @deprecated 6.0.0 Use {@see RegisterSortOptions} instead.
     */
    public const EVENT_REGISTER_SORT_OPTIONS = 'registerSortOptions';

    /**
     * @event RegisterElementTableAttributesEvent The event that is triggered when registering the table attributes for the element type.
     *
     * @see tableAttributes()
     * @since 3.0.0
     * @deprecated 6.0.0 Use {@see RegisterTableAttributes} instead.
     */
    public const EVENT_REGISTER_TABLE_ATTRIBUTES = 'registerTableAttributes';

    /**
     * @event RegisterElementDefaultTableAttributesEvent The event that is triggered when registering the default table attributes for the element type.
     *
     * @see defaultTableAttributes()
     * @since 3.0.0
     * @deprecated 6.0.0 Use {@see RegisterDefaultTableAttributes} instead.
     */
    public const EVENT_REGISTER_DEFAULT_TABLE_ATTRIBUTES = 'registerDefaultTableAttributes';

    /**
     * @event RegisterElementCardAttributesEvent The event that is triggered when registering the card attributes for the element type.
     *
     * @see cardAttributes()
     * @since 5.5.0
     * @deprecated 6.0.0 Use {@see RegisterCardAttributes} instead.
     */
    public const EVENT_REGISTER_CARD_ATTRIBUTES = 'registerCardAttributes';

    /**
     * @event RegisterElementDefaultCardAttributesEvent The event that is triggered when registering the default card attributes for the element type.
     *
     * @see defaultCardAttributes()
     * @since 5.5.0
     * @deprecated 6.0.0 Use {@see RegisterDefaultCardAttributes} instead.
     */
    public const EVENT_REGISTER_DEFAULT_CARD_ATTRIBUTES = 'registerDefaultCardAttributes';

    /**
     * @event RegisterElementSearchableAttributesEvent The event that is triggered when registering the searchable attributes for the element type.
     *
     * @see searchableAttributes()
     * @since 3.0.0
     * @deprecated 6.0.0 Use {@see RegisterSearchableAttributes} instead.
     */
    public const EVENT_REGISTER_SEARCHABLE_ATTRIBUTES = 'registerSearchableAttributes';

    /**
     * @event ElementIndexTableAttributeEvent The event that is triggered when preparing an element query for a table attribute.
     *
     * @see indexHtml()
     * @since 3.7.14
     * @deprecated 6.0.0 Use {@see PrepQueryForTableAttribute} instead.
     */
    public const EVENT_PREP_QUERY_FOR_TABLE_ATTRIBUTE = 'prepQueryForTableAttribute';

    /**
     * @event DefineEagerLoadingMapEvent The event that is triggered when defining an eager-loading map.
     *
     * @see eagerLoadingMap()
     * @since 3.1.0
     * @deprecated 6.0.0 Use {@see DefineEagerLoadingMap} instead.
     */
    public const EVENT_DEFINE_EAGER_LOADING_MAP = 'defineEagerLoadingMap';

    /**
     * @event SetEagerLoadedElementsEvent The event that is triggered when setting eager-loaded elements.
     *
     * @see setEagerLoadedElements()
     * @since 3.5.0
     * @deprecated 6.0.0 Use {@see SetEagerLoadedElements} instead.
     */
    public const EVENT_SET_EAGER_LOADED_ELEMENTS = 'setEagerLoadedElements';

    /**
     * @event ModelEvent The event that is triggered before the element is saved.
     *
     * You may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the element from getting saved.
     *
     * @see beforeSave()
     * @deprecated 6.0.0 Use {@see BeforeSave} instead.
     */
    public const EVENT_BEFORE_SAVE = 'beforeSave';

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

        Event::listen(function(Render $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!is_a($event->element, $class)) {
                    continue;
                }

                if (!YiiEvent::hasHandlers($class, self::EVENT_RENDER)) {
                    continue;
                }

                $yiiEvent = new RenderElementEvent([
                    'sender' => $event->element,
                    'templates' => $event->templates,
                    'variables' => $event->variables,
                ]);

                YiiEvent::trigger($class, self::EVENT_RENDER, $yiiEvent);

                if (isset($yiiEvent->output)) {
                    $event->output = $yiiEvent->output;
                }
                $event->templates = $yiiEvent->templates;
                $event->variables = $yiiEvent->variables;
            }
        });

        Event::listen(function(DefineKeywords $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!is_a($event->element, $class)) {
                    continue;
                }

                if (!YiiEvent::hasHandlers($class, self::EVENT_DEFINE_KEYWORDS)) {
                    continue;
                }

                $yiiEvent = new DefineAttributeKeywordsEvent([
                    'sender' => $event->element,
                    'attribute' => $event->attribute,
                    'keywords' => $event->keywords,
                ]);

                YiiEvent::trigger($class, self::EVENT_DEFINE_KEYWORDS, $yiiEvent);

                if ($yiiEvent->handled) {
                    $event->keywords = $yiiEvent->keywords;
                    $event->handled = true;
                }
            }
        });

        Event::listen(function(RegisterSortOptions $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if ($class !== $event->elementType && !is_subclass_of($event->elementType, $class)) {
                    continue;
                }

                if (!YiiEvent::hasHandlers($class, self::EVENT_REGISTER_SORT_OPTIONS)) {
                    continue;
                }

                $yiiEvent = new RegisterElementSortOptionsEvent([
                    'sortOptions' => $event->sortOptions,
                ]);

                YiiEvent::trigger($class, self::EVENT_REGISTER_SORT_OPTIONS, $yiiEvent);

                $event->sortOptions = $yiiEvent->sortOptions;
            }
        });

        Event::listen(function(RegisterTableAttributes $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if ($class !== $event->elementType && !is_subclass_of($event->elementType, $class)) {
                    continue;
                }

                if (!YiiEvent::hasHandlers($class, self::EVENT_REGISTER_TABLE_ATTRIBUTES)) {
                    continue;
                }

                $yiiEvent = new RegisterElementTableAttributesEvent([
                    'tableAttributes' => $event->tableAttributes,
                ]);

                YiiEvent::trigger($class, self::EVENT_REGISTER_TABLE_ATTRIBUTES, $yiiEvent);

                $event->tableAttributes = $yiiEvent->tableAttributes;
            }
        });

        Event::listen(function(RegisterDefaultTableAttributes $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if ($class !== $event->elementType && !is_subclass_of($event->elementType, $class)) {
                    continue;
                }

                if (!YiiEvent::hasHandlers($class, self::EVENT_REGISTER_DEFAULT_TABLE_ATTRIBUTES)) {
                    continue;
                }

                $yiiEvent = new RegisterElementDefaultTableAttributesEvent([
                    'source' => $event->source,
                    'tableAttributes' => $event->tableAttributes,
                ]);

                YiiEvent::trigger($class, self::EVENT_REGISTER_DEFAULT_TABLE_ATTRIBUTES, $yiiEvent);

                $event->tableAttributes = $yiiEvent->tableAttributes;
            }
        });

        Event::listen(function(RegisterCardAttributes $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if ($class !== $event->elementType && !is_subclass_of($event->elementType, $class)) {
                    continue;
                }

                if (!YiiEvent::hasHandlers($class, self::EVENT_REGISTER_CARD_ATTRIBUTES)) {
                    continue;
                }

                $yiiEvent = new RegisterElementCardAttributesEvent([
                    'cardAttributes' => $event->cardAttributes,
                    'fieldLayout' => $event->fieldLayout,
                ]);

                YiiEvent::trigger($class, self::EVENT_REGISTER_CARD_ATTRIBUTES, $yiiEvent);

                $event->cardAttributes = $yiiEvent->cardAttributes;
            }
        });

        Event::listen(function(RegisterDefaultCardAttributes $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if ($class !== $event->elementType && !is_subclass_of($event->elementType, $class)) {
                    continue;
                }

                if (!YiiEvent::hasHandlers($class, self::EVENT_REGISTER_DEFAULT_CARD_ATTRIBUTES)) {
                    continue;
                }

                $yiiEvent = new RegisterElementDefaultCardAttributesEvent([
                    'cardAttributes' => $event->cardAttributes,
                ]);

                YiiEvent::trigger($class, self::EVENT_REGISTER_DEFAULT_CARD_ATTRIBUTES, $yiiEvent);

                $event->cardAttributes = $yiiEvent->cardAttributes;
            }
        });

        Event::listen(function(RegisterSearchableAttributes $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if ($class !== $event->elementType && !is_subclass_of($event->elementType, $class)) {
                    continue;
                }

                if (!YiiEvent::hasHandlers($class, self::EVENT_REGISTER_SEARCHABLE_ATTRIBUTES)) {
                    continue;
                }

                $yiiEvent = new RegisterElementSearchableAttributesEvent([
                    'attributes' => $event->attributes,
                ]);

                YiiEvent::trigger($class, self::EVENT_REGISTER_SEARCHABLE_ATTRIBUTES, $yiiEvent);

                $event->attributes = $yiiEvent->attributes;
            }
        });

        Event::listen(function(PrepQueryForTableAttribute $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if ($class !== $event->elementType && !is_subclass_of($event->elementType, $class)) {
                    continue;
                }

                if (!YiiEvent::hasHandlers($class, self::EVENT_PREP_QUERY_FOR_TABLE_ATTRIBUTE)) {
                    continue;
                }

                $yiiEvent = new ElementIndexTableAttributeEvent([
                    'query' => $event->query,
                    'attribute' => $event->attribute,
                ]);

                YiiEvent::trigger($class, self::EVENT_PREP_QUERY_FOR_TABLE_ATTRIBUTE, $yiiEvent);

                if ($yiiEvent->handled) {
                    $event->handled = true;
                }
            }
        });

        Event::listen(function(DefineEagerLoadingMap $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if ($class !== $event->elementType && !is_subclass_of($event->elementType, $class)) {
                    continue;
                }

                if (!YiiEvent::hasHandlers($class, self::EVENT_DEFINE_EAGER_LOADING_MAP)) {
                    continue;
                }

                $yiiEvent = new DefineEagerLoadingMapEvent([
                    'sourceElements' => $event->sourceElements,
                    'handle' => $event->handle,
                ]);

                YiiEvent::trigger($class, self::EVENT_DEFINE_EAGER_LOADING_MAP, $yiiEvent);

                if ($yiiEvent->elementType !== null) {
                    $event->targetElementType = $yiiEvent->elementType;
                    $event->map = $yiiEvent->map;
                    $event->criteria = $yiiEvent->criteria;
                }
            }
        });

        Event::listen(function(SetEagerLoadedElements $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!is_a($event->element, $class)) {
                    continue;
                }

                if (!YiiEvent::hasHandlers($class, self::EVENT_SET_EAGER_LOADED_ELEMENTS)) {
                    continue;
                }

                $yiiEvent = new SetEagerLoadedElementsEvent([
                    'sender' => $event->element,
                    'handle' => $event->handle,
                    'elements' => $event->elements,
                    'plan' => $event->plan,
                ]);

                YiiEvent::trigger($class, self::EVENT_SET_EAGER_LOADED_ELEMENTS, $yiiEvent);

                if ($yiiEvent->handled) {
                    $event->handled = true;
                }
            }
        });

        Event::listen(function(BeforeSave $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!is_a($event->element, $class)) {
                    continue;
                }

                if (!YiiEvent::hasHandlers($class, self::EVENT_BEFORE_SAVE)) {
                    continue;
                }

                $yiiEvent = new ModelEvent([
                    'sender' => $event->element,
                    'isNew' => $event->isNew,
                ]);

                YiiEvent::trigger($class, self::EVENT_BEFORE_SAVE, $yiiEvent);

                if (!$yiiEvent->isValid) {
                    $event->isValid = false;
                }
            }
        });
    }
}
