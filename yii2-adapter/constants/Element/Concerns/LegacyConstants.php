<?php

declare(strict_types=1);
namespace CraftCms\Cms\Element\Concerns;

use craft\base\Element;
use craft\base\ElementEventConstants;
use craft\base\Event as YiiEvent;
use craft\elements\Address;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\ContentBlock;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\Tag;
use craft\elements\User;
use craft\events\DefineAltActionsEvent;
use craft\events\DefineAttributeHtmlEvent;
use craft\events\DefineAttributeKeywordsEvent;
use craft\events\DefineEagerLoadingMapEvent;
use craft\events\DefineHtmlEvent;
use craft\events\DefineMenuItemsEvent;
use craft\events\DefineMetadataEvent;
use craft\events\DefineUrlEvent;
use craft\events\DefineValueEvent;
use craft\events\ElementIndexTableAttributeEvent;
use craft\events\ElementStructureEvent;
use craft\events\ModelEvent;
use craft\events\RegisterElementActionsEvent;
use craft\events\RegisterElementCardAttributesEvent;
use craft\events\RegisterElementDefaultCardAttributesEvent;
use craft\events\RegisterElementDefaultTableAttributesEvent;
use craft\events\RegisterElementExportersEvent;
use craft\events\RegisterElementFieldLayoutsEvent;
use craft\events\RegisterElementHtmlAttributesEvent;
use craft\events\RegisterElementSearchableAttributesEvent;
use craft\events\RegisterElementSortOptionsEvent;
use craft\events\RegisterElementSourcesEvent;
use craft\events\RegisterElementTableAttributesEvent;
use craft\events\RegisterPreviewTargetsEvent;
use craft\events\RenderElementEvent;
use craft\events\SetEagerLoadedElementsEvent;
use craft\events\SetElementRouteEvent;
use CraftCms\Cms\Element\Events\ElementActionMenuItemsResolving;
use CraftCms\Cms\Element\Events\ElementActionsResolving;
use CraftCms\Cms\Element\Events\ElementAdditionalButtonsResolving;
use CraftCms\Cms\Element\Events\ElementAltActionsResolving;
use CraftCms\Cms\Element\Events\ElementAttributeHtmlResolving;
use CraftCms\Cms\Element\Events\ElementCacheTagsResolving;
use CraftCms\Cms\Element\Events\ElementCardAttributesResolving;
use CraftCms\Cms\Element\Events\ElementDefaultCardAttributesResolving;
use CraftCms\Cms\Element\Events\ElementDefaultTableAttributesResolving;
use CraftCms\Cms\Element\Events\ElementEagerLoadingMapResolving;
use CraftCms\Cms\Element\Events\ElementExportersResolving;
use CraftCms\Cms\Element\Events\ElementFieldLayoutsResolving;
use CraftCms\Cms\Element\Events\ElementHtmlAttributesResolving;
use CraftCms\Cms\Element\Events\ElementInlineAttributeInputHtmlResolving;
use CraftCms\Cms\Element\Events\ElementKeywordsResolving;
use CraftCms\Cms\Element\Events\ElementLifecycleDeleted;
use CraftCms\Cms\Element\Events\ElementLifecycleDeleting;
use CraftCms\Cms\Element\Events\ElementLifecyclePropagated;
use CraftCms\Cms\Element\Events\ElementLifecycleRestored;
use CraftCms\Cms\Element\Events\ElementLifecycleRestoring;
use CraftCms\Cms\Element\Events\ElementLifecycleSaved;
use CraftCms\Cms\Element\Events\ElementLifecycleSaving;
use CraftCms\Cms\Element\Events\ElementMetadataResolving;
use CraftCms\Cms\Element\Events\ElementMetaFieldsHtmlResolving;
use CraftCms\Cms\Element\Events\ElementMovedInStructure;
use CraftCms\Cms\Element\Events\ElementMovingInStructure;
use CraftCms\Cms\Element\Events\ElementPreviewTargetsResolving;
use CraftCms\Cms\Element\Events\ElementRendering;
use CraftCms\Cms\Element\Events\ElementSearchableAttributesResolving;
use CraftCms\Cms\Element\Events\ElementSidebarHtmlResolving;
use CraftCms\Cms\Element\Events\ElementSortOptionsResolving;
use CraftCms\Cms\Element\Events\ElementSourcesResolving;
use CraftCms\Cms\Element\Events\ElementTableAttributesResolving;
use CraftCms\Cms\Element\Events\ElementUrlResolved;
use CraftCms\Cms\Element\Events\ElementUrlResolving;
use CraftCms\Cms\Element\Events\QueryForTableAttributePreparing;
use CraftCms\Cms\Element\Events\SetEagerLoadedElements;
use CraftCms\Cms\Element\Events\SetRoute;
use CraftCms\Cms\Element\Validation\ElementRules;
use Illuminate\Support\Facades\Event;
use ReflectionClass;

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

    public static function registerEvents(): void
    {
        // Find all classes that extend Element
        $classes = get_declared_classes();
        $elementClasses = [
            Address::class,
            Asset::class,
            Category::class,
            ContentBlock::class,
            Entry::class,
            GlobalSet::class,
            Tag::class,
            User::class,
        ];

        foreach ($classes as $class) {
            if (is_subclass_of($class, Element::class)) {
                $elementClasses[] = $class;
            }
        }

        Event::listen(function(ElementCacheTagsResolving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_DEFINE_CACHE_TAGS)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new DefineValueEvent([
                    'sender' => $event->element,
                    'value' => $event->tags,
                ]);

                self::triggerEvent($class, self::EVENT_DEFINE_CACHE_TAGS, $yiiEvent);

                $event->tags = $yiiEvent->value;
            }
        });

        Event::listen(function(ElementSourcesResolving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_REGISTER_SOURCES)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->elementType)) {
                    continue;
                }

                $yiiEvent = new RegisterElementSourcesEvent([
                    'context' => $event->context,
                    'sources' => $event->sources,
                ]);

                self::triggerEvent($class, self::EVENT_REGISTER_SOURCES, $yiiEvent);

                $event->sources = $yiiEvent->sources;
            }
        });

        Event::listen(function(ElementFieldLayoutsResolving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_REGISTER_FIELD_LAYOUTS)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->elementType)) {
                    continue;
                }

                $yiiEvent = new RegisterElementFieldLayoutsEvent([
                    'source' => $event->source,
                    'fieldLayouts' => $event->fieldLayouts,
                ]);

                self::triggerEvent($class, self::EVENT_REGISTER_FIELD_LAYOUTS, $yiiEvent);

                $event->fieldLayouts = $yiiEvent->fieldLayouts;
            }
        });

        Event::listen(function(ElementPreviewTargetsResolving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_REGISTER_PREVIEW_TARGETS)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new RegisterPreviewTargetsEvent([
                    'sender' => $event->element,
                    'previewTargets' => $event->previewTargets,
                ]);

                self::triggerEvent($class, self::EVENT_REGISTER_PREVIEW_TARGETS, $yiiEvent);

                $event->previewTargets = $yiiEvent->previewTargets;
            }
        });

        Event::listen(function(ElementActionsResolving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_REGISTER_ACTIONS)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->elementType)) {
                    continue;
                }

                $yiiEvent = new RegisterElementActionsEvent([
                    'source' => $event->source,
                    'actions' => $event->actions,
                ]);

                self::triggerEvent($class, self::EVENT_REGISTER_ACTIONS, $yiiEvent);

                $event->actions = $yiiEvent->actions;
            }
        });

        Event::listen(function(ElementExportersResolving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_REGISTER_EXPORTERS)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->elementType)) {
                    continue;
                }

                $yiiEvent = new RegisterElementExportersEvent([
                    'source' => $event->source,
                    'exporters' => $event->exporters,
                ]);

                self::triggerEvent($class, self::EVENT_REGISTER_EXPORTERS, $yiiEvent);

                $event->exporters = $yiiEvent->exporters;
            }
        });

        Event::listen(function(ElementRendering $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_RENDER)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new RenderElementEvent([
                    'sender' => $event->element,
                    'templates' => $event->templates,
                    'variables' => $event->variables,
                ]);

                self::triggerEvent($class, self::EVENT_RENDER, $yiiEvent);

                if (isset($yiiEvent->output)) {
                    $event->output = $yiiEvent->output;
                }
                $event->templates = $yiiEvent->templates;
                $event->variables = $yiiEvent->variables;
            }
        });

        Event::listen(function(ElementKeywordsResolving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_DEFINE_KEYWORDS)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new DefineAttributeKeywordsEvent([
                    'sender' => $event->element,
                    'attribute' => $event->attribute,
                    'keywords' => $event->keywords,
                ]);

                self::triggerEvent($class, self::EVENT_DEFINE_KEYWORDS, $yiiEvent);

                if ($yiiEvent->handled) {
                    $event->keywords = $yiiEvent->keywords;
                    $event->handled = true;
                }
            }
        });

        Event::listen(function(ElementSortOptionsResolving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_REGISTER_SORT_OPTIONS)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->elementType)) {
                    continue;
                }

                $yiiEvent = new RegisterElementSortOptionsEvent([
                    'sortOptions' => $event->sortOptions,
                ]);

                self::triggerEvent($class, self::EVENT_REGISTER_SORT_OPTIONS, $yiiEvent);

                $event->sortOptions = $yiiEvent->sortOptions;
            }
        });

        Event::listen(function(ElementTableAttributesResolving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_REGISTER_TABLE_ATTRIBUTES)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->elementType)) {
                    continue;
                }

                $yiiEvent = new RegisterElementTableAttributesEvent([
                    'tableAttributes' => $event->tableAttributes,
                ]);

                self::triggerEvent($class, self::EVENT_REGISTER_TABLE_ATTRIBUTES, $yiiEvent);

                $event->tableAttributes = $yiiEvent->tableAttributes;
            }
        });

        Event::listen(function(ElementDefaultTableAttributesResolving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_REGISTER_DEFAULT_TABLE_ATTRIBUTES)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->elementType)) {
                    continue;
                }

                $yiiEvent = new RegisterElementDefaultTableAttributesEvent([
                    'source' => $event->source,
                    'tableAttributes' => $event->tableAttributes,
                ]);

                self::triggerEvent($class, self::EVENT_REGISTER_DEFAULT_TABLE_ATTRIBUTES, $yiiEvent);

                $event->tableAttributes = $yiiEvent->tableAttributes;
            }
        });

        Event::listen(function(ElementCardAttributesResolving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_REGISTER_CARD_ATTRIBUTES)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->elementType)) {
                    continue;
                }

                $yiiEvent = new RegisterElementCardAttributesEvent([
                    'cardAttributes' => $event->cardAttributes,
                    'fieldLayout' => $event->fieldLayout,
                ]);

                self::triggerEvent($class, self::EVENT_REGISTER_CARD_ATTRIBUTES, $yiiEvent);

                $event->cardAttributes = $yiiEvent->cardAttributes;
            }
        });

        Event::listen(function(ElementDefaultCardAttributesResolving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_REGISTER_DEFAULT_CARD_ATTRIBUTES)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->elementType)) {
                    continue;
                }

                $yiiEvent = new RegisterElementDefaultCardAttributesEvent([
                    'cardAttributes' => $event->cardAttributes,
                ]);

                self::triggerEvent($class, self::EVENT_REGISTER_DEFAULT_CARD_ATTRIBUTES, $yiiEvent);

                $event->cardAttributes = $yiiEvent->cardAttributes;
            }
        });

        Event::listen(function(ElementSearchableAttributesResolving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_REGISTER_SEARCHABLE_ATTRIBUTES)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->elementType)) {
                    continue;
                }

                $yiiEvent = new RegisterElementSearchableAttributesEvent([
                    'attributes' => $event->attributes,
                ]);

                self::triggerEvent($class, self::EVENT_REGISTER_SEARCHABLE_ATTRIBUTES, $yiiEvent);

                $event->attributes = $yiiEvent->attributes;
            }
        });

        Event::listen(function(QueryForTableAttributePreparing $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_PREP_QUERY_FOR_TABLE_ATTRIBUTE)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->elementType)) {
                    continue;
                }

                $yiiEvent = new ElementIndexTableAttributeEvent([
                    'query' => $event->query,
                    'attribute' => $event->attribute,
                ]);

                self::triggerEvent($class, self::EVENT_PREP_QUERY_FOR_TABLE_ATTRIBUTE, $yiiEvent);

                if ($yiiEvent->handled) {
                    $event->handled = true;
                }
            }
        });

        Event::listen(function(ElementEagerLoadingMapResolving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_DEFINE_EAGER_LOADING_MAP)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->elementType)) {
                    continue;
                }

                $yiiEvent = new DefineEagerLoadingMapEvent([
                    'sourceElements' => $event->sourceElements,
                    'handle' => $event->handle,
                ]);

                self::triggerEvent($class, self::EVENT_DEFINE_EAGER_LOADING_MAP, $yiiEvent);

                if ($yiiEvent->elementType !== null) {
                    $event->targetElementType = $yiiEvent->elementType;
                    $event->map = $yiiEvent->map;
                    $event->criteria = $yiiEvent->criteria;
                }
            }
        });

        Event::listen(function(SetEagerLoadedElements $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_SET_EAGER_LOADED_ELEMENTS)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new SetEagerLoadedElementsEvent([
                    'sender' => $event->element,
                    'handle' => $event->handle,
                    'elements' => $event->elements,
                    'plan' => $event->plan,
                ]);

                self::triggerEvent($class, self::EVENT_SET_EAGER_LOADED_ELEMENTS, $yiiEvent);

                if ($yiiEvent->handled) {
                    $event->handled = true;
                }
            }
        });

        Event::listen(function(ElementLifecycleSaving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_BEFORE_SAVE)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new ModelEvent([
                    'sender' => $event->element,
                    'isNew' => $event->isNew,
                ]);

                self::triggerEvent($class, self::EVENT_BEFORE_SAVE, $yiiEvent);

                if (!$yiiEvent->isValid) {
                    $event->isValid = false;
                }
            }
        });

        Event::listen(function(ElementLifecycleSaved $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_AFTER_SAVE)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new ModelEvent([
                    'sender' => $event->element,
                    'isNew' => $event->isNew,
                ]);

                self::triggerEvent($class, self::EVENT_AFTER_SAVE, $yiiEvent);
            }
        });

        Event::listen(function(ElementLifecyclePropagated $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_AFTER_PROPAGATE)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new ModelEvent([
                    'sender' => $event->element,
                    'isNew' => $event->isNew,
                ]);

                self::triggerEvent($class, self::EVENT_AFTER_PROPAGATE, $yiiEvent);
            }
        });

        Event::listen(function(ElementLifecycleDeleting $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_BEFORE_DELETE)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new ModelEvent([
                    'sender' => $event->element,
                ]);

                self::triggerEvent($class, self::EVENT_BEFORE_DELETE, $yiiEvent);

                if (!$yiiEvent->isValid) {
                    $event->isValid = false;
                }
            }
        });

        Event::listen(function(ElementLifecycleDeleted $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_AFTER_DELETE)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new ModelEvent([
                    'sender' => $event->element,
                ]);

                self::triggerEvent($class, self::EVENT_AFTER_DELETE, $yiiEvent);
            }
        });

        Event::listen(function(ElementLifecycleRestoring $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_BEFORE_RESTORE)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new ModelEvent([
                    'sender' => $event->element,
                ]);

                self::triggerEvent($class, self::EVENT_BEFORE_RESTORE, $yiiEvent);

                if (!$yiiEvent->isValid) {
                    $event->isValid = false;
                }
            }
        });

        Event::listen(function(ElementLifecycleRestored $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_AFTER_RESTORE)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new ModelEvent([
                    'sender' => $event->element,
                ]);

                self::triggerEvent($class, self::EVENT_AFTER_RESTORE, $yiiEvent);
            }
        });

        Event::listen(function(ElementAdditionalButtonsResolving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_DEFINE_ADDITIONAL_BUTTONS)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new DefineHtmlEvent([
                    'sender' => $event->element,
                    'html' => $event->html,
                ]);

                self::triggerEvent($class, self::EVENT_DEFINE_ADDITIONAL_BUTTONS, $yiiEvent);

                $event->html = $yiiEvent->html;
            }
        });

        Event::listen(function(ElementAltActionsResolving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_DEFINE_ALT_ACTIONS)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new DefineAltActionsEvent([
                    'sender' => $event->element,
                    'altActions' => $event->altActions,
                ]);

                self::triggerEvent($class, self::EVENT_DEFINE_ALT_ACTIONS, $yiiEvent);

                $event->altActions = $yiiEvent->altActions;
            }
        });

        Event::listen(function(ElementActionMenuItemsResolving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_DEFINE_ACTION_MENU_ITEMS)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new DefineMenuItemsEvent([
                    'sender' => $event->element,
                    'items' => $event->items,
                ]);

                self::triggerEvent($class, self::EVENT_DEFINE_ACTION_MENU_ITEMS, $yiiEvent);

                $event->items = $yiiEvent->items;
            }
        });

        Event::listen(function(ElementSidebarHtmlResolving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_DEFINE_SIDEBAR_HTML)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new DefineHtmlEvent([
                    'sender' => $event->element,
                    'html' => $event->html,
                ]);

                self::triggerEvent($class, self::EVENT_DEFINE_SIDEBAR_HTML, $yiiEvent);

                $event->html = $yiiEvent->html;
            }
        });

        Event::listen(function(ElementMetaFieldsHtmlResolving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_DEFINE_META_FIELDS_HTML)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new DefineHtmlEvent([
                    'sender' => $event->element,
                    'static' => $event->static,
                    'html' => $event->html,
                ]);

                self::triggerEvent($class, self::EVENT_DEFINE_META_FIELDS_HTML, $yiiEvent);

                $event->html = $yiiEvent->html;
            }
        });

        Event::listen(function(ElementMetadataResolving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_DEFINE_METADATA)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new DefineMetadataEvent([
                    'sender' => $event->element,
                    'metadata' => $event->metadata,
                ]);

                self::triggerEvent($class, self::EVENT_DEFINE_METADATA, $yiiEvent);

                $event->metadata = $yiiEvent->metadata;
            }
        });

        Event::listen(function(ElementHtmlAttributesResolving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_REGISTER_HTML_ATTRIBUTES)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new RegisterElementHtmlAttributesEvent([
                    'sender' => $event->element,
                    'htmlAttributes' => $event->htmlAttributes,
                ]);

                self::triggerEvent($class, self::EVENT_REGISTER_HTML_ATTRIBUTES, $yiiEvent);

                $event->htmlAttributes = $yiiEvent->htmlAttributes;
            }
        });

        Event::listen(function(ElementAttributeHtmlResolving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_DEFINE_ATTRIBUTE_HTML)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new DefineAttributeHtmlEvent([
                    'sender' => $event->element,
                    'attribute' => $event->attribute,
                ]);

                self::triggerEvent($class, self::EVENT_DEFINE_ATTRIBUTE_HTML, $yiiEvent);

                if (isset($yiiEvent->html)) {
                    $event->html = $yiiEvent->html;
                }
            }
        });

        Event::listen(function(ElementInlineAttributeInputHtmlResolving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_DEFINE_INLINE_ATTRIBUTE_INPUT_HTML)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new DefineAttributeHtmlEvent([
                    'sender' => $event->element,
                    'attribute' => $event->attribute,
                ]);

                self::triggerEvent($class, self::EVENT_DEFINE_INLINE_ATTRIBUTE_INPUT_HTML, $yiiEvent);

                if (isset($yiiEvent->html)) {
                    $event->html = $yiiEvent->html;
                }
            }
        });

        Event::listen(function(SetRoute $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_SET_ROUTE)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new SetElementRouteEvent([
                    'sender' => $event->element,
                    'route' => $event->route,
                ]);

                self::triggerEvent($class, self::EVENT_SET_ROUTE, $yiiEvent);

                $event->route = $yiiEvent->route;
                if ($yiiEvent->handled) {
                    $event->handled = true;
                }
            }
        });

        Event::listen(function(ElementUrlResolving $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_BEFORE_DEFINE_URL)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new DefineUrlEvent([
                    'sender' => $event->element,
                    'url' => $event->url,
                ]);

                self::triggerEvent($class, self::EVENT_BEFORE_DEFINE_URL, $yiiEvent);

                $event->url = $yiiEvent->url;
                if ($yiiEvent->handled) {
                    $event->handled = true;
                }
            }
        });

        Event::listen(function(ElementUrlResolved $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_DEFINE_URL)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new DefineUrlEvent([
                    'sender' => $event->element,
                    'url' => $event->url,
                ]);

                self::triggerEvent($class, self::EVENT_DEFINE_URL, $yiiEvent);

                $event->url = $yiiEvent->url;
                if ($yiiEvent->handled) {
                    $event->handled = true;
                }
            }
        });

        Event::listen(function(ElementMovingInStructure $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_BEFORE_MOVE_IN_STRUCTURE)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new ElementStructureEvent([
                    'sender' => $event->element,
                    'structureId' => $event->structureId,
                ]);

                self::triggerEvent($class, self::EVENT_BEFORE_MOVE_IN_STRUCTURE, $yiiEvent);

                if (!$yiiEvent->isValid) {
                    $event->isValid = false;
                }
            }
        });

        Event::listen(function(ElementMovedInStructure $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!self::hasEventHandlers($class, self::EVENT_AFTER_MOVE_IN_STRUCTURE)) {
                    continue;
                }

                if (!self::matchesElementClass($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new ElementStructureEvent([
                    'sender' => $event->element,
                    'structureId' => $event->structureId,
                ]);

                self::triggerEvent($class, self::EVENT_AFTER_MOVE_IN_STRUCTURE, $yiiEvent);
            }
        });
    }

    private static function hasEventHandlers(string $class, string $name): bool
    {
        foreach (self::eventTargetClasses($class) as $targetClass) {
            if (YiiEvent::hasHandlers($targetClass, $name)) {
                return true;
            }
        }

        return false;
    }

    private static function triggerEvent(string $class, string $name, \yii\base\Event $event): void
    {
        foreach (self::eventTargetClasses($class) as $targetClass) {
            if (!YiiEvent::hasHandlers($targetClass, $name)) {
                continue;
            }

            YiiEvent::trigger($targetClass, $name, $event);

            if ($event->handled) {
                return;
            }
        }
    }

    /**
     * @return list<class-string>
     */
    private static function eventTargetClasses(string $class): array
    {
        $classes = [$class];
        $resolvedClass = self::resolvedClass($class);

        if ($resolvedClass !== $class) {
            $classes[] = $resolvedClass;
        }

        if (
            $class !== Element::class &&
            !is_subclass_of($class, Element::class) &&
            is_a($resolvedClass, \CraftCms\Cms\Element\Element::class, true)
        ) {
            $classes[] = Element::class;
        }

        return array_values(array_unique($classes));
    }

    private static function matchesElementClass(string $class, string $elementClass): bool
    {
        $class = self::resolvedClass($class);
        $elementClass = self::resolvedClass($elementClass);

        return $class === $elementClass ||
            is_subclass_of($class, $elementClass) ||
            is_subclass_of($elementClass, $class);
    }

    private static function resolvedClass(string $class): string
    {
        if (!class_exists($class) && !interface_exists($class)) {
            return $class;
        }

        return new ReflectionClass($class)->getName();
    }
}
