<?php

declare(strict_types=1);
namespace CraftCms\Yii2Adapter\Element\Concerns;

use craft\base\Element;
use craft\base\ElementEventConstants;
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
use CraftCms\Cms\Element\Events\ElementActionMenuDescriptorsResolving;
use CraftCms\Cms\Element\Events\ElementActionMenuItemsResolving;
use CraftCms\Cms\Element\Events\ElementActionsResolving;
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
use CraftCms\Yii2Adapter\Element\LegacyActionMenuItems;
use Illuminate\Support\Facades\Event;
use ReflectionClass;
use ReflectionProperty;
use yii\base\Event as YiiBaseEvent;
use yii\helpers\StringHelper;

/**
 * @internal
 * @deprecated 6.0.0
 * @phpstan-ignore trait.unused
 */
trait LegacyConstants
{
    use ElementEventConstants;

    public const string EVENT_DEFINE_BEHAVIORS = 'defineBehaviors';

    public const string SCENARIO_DEFAULT = ElementRules::SCENARIO_DEFAULT;

    public const string SCENARIO_ESSENTIALS = ElementRules::SCENARIO_ESSENTIALS;

    public const string SCENARIO_LIVE = ElementRules::SCENARIO_LIVE;

    public static function registerEvents(): void
    {
        Event::listen(function(ElementCacheTagsResolving $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_DEFINE_CACHE_TAGS)) {
                return;
            }

            $yiiEvent = new DefineValueEvent([
                'sender' => $event->element,
                'value' => $event->tags,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_DEFINE_CACHE_TAGS, $yiiEvent);

            $event->tags = $yiiEvent->value;
        });

        Event::listen(function(ElementSourcesResolving $event) {
            if (!self::hasEventHandlers($event->elementType, Element::EVENT_REGISTER_SOURCES)) {
                return;
            }

            $yiiEvent = new RegisterElementSourcesEvent([
                'context' => $event->context,
                'sources' => $event->sources,
            ]);

            self::triggerEvent($event->elementType, Element::EVENT_REGISTER_SOURCES, $yiiEvent);

            $event->sources = $yiiEvent->sources;
        });

        Event::listen(function(ElementFieldLayoutsResolving $event) {
            if (!self::hasEventHandlers($event->elementType, Element::EVENT_REGISTER_FIELD_LAYOUTS)) {
                return;
            }

            $yiiEvent = new RegisterElementFieldLayoutsEvent([
                'source' => $event->source,
                'fieldLayouts' => $event->fieldLayouts,
            ]);

            self::triggerEvent($event->elementType, Element::EVENT_REGISTER_FIELD_LAYOUTS, $yiiEvent);

            $event->fieldLayouts = $yiiEvent->fieldLayouts;
        });

        Event::listen(function(ElementPreviewTargetsResolving $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_REGISTER_PREVIEW_TARGETS)) {
                return;
            }

            $yiiEvent = new RegisterPreviewTargetsEvent([
                'sender' => $event->element,
                'previewTargets' => $event->previewTargets,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_REGISTER_PREVIEW_TARGETS, $yiiEvent);

            $event->previewTargets = $yiiEvent->previewTargets;
        });

        Event::listen(function(ElementActionsResolving $event) {
            if (!self::hasEventHandlers($event->elementType, Element::EVENT_REGISTER_ACTIONS)) {
                return;
            }

            $yiiEvent = new RegisterElementActionsEvent([
                'source' => $event->source,
                'actions' => $event->actions,
            ]);

            self::triggerEvent($event->elementType, Element::EVENT_REGISTER_ACTIONS, $yiiEvent);

            $event->actions = $yiiEvent->actions;
        });

        Event::listen(function(ElementExportersResolving $event) {
            if (!self::hasEventHandlers($event->elementType, Element::EVENT_REGISTER_EXPORTERS)) {
                return;
            }

            $yiiEvent = new RegisterElementExportersEvent([
                'source' => $event->source,
                'exporters' => $event->exporters,
            ]);

            self::triggerEvent($event->elementType, Element::EVENT_REGISTER_EXPORTERS, $yiiEvent);

            $event->exporters = $yiiEvent->exporters;
        });

        Event::listen(function(ElementRendering $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_RENDER)) {
                return;
            }

            $yiiEvent = new RenderElementEvent([
                'sender' => $event->element,
                'templates' => $event->templates,
                'variables' => $event->variables,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_RENDER, $yiiEvent);

            if (isset($yiiEvent->output)) {
                $event->output = $yiiEvent->output;
            }
            $event->templates = $yiiEvent->templates;
            $event->variables = $yiiEvent->variables;
        });

        Event::listen(function(ElementKeywordsResolving $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_DEFINE_KEYWORDS)) {
                return;
            }

            $yiiEvent = new DefineAttributeKeywordsEvent([
                'sender' => $event->element,
                'attribute' => $event->attribute,
                'keywords' => $event->keywords,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_DEFINE_KEYWORDS, $yiiEvent);

            if ($yiiEvent->handled) {
                $event->keywords = $yiiEvent->keywords;
                $event->handled = true;
            }
        });

        Event::listen(function(ElementSortOptionsResolving $event) {
            if (!self::hasEventHandlers($event->elementType, Element::EVENT_REGISTER_SORT_OPTIONS)) {
                return;
            }

            $yiiEvent = new RegisterElementSortOptionsEvent([
                'sortOptions' => $event->sortOptions,
            ]);

            self::triggerEvent($event->elementType, Element::EVENT_REGISTER_SORT_OPTIONS, $yiiEvent);

            $event->sortOptions = $yiiEvent->sortOptions;
        });

        Event::listen(function(ElementTableAttributesResolving $event) {
            if (!self::hasEventHandlers($event->elementType, Element::EVENT_REGISTER_TABLE_ATTRIBUTES)) {
                return;
            }

            $yiiEvent = new RegisterElementTableAttributesEvent([
                'tableAttributes' => $event->tableAttributes,
            ]);

            self::triggerEvent($event->elementType, Element::EVENT_REGISTER_TABLE_ATTRIBUTES, $yiiEvent);

            $event->tableAttributes = $yiiEvent->tableAttributes;
        });

        Event::listen(function(ElementDefaultTableAttributesResolving $event) {
            if (!self::hasEventHandlers($event->elementType, Element::EVENT_REGISTER_DEFAULT_TABLE_ATTRIBUTES)) {
                return;
            }

            $yiiEvent = new RegisterElementDefaultTableAttributesEvent([
                'source' => $event->source,
                'tableAttributes' => $event->tableAttributes,
            ]);

            self::triggerEvent($event->elementType, Element::EVENT_REGISTER_DEFAULT_TABLE_ATTRIBUTES, $yiiEvent);

            $event->tableAttributes = $yiiEvent->tableAttributes;
        });

        Event::listen(function(ElementCardAttributesResolving $event) {
            if (!self::hasEventHandlers($event->elementType, Element::EVENT_REGISTER_CARD_ATTRIBUTES)) {
                return;
            }

            $yiiEvent = new RegisterElementCardAttributesEvent([
                'cardAttributes' => $event->cardAttributes,
                'fieldLayout' => $event->fieldLayout,
            ]);

            self::triggerEvent($event->elementType, Element::EVENT_REGISTER_CARD_ATTRIBUTES, $yiiEvent);

            $event->cardAttributes = $yiiEvent->cardAttributes;
        });

        Event::listen(function(ElementDefaultCardAttributesResolving $event) {
            if (!self::hasEventHandlers($event->elementType, Element::EVENT_REGISTER_DEFAULT_CARD_ATTRIBUTES)) {
                return;
            }

            $yiiEvent = new RegisterElementDefaultCardAttributesEvent([
                'cardAttributes' => $event->cardAttributes,
            ]);

            self::triggerEvent($event->elementType, Element::EVENT_REGISTER_DEFAULT_CARD_ATTRIBUTES, $yiiEvent);

            $event->cardAttributes = $yiiEvent->cardAttributes;
        });

        Event::listen(function(ElementSearchableAttributesResolving $event) {
            if (!self::hasEventHandlers($event->elementType, Element::EVENT_REGISTER_SEARCHABLE_ATTRIBUTES)) {
                return;
            }

            $yiiEvent = new RegisterElementSearchableAttributesEvent([
                'attributes' => $event->attributes,
            ]);

            self::triggerEvent($event->elementType, Element::EVENT_REGISTER_SEARCHABLE_ATTRIBUTES, $yiiEvent);

            $event->attributes = $yiiEvent->attributes;
        });

        Event::listen(function(QueryForTableAttributePreparing $event) {
            if (!self::hasEventHandlers($event->elementType, Element::EVENT_PREP_QUERY_FOR_TABLE_ATTRIBUTE)) {
                return;
            }

            $yiiEvent = new ElementIndexTableAttributeEvent([
                'query' => $event->query,
                'attribute' => $event->attribute,
            ]);

            self::triggerEvent($event->elementType, Element::EVENT_PREP_QUERY_FOR_TABLE_ATTRIBUTE, $yiiEvent);

            if ($yiiEvent->handled) {
                $event->handled = true;
            }
        });

        Event::listen(function(ElementEagerLoadingMapResolving $event) {
            if (!self::hasEventHandlers($event->elementType, Element::EVENT_DEFINE_EAGER_LOADING_MAP)) {
                return;
            }

            $yiiEvent = new DefineEagerLoadingMapEvent([
                'sourceElements' => $event->sourceElements,
                'handle' => $event->handle,
            ]);

            self::triggerEvent($event->elementType, Element::EVENT_DEFINE_EAGER_LOADING_MAP, $yiiEvent);

            if ($yiiEvent->elementType !== null) {
                $event->targetElementType = $yiiEvent->elementType;
                $event->map = $yiiEvent->map;
                $event->criteria = $yiiEvent->criteria;
            }
        });

        Event::listen(function(SetEagerLoadedElements $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_SET_EAGER_LOADED_ELEMENTS)) {
                return;
            }

            $yiiEvent = new SetEagerLoadedElementsEvent([
                'sender' => $event->element,
                'handle' => $event->handle,
                'elements' => $event->elements,
                'plan' => $event->plan,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_SET_EAGER_LOADED_ELEMENTS, $yiiEvent);

            if ($yiiEvent->handled) {
                $event->handled = true;
            }
        });

        Event::listen(function(ElementLifecycleSaving $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_BEFORE_SAVE)) {
                return;
            }

            $yiiEvent = new ModelEvent([
                'sender' => $event->element,
                'isNew' => $event->isNew,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_BEFORE_SAVE, $yiiEvent);

            if (!$yiiEvent->isValid) {
                $event->isValid = false;
            }
        });

        Event::listen(function(ElementLifecycleSaved $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_AFTER_SAVE)) {
                return;
            }

            $yiiEvent = new ModelEvent([
                'sender' => $event->element,
                'isNew' => $event->isNew,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_AFTER_SAVE, $yiiEvent);
        });

        Event::listen(function(ElementLifecyclePropagated $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_AFTER_PROPAGATE)) {
                return;
            }

            $yiiEvent = new ModelEvent([
                'sender' => $event->element,
                'isNew' => $event->isNew,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_AFTER_PROPAGATE, $yiiEvent);
        });

        Event::listen(function(ElementLifecycleDeleting $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_BEFORE_DELETE)) {
                return;
            }

            $yiiEvent = new ModelEvent([
                'sender' => $event->element,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_BEFORE_DELETE, $yiiEvent);

            if (!$yiiEvent->isValid) {
                $event->isValid = false;
            }
        });

        Event::listen(function(ElementLifecycleDeleted $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_AFTER_DELETE)) {
                return;
            }

            $yiiEvent = new ModelEvent([
                'sender' => $event->element,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_AFTER_DELETE, $yiiEvent);
        });

        Event::listen(function(ElementLifecycleRestoring $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_BEFORE_RESTORE)) {
                return;
            }

            $yiiEvent = new ModelEvent([
                'sender' => $event->element,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_BEFORE_RESTORE, $yiiEvent);

            if (!$yiiEvent->isValid) {
                $event->isValid = false;
            }
        });

        Event::listen(function(ElementLifecycleRestored $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_AFTER_RESTORE)) {
                return;
            }

            $yiiEvent = new ModelEvent([
                'sender' => $event->element,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_AFTER_RESTORE, $yiiEvent);
        });

        Event::listen(function(ElementAltActionsResolving $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_DEFINE_ALT_ACTIONS)) {
                return;
            }

            $yiiEvent = new DefineAltActionsEvent([
                'sender' => $event->element,
                'altActions' => $event->altActions,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_DEFINE_ALT_ACTIONS, $yiiEvent);

            $event->altActions = $yiiEvent->altActions;
        });

        Event::listen(function(ElementActionMenuItemsResolving $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_DEFINE_ACTION_MENU_ITEMS)) {
                return;
            }

            $yiiEvent = new DefineMenuItemsEvent([
                'sender' => $event->element,
                'items' => $event->items,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_DEFINE_ACTION_MENU_ITEMS, $yiiEvent);

            $event->items = $yiiEvent->items;
        });

        // The Inertia editor and element chips read descriptors, so legacy items are converted for them.
        Event::listen(function(ElementActionMenuDescriptorsResolving $event) {
            $legacy = app(LegacyActionMenuItems::class);
            $items = [];

            if (self::hasEventHandlers($event->element::class, Element::EVENT_DEFINE_ACTION_MENU_ITEMS)) {
                // Handlers only see each other's items; the core ones are already descriptors.
                $yiiEvent = new DefineMenuItemsEvent([
                    'sender' => $event->element,
                    'items' => $items,
                ]);

                $legacy->withoutJs(fn() => self::triggerEvent($event->element::class, Element::EVENT_DEFINE_ACTION_MENU_ITEMS, $yiiEvent));

                $items = $yiiEvent->items;
            }

            $legacy->addEventItems($event, $items);
            $legacy->addOverrideItems($event);
        });

        Event::listen(function(ElementSidebarHtmlResolving $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_DEFINE_SIDEBAR_HTML)) {
                return;
            }

            $yiiEvent = new DefineHtmlEvent([
                'sender' => $event->element,
                'html' => $event->html,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_DEFINE_SIDEBAR_HTML, $yiiEvent);

            $event->html = $yiiEvent->html;
        });

        Event::listen(function(ElementMetaFieldsHtmlResolving $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_DEFINE_META_FIELDS_HTML)) {
                return;
            }

            $yiiEvent = new DefineHtmlEvent([
                'sender' => $event->element,
                'static' => $event->static,
                'html' => $event->html,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_DEFINE_META_FIELDS_HTML, $yiiEvent);

            $event->html = $yiiEvent->html;
        });

        Event::listen(function(ElementMetadataResolving $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_DEFINE_METADATA)) {
                return;
            }

            $yiiEvent = new DefineMetadataEvent([
                'sender' => $event->element,
                'metadata' => $event->metadata,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_DEFINE_METADATA, $yiiEvent);

            $event->metadata = $yiiEvent->metadata;
        });

        Event::listen(function(ElementHtmlAttributesResolving $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_REGISTER_HTML_ATTRIBUTES)) {
                return;
            }

            $yiiEvent = new RegisterElementHtmlAttributesEvent([
                'sender' => $event->element,
                'htmlAttributes' => $event->htmlAttributes,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_REGISTER_HTML_ATTRIBUTES, $yiiEvent);

            $event->htmlAttributes = $yiiEvent->htmlAttributes;
        });

        Event::listen(function(ElementAttributeHtmlResolving $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_DEFINE_ATTRIBUTE_HTML)) {
                return;
            }

            $yiiEvent = new DefineAttributeHtmlEvent([
                'sender' => $event->element,
                'attribute' => $event->attribute,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_DEFINE_ATTRIBUTE_HTML, $yiiEvent);

            if (isset($yiiEvent->html)) {
                $event->html = $yiiEvent->html;
            }
        });

        Event::listen(function(ElementInlineAttributeInputHtmlResolving $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_DEFINE_INLINE_ATTRIBUTE_INPUT_HTML)) {
                return;
            }

            $yiiEvent = new DefineAttributeHtmlEvent([
                'sender' => $event->element,
                'attribute' => $event->attribute,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_DEFINE_INLINE_ATTRIBUTE_INPUT_HTML, $yiiEvent);

            if (isset($yiiEvent->html)) {
                $event->html = $yiiEvent->html;
            }
        });

        Event::listen(function(SetRoute $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_SET_ROUTE)) {
                return;
            }

            $yiiEvent = new SetElementRouteEvent([
                'sender' => $event->element,
                'route' => $event->route,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_SET_ROUTE, $yiiEvent);

            $event->route = $yiiEvent->route;
            if ($yiiEvent->handled) {
                $event->handled = true;
            }
        });

        Event::listen(function(ElementUrlResolving $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_BEFORE_DEFINE_URL)) {
                return;
            }

            $yiiEvent = new DefineUrlEvent([
                'sender' => $event->element,
                'url' => $event->url,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_BEFORE_DEFINE_URL, $yiiEvent);

            $event->url = $yiiEvent->url;
            if ($yiiEvent->handled) {
                $event->handled = true;
            }
        });

        Event::listen(function(ElementUrlResolved $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_DEFINE_URL)) {
                return;
            }

            $yiiEvent = new DefineUrlEvent([
                'sender' => $event->element,
                'url' => $event->url,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_DEFINE_URL, $yiiEvent);

            $event->url = $yiiEvent->url;
            if ($yiiEvent->handled) {
                $event->handled = true;
            }
        });

        Event::listen(function(ElementMovingInStructure $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_BEFORE_MOVE_IN_STRUCTURE)) {
                return;
            }

            $yiiEvent = new ElementStructureEvent([
                'sender' => $event->element,
                'structureId' => $event->structureId,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_BEFORE_MOVE_IN_STRUCTURE, $yiiEvent);

            if (!$yiiEvent->isValid) {
                $event->isValid = false;
            }
        });

        Event::listen(function(ElementMovedInStructure $event) {
            if (!self::hasEventHandlers($event->element::class, Element::EVENT_AFTER_MOVE_IN_STRUCTURE)) {
                return;
            }

            $yiiEvent = new ElementStructureEvent([
                'sender' => $event->element,
                'structureId' => $event->structureId,
            ]);

            self::triggerEvent($event->element::class, Element::EVENT_AFTER_MOVE_IN_STRUCTURE, $yiiEvent);
        });
    }

    /**
     * Returns additional buttons that should be shown on the element's edit page.
     *
     * @deprecated 6.0.0 Override `defineAdditionalButtonDescriptors()` or listen for {@see \CraftCms\Cms\Element\Events\ElementAdditionalButtonDescriptorsResolving} instead.
     */
    public function getAdditionalButtons(): string
    {
        $event = new DefineHtmlEvent(['sender' => $this]);

        self::triggerEvent(static::class, Element::EVENT_DEFINE_ADDITIONAL_BUTTONS, $event);

        return $event->html;
    }

    private static function hasEventHandlers(string $class, string $name): bool
    {
        return self::eventHandlers($class, $name) !== [];
    }

    private static function triggerEvent(string $class, string $name, YiiBaseEvent $event): void
    {
        $event->handled = false;
        $event->name = $name;

        foreach (self::eventHandlers($class, $name) as [$handler, $data]) {
            $event->data = $data;
            $handler($event);

            if ($event->handled) {
                return;
            }
        }
    }

    /**
     * The class-level Yii handlers for an event on the class, in the order Yii
     * would call them.
     *
     * Yii looks handlers up by the class's name and its parents' and
     * interfaces' names as PHP reports them, which are the native ones, so it
     * never finds handlers attached to their legacy aliases, such as
     * `craft\base\Element`. This looks both up.
     *
     * @return list<array{0: callable, 1: mixed}>
     */
    private static function eventHandlers(string $class, string $name): array
    {
        static $eventsProperty = new ReflectionProperty(YiiBaseEvent::class, '_events');
        static $wildcardsProperty = new ReflectionProperty(YiiBaseEvent::class, '_eventWildcards');

        $events = $eventsProperty->getValue()[$name] ?? [];
        $wildcards = [];

        foreach ($wildcardsProperty->getValue() as $nameWildcard => $classHandlers) {
            if (StringHelper::matchWildcard($nameWildcard, $name)) {
                $wildcards = array_merge($wildcards, $classHandlers);
            }
        }

        if ($events === [] && $wildcards === []) {
            return [];
        }

        $handlersByClass = [];

        foreach ($events as $registeredClass => $classHandlers) {
            $resolvedClass = self::resolvedClass($registeredClass);
            $handlersByClass[$resolvedClass] = array_merge($handlersByClass[$resolvedClass] ?? [], $classHandlers);
        }

        $class = self::resolvedClass(ltrim($class, '\\'));
        $handlers = [];

        foreach ([$class, ...class_parents($class), ...class_implements($class)] as $targetClass) {
            foreach ($wildcards as $classWildcard => $classHandlers) {
                if (StringHelper::matchWildcard($classWildcard, $targetClass, ['escape' => false])) {
                    array_push($handlers, ...$classHandlers);
                    unset($wildcards[$classWildcard]);
                }
            }

            array_push($handlers, ...$handlersByClass[$targetClass] ?? []);
        }

        return $handlers;
    }

    private static function resolvedClass(string $class): string
    {
        if (!class_exists($class) && !interface_exists($class)) {
            return $class;
        }

        return new ReflectionClass($class)->getName();
    }
}
