<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\base\Event as YiiEvent;
use craft\behaviors\CustomFieldBehavior;
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
use CraftCms\Cms\Element\Events\AfterDelete;
use CraftCms\Cms\Element\Events\AfterMoveInStructure;
use CraftCms\Cms\Element\Events\AfterPropagate;
use CraftCms\Cms\Element\Events\AfterRestore;
use CraftCms\Cms\Element\Events\AfterSave;
use CraftCms\Cms\Element\Events\BeforeDefineUrl;
use CraftCms\Cms\Element\Events\BeforeDelete;
use CraftCms\Cms\Element\Events\BeforeMoveInStructure;
use CraftCms\Cms\Element\Events\BeforeRestore;
use CraftCms\Cms\Element\Events\BeforeSave;
use CraftCms\Cms\Element\Events\DefineActionMenuItems;
use CraftCms\Cms\Element\Events\DefineAdditionalButtons;
use CraftCms\Cms\Element\Events\DefineAltActions;
use CraftCms\Cms\Element\Events\DefineAttributeHtml;
use CraftCms\Cms\Element\Events\DefineCacheTags;
use CraftCms\Cms\Element\Events\DefineEagerLoadingMap;
use CraftCms\Cms\Element\Events\DefineInlineAttributeInputHtml;
use CraftCms\Cms\Element\Events\DefineKeywords;
use CraftCms\Cms\Element\Events\DefineMetadata;
use CraftCms\Cms\Element\Events\DefineMetaFieldsHtml;
use CraftCms\Cms\Element\Events\DefineSidebarHtml;
use CraftCms\Cms\Element\Events\DefineUrl;
use CraftCms\Cms\Element\Events\PrepQueryForTableAttribute;
use CraftCms\Cms\Element\Events\RegisterActions;
use CraftCms\Cms\Element\Events\RegisterCardAttributes;
use CraftCms\Cms\Element\Events\RegisterDefaultCardAttributes;
use CraftCms\Cms\Element\Events\RegisterDefaultTableAttributes;
use CraftCms\Cms\Element\Events\RegisterExporters;
use CraftCms\Cms\Element\Events\RegisterFieldLayouts;
use CraftCms\Cms\Element\Events\RegisterHtmlAttributes;
use CraftCms\Cms\Element\Events\RegisterPreviewTargets;
use CraftCms\Cms\Element\Events\RegisterSearchableAttributes;
use CraftCms\Cms\Element\Events\RegisterSortOptions;
use CraftCms\Cms\Element\Events\RegisterSources;
use CraftCms\Cms\Element\Events\RegisterTableAttributes;
use CraftCms\Cms\Element\Events\Render;
use CraftCms\Cms\Element\Events\SetEagerLoadedElements;
use CraftCms\Cms\Element\Events\SetRoute;
use Illuminate\Support\Facades\Event;
use Override;

/**
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Element} instead.
 *
 * @mixin CustomFieldBehavior
 */
abstract class Element extends \CraftCms\Cms\Element\Element
{
    use ElementEventConstants;

    public function init(): void
    {
        parent::init();

        // Stop allowing setting custom field values directly on the behavior
        /** @var CustomFieldBehavior $behavior */
        $behavior = $this->getBehavior('customFields');
        $behavior->canSetProperties = false;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function defineBehaviors(): array
    {
        return [
            'customFields' => [
                'class' => CustomFieldBehavior::class,
            ],
        ];
    }

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
            if (is_subclass_of($class, self::class)) {
                $elementClasses[] = $class;
            }
        }

        Event::listen(function(DefineCacheTags $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!YiiEvent::hasHandlers($class, self::EVENT_DEFINE_CACHE_TAGS)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
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
                if (!YiiEvent::hasHandlers($class, self::EVENT_REGISTER_SOURCES)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->elementType)) {
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
                if (!YiiEvent::hasHandlers($class, self::EVENT_REGISTER_FIELD_LAYOUTS)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->elementType)) {
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
                if (!YiiEvent::hasHandlers($class, self::EVENT_REGISTER_PREVIEW_TARGETS)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
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
                if (!YiiEvent::hasHandlers($class, self::EVENT_REGISTER_ACTIONS)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->elementType)) {
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
                if (!YiiEvent::hasHandlers($class, self::EVENT_REGISTER_EXPORTERS)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->elementType)) {
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
                if (!YiiEvent::hasHandlers($class, self::EVENT_RENDER)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
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
                if (!YiiEvent::hasHandlers($class, self::EVENT_DEFINE_KEYWORDS)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
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
                if (!YiiEvent::hasHandlers($class, self::EVENT_REGISTER_SORT_OPTIONS)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->elementType)) {
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
                if (!YiiEvent::hasHandlers($class, self::EVENT_REGISTER_TABLE_ATTRIBUTES)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->elementType)) {
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
                if (!YiiEvent::hasHandlers($class, self::EVENT_REGISTER_DEFAULT_TABLE_ATTRIBUTES)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->elementType)) {
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
                if (!YiiEvent::hasHandlers($class, self::EVENT_REGISTER_CARD_ATTRIBUTES)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->elementType)) {
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
                if (!YiiEvent::hasHandlers($class, self::EVENT_REGISTER_DEFAULT_CARD_ATTRIBUTES)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->elementType)) {
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
                if (!YiiEvent::hasHandlers($class, self::EVENT_REGISTER_SEARCHABLE_ATTRIBUTES)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->elementType)) {
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
                if (!YiiEvent::hasHandlers($class, self::EVENT_PREP_QUERY_FOR_TABLE_ATTRIBUTE)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->elementType)) {
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
                if (!YiiEvent::hasHandlers($class, self::EVENT_DEFINE_EAGER_LOADING_MAP)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->elementType)) {
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
                if (!YiiEvent::hasHandlers($class, self::EVENT_SET_EAGER_LOADED_ELEMENTS)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
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
                if (!YiiEvent::hasHandlers($class, self::EVENT_BEFORE_SAVE)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
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

        Event::listen(function(AfterSave $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!YiiEvent::hasHandlers($class, self::EVENT_AFTER_SAVE)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new ModelEvent([
                    'sender' => $event->element,
                    'isNew' => $event->isNew,
                ]);

                YiiEvent::trigger($class, self::EVENT_AFTER_SAVE, $yiiEvent);
            }
        });

        Event::listen(function(AfterPropagate $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!YiiEvent::hasHandlers($class, self::EVENT_AFTER_PROPAGATE)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new ModelEvent([
                    'sender' => $event->element,
                    'isNew' => $event->isNew,
                ]);

                YiiEvent::trigger($class, self::EVENT_AFTER_PROPAGATE, $yiiEvent);
            }
        });

        Event::listen(function(BeforeDelete $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!YiiEvent::hasHandlers($class, self::EVENT_BEFORE_DELETE)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new ModelEvent([
                    'sender' => $event->element,
                ]);

                YiiEvent::trigger($class, self::EVENT_BEFORE_DELETE, $yiiEvent);

                if (!$yiiEvent->isValid) {
                    $event->isValid = false;
                }
            }
        });

        Event::listen(function(AfterDelete $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!YiiEvent::hasHandlers($class, self::EVENT_AFTER_DELETE)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new ModelEvent([
                    'sender' => $event->element,
                ]);

                YiiEvent::trigger($class, self::EVENT_AFTER_DELETE, $yiiEvent);
            }
        });

        Event::listen(function(BeforeRestore $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!YiiEvent::hasHandlers($class, self::EVENT_BEFORE_RESTORE)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new ModelEvent([
                    'sender' => $event->element,
                ]);

                YiiEvent::trigger($class, self::EVENT_BEFORE_RESTORE, $yiiEvent);

                if (!$yiiEvent->isValid) {
                    $event->isValid = false;
                }
            }
        });

        Event::listen(function(AfterRestore $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!YiiEvent::hasHandlers($class, self::EVENT_AFTER_RESTORE)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new ModelEvent([
                    'sender' => $event->element,
                ]);

                YiiEvent::trigger($class, self::EVENT_AFTER_RESTORE, $yiiEvent);
            }
        });

        Event::listen(function(DefineAdditionalButtons $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!YiiEvent::hasHandlers($class, self::EVENT_DEFINE_ADDITIONAL_BUTTONS)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new DefineHtmlEvent([
                    'sender' => $event->element,
                    'html' => $event->html,
                ]);

                YiiEvent::trigger($class, self::EVENT_DEFINE_ADDITIONAL_BUTTONS, $yiiEvent);

                $event->html = $yiiEvent->html;
            }
        });

        Event::listen(function(DefineAltActions $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!YiiEvent::hasHandlers($class, self::EVENT_DEFINE_ALT_ACTIONS)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new DefineAltActionsEvent([
                    'sender' => $event->element,
                    'altActions' => $event->altActions,
                ]);

                YiiEvent::trigger($class, self::EVENT_DEFINE_ALT_ACTIONS, $yiiEvent);

                $event->altActions = $yiiEvent->altActions;
            }
        });

        Event::listen(function(DefineActionMenuItems $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!YiiEvent::hasHandlers($class, self::EVENT_DEFINE_ACTION_MENU_ITEMS)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new DefineMenuItemsEvent([
                    'sender' => $event->element,
                    'items' => $event->items,
                ]);

                YiiEvent::trigger($class, self::EVENT_DEFINE_ACTION_MENU_ITEMS, $yiiEvent);

                $event->items = $yiiEvent->items;
            }
        });

        Event::listen(function(DefineSidebarHtml $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!YiiEvent::hasHandlers($class, self::EVENT_DEFINE_SIDEBAR_HTML)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new DefineHtmlEvent([
                    'sender' => $event->element,
                    'html' => $event->html,
                ]);

                YiiEvent::trigger($class, self::EVENT_DEFINE_SIDEBAR_HTML, $yiiEvent);

                $event->html = $yiiEvent->html;
            }
        });

        Event::listen(function(DefineMetaFieldsHtml $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!YiiEvent::hasHandlers($class, self::EVENT_DEFINE_META_FIELDS_HTML)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new DefineHtmlEvent([
                    'sender' => $event->element,
                    'static' => $event->static,
                    'html' => $event->html,
                ]);

                YiiEvent::trigger($class, self::EVENT_DEFINE_META_FIELDS_HTML, $yiiEvent);

                $event->html = $yiiEvent->html;
            }
        });

        Event::listen(function(DefineMetadata $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!YiiEvent::hasHandlers($class, self::EVENT_DEFINE_METADATA)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new DefineMetadataEvent([
                    'sender' => $event->element,
                    'metadata' => $event->metadata,
                ]);

                YiiEvent::trigger($class, self::EVENT_DEFINE_METADATA, $yiiEvent);

                $event->metadata = $yiiEvent->metadata;
            }
        });

        Event::listen(function(RegisterHtmlAttributes $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!YiiEvent::hasHandlers($class, self::EVENT_REGISTER_HTML_ATTRIBUTES)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new RegisterElementHtmlAttributesEvent([
                    'sender' => $event->element,
                    'htmlAttributes' => $event->htmlAttributes,
                ]);

                YiiEvent::trigger($class, self::EVENT_REGISTER_HTML_ATTRIBUTES, $yiiEvent);

                $event->htmlAttributes = $yiiEvent->htmlAttributes;
            }
        });

        Event::listen(function(DefineAttributeHtml $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!YiiEvent::hasHandlers($class, self::EVENT_DEFINE_ATTRIBUTE_HTML)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new DefineAttributeHtmlEvent([
                    'sender' => $event->element,
                    'attribute' => $event->attribute,
                ]);

                YiiEvent::trigger($class, self::EVENT_DEFINE_ATTRIBUTE_HTML, $yiiEvent);

                if (isset($yiiEvent->html)) {
                    $event->html = $yiiEvent->html;
                }
            }
        });

        Event::listen(function(DefineInlineAttributeInputHtml $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!YiiEvent::hasHandlers($class, self::EVENT_DEFINE_INLINE_ATTRIBUTE_INPUT_HTML)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new DefineAttributeHtmlEvent([
                    'sender' => $event->element,
                    'attribute' => $event->attribute,
                ]);

                YiiEvent::trigger($class, self::EVENT_DEFINE_INLINE_ATTRIBUTE_INPUT_HTML, $yiiEvent);

                if (isset($yiiEvent->html)) {
                    $event->html = $yiiEvent->html;
                }
            }
        });

        Event::listen(function(SetRoute $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!YiiEvent::hasHandlers($class, self::EVENT_SET_ROUTE)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new SetElementRouteEvent([
                    'sender' => $event->element,
                    'route' => $event->route,
                ]);

                YiiEvent::trigger($class, self::EVENT_SET_ROUTE, $yiiEvent);

                $event->route = $yiiEvent->route;
                if ($yiiEvent->handled) {
                    $event->handled = true;
                }
            }
        });

        Event::listen(function(BeforeDefineUrl $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!YiiEvent::hasHandlers($class, self::EVENT_BEFORE_DEFINE_URL)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new DefineUrlEvent([
                    'sender' => $event->element,
                    'url' => $event->url,
                ]);

                YiiEvent::trigger($class, self::EVENT_BEFORE_DEFINE_URL, $yiiEvent);

                $event->url = $yiiEvent->url;
                if ($yiiEvent->handled) {
                    $event->handled = true;
                }
            }
        });

        Event::listen(function(DefineUrl $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!YiiEvent::hasHandlers($class, self::EVENT_DEFINE_URL)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new DefineUrlEvent([
                    'sender' => $event->element,
                    'url' => $event->url,
                ]);

                YiiEvent::trigger($class, self::EVENT_DEFINE_URL, $yiiEvent);

                $event->url = $yiiEvent->url;
                if ($yiiEvent->handled) {
                    $event->handled = true;
                }
            }
        });

        Event::listen(function(BeforeMoveInStructure $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!YiiEvent::hasHandlers($class, self::EVENT_BEFORE_MOVE_IN_STRUCTURE)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new ElementStructureEvent([
                    'sender' => $event->element,
                    'structureId' => $event->structureId,
                ]);

                YiiEvent::trigger($class, self::EVENT_BEFORE_MOVE_IN_STRUCTURE, $yiiEvent);

                if (!$yiiEvent->isValid) {
                    $event->isValid = false;
                }
            }
        });

        Event::listen(function(AfterMoveInStructure $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!YiiEvent::hasHandlers($class, self::EVENT_AFTER_MOVE_IN_STRUCTURE)) {
                    continue;
                }

                if (!is_subclass_of($class, $event->element::class)) {
                    continue;
                }

                $yiiEvent = new ElementStructureEvent([
                    'sender' => $event->element,
                    'structureId' => $event->structureId,
                ]);

                YiiEvent::trigger($class, self::EVENT_AFTER_MOVE_IN_STRUCTURE, $yiiEvent);
            }
        });
    }
}
