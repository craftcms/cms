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
 * @mixin CustomFieldBehavior
 */
abstract class Element extends \CraftCms\Cms\Element\Element
{
    /**
     * @event AuthorizationCheckEvent The event that is triggered when determining whether a user is authorized to view the element's edit page.
     *
     * @see canView()
     * @since 4.0.0
     * @deprecated in 4.3.0. [[\craft\services\Elements::EVENT_AUTHORIZE_VIEW]] should be used instead.
     */
    public const EVENT_AUTHORIZE_VIEW = 'authorizeView';

    /**
     * @event AuthorizationCheckEvent The event that is triggered when determining whether a user is authorized to save the element in its current state.
     *
     * @see canSave()
     * @since 4.0.0
     * @deprecated in 4.3.0. [[\craft\services\Elements::EVENT_AUTHORIZE_SAVE]] should be used instead.
     */
    public const EVENT_AUTHORIZE_SAVE = 'authorizeSave';

    /**
     * @event AuthorizationCheckEvent The event that is triggered when determining whether a user is authorized to create drafts for the element.
     *
     * @see canCreateDrafts()
     * @since 4.0.0
     * @deprecated in 4.3.0. [[\craft\services\Elements::EVENT_AUTHORIZE_CREATE_DRAFTS]] should be used instead.
     */
    public const EVENT_AUTHORIZE_CREATE_DRAFTS = 'authorizeCreateDrafts';

    /**
     * @event AuthorizationCheckEvent The event that is triggered when determining whether a user is authorized to duplicate the element.
     *
     * @see canDuplicate()
     * @since 4.0.0
     * @deprecated in 4.3.0. [[\craft\services\Elements::EVENT_AUTHORIZE_DUPLICATE]] should be used instead.
     */
    public const EVENT_AUTHORIZE_DUPLICATE = 'authorizeDuplicate';

    /**
     * @event AuthorizationCheckEvent The event that is triggered when determining whether a user is authorized to delete the element.
     *
     * @see canDelete()
     * @since 4.0.0
     * @deprecated in 4.3.0. [[\craft\services\Elements::EVENT_AUTHORIZE_DELETE]] should be used instead.
     */
    public const EVENT_AUTHORIZE_DELETE = 'authorizeDelete';

    /**
     * @event AuthorizationCheckEvent The event that is triggered when determining whether a user is authorized to delete the element for its current site.
     *
     * @see canDeleteForSite()
     * @since 4.0.0
     * @deprecated in 4.3.0. [[\craft\services\Elements::EVENT_AUTHORIZE_DELETE_FOR_SITE]] should be used instead.
     */
    public const EVENT_AUTHORIZE_DELETE_FOR_SITE = 'authorizeDeleteForSite';

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

    /**
     * @event ModelEvent The event that is triggered after the element is saved.
     *
     * @see afterSave()
     * @deprecated 6.0.0 Use {@see AfterSave} instead.
     */
    public const EVENT_AFTER_SAVE = 'afterSave';

    /**
     * @event ModelEvent The event that is triggered after the element is fully saved and propagated to other sites.
     *
     * @see afterPropagate()
     * @since 3.2.0
     * @deprecated 6.0.0 Use {@see AfterPropagate} instead.
     */
    public const EVENT_AFTER_PROPAGATE = 'afterPropagate';

    /**
     * @event ModelEvent The event that is triggered before the element is deleted.
     *
     * You may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the element from getting deleted.
     *
     * @see beforeDelete()
     * @deprecated 6.0.0 Use {@see BeforeDelete} instead.
     */
    public const EVENT_BEFORE_DELETE = 'beforeDelete';

    /**
     * @event \yii\base\Event The event that is triggered after the element is deleted.
     *
     * @see afterDelete()
     * @deprecated 6.0.0 Use {@see AfterDelete} instead.
     */
    public const EVENT_AFTER_DELETE = 'afterDelete';

    /**
     * @event ModelEvent The event that is triggered before the element is restored.
     *
     * You may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the element from getting restored.
     *
     * @see beforeRestore()
     * @since 3.1.0
     * @deprecated 6.0.0 Use {@see BeforeRestore} instead.
     */
    public const EVENT_BEFORE_RESTORE = 'beforeRestore';

    /**
     * @event \yii\base\Event The event that is triggered after the element is restored.
     *
     * @see afterRestore()
     * @since 3.1.0
     * @deprecated 6.0.0 Use {@see AfterRestore} instead.
     */
    public const EVENT_AFTER_RESTORE = 'afterRestore';

    /**
     * @event DefineHtmlEvent The event that is triggered when defining additional buttons that should be shown at the top of the element's edit page.
     *
     * @see getAdditionalButtons()
     * @since 4.0.0
     * @deprecated 6.0.0 Use {@see DefineAdditionalButtons} instead.
     */
    public const EVENT_DEFINE_ADDITIONAL_BUTTONS = 'defineAdditionalButtons';

    /**
     * @event DefineAltActionsEvent The event that is triggered when defining alternative form actions for the element.
     *
     * @see getAltActions()
     * @since 5.6.0
     * @deprecated 6.0.0 Use {@see DefineAltActions} instead.
     */
    public const EVENT_DEFINE_ALT_ACTIONS = 'defineAltActions';

    /**
     * @event DefineMenuItemsEvent The event that is triggered when defining action menu items.
     *
     * @see getActionMenuItems()
     * @since 5.0.0
     * @deprecated 6.0.0 Use {@see DefineActionMenuItems} instead.
     */
    public const EVENT_DEFINE_ACTION_MENU_ITEMS = 'defineActionMenuItems';

    /**
     * @event DefineHtmlEvent The event that is triggered when defining the HTML for the editor sidebar.
     *
     * @see getSidebarHtml()
     * @since 3.7.0
     * @deprecated 6.0.0 Use {@see DefineSidebarHtml} instead.
     */
    public const EVENT_DEFINE_SIDEBAR_HTML = 'defineSidebarHtml';

    /**
     * @event DefineHtmlEvent The event that is triggered when defining the HTML for meta fields within the editor sidebar.
     *
     * @see metaFieldsHtml()
     * @since 3.7.0
     * @deprecated 6.0.0 Use {@see DefineMetaFieldsHtml} instead.
     */
    public const EVENT_DEFINE_META_FIELDS_HTML = 'defineMetaFieldsHtml';

    /**
     * @event DefineMetadataEvent The event that is triggered when defining the element's metadata info.
     *
     * @see getMetadata()
     * @since 3.7.0
     * @deprecated 6.0.0 Use {@see DefineMetadata} instead.
     */
    public const EVENT_DEFINE_METADATA = 'defineMetadata';

    /**
     * @event RegisterElementHtmlAttributesEvent The event that is triggered when registering the HTML attributes that should be included in the element's DOM representation in the control panel.
     *
     * @deprecated 6.0.0 Use {@see RegisterHtmlAttributes} instead.
     */
    public const EVENT_REGISTER_HTML_ATTRIBUTES = 'registerHtmlAttributes';

    /**
     * @event DefineAttributeHtmlEvent The event that is triggered when defining an attribute's HTML for table and card views.
     *
     * @see getAttributeHtml()
     * @since 5.0.0
     * @deprecated 6.0.0 Use {@see DefineAttributeHtml} instead.
     */
    public const EVENT_DEFINE_ATTRIBUTE_HTML = 'defineAttributeHtml';

    /**
     * @event DefineAttributeHtmlEvent The event that is triggered when defining an attribute's inline input HTML.
     *
     * @see getInlineAttributeInputHtml()
     * @since 5.0.0
     * @deprecated 6.0.0 Use {@see DefineInlineAttributeInputHtml} instead.
     */
    public const EVENT_DEFINE_INLINE_ATTRIBUTE_INPUT_HTML = 'defineInlineAttributeInputHtml';

    /**
     * @event SetElementRouteEvent The event that is triggered when defining the route that should be used when this element's URL is requested.
     *
     * @see getRoute()
     * @since 3.0.0
     * @deprecated 6.0.0 Use {@see SetRoute} instead.
     */
    public const EVENT_SET_ROUTE = 'setRoute';

    /**
     * @event DefineUrlEvent The event that is triggered before defining the element's URL.
     *
     * @see getUrl()
     * @since 4.4.6
     * @deprecated 6.0.0 Use {@see BeforeDefineUrl} instead.
     */
    public const EVENT_BEFORE_DEFINE_URL = 'beforeDefineUrl';

    /**
     * @event DefineUrlEvent The event that is triggered when defining the element's URL.
     *
     * @see getUrl()
     * @since 4.3.0
     * @deprecated 6.0.0 Use {@see DefineUrl} instead.
     */
    public const EVENT_DEFINE_URL = 'defineUrl';

    /**
     * @event ElementStructureEvent The event that is triggered before the element is moved in a structure.
     *
     * @see beforeMoveInStructure()
     * @since 3.0.0
     * @deprecated 6.0.0 Use {@see BeforeMoveInStructure} instead.
     */
    public const EVENT_BEFORE_MOVE_IN_STRUCTURE = 'beforeMoveInStructure';

    /**
     * @event ElementStructureEvent The event that is triggered after the element is moved in a structure.
     *
     * @see afterMoveInStructure()
     * @since 3.0.0
     * @deprecated 6.0.0 Use {@see AfterMoveInStructure} instead.
     */
    public const EVENT_AFTER_MOVE_IN_STRUCTURE = 'afterMoveInStructure';

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

                $yiiEvent = new ElementStructureEvent([
                    'sender' => $event->element,
                    'structureId' => $event->structureId,
                ]);

                YiiEvent::trigger($class, self::EVENT_AFTER_MOVE_IN_STRUCTURE, $yiiEvent);
            }
        });
    }
}
