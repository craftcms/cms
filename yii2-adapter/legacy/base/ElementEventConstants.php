<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * @phpstan-ignore trait.unused
 */
trait ElementEventConstants
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
     * @deprecated 6.0.0 Use {@see ElementQueryCacheTagsResolving} instead.
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

    /**
     * @event DefineElementDeletionBlockersEvent The event that is triggered when defining any blockers that should prevent a user from being deleted
     *
     * ---
     * ```php
     * use craft\elements\User;
     * use craft\events\DefineUserDeletionBlockersEvent;
     * use yii\base\Event;
     *
     * Event::on(User::class, User::EVENT_DEFINE_DELETION_BLOCKERS, function(DefineElementDeletionBlockersEvent $event) {
     *     $event->blockers[] = // ...
     * });
     * ```
     *
     * @since 5.10.0
     * @deprecated 6.0.0 Use {@see \CraftCms\Cms\Element\Events\DefineDeletionBlockers} instead.
     */
    public const EVENT_DEFINE_DELETION_BLOCKERS = 'defineDeletionBlockers';
}
