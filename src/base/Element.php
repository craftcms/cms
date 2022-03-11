<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Craft;
use craft\behaviors\CustomFieldBehavior;
use craft\behaviors\DraftBehavior;
use craft\behaviors\RevisionBehavior;
use craft\db\Query;
use craft\db\Table;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\exporters\Expanded;
use craft\elements\exporters\Raw;
use craft\elements\User;
use craft\errors\InvalidFieldException;
use craft\events\DefineAttributeKeywordsEvent;
use craft\events\DefineEagerLoadingMapEvent;
use craft\events\DefineHtmlEvent;
use craft\events\DefineMetadataEvent;
use craft\events\DefineValueEvent;
use craft\events\ElementIndexTableAttributeEvent;
use craft\events\ElementStructureEvent;
use craft\events\ModelEvent;
use craft\events\RegisterElementActionsEvent;
use craft\events\RegisterElementDefaultTableAttributesEvent;
use craft\events\RegisterElementExportersEvent;
use craft\events\RegisterElementFieldLayoutsEvent;
use craft\events\RegisterElementHtmlAttributesEvent;
use craft\events\RegisterElementSearchableAttributesEvent;
use craft\events\RegisterElementSortOptionsEvent;
use craft\events\RegisterElementSourcesEvent;
use craft\events\RegisterElementTableAttributesEvent;
use craft\events\RegisterPreviewTargetsEvent;
use craft\events\SetEagerLoadedElementsEvent;
use craft\events\SetElementRouteEvent;
use craft\events\SetElementTableAttributeHtmlEvent;
use craft\fieldlayoutelements\BaseField;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\i18n\Formatter;
use craft\i18n\Locale;
use craft\models\FieldLayout;
use craft\models\Site;
use craft\validators\DateTimeValidator;
use craft\validators\ElementUriValidator;
use craft\validators\SiteIdValidator;
use craft\validators\SlugValidator;
use craft\validators\StringValidator;
use craft\web\UploadedFile;
use DateTime;
use Twig\Markup;
use yii\base\Event;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\base\UnknownPropertyException;
use yii\db\ExpressionInterface;
use yii\validators\NumberValidator;
use yii\validators\Validator;

/**
 * Element is the base class for classes representing elements in terms of objects.
 *
 * @property int|null $canonicalId The element’s canonical ID
 * @property-read string $canonicalUid The element’s canonical UID
 * @property-read $isCanonical Whether this is the canonical element
 * @property-read $isDerivative Whether this is a derivative element, such as a draft or revision
 * @property ElementQueryInterface $ancestors The element’s ancestors
 * @property ElementQueryInterface $children The element’s children
 * @property string $contentTable The name of the table this element’s content is stored in
 * @property string|null $cpEditUrl The element’s edit URL in the control panel
 * @property ElementQueryInterface $descendants The element’s descendants
 * @property string $editorHtml The HTML for the element’s editor HUD
 * @property bool $enabledForSite Whether the element is enabled for this site
 * @property string $fieldColumnPrefix The field column prefix this element’s content uses
 * @property string $fieldContext The field context this element’s content uses
 * @property FieldLayout|null $fieldLayout The field layout used by this element
 * @property array $fieldParamNamespace The namespace used by custom field params on the request
 * @property array $fieldValues The element’s normalized custom field values, indexed by their handles
 * @property bool $hasDescendants Whether the element has descendants
 * @property bool $hasFreshContent Whether the element’s content is "fresh" (unsaved and without validation errors)
 * @property array $htmlAttributes Any attributes that should be included in the element’s DOM representation in the control panel
 * @property bool $isEditable Whether the current user can edit the element
 * @property Markup|null $link An anchor pre-filled with this element’s URL and title
 * @property ElementInterface|null $canonical The canonical element, if one exists for the current site
 * @property ElementInterface|null $next The next element relative to this one, from a given set of criteria
 * @property ElementInterface|null $nextSibling The element’s next sibling
 * @property ElementInterface|null $parent The element’s parent
 * @property ElementInterface|null $prev The previous element relative to this one, from a given set of criteria
 * @property ElementInterface|null $prevSibling The element’s previous sibling
 * @property string|null $ref The reference string to this element
 * @property mixed $route The route that should be used when the element’s URI is requested
 * @property array $serializedFieldValues Array of the element’s serialized custom field values, indexed by their handles
 * @property ElementQueryInterface $siblings All of the element’s siblings
 * @property Site $site Site the element is associated with
 * @property string|null $status The element’s status
 * @property int[]|array $supportedSites The sites this element is associated with
 * @property int $totalDescendants The total number of descendants that the element has
 * @property string|null $uriFormat The URI format used to generate this element’s URL
 * @property string|null $url The element’s full URL
 * @property-write int|null $revisionCreatorId revision creator ID to be saved
 * @property-write string|null $revisionNotes revision notes to be saved
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
abstract class Element extends Component implements ElementInterface
{
    use ElementTrait;

    /**
     * @since 3.3.6
     */
    const HOMEPAGE_URI = '__home__';

    // Statuses
    // -------------------------------------------------------------------------

    const STATUS_ENABLED = 'enabled';
    const STATUS_DISABLED = 'disabled';
    const STATUS_ARCHIVED = 'archived';

    // Validation scenarios
    // -------------------------------------------------------------------------

    const SCENARIO_ESSENTIALS = 'essentials';
    const SCENARIO_LIVE = 'live';

    // Attribute/Field Statuses
    // -------------------------------------------------------------------------

    const ATTR_STATUS_MODIFIED = 'modified';
    const ATTR_STATUS_OUTDATED = 'outdated';
    /** @deprecated in 3.7.0 */
    const ATTR_STATUS_CONFLICTED = 'conflicted';

    // Events
    // -------------------------------------------------------------------------

    /**
     * @event RegisterElementSourcesEvent The event that is triggered when registering the available sources for the element type.
     */
    const EVENT_REGISTER_SOURCES = 'registerSources';

    /**
     * @event RegisterElementFieldLayoutsEvent The event that is triggered when registering all of the field layouts
     * associated with elements from a given source.
     * @see fieldLayouts()
     * @since 3.5.0
     */
    const EVENT_REGISTER_FIELD_LAYOUTS = 'registerFieldLayouts';

    /**
     * @event RegisterElementActionsEvent The event that is triggered when registering the available actions for the element type.
     */
    const EVENT_REGISTER_ACTIONS = 'registerActions';

    /**
     * @event RegisterElementExportersEvent The event that is triggered when registering the available exporters for the element type.
     * @since 3.4.0
     */
    const EVENT_REGISTER_EXPORTERS = 'registerExporters';

    /**
     * @event RegisterElementSearchableAttributesEvent The event that is triggered when registering the searchable attributes for the element type.
     */
    const EVENT_REGISTER_SEARCHABLE_ATTRIBUTES = 'registerSearchableAttributes';

    /**
     * @event RegisterElementSortOptionsEvent The event that is triggered when registering the sort options for the element type.
     */
    const EVENT_REGISTER_SORT_OPTIONS = 'registerSortOptions';

    /**
     * @event RegisterElementTableAttributesEvent The event that is triggered when registering the table attributes for the element type.
     */
    const EVENT_REGISTER_TABLE_ATTRIBUTES = 'registerTableAttributes';

    /**
     * @event RegisterElementTableAttributesEvent The event that is triggered when registering the table attributes for the element type.
     */
    const EVENT_REGISTER_DEFAULT_TABLE_ATTRIBUTES = 'registerDefaultTableAttributes';

    /**
     * @event ElementIndexTableAttributeEvent The event that is triggered when preparing an element query for an element index, for each
     * attribute present in the table.
     *
     * Paired with [[EVENT_REGISTER_TABLE_ATTRIBUTES]] and [[EVENT_SET_TABLE_ATTRIBUTE_HTML]], this allows optimization of queries on element indexes.
     *
     * ```php
     * use craft\base\Element;
     * use craft\elements\Entry;
     * use craft\events\PrepareElementQueryForTableAttributeEvent;
     * use craft\events\RegisterElementTableAttributesEvent;
     * use craft\events\SetElementTableAttributeHtmlEvent;
     * use craft\helpers\Cp;
     * use yii\base\Event;
     *
     * Event::on(
     *     Entry::class,
     *     Element::EVENT_REGISTER_TABLE_ATTRIBUTES,
     *     function(RegisterElementTableAttributesEvent $e) {
     *         $e->attributes[] = 'authorExpertise';
     *     }
     * );
     *
     * Event::on(
     *     Entry::class,
     *     Element::EVENT_PREP_QUERY_FOR_TABLE_ATTRIBUTE,
     *     function(PrepareElementQueryForTableAttributeEvent $e) {
     *         $query = $e->query;
     *         $attr = $e->attribute;
     *
     *         if ($attr === 'authorExpertise') {
     *             $query->andWith(['author.areasOfExpertiseCategoryField']);
     *         }
     *     }
     * );
     *
     * Event::on(
     *     Entry::class,
     *     Element::EVENT_SET_TABLE_ATTRIBUTE_HTML,
     *     function(SetElementTableAttributeHtmlEvent $e) {
     *         $attribute = $e->attribute;
     *
     *         if ($attribute !== 'authorExpertise') {
     *             return;
     *         }
     *
     *         // The field data is eager-loaded!
     *         $author = $e->sender->getAuthor();
     *         $categories = $author->areasOfExpertiseCategoryField;
     *
     *         $e->html = Cp::elementPreviewHtml($categories);
     *     }
     * );
     * ```
     *
     * @since 3.7.14
     */
    const EVENT_PREP_QUERY_FOR_TABLE_ATTRIBUTE = 'prepQueryForTableAttribute';

    /**
     * @event DefineEagerLoadingMapEvent The event that is triggered when defining an eager-loading map.
     *
     * ```php
     * use craft\base\Element;
     * use craft\db\Query;
     * use craft\elements\Entry;
     * use craft\events\DefineEagerLoadingMapEvent;
     * use craft\helpers\ArrayHelper;
     * use yii\base\Event;
     *
     * // Add support for `with(['bookClub'])` to entries
     * Event::on(
     *     Entry::class,
     *     Element::EVENT_DEFINE_EAGER_LOADING_MAP,
     *     function(DefineEagerLoadingMapEvent $e) {
     *         if ($e->handle === 'bookClub') {
     *             $bookEntryIds = ArrayHelper::getColumn($e->elements, 'id');
     *             $e->elementType = \my\plugin\BookClub::class,
     *             $e->map = (new Query)
     *                 ->select(['source' => 'bookId', 'target' => 'clubId'])
     *                 ->from('{{%bookclub_books}}')
     *                 ->where(['bookId' => $bookEntryIds])
     *                 ->all();
     *             $e->handled = true;
     *         }
     *     }
     * );
     * ```
     *
     * @since 3.1.0
     */
    const EVENT_DEFINE_EAGER_LOADING_MAP = 'defineEagerLoadingMap';

    /**
     * @event SetEagerLoadedElementsEvent The event that is triggered when setting eager-loaded elements.
     *
     * Set [[Event::$handled]] to `true` to prevent the elements from getting stored to the private
     * `$_eagerLoadedElements` array.
     *
     * @since 3.5.0
     */
    const EVENT_SET_EAGER_LOADED_ELEMENTS = 'setEagerLoadedElements';

    /**
     * @event RegisterPreviewTargetsEvent The event that is triggered when registering the element’s preview targets.
     * @since 3.2.0
     */
    const EVENT_REGISTER_PREVIEW_TARGETS = 'registerPreviewTargets';

    /**
     * @event SetElementTableAttributeHtmlEvent The event that is triggered when defining the HTML to represent a table attribute.
     */
    const EVENT_SET_TABLE_ATTRIBUTE_HTML = 'setTableAttributeHtml';

    /**
     * @event RegisterElementHtmlAttributesEvent The event that is triggered when registering the HTML attributes that should be included in the element’s DOM representation in the control panel.
     */
    const EVENT_REGISTER_HTML_ATTRIBUTES = 'registerHtmlAttributes';

    /**
     * @event DefineHtmlEvent The event that is triggered when defining the HTML for the element’s editor slideout sidebar.
     * @see getSidebarHtml()
     * @since 3.7.0
     */
    const EVENT_DEFINE_SIDEBAR_HTML = 'defineSidebarHtml';

    /**
     * @event DefineHtmlEvent The event that is triggered when defining the HTML for meta fields within the element’s editor slideout sidebar.
     * @see metaFieldsHtml()
     * @since 3.7.0
     */
    const EVENT_DEFINE_META_FIELDS_HTML = 'defineMetaFieldsHtml';

    /**
     * @event DefineMetadataEvent The event that is triggered when defining the element’s metadata info.
     * @see getMetadata()
     * @since 3.7.0
     */
    const EVENT_DEFINE_METADATA = 'defineMetadata';

    /**
     * @event DefineValueEvent The event that is triggered when determining whether the element should be editable by the current user.
     * @see getIsEditable()
     * @since 3.7.0
     */
    const EVENT_DEFINE_IS_EDITABLE = 'defineIsEditable';

    /**
     * @event DefineValueEvent The event that is triggered when determining whether the element should be deletable by the current user.
     * @see getIsDeletable()
     * @since 3.7.0
     */
    const EVENT_DEFINE_IS_DELETABLE = 'defineIsDeletable';

    /**
     * @event SetElementRouteEvent The event that is triggered when defining the route that should be used when this element’s URL is requested.
     *
     * Set [[Event::$handled]] to `true` to explicitly tell the element that a route has been set (even if you’re
     * setting it to `null`).
     *
     * ```php
     * Event::on(craft\elements\Entry::class, craft\base\Element::EVENT_SET_ROUTE, function(craft\events\SetElementRouteEvent $e) {
     *     // @var craft\elements\Entry $entry
     *     $entry = $e->sender;
     *
     *     if ($entry->uri === 'pricing') {
     *         $e->route = 'module/pricing/index';
     *
     *         // Explicitly tell the element that a route has been set,
     *         // and prevent other event handlers from running, and tell
     *         $e->handled = true;
     *     }
     * });
     * ```
     */
    const EVENT_SET_ROUTE = 'setRoute';

    /**
     * @event DefineAttributeKeywordsEvent The event that is triggered when defining the search keywords for an
     * element attribute.
     *
     * Note that you _must_ set [[Event::$handled]] to `true` if you want the element to accept your custom
     * [[DefineAttributeKeywordsEvent::$keywords|$keywords]] value.
     *
     * ```php
     * Event::on(
     *     craft\elements\Entry::class,
     *     craft\base\Element::EVENT_DEFINE_KEYWORDS,
     *     function(craft\events\DefineAttributeKeywordsEvent $e
     * ) {
     *     // @var craft\elements\Entry $entry
     *     $entry = $e->sender;
     *
     *     // Prevent entry titles in the Parts section from getting search keywords
     *     if ($entry->section->handle === 'parts' && $e->attribute === 'title') {
     *         $e->keywords = '';
     *         $e->handled = true;
     *     }
     * });
     * ```
     *
     * @since 3.5.0
     */
    const EVENT_DEFINE_KEYWORDS = 'defineKeywords';

    /**
     * @event ModelEvent The event that is triggered before the element is saved.
     *
     * You may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the element from getting saved.
     *
     * If you want to ignore events for drafts or revisions, call [[\craft\helpers\ElementHelper::isDraftOrRevision()]]
     * from your event handler:
     *
     * ```php
     * use craft\base\Element;
     * use craft\elements\Entry;
     * use craft\events\ModelEvent;
     * use craft\helpers\ElementHelper;
     * use yii\base\Event;
     *
     * Event::on(Entry::class, Element::EVENT_BEFORE_SAVE, function(ModelEvent $e) {
     *     // @var Entry $entry
     *     $entry = $e->sender;
     *
     *     if (ElementHelper::isDraftOrRevision($entry)) {
     *         return;
     *     }
     *
     *     // ...
     * });
     * ```
     */
    const EVENT_BEFORE_SAVE = 'beforeSave';

    /**
     * @event ModelEvent The event that is triggered after the element is saved.
     *
     * If you want to ignore events for drafts or revisions, call [[\craft\helpers\ElementHelper::isDraftOrRevision()]]
     * from your event handler:
     *
     * ```php
     * use craft\base\Element;
     * use craft\elements\Entry;
     * use craft\events\ModelEvent;
     * use craft\helpers\ElementHelper;
     * use yii\base\Event;
     *
     * Event::on(Entry::class, Element::EVENT_AFTER_SAVE, function(ModelEvent $e) {
     *     // @var Entry $entry
     *     $entry = $e->sender;
     *
     *     if (ElementHelper::isDraftOrRevision($entry)) {
     *         return;
     *     }
     *
     *     // ...
     * });
     * ```
     */
    const EVENT_AFTER_SAVE = 'afterSave';

    /**
     * @event ModelEvent The event that is triggered after the element is fully saved and propagated to other sites.
     *
     * If you want to ignore events for drafts or revisions, call [[\craft\helpers\ElementHelper::isDraftOrRevision()]]
     * from your event handler:
     *
     * ```php
     * use craft\base\Element;
     * use craft\elements\Entry;
     * use craft\events\ModelEvent;
     * use craft\helpers\ElementHelper;
     * use yii\base\Event;
     *
     * Event::on(Entry::class, Element::EVENT_AFTER_PROPAGATE, function(ModelEvent $e) {
     *     // @var Entry $entry
     *     $entry = $e->sender;
     *
     *     if (ElementHelper::isDraftOrRevision($entry) {
     *         return;
     *     }
     *
     *     // ...
     * });
     * ```
     *
     * @since 3.2.0
     */
    const EVENT_AFTER_PROPAGATE = 'afterPropagate';

    /**
     * @event ModelEvent The event that is triggered before the element is deleted.
     *
     * You may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the element from getting deleted.
     */
    const EVENT_BEFORE_DELETE = 'beforeDelete';

    /**
     * @event \yii\base\Event The event that is triggered after the element is deleted.
     */
    const EVENT_AFTER_DELETE = 'afterDelete';

    /**
     * @event ModelEvent The event that is triggered before the element is restored.
     *
     * You may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the element from getting restored.
     * @since 3.1.0
     */
    const EVENT_BEFORE_RESTORE = 'beforeRestore';

    /**
     * @event \yii\base\Event The event that is triggered after the element is restored.
     * @since 3.1.0
     */
    const EVENT_AFTER_RESTORE = 'afterRestore';

    /**
     * @event ElementStructureEvent The event that is triggered before the element is moved in a structure.
     *
     * You may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the element from getting moved.
     */
    const EVENT_BEFORE_MOVE_IN_STRUCTURE = 'beforeMoveInStructure';

    /**
     * @event ElementStructureEvent The event that is triggered after the element is moved in a structure.
     */
    const EVENT_AFTER_MOVE_IN_STRUCTURE = 'afterMoveInStructure';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Element');
    }

    /**
     * @inheritdoc
     */
    public static function lowerDisplayName(): string
    {
        return StringHelper::toLowerCase(static::displayName());
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('app', 'Elements');
    }

    /**
     * @inheritdoc
     */
    public static function pluralLowerDisplayName(): string
    {
        return StringHelper::toLowerCase(static::pluralDisplayName());
    }

    /**
     * @inheritdoc
     */
    public static function refHandle()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public static function trackChanges(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasUris(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_ENABLED => Craft::t('app', 'Enabled'),
            self::STATUS_DISABLED => Craft::t('app', 'Disabled'),
        ];
    }

    /**
     * @inheritdoc
     * @return ElementQueryInterface
     */
    public static function find(): ElementQueryInterface
    {
        return new ElementQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    public static function findOne($criteria = null)
    {
        return static::findByCondition($criteria, true);
    }

    /**
     * @inheritdoc
     */
    public static function findAll($criteria = null): array
    {
        return static::findByCondition($criteria, false);
    }

    /**
     * @inheritdoc
     */
    public static function sources(string $context = null): array
    {
        $sources = static::defineSources($context);

        // Give plugins a chance to modify them
        $event = new RegisterElementSourcesEvent([
            'context' => $context,
            'sources' => $sources,
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_SOURCES, $event);

        return $event->sources;
    }

    /**
     * Defines the sources that elements of this type may belong to.
     *
     * @param string|null $context The context ('index' or 'modal').
     * @return array The sources.
     * @see sources()
     */
    protected static function defineSources(string $context = null): array
    {
        return [];
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public static function fieldLayouts(string $source): array
    {
        $fieldLayouts = static::defineFieldLayouts($source);

        // Give plugins a chance to modify them
        $event = new RegisterElementFieldLayoutsEvent([
            'source' => $source,
            'fieldLayouts' => $fieldLayouts,
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_FIELD_LAYOUTS, $event);

        return $event->fieldLayouts;
    }

    /**
     * Defines the field layouts associated with elements for a given source.
     *
     * @param string $source The selected source’s key, if any
     * @return FieldLayout[] The associated field layouts
     * @see fieldLayouts()
     * @since 3.5.0
     */
    protected static function defineFieldLayouts(string $source): array
    {
        // Default to all of the field layouts associated with this element type
        return Craft::$app->getFields()->getLayoutsByType(static::class);
    }

    /**
     * @inheritdoc
     */
    public static function actions(string $source): array
    {
        $actions = static::defineActions($source);

        // Give plugins a chance to modify them
        $event = new RegisterElementActionsEvent([
            'source' => $source,
            'actions' => $actions,
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_ACTIONS, $event);

        return $event->actions;
    }

    /**
     * Defines the available element actions for a given source.
     *
     * @param string|null $source The selected source’s key, if any.
     * @return array The available element actions.
     * @see actions()
     * @todo this shouldn't allow null in Craft 4
     */
    protected static function defineActions(string $source = null): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function exporters(string $source): array
    {
        $exporters = static::defineExporters($source);

        // Give plugins a chance to modify them
        $event = new RegisterElementExportersEvent([
            'source' => $source,
            'exporters' => $exporters,
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_EXPORTERS, $event);

        return $event->exporters;
    }

    /**
     * Defines the available element exporters for a given source.
     *
     * @param string $source The selected source’s key
     * @return array The available element exporters
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

    /**
     * @inheritdoc
     */
    public static function searchableAttributes(): array
    {
        $attributes = static::defineSearchableAttributes();

        // Give plugins a chance to modify them
        $event = new RegisterElementSearchableAttributesEvent([
            'attributes' => $attributes,
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_SEARCHABLE_ATTRIBUTES, $event);

        return $event->attributes;
    }

    /**
     * Defines which element attributes should be searchable.
     *
     * @return string[] The element attributes that should be searchable
     * @see searchableAttributes()
     */
    protected static function defineSearchableAttributes(): array
    {
        return [];
    }

    // Element index methods
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public static function indexHtml(ElementQueryInterface $elementQuery, array $disabledElementIds = null, array $viewState, string $sourceKey = null, string $context = null, bool $includeContainer, bool $showCheckboxes): string
    {
        $variables = [
            'viewMode' => $viewState['mode'],
            'context' => $context,
            'disabledElementIds' => $disabledElementIds,
            'collapsedElementIds' => Craft::$app->getRequest()->getParam('collapsedElementIds'),
            'showCheckboxes' => $showCheckboxes,
        ];

        if (!empty($viewState['order'])) {
            // Special case for sorting by structure
            if (isset($viewState['order']) && $viewState['order'] === 'structure') {
                $source = ElementHelper::findSource(static::class, $sourceKey, $context);

                if (isset($source['structureId'])) {
                    $elementQuery->orderBy(['lft' => SORT_ASC]);
                    $variables['structure'] = Craft::$app->getStructures()->getStructureById($source['structureId']);

                    // Are they allowed to make changes to this structure?
                    if ($context === 'index' && $variables['structure'] && !empty($source['structureEditable'])) {
                        $variables['structureEditable'] = true;

                        // Let StructuresController know that this user can make changes to the structure
                        Craft::$app->getSession()->authorize('editStructure:' . $variables['structure']->id);
                    }
                } else {
                    unset($viewState['order']);
                }
            } elseif ($orderBy = self::_indexOrderBy($sourceKey, $viewState['order'], $viewState['sort'] ?? 'asc')) {
                $elementQuery->orderBy($orderBy);

                if ((!is_array($orderBy) || !isset($orderBy['score'])) && !empty($viewState['orderHistory'])) {
                    foreach ($viewState['orderHistory'] as $order) {
                        if ($order[0] && $orderBy = self::_indexOrderBy($sourceKey, $order[0], $order[1])) {
                            $elementQuery->addOrderBy($orderBy);
                        } else {
                            break;
                        }
                    }
                }
            }
        }

        if ($viewState['mode'] === 'table') {
            // Get the table columns
            $variables['attributes'] = Craft::$app->getElementIndexes()->getTableAttributes(static::class, $sourceKey);

            // Give each attribute a chance to modify the criteria
            foreach ($variables['attributes'] as $attribute) {
                $event = new ElementIndexTableAttributeEvent([
                    'query' => $elementQuery,
                    'attribute' => $attribute[0],
                ]);

                Event::trigger(static::class, self::EVENT_PREP_QUERY_FOR_TABLE_ATTRIBUTE, $event);

                if (!$event->handled) {
                    static::prepElementQueryForTableAttribute($elementQuery, $attribute[0]);
                }
            }
        }

        $variables['elements'] = $elementQuery->cache()->all();

        $template = '_elements/' . $viewState['mode'] . 'view/' . ($includeContainer ? 'container' : 'elements');

        return Craft::$app->getView()->renderTemplate($template, $variables);
    }

    /**
     * Preps the element criteria for a given table attribute
     *
     * @param ElementQueryInterface $elementQuery
     * @param string $attribute
     */
    protected static function prepElementQueryForTableAttribute(ElementQueryInterface $elementQuery, string $attribute)
    {
        /** @var ElementQuery $elementQuery */
        // Is this a custom field?
        if (preg_match('/^field:(\d+)$/', $attribute, $matches)) {
            $fieldId = $matches[1];
            $field = Craft::$app->getFields()->getFieldById($fieldId);

            if ($field) {
                $field->modifyElementIndexQuery($elementQuery);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public static function sortOptions(): array
    {
        $sortOptions = static::defineSortOptions();

        // Give plugins a chance to modify them
        $event = new RegisterElementSortOptionsEvent([
            'sortOptions' => $sortOptions,
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_SORT_OPTIONS, $event);

        return $event->sortOptions;
    }

    /**
     * Returns the sort options for the element type.
     *
     * @return array The attributes that elements can be sorted by
     * @see sortOptions()
     */
    protected static function defineSortOptions(): array
    {
        // Default to the available table attributes
        $tableAttributes = Craft::$app->getElementIndexes()->getAvailableTableAttributes(static::class);
        $sortOptions = [];

        foreach ($tableAttributes as $key => $labelInfo) {
            $sortOptions[$key] = $labelInfo['label'];
        }

        return $sortOptions;
    }

    /**
     * @inheritdoc
     */
    public static function tableAttributes(): array
    {
        $tableAttributes = static::defineTableAttributes();

        // Give plugins a chance to modify them
        $event = new RegisterElementTableAttributesEvent([
            'tableAttributes' => $tableAttributes,
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_TABLE_ATTRIBUTES, $event);

        return $event->tableAttributes;
    }

    /**
     * Defines all of the available columns that can be shown in table views.
     *
     * @return array The table attributes.
     * @see tableAttributes()
     */
    protected static function defineTableAttributes(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function defaultTableAttributes(string $source): array
    {
        $tableAttributes = static::defineDefaultTableAttributes($source);

        // Give plugins a chance to modify them
        $event = new RegisterElementDefaultTableAttributesEvent([
            'source' => $source,
            'tableAttributes' => $tableAttributes,
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_DEFAULT_TABLE_ATTRIBUTES, $event);

        return $event->tableAttributes;
    }

    /**
     * Returns the list of table attribute keys that should be shown by default.
     *
     * @param string $source The selected source’s key
     * @return string[] The table attributes.
     * @see defaultTableAttributes()
     * @see tableAttributes()
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        // Return all of them by default
        $availableTableAttributes = static::tableAttributes();

        return array_keys($availableTableAttributes);
    }

    // Methods for customizing element queries
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle)
    {
        switch ($handle) {
            case 'descendants':
            case 'children':
                return self::_mapDescendants($sourceElements, $handle === 'children');
            case 'ancestors':
            case 'parent':
                return self::_mapAncestors($sourceElements, $handle === 'parent');
            case 'localized':
                return self::_mapLocalized($sourceElements);
            case 'currentRevision':
                return self::_mapCurrentRevisions($sourceElements);
            case 'drafts':
                return self::_mapDrafts($sourceElements);
            case 'revisions':
                return self::_mapRevisions($sourceElements);
            case 'draftCreator':
                return self::_mapDraftCreators($sourceElements);
            case 'revisionCreator':
                return self::_mapRevisionCreators($sourceElements);
        }

        // Is $handle a custom field handle?
        // (Leave it up to the extended class to set the field context, if it shouldn't be 'global')
        $field = Craft::$app->getFields()->getFieldByHandle($handle);
        if ($field && $field instanceof EagerLoadingFieldInterface) {
            return $field->getEagerLoadingMap($sourceElements);
        }

        // Give plugins a chance to provide custom mappings
        $event = new DefineEagerLoadingMapEvent([
            'sourceElements' => $sourceElements,
            'handle' => $handle,
        ]);
        Event::trigger(static::class, self::EVENT_DEFINE_EAGER_LOADING_MAP, $event);
        if ($event->elementType !== null) {
            return [
                'elementType' => $event->elementType,
                'map' => $event->map,
                'criteria' => $event->criteria,
            ];
        }

        return false;
    }

    /**
     * Returns an eager-loading map for the source elements’ descendants.
     *
     * @param ElementInterface[] $sourceElements An array of the source elements
     * @param bool $children Whether only direct children should be included
     * @return array|null The eager-loading element ID mappings, or null if the result should be ignored
     */
    private static function _mapDescendants(array $sourceElements, bool $children): ?array
    {
        // Get the source element IDs
        $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

        // Get the structure data for these elements
        $selectColumns = ['structureId', 'elementId', 'lft', 'rgt'];

        if ($children) {
            $selectColumns[] = 'level';
        }

        $elementStructureData = (new Query())
            ->select($selectColumns)
            ->from([Table::STRUCTUREELEMENTS])
            ->where(['elementId' => $sourceElementIds])
            ->all();

        if (empty($elementStructureData)) {
            return null;
        }

        // Build the descendant condition & params
        $condition = ['or'];

        foreach ($elementStructureData as $elementStructureDatum) {
            $thisElementCondition = [
                'and',
                ['structureId' => $elementStructureDatum['structureId']],
                ['>', 'lft', $elementStructureDatum['lft']],
                ['<', 'rgt', $elementStructureDatum['rgt']],
            ];

            if ($children) {
                $thisElementCondition[] = ['level' => $elementStructureDatum['level'] + 1];
            }

            $condition[] = $thisElementCondition;
        }

        // Fetch the descendant data
        $descendantStructureQuery = (new Query())
            ->select(['structureId', 'lft', 'rgt', 'elementId'])
            ->from([Table::STRUCTUREELEMENTS])
            ->where($condition)
            ->orderBy(['lft' => SORT_ASC]);

        if ($children) {
            $descendantStructureQuery->addSelect('level');
        }

        $descendantStructureData = $descendantStructureQuery->all();

        // Map the elements to their descendants
        $map = [];
        foreach ($elementStructureData as $elementStructureDatum) {
            foreach ($descendantStructureData as $descendantStructureDatum) {
                if (
                    $descendantStructureDatum['structureId'] === $elementStructureDatum['structureId'] &&
                    $descendantStructureDatum['lft'] > $elementStructureDatum['lft'] &&
                    $descendantStructureDatum['rgt'] < $elementStructureDatum['rgt'] &&
                    (!$children || $descendantStructureDatum['level'] == $elementStructureDatum['level'] + 1)
                ) {
                    if ($descendantStructureDatum['elementId']) {
                        $map[] = [
                            'source' => $elementStructureDatum['elementId'],
                            'target' => $descendantStructureDatum['elementId'],
                        ];
                    }
                }
            }
        }

        return [
            'elementType' => static::class,
            'map' => $map,
        ];
    }

    /**
     * Returns an eager-loading map for the source elements’ ancestors.
     *
     * @param ElementInterface[] $sourceElements An array of the source elements
     * @param bool $parents Whether only direct parents should be included
     * @return array|null The eager-loading element ID mappings, or null if the result should be ignored
     */
    private static function _mapAncestors(array $sourceElements, bool $parents): ?array
    {
        // Get the source element IDs
        $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

        // Get the structure data for these elements
        $selectColumns = ['structureId', 'elementId', 'lft', 'rgt'];

        if ($parents) {
            $selectColumns[] = 'level';
        }

        $elementStructureData = (new Query())
            ->select($selectColumns)
            ->from([Table::STRUCTUREELEMENTS])
            ->where(['elementId' => $sourceElementIds])
            ->all();

        if (empty($elementStructureData)) {
            return null;
        }

        // Build the ancestor condition & params
        $condition = ['or'];

        foreach ($elementStructureData as $elementStructureDatum) {
            $thisElementCondition = [
                'and',
                ['structureId' => $elementStructureDatum['structureId']],
                ['<', 'lft', $elementStructureDatum['lft']],
                ['>', 'rgt', $elementStructureDatum['rgt']],
            ];

            if ($parents) {
                $thisElementCondition[] = ['level' => $elementStructureDatum['level'] - 1];
            }

            $condition[] = $thisElementCondition;
        }

        // Fetch the ancestor data
        $ancestorStructureQuery = (new Query())
            ->select(['structureId', 'lft', 'rgt', 'elementId'])
            ->from([Table::STRUCTUREELEMENTS])
            ->where($condition)
            ->orderBy(['lft' => SORT_ASC]);

        if ($parents) {
            $ancestorStructureQuery->addSelect('level');
        }

        $ancestorStructureData = $ancestorStructureQuery->all();

        // Map the elements to their ancestors
        $map = [];
        foreach ($elementStructureData as $elementStructureDatum) {
            foreach ($ancestorStructureData as $ancestorStructureDatum) {
                if (
                    $ancestorStructureDatum['structureId'] === $elementStructureDatum['structureId'] &&
                    $ancestorStructureDatum['lft'] < $elementStructureDatum['lft'] &&
                    $ancestorStructureDatum['rgt'] > $elementStructureDatum['rgt'] &&
                    (!$parents || $ancestorStructureDatum['level'] == $elementStructureDatum['level'] - 1)
                ) {
                    if ($ancestorStructureDatum['elementId']) {
                        $map[] = [
                            'source' => $elementStructureDatum['elementId'],
                            'target' => $ancestorStructureDatum['elementId'],
                        ];
                    }

                    // If we're just fetching the parents, then we're done with this element
                    if ($parents) {
                        break;
                    }
                }
            }
        }

        return [
            'elementType' => static::class,
            'map' => $map,
        ];
    }

    /**
     * Returns an eager-loading map for the source elements in other locales.
     *
     * @param ElementInterface[] $sourceElements An array of the source elements
     * @return array The eager-loading element ID mappings
     */
    private static function _mapLocalized(array $sourceElements): array
    {
        $sourceSiteId = $sourceElements[0]->siteId;
        $otherSiteIds = [];
        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            if ($site->id != $sourceSiteId) {
                $otherSiteIds[] = $site->id;
            }
        }

        // Map the source elements to themselves
        $map = [];
        if (!empty($otherSiteIds)) {
            foreach ($sourceElements as $element) {
                $map[] = [
                    'source' => $element->id,
                    'target' => $element->id,
                ];
            }
        }

        return [
            'elementType' => static::class,
            'map' => $map,
            'criteria' => [
                'siteId' => $otherSiteIds,
            ],
        ];
    }

    /**
     * Returns an eager-loading map for the source elements’ current revisions.
     *
     * @param ElementInterface[] $sourceElements An array of the source elements
     * @return array The eager-loading element ID mappings
     */
    private static function _mapCurrentRevisions(array $sourceElements): array
    {
        // Get the source element IDs
        $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

        $map = (new Query())
            ->select([
                'source' => 'se.id',
                'target' => 're.id',
            ])
            ->from(['re' => Table::ELEMENTS])
            ->innerJoin(['r' => Table::REVISIONS], '[[r.id]] = [[re.revisionId]]')
            ->innerJoin(['se' => Table::ELEMENTS], '[[se.id]] = [[r.sourceId]]')
            ->where('[[re.dateCreated]] = [[se.dateUpdated]]')
            ->andWhere(['se.id' => $sourceElementIds])
            ->all();

        return [
            'elementType' => static::class,
            'map' => $map,
            'criteria' => ['revisions' => true],
        ];
    }

    /**
     * Returns an eager-loading map for the source elements’ current drafts.
     *
     * @param ElementInterface[] $sourceElements An array of the source elements
     * @return array The eager-loading element ID mappings
     */
    private static function _mapDrafts(array $sourceElements): array
    {
        // Get the source element IDs
        $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

        $map = (new Query())
            ->select([
                'source' => 'd.sourceId',
                'target' => 'e.id',
            ])
            ->from(['d' => Table::DRAFTS])
            ->innerJoin(['e' => Table::ELEMENTS], '[[e.draftId]] = [[d.id]]')
            ->where(['d.sourceId' => $sourceElementIds])
            ->all();

        return [
            'elementType' => static::class,
            'map' => $map,
            'criteria' => ['drafts' => true],
        ];
    }

    /**
     * Returns an eager-loading map for the source elements’ current revisions.
     *
     * @param ElementInterface[] $sourceElements An array of the source elements
     * @return array The eager-loading element ID mappings
     */
    private static function _mapRevisions(array $sourceElements): array
    {
        // Get the source element IDs
        $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

        $map = (new Query())
            ->select([
                'source' => 'r.sourceId',
                'target' => 'e.id',
            ])
            ->from(['r' => Table::REVISIONS])
            ->innerJoin(['e' => Table::ELEMENTS], '[[e.revisionId]] = [[r.id]]')
            ->where(['r.sourceId' => $sourceElementIds])
            ->all();

        return [
            'elementType' => static::class,
            'map' => $map,
            'criteria' => ['revisions' => true],
        ];
    }

    /**
     * Returns an eager-loading map for the source elements’ draft creators.
     *
     * @param ElementInterface[] $sourceElements An array of the source elements
     * @return array The eager-loading element ID mappings
     */
    private static function _mapDraftCreators(array $sourceElements): array
    {
        // Get the source element IDs
        $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

        $map = (new Query())
            ->select([
                'source' => 'e.id',
                'target' => 'd.creatorId',
            ])
            ->from(['e' => Table::ELEMENTS])
            ->innerJoin(['d' => Table::DRAFTS], '[[d.id]] = [[e.draftId]]')
            ->where(['e.id' => $sourceElementIds])
            ->andWhere(['not', ['d.creatorId' => null]])
            ->all();

        return [
            'elementType' => User::class,
            'map' => $map,
        ];
    }

    /**
     * Returns an eager-loading map for the source elements’ revision creators.
     *
     * @param ElementInterface[] $sourceElements An array of the source elements
     * @return array The eager-loading element ID mappings
     */
    private static function _mapRevisionCreators(array $sourceElements): array
    {
        // Get the source element IDs
        $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

        $map = (new Query())
            ->select([
                'source' => 'e.id',
                'target' => 'r.creatorId',
            ])
            ->from(['e' => Table::ELEMENTS])
            ->innerJoin(['r' => Table::REVISIONS], '[[r.id]] = [[e.revisionId]]')
            ->where(['e.id' => $sourceElementIds])
            ->andWhere(['not', ['r.creatorId' => null]])
            ->all();

        return [
            'elementType' => User::class,
            'map' => $map,
        ];
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function gqlTypeNameByContext($context): string
    {
        // Default to the same type
        return 'Element';
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public static function gqlMutationNameByContext($context): string
    {
        // Default to the same type
        return 'saveElement';
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function gqlScopesByContext($context): array
    {
        // Default to no scopes required
        return [];
    }

    /**
     * Returns the orderBy value for element indexes
     *
     * @param string $sourceKey
     * @param string $attribute
     * @param string $dir `asc` or `desc`
     * @return array|ExpressionInterface|false
     */
    private static function _indexOrderBy(string $sourceKey, string $attribute, string $dir)
    {
        $dir = strcasecmp($dir, 'desc') === 0 ? SORT_DESC : SORT_ASC;
        $columns = self::_indexOrderByColumns($sourceKey, $attribute, $dir);

        if ($columns === false || $columns instanceof ExpressionInterface) {
            return $columns;
        }

        // Borrowed from QueryTrait::normalizeOrderBy()
        if (is_string($columns)) {
            $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        }

        $result = [];

        foreach ($columns as $i => $column) {
            if ($i === 0) {
                // The first column's sort direction is always user-defined
                $result[$column] = $dir;
            } elseif (preg_match('/^(.*?)\s+(asc|desc)$/i', $column, $matches)) {
                $result[$matches[1]] = strcasecmp($matches[2], 'desc') ? SORT_ASC : SORT_DESC;
            } else {
                $result[$column] = SORT_ASC;
            }
        }

        return $result;
    }

    /**
     * @param string $sourceKey
     * @param string $attribute
     * @param int $dir
     * @return bool|string|array|ExpressionInterface
     */
    private static function _indexOrderByColumns(string $sourceKey, string $attribute, int $dir)
    {
        if (!$attribute) {
            return false;
        }

        if ($attribute === 'score') {
            return 'score';
        }

        foreach (static::sortOptions() as $key => $sortOption) {
            if (is_array($sortOption)) {
                $a = $sortOption['attribute'] ?? $sortOption['orderBy'];
                if ($a === $attribute) {
                    if (is_callable($sortOption['orderBy'])) {
                        return $sortOption['orderBy']($dir);
                    }
                    return $sortOption['orderBy'];
                }
            } elseif ($key === $attribute) {
                return $key;
            }
        }

        // See if it's a source-specific sort option
        foreach (Craft::$app->getElementIndexes()->getSourceSortOptions(static::class, $sourceKey) as $sortOption) {
            if ($sortOption['attribute'] === $attribute) {
                return $sortOption['orderBy'];
            }
        }

        return false;
    }

    /**
     * @var string|null Revision creator ID to be saved
     * @see setRevisionCreatorId()
     */
    protected $revisionCreatorId;

    /**
     * @var string|null Revision notes to be saved
     * @see setRevisionNotes()
     */
    protected $revisionNotes;

    /**
     * @var int|null
     * @see getCanonicalId()
     * @see setCanonicalId()
     * @see getIsCanonical()
     * @see getIsDerivative()
     */
    private $_canonicalId;

    /**
     * @var static|null
     * @see getCanonical()
     */
    private $_canonical;

    /**
     * @var static|null
     * @see getCanonical()
     */
    private $_canonicalAnySite;

    /**
     * @var string|null
     * @see getCanonicalUid()
     */
    private $_canonicalUid;

    /**
     * @var array|null
     * @see _outdatedAttributes()
     */
    private $_outdatedAttributes;

    /**
     * @var array|null
     * @see _modifiedAttributes()
     */
    private $_modifiedAttributes;

    /**
     * @var array|null
     * @see _outdatedFields()
     */
    private $_outdatedFields;

    /**
     * @var array|null
     * @see _modifiedFields()
     */
    private $_modifiedFields;

    /**
     * @var bool
     */
    private $_initialized = false;

    /**
     * @var
     */
    private $_fieldsByHandle;

    /**
     * @var
     */
    private $_fieldParamNamePrefix;

    /**
     * @var array|null Record of the fields whose values have already been normalized
     */
    private $_normalizedFieldValues;

    /**
     * @var bool Whether all attributes and field values should be considered dirty.
     * @see getDirtyAttributes()
     * @see getDirtyFields()
     * @see isFieldDirty()
     */
    private $_allDirty = false;

    /**
     * @var string[]|null Record of dirty attributes.
     * @see getDirtyAttributes()
     * @see isAttributeDirty()
     */
    private $_dirtyAttributes = [];

    /**
     * @var string|null The initial title value, if there was one.
     * @see getDirtyAttributes()
     */
    private $_savedTitle;

    /**
     * @var array Record of dirty fields.
     * @see getDirtyFields()
     * @see isFieldDirty()
     */
    private $_dirtyFields;

    /**
     * @var
     */
    private $_nextElement;

    /**
     * @var
     */
    private $_prevElement;

    /**
     * @var
     */
    private $_parent;

    /**
     * @var
     */
    private $_prevSibling;

    /**
     * @var
     */
    private $_nextSibling;

    /**
     * @var array|null
     */
    private $_eagerLoadedElements;

    /**
     * @var array|null
     */
    private $_eagerLoadedElementCounts;

    /**
     * @var ElementInterface|false
     * @see getCurrentRevision()
     */
    private $_currentRevision;

    /**
     * @var bool|bool[]
     * @see getEnabledForSite()
     * @see setEnabledForSite()
     */
    private $_enabledForSite = true;

    /**
     * @var string|null
     * @see getUiLabel()
     * @see setUiLabel()
     */
    private $_uiLabel;

    /**
     * @var bool|null
     * @see getIsFresh()
     * @see setIsFresh()
     */
    private $_isFresh;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // Make sure the field layout ID is set before any custom fields
        if (isset($config['fieldLayoutId'])) {
            $config = ['fieldLayoutId' => $config['fieldLayoutId']] + $config;
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function __clone()
    {
        // Mark all fields as dirty
        $this->_allDirty = true;
        parent::__clone();
    }

    /**
     * Returns the string representation of the element.
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->title !== null && $this->title !== '') {
            return (string)$this->title;
        }
        return (string)$this->id ?: static::class;
    }

    /**
     * Checks if a property is set.
     *
     * This method will check if $name is one of the following:
     * - "title"
     * - a magic property supported by [[\yii\base\Component::__isset()]]
     * - a custom field handle
     *
     * @param string $name The property name
     * @return bool Whether the property is set
     */
    public function __isset($name): bool
    {
        // Is this the "field:handle" syntax?
        if (strncmp($name, 'field:', 6) === 0) {
            return $this->fieldByHandle(substr($name, 6)) !== null;
        }

        return $name === 'title' || $this->hasEagerLoadedElements($name) || parent::__isset($name) || $this->fieldByHandle($name);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if ($name === 'locale') {
            Craft::$app->getDeprecator()->log('Element::locale', 'The `locale` element property has been deprecated. Use `siteId` instead.');

            return $this->getSite()->handle;
        }

        // Is $name a set of eager-loaded elements?
        if ($this->hasEagerLoadedElements($name)) {
            return $this->getEagerLoadedElements($name);
        }

        // Is this the "field:handle" syntax?
        if (strncmp($name, 'field:', 6) === 0) {
            return $this->getFieldValue(substr($name, 6));
        }

        // If this is a field, make sure the value has been normalized before returning the CustomFieldBehavior value
        if ($this->fieldByHandle($name) !== null) {
            return $this->getFieldValue($name);
        }

        return parent::__get($name);
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        // Is this the "field:handle" syntax?
        if (strncmp($name, 'field:', 6) === 0) {
            $this->setFieldValue(substr($name, 6), $value);
            return;
        }

        try {
            parent::__set($name, $value);
        } catch (InvalidCallException | UnknownPropertyException $e) {
            // Is this is a field?
            if ($this->fieldByHandle($name) !== null) {
                $this->setFieldValue($name, $value);
            } else {
                throw $e;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function __call($name, $params)
    {
        if (strncmp($name, 'isFieldEmpty:', 13) === 0) {
            return $this->isFieldEmpty(substr($name, 13));
        }

        return parent::__call($name, $params);
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['customFields'] = [
            'class' => CustomFieldBehavior::class,
        ];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        // Typecast DB values
        $this->id = (int)$this->id ?: null;
        $this->draftId = (int)$this->draftId ?: null;
        $this->revisionId = (int)$this->revisionId ?: null;
        $this->siteSettingsId = (int)$this->siteSettingsId ?: null;
        $this->fieldLayoutId = (int)$this->fieldLayoutId ?: null;
        $this->structureId = (int)$this->structureId ?: null;
        $this->contentId = (int)$this->contentId ?: null;
        $this->enabled = (bool)$this->enabled;
        $this->archived = (bool)$this->archived;
        $this->siteId = (int)$this->siteId ?: null;
        $this->root = (int)$this->root ?: null;
        $this->lft = (int)$this->lft ?: null;
        $this->rgt = (int)$this->rgt ?: null;
        $this->level = (int)$this->level ?: null;
        $this->searchScore = (int)$this->searchScore ?: null;
        $this->trashed = (bool)$this->trashed;

        parent::init();

        if ($this->siteId === null && Craft::$app->getIsInstalled()) {
            $this->siteId = Craft::$app->getSites()->getPrimarySite()->id;
        }

        if (static::hasTitles()) {
            $this->_savedTitle = $this->title;
        }

        $this->_initialized = true;

        // Stop allowing setting custom field values directly on the behavior
        /** @var CustomFieldBehavior $behavior */
        $behavior = $this->getBehavior('customFields');
        $behavior->canSetProperties = false;
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        $names = parent::attributes();

        if (!$this->structureId) {
            ArrayHelper::removeValue($names, 'structureId');
            ArrayHelper::removeValue($names, 'root');
            ArrayHelper::removeValue($names, 'lft');
            ArrayHelper::removeValue($names, 'rgt');
            ArrayHelper::removeValue($names, 'level');
        }

        ArrayHelper::removeValue($names, 'searchScore');
        ArrayHelper::removeValue($names, 'awaitingFieldValues');
        ArrayHelper::removeValue($names, 'firstSave');
        ArrayHelper::removeValue($names, 'propagating');
        ArrayHelper::removeValue($names, 'propagateAll');
        ArrayHelper::removeValue($names, 'newSiteIds');
        ArrayHelper::removeValue($names, 'resaving');
        ArrayHelper::removeValue($names, 'duplicateOf');
        ArrayHelper::removeValue($names, 'mergingCanonicalChanges');
        ArrayHelper::removeValue($names, 'updatingFromDerivative');
        ArrayHelper::removeValue($names, 'previewing');
        ArrayHelper::removeValue($names, 'hardDelete');

        $names[] = 'canonicalId';
        $names[] = 'ref';
        $names[] = 'status';
        $names[] = 'structureId';
        $names[] = 'url';

        // Include custom field handles
        if (static::hasContent() && ($fieldLayout = $this->getFieldLayout()) !== null) {
            foreach ($fieldLayout->getFields() as $field) {
                $names[] = $field->handle;
            }
        }

        // In case there are any field handles that had the same name as an existing property
        return array_unique($names);
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'ancestors',
            'canonical',
            'canonicalUid',
            'children',
            'descendants',
            'hasDescendants',
            'next',
            'nextSibling',
            'parent',
            'prev',
            'prevSibling',
            'siblings',
            'site',
            'totalDescendants',
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAttributeLabel($attribute)
    {
        // Is this the "field:handle" syntax?
        if (strncmp($attribute, 'field:', 6) === 0) {
            $attribute = substr($attribute, 6);
        }

        return parent::getAttributeLabel($attribute);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        $labels = [
            'dateCreated' => Craft::t('app', 'Date Created'),
            'dateUpdated' => Craft::t('app', 'Date Updated'),
            'id' => Craft::t('app', 'ID'),
            'slug' => Craft::t('app', 'Slug'),
            'title' => Craft::t('app', 'Title'),
            'uid' => Craft::t('app', 'UID'),
            'uri' => Craft::t('app', 'URI'),
        ];

        if (Craft::$app->getIsInstalled()) {
            $layout = $this->getFieldLayout();

            if ($layout !== null) {
                foreach ($layout->getTabs() as $tab) {
                    if ($tab->elements) {
                        foreach ($tab->elements as $layoutElement) {
                            if ($layoutElement instanceof BaseField && ($label = $layoutElement->label()) !== null) {
                                $labels[$layoutElement->attribute()] = $label;
                            }
                        }
                    }
                }
            }
        }

        return $labels;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'contentId', 'root', 'lft', 'rgt', 'level'], 'number', 'integerOnly' => true, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]];
        $rules[] = [
            ['siteId'],
            SiteIdValidator::class,
            'allowDisabled' => $this->propagating ?: null,
            'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE, self::SCENARIO_ESSENTIALS],
        ];
        $rules[] = [['dateCreated', 'dateUpdated'], DateTimeValidator::class, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]];

        if (static::hasTitles()) {
            $rules[] = [['title'], 'trim', 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]];
            $rules[] = [['title'], StringValidator::class, 'max' => 255, 'disallowMb4' => true, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]];
            $rules[] = [['title'], 'required', 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]];
        }

        if (static::hasUris()) {
            try {
                $language = $this->getSite()->language;
            } catch (InvalidConfigException $e) {
                $language = null;
            }

            $rules[] = [['slug'], SlugValidator::class, 'language' => $language, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE, self::SCENARIO_ESSENTIALS]];
            $rules[] = [['slug'], 'string', 'max' => 255, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE, self::SCENARIO_ESSENTIALS]];
            $rules[] = [['uri'], ElementUriValidator::class, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE, self::SCENARIO_ESSENTIALS]];
        }

        // Are we validating custom fields?
        if (static::hasContent() && Craft::$app->getIsInstalled() && $fieldLayout = $this->getFieldLayout()) {
            $fieldsWithColumns = [];

            foreach ($fieldLayout->getFields() as $field) {
                $attribute = 'field:' . $field->handle;
                $isEmpty = [$this, 'isFieldEmpty:' . $field->handle];

                if ($field->required) {
                    // Only validate required custom fields on the LIVE scenario
                    $rules[] = [[$attribute], 'required', 'isEmpty' => $isEmpty, 'on' => self::SCENARIO_LIVE];
                }

                if ($field::hasContentColumn()) {
                    $fieldsWithColumns[] = $field->handle;
                }

                foreach ($field->getElementValidationRules() as $rule) {
                    $rules[] = $this->_normalizeFieldRule($attribute, $rule, $field, $isEmpty);
                }
            }

            if (!empty($fieldsWithColumns)) {
                $rules[] = [$fieldsWithColumns, 'validateCustomFieldContentSize', 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE, self::SCENARIO_ESSENTIALS]];
            }
        }

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'dateLastMerged';
        return $attributes;
    }

    /**
     * Normalizes a field’s validation rule.
     *
     * @param string $attribute
     * @param mixed $rule
     * @param FieldInterface $field
     * @param callable $isEmpty
     * @return Validator|array
     * @throws InvalidConfigException
     */
    private function _normalizeFieldRule(string $attribute, $rule, FieldInterface $field, callable $isEmpty)
    {
        if ($rule instanceof Validator) {
            return $rule;
        }

        if (is_string($rule)) {
            // "Validator" syntax
            $rule = [$attribute, $rule, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]];
        }

        if (!is_array($rule) || !isset($rule[0])) {
            throw new InvalidConfigException('Invalid validation rule for custom field "' . $field->handle . '".');
        }

        if (isset($rule[1])) {
            // Make sure the attribute name starts with 'field:'
            if ($rule[0] === $field->handle) {
                $rule[0] = $attribute;
            }
        } else {
            // ["Validator"] syntax
            array_unshift($rule, $attribute);
        }

        if ($rule[1] instanceof \Closure || $field->hasMethod($rule[1])) {
            // InlineValidator assumes that the closure is on the model being validated
            // so it won’t pass a reference to the element
            $rule['params'] = [
                $field,
                $rule[1],
                $rule['params'] ?? null,
            ];
            $rule[1] = 'validateCustomFieldAttribute';
        }

        // Set 'isEmpty' to the field's isEmpty() method by default
        if (!array_key_exists('isEmpty', $rule)) {
            $rule['isEmpty'] = $isEmpty;
        }

        // Set 'on' to the main scenarios by default
        if (!array_key_exists('on', $rule)) {
            $rule['on'] = [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE];
        }

        return $rule;
    }

    /**
     * Calls a custom validation function on a custom field.
     *
     * This will be called by [[\yii\validators\InlineValidator]] if a custom field specified
     * a closure or the name of a class-level method as the validation type.
     *
     * @param string $attribute The field handle
     * @param array|null $params
     */
    public function validateCustomFieldAttribute(string $attribute, array $params = null)
    {
        /** @var array|null $params */
        [$field, $method, $fieldParams] = $params;

        if (is_string($method)) {
            $method = [$field, $method];
        }

        $method($this, $fieldParams);
    }

    /**
     * @inheritdoc
     */
    public function isFieldEmpty(string $handle): bool
    {
        if (
            ($fieldLayout = $this->getFieldLayout()) === null ||
            ($field = $fieldLayout->getFieldByHandle($handle)) === null
        ) {
            return true;
        }

        return $field->isValueEmpty($this->getFieldValue($handle), $this);
    }

    /**
     * Validates that the content size is going to fit within the field’s database column.
     *
     * @param string $attribute
     */
    public function validateCustomFieldContentSize(string $attribute)
    {
        $field = $this->fieldByHandle($attribute);
        $columnType = $field->getContentColumnType();
        $value = $field->serializeValue($this->getFieldValue($attribute), $this);

        if (is_array($columnType)) {
            foreach ($columnType as $key => $type) {
                $this->_validateCustomFieldContentSizeInternal($attribute, $field, $type, $value[$key] ?? null);
            }
        } else {
            $this->_validateCustomFieldContentSizeInternal($attribute, $field, $columnType, $value);
        }
    }

    /**
     * @param string $attribute
     * @param FieldInterface $field
     * @param string $columnType
     * @param mixed $value
     * @return void
     */
    private function _validateCustomFieldContentSizeInternal(string $attribute, FieldInterface $field, string $columnType, $value): void
    {
        $simpleColumnType = Db::getSimplifiedColumnType($columnType);

        if (!in_array($simpleColumnType, [Db::SIMPLE_TYPE_NUMERIC, Db::SIMPLE_TYPE_TEXTUAL], true)) {
            return;
        }

        $value = Db::prepareValueForDb($value);

        // Ignore empty values
        if ($value === null || $value === '') {
            return;
        }

        if ($simpleColumnType === Db::SIMPLE_TYPE_NUMERIC) {
            $validator = new NumberValidator([
                'min' => Db::getMinAllowedValueForNumericColumn($columnType) ?: null,
                'max' => Db::getMaxAllowedValueForNumericColumn($columnType) ?: null,
            ]);
        } else {
            $validator = new StringValidator([
                // Don't count multibyte characters as a single char
                'encoding' => '8bit',
                'max' => Db::getTextualColumnStorageCapacity($columnType) ?: null,
                'disallowMb4' => true,
            ]);
        }

        if (!$validator->validate($value, $error)) {
            $error = str_replace(Craft::t('yii', 'the input value'), Craft::t('site', $field->name), $error);
            $this->addError($attribute, $error);
        }
    }

    /**
     * @inheritdoc
     */
    public function addError($attribute, $error = '')
    {
        if (strncmp($attribute, 'field:', 6) === 0) {
            $attribute = substr($attribute, 6);
        }

        parent::addError($attribute, $error);
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getIsDraft(): bool
    {
        return !empty($this->draftId);
    }

    /**
     * Returns whether this is a provisional draft.
     *
     * @return bool
     * @since 3.7.0
     * @deprecated in 3.7.0. Use [[isProvisionalDraft]] instead.
     */
    public function getIsProvisionalDraft(): bool
    {
        return $this->isProvisionalDraft;
    }

    /**
     * @inheritdoc
     */
    public function getIsRevision(): bool
    {
        return !empty($this->revisionId);
    }

    /**
     * @inheritdoc
     */
    public function getIsCanonical(): bool
    {
        return $this->_canonicalId === null;
    }

    /**
     * @inheritdoc
     */
    public function getIsDerivative(): bool
    {
        return !$this->getIsCanonical();
    }

    /**
     * @inheritdoc
     */
    public function getCanonical(bool $anySite = false): ElementInterface
    {
        if ($this->getIsCanonical()) {
            return $this;
        }

        $prop = $anySite ? '_canonicalAnySite' : '_canonical';

        if ($this->$prop === null) {
            $this->$prop = static::find()
                    ->id($this->_canonicalId)
                    ->siteId($anySite ? '*' : $this->siteId)
                    ->preferSites([$this->siteId])
                    ->structureId($this->structureId)
                    ->unique()
                    ->anyStatus()
                    ->trashed(null)
                    ->ignorePlaceholders()
                    ->one() ?? false;
        }

        return $this->$prop ?: $this;
    }

    /**
     * @inheritdoc
     */
    public function setCanonical(ElementInterface $element): void
    {
        if ($this->getIsCanonical()) {
            throw new NotSupportedException('setCanonical() can only be called on a derivative element.');
        }

        $this->_canonical = $element;
    }

    /**
     * @inheritdoc
     */
    public function getCanonicalId(): ?int
    {
        return $this->_canonicalId ?? $this->id;
    }

    /**
     * @inheritdoc
     */
    public function setCanonicalId(?int $canonicalId): void
    {
        if ($canonicalId != $this->id) {
            $this->_canonicalId = $canonicalId;
        } else {
            $this->_canonicalId = null;
        }

        $this->_canonical = null;
    }

    /**
     * @inheritdoc
     */
    public function getCanonicalUid(): ?string
    {
        // If this is the canonical element, return its UUID
        if ($this->getIsCanonical()) {
            return $this->uid;
        }

        // If the canonical element is already memoized via getCanonical(), go with its UUID
        if ($this->_canonical !== null) {
            return $this->_canonical->uid;
        }

        // Just fetch that one value ourselves
        if ($this->_canonicalUid === null) {
            $this->_canonicalUid = static::find()
                ->select(['elements.uid'])
                ->id($this->_canonicalId)
                ->site('*')
                ->anyStatus()
                ->ignorePlaceholders()
                ->scalar();
        }

        return $this->_canonicalUid;
    }

    /**
     * Returns the element’s canonical ID.
     *
     * @return int|null
     * @since 3.2.0
     * @deprecated in 3.7.0. Use [[getCanonicalId()]] instead.
     */
    public function getSourceId()
    {
        Craft::$app->getDeprecator()->log(__METHOD__, 'Elements’ `getSourceId()` method has been deprecated. Use `getCanonicalId()` instead.');
        return $this->getCanonicalId();
    }

    /**
     * Returns the element’s canonical UID.
     *
     * @return string
     * @since 3.2.0
     * @deprecated in 3.7.0. Use [[getCanonicalUid()]] instead.
     */
    public function getSourceUid(): string
    {
        Craft::$app->getDeprecator()->log(__METHOD__, 'Elements’ `getSourceUid()` method has been deprecated. Use `getCanonicalUid()` instead.');
        return $this->getCanonicalUid();
    }

    /**
     * @inheritdoc
     */
    public function getIsUnpublishedDraft(): bool
    {
        return $this->getIsDraft() && $this->getIsCanonical();
    }

    /**
     * Returns whether the element is an unpublished draft.
     *
     * @return bool
     * @since 3.2.0
     * @deprecated in 3.6.0. Use [[getIsUnpublishedDraft()]] instead.
     */
    public function getIsUnsavedDraft(): bool
    {
        return $this->getIsUnpublishedDraft();
    }

    /**
     * @inheritdoc
     */
    public function mergeCanonicalChanges(): void
    {
        if (($canonical = $this->getCanonical()) === null) {
            return;
        }

        // Update any attributes that were modified upstream
        foreach ($this->getOutdatedAttributes() as $attribute) {
            if (!$this->isAttributeModified($attribute)) {
                $this->$attribute = $canonical->$attribute;
            }
        }

        foreach ($this->getOutdatedFields() as $fieldHandle) {
            if (
                !$this->isFieldModified($fieldHandle) &&
                ($field = $this->fieldByHandle($fieldHandle)) !== null
            ) {
                $field->copyValue($canonical, $this);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout()
    {
        if ($this->fieldLayoutId) {
            return Craft::$app->getFields()->getLayoutById($this->fieldLayoutId);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getSupportedSites(): array
    {
        if (static::isLocalized()) {
            return Craft::$app->getSites()->getAllSiteIds();
        }

        return [Craft::$app->getSites()->getPrimarySite()->id];
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getCacheTags(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getUriFormat()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getSearchKeywords(string $attribute): string
    {
        // Give plugins/modules a chance to define custom keywords
        if ($this->hasEventHandlers(self::EVENT_DEFINE_KEYWORDS)) {
            $event = new DefineAttributeKeywordsEvent([
                'attribute' => $attribute,
            ]);
            $this->trigger(self::EVENT_DEFINE_KEYWORDS, $event);
            if ($event->handled) {
                return $event->keywords ?? '';
            }
        }
        return $this->searchKeywords($attribute);
    }

    /**
     * Returns the search keywords for a given search attribute.
     *
     * @param string $attribute
     * @return string
     * @since 3.5.0
     */
    protected function searchKeywords(string $attribute): string
    {
        return StringHelper::toString($this->$attribute);
    }

    /**
     * @inheritdoc
     */
    public function getRoute()
    {
        // Give plugins a chance to set this
        if ($this->hasEventHandlers(self::EVENT_SET_ROUTE)) {
            $event = new SetElementRouteEvent();
            $this->trigger(self::EVENT_SET_ROUTE, $event);

            if ($event->handled || $event->route !== null) {
                return $event->route ?: null;
            }
        }

        return $this->route();
    }

    /**
     * Returns the route that should be used when the element’s URI is requested.
     *
     * @return mixed The route that the request should use, or null if no special action should be taken
     * @see getRoute()
     */
    protected function route()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getIsHomepage(): bool
    {
        return $this->uri === self::HOMEPAGE_URI;
    }

    /**
     * @inheritdoc
     */
    public function getUrl()
    {
        if ($this->uri === null) {
            return null;
        }

        $path = $this->getIsHomepage() ? '' : $this->uri;
        return UrlHelper::siteUrl($path, null, null, $this->siteId);
    }

    /**
     * @inheritdoc
     */
    public function getLink()
    {
        if (($url = $this->getUrl()) === null) {
            return null;
        }

        $a = Html::a(Html::encode($this->getUiLabel()), $url);
        return Template::raw($a);
    }

    /**
     * @inheritdoc
     */
    public function getUiLabel(): string
    {
        return $this->_uiLabel ?? $this->uiLabel() ?? (string)$this;
    }

    /**
     * @inheritdoc
     */
    public function setUiLabel(?string $label): void
    {
        $this->_uiLabel = $label;
    }

    /**
     * Returns what the element should be called within the control panel.
     *
     * @return string|null
     * @since 3.6.4
     */
    protected function uiLabel(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getRef()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getIsEditable(): bool
    {
        $event = new DefineValueEvent([
            'value' => $this->isEditable(),
        ]);
        $this->trigger(self::EVENT_DEFINE_IS_EDITABLE, $event);
        return $event->value;
    }

    /**
     * Returns whether the current user can edit the element.
     *
     * @return bool
     * @since 3.7.0
     */
    protected function isEditable(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getIsDeletable(): bool
    {
        $event = new DefineValueEvent([
            'value' => $this->isDeletable(),
        ]);
        $this->trigger(self::EVENT_DEFINE_IS_DELETABLE, $event);
        return $event->value;
    }

    /**
     * Returns whether the current user can delete the element.
     *
     * @return bool
     * @since 3.5.12
     */
    protected function isDeletable(): bool
    {
        // todo: change to false in 4.0
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl()
    {
        $cpEditUrl = $this->cpEditUrl();

        if ($cpEditUrl !== null) {
            if ($this->getIsDraft() && !$this->isProvisionalDraft) {
                $cpEditUrl = UrlHelper::urlWithParams($cpEditUrl, ['draftId' => $this->draftId]);
            } elseif ($this->getIsRevision()) {
                $cpEditUrl = UrlHelper::urlWithParams($cpEditUrl, ['revisionId' => $this->revisionId]);
            }
        }

        return $cpEditUrl;
    }

    /**
     * Returns the element’s edit URL in the control panel.
     *
     * @return string|null
     * @since 3.7.0
     */
    protected function cpEditUrl(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getPreviewTargets(): array
    {
        if (Craft::$app->getEdition() === Craft::Pro) {
            $previewTargets = $this->previewTargets();
            // Give plugins a chance to modify them
            if ($this->hasEventHandlers(self::EVENT_REGISTER_PREVIEW_TARGETS)) {
                $event = new RegisterPreviewTargetsEvent([
                    'previewTargets' => $previewTargets,
                ]);
                $this->trigger(self::EVENT_REGISTER_PREVIEW_TARGETS, $event);
                $previewTargets = $event->previewTargets;
            }
        } elseif ($url = $this->getUrl()) {
            $previewTargets = [
                [
                    'label' => Craft::t('app', 'Primary {type} page', [
                        'type' => static::lowerDisplayName(),
                    ]),
                    'url' => $url,
                ],
            ];
        } else {
            return [];
        }

        // Normalize the targets
        $normalized = [];
        $view = Craft::$app->getView();

        foreach ($previewTargets as $previewTarget) {
            if (isset($previewTarget['urlFormat'])) {
                $url = trim($view->renderObjectTemplate(App::parseEnv($previewTarget['urlFormat']), $this));
                if ($url !== '') {
                    $previewTarget['url'] = $url;
                    unset($previewTarget['urlFormat']);
                }
            }
            if (!isset($previewTarget['url'])) {
                // No URL, no preview target
                continue;
            }
            $previewTarget['url'] = UrlHelper::siteUrl($previewTarget['url']);
            if (!isset($previewTarget['refresh'])) {
                $previewTarget['refresh'] = true;
            }
            $normalized[] = $previewTarget;
        }

        return $normalized;
    }

    /**
     * Returns the additional locations that should be available for previewing the element, besides its primary [[getUrl()|URL]].
     *
     * Each target should be represented by a sub-array with `'label'` and `'url'` keys.
     *
     * @return array
     * @see getPreviewTargets()
     * @since 3.2.0
     */
    protected function previewTargets(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getThumbUrl(int $size)
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getHasCheckeredThumb(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getHasRoundedThumb(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getEnabledForSite(int $siteId = null)
    {
        if ($siteId === null) {
            $siteId = $this->siteId;
        }
        if (is_array($this->_enabledForSite)) {
            return $this->_enabledForSite[$siteId] ?? ($siteId == $this->siteId ? true : null);
        }
        if ($siteId == $this->siteId) {
            return is_bool($this->_enabledForSite) ? $this->_enabledForSite : true;
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function setEnabledForSite($enabledForSite)
    {
        if (is_array($enabledForSite)) {
            foreach ($enabledForSite as &$value) {
                $value = (bool)$value;
            }
        } else {
            $enabledForSite = (bool)$enabledForSite;
        }
        $this->_enabledForSite = $enabledForSite;
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        if ($this->archived) {
            return self::STATUS_ARCHIVED;
        }

        if (!$this->enabled || !$this->getEnabledForSite()) {
            return self::STATUS_DISABLED;
        }

        return self::STATUS_ENABLED;
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getLocalized()
    {
        // Eager-loaded?
        if (($localized = $this->getEagerLoadedElements('localized')) !== null) {
            return $localized;
        }

        return static::find()
            ->id($this->id ?: false)
            ->structureId($this->structureId)
            ->siteId(['not', $this->siteId])
            ->drafts($this->getIsDraft())
            ->provisionalDrafts($this->isProvisionalDraft)
            ->revisions($this->getIsRevision());
    }

    /**
     * @inheritdoc
     */
    public function getNext($criteria = false)
    {
        if ($criteria !== false || $this->_nextElement === null) {
            return $this->_getRelativeElement($criteria, 1);
        }

        if ($this->_nextElement === false) {
            return null;
        }

        return $this->_nextElement;
    }

    /**
     * @inheritdoc
     */
    public function getPrev($criteria = false)
    {
        if ($criteria !== false || $this->_prevElement === null) {
            return $this->_getRelativeElement($criteria, -1);
        }

        if ($this->_prevElement === false) {
            return null;
        }

        return $this->_prevElement;
    }

    /**
     * @inheritdoc
     */
    public function setNext($element)
    {
        $this->_nextElement = $element;
    }

    /**
     * @inheritdoc
     */
    public function setPrev($element)
    {
        $this->_prevElement = $element;
    }

    /**
     * @inheritdoc
     */
    public function getParent()
    {
        if ($this->_parent === null) {
            $ancestors = $this->getAncestors(1);

            // Eager-loaded?
            if (is_array($ancestors)) {
                $this->_parent = reset($ancestors);
            } else {
                $this->_parent = $ancestors
                        ->anyStatus()
                        ->one()
                    ?? false;
            }
        }

        return $this->_parent ?: null;
    }

    /**
     * @inheritdoc
     */
    public function getParentUri(): ?string
    {
        $parent = $this->getParent();
        if ($parent && $parent->uri !== self::HOMEPAGE_URI) {
            return $parent->uri;
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function setParent(ElementInterface $parent = null)
    {
        $this->_parent = $parent;

        if ($parent) {
            $this->level = $parent->level + 1;
        } else {
            $this->level = 1;
        }
    }

    /**
     * @inheritdoc
     */
    public function getAncestors(int $dist = null)
    {
        // Eager-loaded?
        if (($ancestors = $this->getEagerLoadedElements('ancestors')) !== null) {
            if ($dist === null) {
                return $ancestors;
            }
            return ArrayHelper::where($ancestors, function(self $element) use ($dist) {
                return $element->level >= $this->level - $dist;
            }, true, true, false);
        }

        return static::find()
            ->structureId($this->structureId)
            ->ancestorOf($this)
            ->siteId($this->siteId)
            ->ancestorDist($dist);
    }

    /**
     * @inheritdoc
     */
    public function getDescendants(int $dist = null)
    {
        // Eager-loaded?
        if (($descendants = $this->getEagerLoadedElements('descendants')) !== null) {
            if ($dist === null) {
                return $descendants;
            }
            return ArrayHelper::where($descendants, function(self $element) use ($dist) {
                return $element->level <= $this->level + $dist;
            }, true, true, false);
        }

        return static::find()
            ->structureId($this->structureId)
            ->descendantOf($this)
            ->siteId($this->siteId)
            ->descendantDist($dist);
    }

    /**
     * @inheritdoc
     */
    public function getChildren()
    {
        // Eager-loaded?
        if (($children = $this->getEagerLoadedElements('children')) !== null) {
            return $children;
        }

        return $this->getDescendants(1);
    }

    /**
     * @inheritdoc
     */
    public function getSiblings()
    {
        return static::find()
            ->structureId($this->structureId)
            ->siblingOf($this)
            ->siteId($this->siteId);
    }

    /**
     * @inheritdoc
     */
    public function getPrevSibling()
    {
        if ($this->_prevSibling === null) {
            /** @var ElementQuery $query */
            $query = $this->_prevSibling = static::find();
            $query->structureId = $this->structureId;
            $query->prevSiblingOf = $this;
            $query->siteId = $this->siteId;
            $query->anyStatus();
            $this->_prevSibling = $query->one();

            if ($this->_prevSibling === null) {
                $this->_prevSibling = false;
            }
        }

        return $this->_prevSibling ?: null;
    }

    /**
     * @inheritdoc
     */
    public function getNextSibling()
    {
        if ($this->_nextSibling === null) {
            /** @var ElementQuery $query */
            $query = $this->_nextSibling = static::find();
            $query->structureId = $this->structureId;
            $query->nextSiblingOf = $this;
            $query->siteId = $this->siteId;
            $query->anyStatus();
            $this->_nextSibling = $query->one();

            if ($this->_nextSibling === null) {
                $this->_nextSibling = false;
            }
        }

        return $this->_nextSibling ?: null;
    }

    /**
     * @inheritdoc
     */
    public function getHasDescendants(): bool
    {
        $descendants = $this->getDescendants();
        if (is_array($descendants)) {
            return !empty($descendants);
        }
        return $descendants->exists();
    }

    /**
     * @inheritdoc
     */
    public function getTotalDescendants(): int
    {
        $descendants = $this->getDescendants();
        if (is_array($descendants)) {
            return count($descendants);
        }
        return $descendants->count();
    }

    /**
     * @inheritdoc
     */
    public function isAncestorOf(ElementInterface $element): bool
    {
        $canonical = $this->getCanonical();
        return ($canonical->root == $element->root && $canonical->lft < $element->lft && $canonical->rgt > $element->rgt);
    }

    /**
     * @inheritdoc
     */
    public function isDescendantOf(ElementInterface $element): bool
    {
        return ($this->root == $element->root && $this->lft > $element->lft && $this->rgt < $element->rgt);
    }

    /**
     * @inheritdoc
     */
    public function isParentOf(ElementInterface $element): bool
    {
        $canonical = $this->getCanonical();
        return ($canonical->root == $element->root && $canonical->level == $element->level - 1 && $canonical->isAncestorOf($element));
    }

    /**
     * @inheritdoc
     */
    public function isChildOf(ElementInterface $element): bool
    {
        return ($this->root == $element->root && $this->level == $element->level + 1 && $this->isDescendantOf($element));
    }

    /**
     * @inheritdoc
     */
    public function isSiblingOf(ElementInterface $element): bool
    {
        if ($this->root == $element->root && $this->level !== null && $this->level == $element->level) {
            if ($this->level == 1 || $this->isPrevSiblingOf($element) || $this->isNextSiblingOf($element)) {
                return true;
            }

            $parent = $this->getParent();

            if ($parent) {
                return $element->isDescendantOf($parent);
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function isPrevSiblingOf(ElementInterface $element): bool
    {
        return ($this->root == $element->root && $this->level == $element->level && $this->rgt == $element->lft - 1);
    }

    /**
     * @inheritdoc
     */
    public function isNextSiblingOf(ElementInterface $element): bool
    {
        return ($this->root == $element->root && $this->level == $element->level && $this->lft == $element->rgt + 1);
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return $offset === 'title' || $this->hasEagerLoadedElements($offset) || parent::offsetExists($offset) || $this->fieldByHandle($offset);
    }

    /**
     * @inheritdoc
     */
    public function getAttributeStatus(string $attribute)
    {
        if ($this->isAttributeModified($attribute)) {
            return [
                self::ATTR_STATUS_MODIFIED,
                Craft::t('app', 'This field has been modified.'),
            ];
        }

        if ($this->isAttributeOutdated($attribute)) {
            return [
                self::ATTR_STATUS_OUTDATED,
                Craft::t('app', 'This field was updated in the Current revision.'),
            ];
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getOutdatedAttributes(): array
    {
        return array_keys($this->_outdatedAttributes());
    }

    /**
     * @inheritdoc
     */
    public function isAttributeOutdated(string $name): bool
    {
        return isset($this->_outdatedAttributes()[$name]);
    }

    /**
     * @inheritdoc
     */
    public function getModifiedAttributes(): array
    {
        return array_keys($this->_modifiedAttributes());
    }

    /**
     * @inheritdoc
     */
    public function isAttributeModified(string $name): bool
    {
        return isset($this->_modifiedAttributes()[$name]);
    }

    /**
     * @return array The attribute names that have been modified for this element
     */
    private function _outdatedAttributes(): array
    {
        if (!static::trackChanges() || $this->getIsCanonical()) {
            return [];
        }

        if ($this->_outdatedAttributes === null) {
            $query = (new Query())
                ->select(['attribute'])
                ->from([Table::CHANGEDATTRIBUTES])
                ->where([
                    'elementId' => $this->getCanonicalId(),
                    'siteId' => $this->siteId,
                ]);

            if ($this->dateLastMerged) {
                $query->andWhere(['>=', 'dateUpdated', Db::prepareDateForDb($this->dateLastMerged)]);
            } else {
                $query->andWhere(['>=', 'dateUpdated', Db::prepareDateForDb($this->dateCreated)]);
            }

            $this->_outdatedAttributes = array_flip($query->column());
        }

        return $this->_outdatedAttributes;
    }

    /**
     * @return array The attribute names that have been modified for this element
     */
    private function _modifiedAttributes(): array
    {
        if (!static::trackChanges() || $this->getIsCanonical()) {
            return [];
        }

        if ($this->_modifiedAttributes === null) {
            $this->_modifiedAttributes = array_flip((new Query())
                ->select(['attribute'])
                ->from([Table::CHANGEDATTRIBUTES])
                ->where([
                    'elementId' => $this->id,
                    'siteId' => $this->siteId,
                ])
                ->column());
        }

        return $this->_modifiedAttributes;
    }

    /**
     * @inheritdoc
     */
    public function isAttributeDirty(string $name): bool
    {
        return $this->_allDirty() || isset($this->_dirtyAttributes[$name]);
    }

    /**
     * @inheritdoc
     */
    public function getDirtyAttributes(): array
    {
        if (static::hasTitles() && $this->title !== $this->_savedTitle) {
            $this->_dirtyAttributes['title'] = true;
        }
        return array_keys($this->_dirtyAttributes);
    }

    /**
     * @inheritdoc
     */
    public function setDirtyAttributes(array $names, bool $merge = true)
    {
        if ($merge) {
            $this->_dirtyAttributes = array_merge($this->_dirtyAttributes, array_flip($names));
        } else {
            $this->_dirtyAttributes = array_flip($names);
        }
    }

    /**
     * @inheritdoc
     */
    public function getIsTitleTranslatable(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getTitleTranslationDescription()
    {
        return ElementHelper::translationDescription(Field::TRANSLATION_METHOD_SITE);
    }

    /**
     * @inheritdoc
     */
    public function getTitleTranslationKey(): string
    {
        return ElementHelper::translationKey($this, Field::TRANSLATION_METHOD_SITE);
    }

    /**
     * @inheritdoc
     */
    public function getFieldValues(array $fieldHandles = null): array
    {
        $values = [];

        foreach ($this->fieldLayoutFields() as $field) {
            if ($fieldHandles === null || in_array($field->handle, $fieldHandles, true)) {
                $values[$field->handle] = $this->getFieldValue($field->handle);
            }
        }

        return $values;
    }

    /**
     * @inheritdoc
     */
    public function getSerializedFieldValues(array $fieldHandles = null): array
    {
        $serializedValues = [];

        foreach ($this->fieldLayoutFields() as $field) {
            if ($fieldHandles === null || in_array($field->handle, $fieldHandles, true)) {
                $value = $this->getFieldValue($field->handle);
                $serializedValues[$field->handle] = $field->serializeValue($value, $this);
            }
        }

        return $serializedValues;
    }

    /**
     * @inheritdoc
     */
    public function setFieldValues(array $values)
    {
        foreach ($values as $fieldHandle => $value) {
            $this->setFieldValue($fieldHandle, $value);
        }
    }

    /**
     * @inheritdoc
     */
    public function getFieldValue(string $fieldHandle)
    {
        // Make sure the value has been normalized
        $this->normalizeFieldValue($fieldHandle);

        return $this->getBehavior('customFields')->$fieldHandle;
    }

    /**
     * @inheritdoc
     */
    public function setFieldValue(string $fieldHandle, $value)
    {
        $behavior = $this->getBehavior('customFields');
        $behavior->$fieldHandle = $value;

        // Don't assume that $value has been normalized
        unset($this->_normalizedFieldValues[$fieldHandle]);

        // If the element is fully initialized, mark the value as dirty
        if ($this->_initialized) {
            $this->_dirtyFields[$fieldHandle] = true;
        }
    }

    /**
     * Returns the status of a given field.
     *
     * @param string $fieldHandle
     * @return array|null
     * @since 3.4.0
     * @deprecated in 3.7.0. Use [[FieldInterface::getStatus()]] instead.
     */
    public function getFieldStatus(string $fieldHandle)
    {
        if (($field = $this->fieldByHandle($fieldHandle)) !== null) {
            return $field->getStatus($this);
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getOutdatedFields(): array
    {
        return array_keys($this->_outdatedFields());
    }

    /**
     * @inheritdoc
     */
    public function isFieldOutdated(string $fieldHandle): bool
    {
        return isset($this->_outdatedFields()[$fieldHandle]);
    }

    /**
     * @inheritdoc
     */
    public function getModifiedFields(bool $anySite = false): array
    {
        return array_keys($this->_modifiedFields($anySite));
    }

    /**
     * @inheritdoc
     */
    public function isFieldModified(string $fieldHandle, bool $anySite = false): bool
    {
        return isset($this->_modifiedFields($anySite)[$fieldHandle]);
    }

    /**
     * @return array The field handles that have been modified for this element
     */
    private function _outdatedFields(): array
    {
        if (!static::trackChanges() || $this->getIsCanonical()) {
            return [];
        }

        if ($this->_outdatedFields === null) {
            $query = (new Query())
                ->select(['f.handle'])
                ->from(['f' => Table::FIELDS])
                ->innerJoin(['cf' => Table::CHANGEDFIELDS], '[[cf.fieldId]] = [[f.id]]')
                ->where([
                    'cf.elementId' => $this->getCanonicalId(),
                    'cf.siteId' => $this->siteId,
                ]);

            if ($this->dateLastMerged) {
                $query->andWhere(['>=', 'cf.dateUpdated', Db::prepareDateForDb($this->dateLastMerged)]);
            } else {
                $query->andWhere(['>=', 'cf.dateUpdated', Db::prepareDateForDb($this->dateCreated)]);
            }

            $this->_outdatedFields = array_flip($query->column());
        }

        return $this->_outdatedFields;
    }

    /**
     * @param bool $anySite
     * @return array The field handles that have been modified for this element
     */
    private function _modifiedFields(bool $anySite): array
    {
        if (!static::trackChanges() || $this->getIsCanonical()) {
            return [];
        }

        $key = $anySite ? 'any' : 'this';

        if (!isset($this->_modifiedFields[$key])) {
            $query = (new Query())
                ->select(['f.handle'])
                ->from(['f' => Table::FIELDS])
                ->innerJoin(['cf' => Table::CHANGEDFIELDS], '[[cf.fieldId]] = [[f.id]]')
                ->where(['cf.elementId' => $this->id]);

            if (!$anySite) {
                $query->andWhere(['cf.siteId' => $this->siteId]);
            }

            $this->_modifiedFields[$key] = array_flip($query->column());
        }

        return $this->_modifiedFields[$key];
    }

    /**
     * @inheritdoc
     */
    public function isFieldDirty(string $fieldHandle): bool
    {
        return $this->_allDirty() || isset($this->_dirtyFields[$fieldHandle]);
    }

    /**
     * @inheritdoc
     */
    public function getDirtyFields(): array
    {
        if ($this->_allDirty()) {
            return ArrayHelper::getColumn($this->fieldLayoutFields(), 'handle');
        }
        if ($this->_dirtyFields) {
            return array_keys($this->_dirtyFields);
        }
        return [];
    }

    /**
     * Returns whether all fields and attributes should be considered dirty.
     *
     * @return bool
     */
    private function _allDirty(): bool
    {
        return $this->_allDirty || $this->resaving;
    }

    /**
     * @inheritdoc
     */
    public function markAsDirty()
    {
        $this->_allDirty = true;
    }

    /**
     * @inheritdoc
     */
    public function markAsClean()
    {
        $this->_allDirty = false;
        $this->_dirtyAttributes = [];
        $this->_dirtyFields = null;
        if (static::hasTitles()) {
            $this->_savedTitle = $this->title;
        }
    }

    /**
     * @inheritdoc
     */
    public function setFieldValuesFromRequest(string $paramNamespace = '')
    {
        $this->setFieldParamNamespace($paramNamespace);
        $values = Craft::$app->getRequest()->getBodyParam($paramNamespace, []);

        foreach ($this->fieldLayoutFields() as $field) {
            // Do we have any post data for this field?
            if (isset($values[$field->handle])) {
                $value = $values[$field->handle];
            } elseif (!empty($this->_fieldParamNamePrefix) && UploadedFile::getInstancesByName($this->_fieldParamNamePrefix . '.' . $field->handle)) {
                // A file was uploaded for this field
                $value = null;
            } else {
                continue;
            }

            $this->setFieldValue($field->handle, $value);

            // Normalize it now in case the system language changes later
            $this->normalizeFieldValue($field->handle);
        }
    }

    /**
     * @inheritdoc
     */
    public function getFieldParamNamespace()
    {
        return $this->_fieldParamNamePrefix;
    }

    /**
     * @inheritdoc
     */
    public function setFieldParamNamespace(string $namespace)
    {
        $this->_fieldParamNamePrefix = $namespace;
    }

    /**
     * @inheritdoc
     */
    public function getContentTable(): string
    {
        return Craft::$app->getContent()->contentTable;
    }

    /**
     * @inheritdoc
     */
    public function getFieldColumnPrefix(): string
    {
        return Craft::$app->getContent()->fieldColumnPrefix;
    }

    /**
     * @inheritdoc
     */
    public function getFieldContext(): string
    {
        return Craft::$app->getContent()->fieldContext;
    }

    /**
     * @inheritdoc
     */
    public function hasEagerLoadedElements(string $handle): bool
    {
        return isset($this->_eagerLoadedElements[$handle]);
    }

    /**
     * @inheritdoc
     */
    public function getEagerLoadedElements(string $handle)
    {
        if (!isset($this->_eagerLoadedElements[$handle])) {
            return null;
        }

        /** @var ElementInterface[] $elements */
        $elements = $this->_eagerLoadedElements[$handle];
        ElementHelper::setNextPrevOnElements($elements);
        return $elements;
    }

    /**
     * @inheritdoc
     */
    public function setEagerLoadedElements(string $handle, array $elements)
    {
        switch ($handle) {
            case 'parent':
                $this->_parent = $elements[0] ?? false;
                break;
            case 'currentRevision':
                $this->_currentRevision = $elements[0] ?? false;
                break;
            case 'draftCreator':
                /** @var DraftBehavior|null $behavior */
                if ($behavior = $this->getBehavior('draft')) {
                    $behavior->setCreator($elements[0] ?? null);
                }
                break;
            case 'revisionCreator':
                /** @var RevisionBehavior|null $behavior */
                if ($behavior = $this->getBehavior('revision')) {
                    $behavior->setCreator($elements[0] ?? null);
                }
                break;
            default:
                // Give plugins a chance to store this
                $event = new SetEagerLoadedElementsEvent([
                    'handle' => $handle,
                    'elements' => $elements,
                ]);
                $this->trigger(self::EVENT_SET_EAGER_LOADED_ELEMENTS, $event);
                if (!$event->handled) {
                    // No takers. Just store it in the internal array then.
                    $this->_eagerLoadedElements[$handle] = $elements;
                }
        }
    }

    /**
     * @inheritdoc
     */
    public function getEagerLoadedElementCount(string $handle): int
    {
        return $this->_eagerLoadedElementCounts[$handle] ?? 0;
    }

    /**
     * @inheritdoc
     */
    public function setEagerLoadedElementCount(string $handle, int $count)
    {
        $this->_eagerLoadedElementCounts[$handle] = $count;
    }

    /**
     * @inheritdoc
     */
    public function getIsFresh(): bool
    {
        if ($this->hasErrors()) {
            return false;
        }

        if ($this->contentId === null) {
            return true;
        }

        if ($this->_isFresh !== null) {
            return $this->_isFresh;
        }

        return false;
    }

    /**
     * Returns whether the element is "fresh" (not yet explicitly saved, and without validation errors).
     *
     * @return bool
     * @deprecated in 3.7.14. [[getIsFresh()]] should be used instead.
     */
    public function getHasFreshContent(): bool
    {
        return $this->getIsFresh();
    }

    /**
     * @inheritdoc
     */
    public function setIsFresh(bool $isFresh = true): void
    {
        $this->_isFresh = $isFresh;
    }

    /**
     * @inheritdoc
     */
    public function setRevisionCreatorId(int $creatorId = null)
    {
        $this->revisionCreatorId = $creatorId;
    }

    /**
     * @inheritdoc
     */
    public function setRevisionNotes(string $notes = null)
    {
        $this->revisionNotes = $notes;
    }

    /**
     * @inheritdoc
     */
    public function getCurrentRevision()
    {
        if (!$this->id) {
            return null;
        }

        if ($this->_currentRevision === null) {
            $canonical = $this->getCanonical(true);
            $this->_currentRevision = static::find()
                ->revisionOf($canonical->id)
                ->dateCreated($canonical->dateUpdated)
                ->anyStatus()
                ->orderBy(['num' => SORT_DESC])
                ->one() ?: false;
        }

        return $this->_currentRevision ?: null;
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function getHtmlAttributes(string $context): array
    {
        $htmlAttributes = $this->htmlAttributes($context);

        // Give plugins a chance to modify them
        $event = new RegisterElementHtmlAttributesEvent([
            'htmlAttributes' => $htmlAttributes,
        ]);
        $this->trigger(self::EVENT_REGISTER_HTML_ATTRIBUTES, $event);

        return $event->htmlAttributes;
    }

    /**
     * Returns any attributes that should be included in the element’s DOM representation in the control panel.
     *
     * @param string $context The context that the element is being rendered in ('index', 'field', etc.)
     * @return array
     * @see getHtmlAttributes()
     */
    protected function htmlAttributes(string $context): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getTableAttributeHtml(string $attribute): string
    {
        // Give plugins a chance to set this
        $event = new SetElementTableAttributeHtmlEvent([
            'attribute' => $attribute,
        ]);
        $this->trigger(self::EVENT_SET_TABLE_ATTRIBUTE_HTML, $event);

        if ($event->html !== null) {
            return $event->html;
        }

        return $this->tableAttributeHtml($attribute);
    }

    /**
     * Returns the HTML that should be shown for a given attribute in Table View.
     *
     * This method can be used to completely customize what actually shows up within the table’s body for a given
     * attribute, rather than simply showing the attribute’s raw value.
     *
     * For example, if your elements have an `email` attribute that you want to wrap in a `mailto:` link, your
     * getTableAttributesHtml() method could do this:
     *
     * ```php
     * switch ($attribute) {
     *     case 'email':
     *         return $this->email ? Html::mailto(Html::encode($this->email)) : '';
     *     // ...
     * }
     * return parent::tableAttributeHtml($attribute);
     * ```
     *
     * ::: warning
     * All untrusted text should be passed through [[Html::encode()]] to prevent XSS attacks.
     * :::
     *
     * By default the following will be returned:
     *
     * - If the attribute name is `link` or `uri`, it will be linked to the front-end URL.
     * - If the attribute is a custom field handle, it will pass the responsibility off to the field type.
     * - If the attribute value is a [[DateTime]] object, the date will be formatted with a localized date format.
     * - For anything else, it will output the attribute value as a string.
     *
     * @param string $attribute The attribute name.
     * @return string The HTML that should be shown for a given attribute in Table View.
     * @throws InvalidConfigException
     * @see getTableAttributeHtml()
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'link':
                if (ElementHelper::isDraftOrRevision($this)) {
                    return '';
                }

                $url = $this->getUrl();

                if ($url !== null) {
                    return Html::a('', $url, [
                        'rel' => 'noopener',
                        'target' => '_blank',
                        'data-icon' => 'world',
                        'title' => Craft::t('app', 'Visit webpage'),
                    ]);
                }

                return '';

            case 'uri':
                if ($this->getIsDraft() && ElementHelper::isTempSlug($this->slug)) {
                    return '';
                }

                $url = $this->getUrl();

                if ($url !== null) {
                    if ($this->getIsHomepage()) {
                        $value = Html::tag('span', '', [
                            'data-icon' => 'home',
                            'title' => Craft::t('app', 'Homepage'),
                        ]);
                    } else {
                        // Add some <wbr> tags in there so it doesn't all have to be on one line
                        $find = ['/'];
                        $replace = ['/<wbr>'];

                        $wordSeparator = Craft::$app->getConfig()->getGeneral()->slugWordSeparator;

                        if ($wordSeparator) {
                            $find[] = $wordSeparator;
                            $replace[] = $wordSeparator . '<wbr>';
                        }

                        $value = str_replace($find, $replace, $this->uri);
                    }

                    return Html::a(Html::tag('span', $value, ['dir' => 'ltr']), $url, [
                        'href' => $url,
                        'rel' => 'noopener',
                        'target' => '_blank',
                        'class' => 'go',
                        'title' => Craft::t('app', 'Visit webpage'),
                    ]);
                }

                return '';

            case 'slug':
                if ($this->getIsDraft() && ElementHelper::isTempSlug($this->slug)) {
                    return '';
                }

                return Html::encode($this->slug);

            default:
                // Is this a custom field?
                if (preg_match('/^field:(\d+)$/', $attribute, $matches)) {
                    $fieldId = $matches[1];
                    $field = Craft::$app->getFields()->getFieldById($fieldId);

                    if ($field) {
                        if ($field instanceof PreviewableFieldInterface) {
                            // Was this field value eager-loaded?
                            if ($field instanceof EagerLoadingFieldInterface && $this->hasEagerLoadedElements($field->handle)) {
                                $value = $this->getEagerLoadedElements($field->handle);
                            } else {
                                // The field might not actually belong to this element
                                try {
                                    $value = $this->getFieldValue($field->handle);
                                } catch (InvalidFieldException $e) {
                                    return '';
                                }
                            }

                            return $field->getTableAttributeHtml($value, $this);
                        }
                    }

                    return '';
                }

                $value = $this->$attribute;

                if ($value instanceof DateTime) {
                    $formatter = Craft::$app->getFormatter();
                    return Html::tag('span', $formatter->asTimestamp($value, Locale::LENGTH_SHORT), [
                        'title' => $formatter->asDatetime($value, Locale::LENGTH_SHORT),
                    ]);
                }

                return Html::encode($value);
        }
    }

    /**
     * @inheritdoc
     */
    public function getEditorHtml(): string
    {
        if (!$this->hasFieldLayout()) {
            // No field layout, so show the meta fields here instead
            return $this->metaFieldsHtml();
        }

        // Return a placeholder for displaying the custom fields. If this is *all* that’s returned,
        // we can safely use the full field layout form render. Otherwise there may be other
        // fields intermingled by the child method, so only the custom fields should be shown.
        return '<!-- FIELD LAYOUT -->';
    }

    /**
     * @inheritdoc
     */
    public function getSidebarHtml(): string
    {
        $components = [];

        if ($this->hasFieldLayout()) {
            // The main editor body is reserved for the field layout
            $metaFieldsHtml = $this->metaFieldsHtml();
            if ($metaFieldsHtml !== '') {
                $components[] = Html::tag('div', $metaFieldsHtml, ['class' => 'meta']);
            }
        }

        if ($this->id) {
            $components[] = Cp::metadataHtml($this->getMetadata());
        }

        // Fire a defineSidebarHtml event
        $event = new DefineHtmlEvent([
            'html' => implode("\n", $components),
        ]);
        $this->trigger(self::EVENT_DEFINE_SIDEBAR_HTML, $event);
        return $event->html;
    }

    /**
     * Returns the HTML for any meta fields that should be shown within the sidebar of element editor
     * slideouts. Or if the element doesn’t have a field layout, they’ll be shown in the main body of the slideout.
     *
     * @return string
     * @since 3.7.0
     */
    protected function metaFieldsHtml(): string
    {
        // Fire a defineMetaFieldsHtml event
        $event = new DefineHtmlEvent();
        $this->trigger(self::EVENT_DEFINE_META_FIELDS_HTML, $event);
        return $event->html;
    }

    /**
     * Returns the HTML for the element’s Slug field.
     *
     * @return string
     * @since 3.7.0
     */
    protected function slugFieldHtml(): string
    {
        return Cp::textFieldHtml([
            'label' => Craft::t('app', 'Slug'),
            'siteId' => $this->siteId,
            'translationDescription' => Craft::t('app', 'This field is translated for each site.'),
            'id' => 'slug',
            'name' => 'slug',
            'autocorrect' => false,
            'autocapitalize' => false,
            'value' => $this->slug !== null && !ElementHelper::isTempSlug($this->slug) ? $this->slug : '',
            'errors' => array_merge($this->getErrors('slug'), $this->getErrors('uri')),
        ]);
    }

    /**
     * Returns whether the element has a field layout with at least one tab.
     *
     * @return bool Returns whether the element has a field layout with at least one tab.
     * @since 3.7.0
     */
    protected function hasFieldLayout(): bool
    {
        $fieldLayout = $this->getFieldLayout();
        return $fieldLayout && !empty($fieldLayout->getTabs());
    }

    /**
     * @inheritdoc
     */
    public function getMetadata(): array
    {
        $metadata = $this->metadata();

        // Fire a defineMetadata event
        $event = new DefineMetadataEvent([
            'metadata' => $metadata,
        ]);
        $this->trigger(self::EVENT_DEFINE_METADATA, $event);

        $formatter = Craft::$app->getFormatter();

        return array_merge([
            Craft::t('app', 'Status') => function() {
                if (!static::hasStatuses()) {
                    return false;
                }
                if ($this->getIsUnpublishedDraft()) {
                    $icon = Html::tag('span', '', ['data' => ['icon' => 'draft']]);
                    $label = Craft::t('app', 'Draft');
                } else {
                    $status = $this->getStatus();
                    $statusDef = static::statuses()[$status] ?? null;
                    $icon = Html::tag('span', '', ['class' => ['status', $statusDef['color'] ?? $status]]);
                    $label = $statusDef['label'] ?? $statusDef ?? ucfirst($status);
                }
                return $icon . Html::tag('span', $label);
            },
        ], $event->metadata, [
            Craft::t('app', 'Created at') => $this->dateCreated
                ? $formatter->asDatetime($this->dateCreated, Formatter::FORMAT_WIDTH_SHORT)
                : false,
            Craft::t('app', 'Updated at') => $this->dateUpdated
                ? $formatter->asDatetime($this->dateUpdated, Formatter::FORMAT_WIDTH_SHORT)
                : false,
            Craft::t('app', 'Notes') => function() {
                if ($this->getIsRevision()) {
                    $revision = $this;
                } elseif ($this->getIsCanonical() || $this->isProvisionalDraft) {
                    $element = $this->getCanonical(true);
                    $revision = $element->getCurrentRevision();
                }
                if (!isset($revision)) {
                    return false;
                }
                /** @var RevisionBehavior $behavior */
                $behavior = $revision->getBehavior('revision');
                return $behavior->revisionNotes ?: false;
            },
        ]);
    }

    /**
     * Returns element metadata that can be shown on its edit page or within element editor slideouts.
     *
     * @return array The data, with keys representing the labels. The values can either be strings or callables.
     * If a value is `false`, it will be omitted.
     * @since 3.7.0
     */
    protected function metadata(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getGqlTypeName(): string
    {
        // Default to the same type
        return static::gqlTypeNameByContext(null);
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function beforeSave(bool $isNew): bool
    {
        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
            if (!$field->beforeElementSave($this, $isNew)) {
                return false;
            }
        }

        // Trigger a 'beforeSave' event
        $event = new ModelEvent([
            'isNew' => $isNew,
        ]);
        $this->trigger(self::EVENT_BEFORE_SAVE, $event);

        return $event->isValid;
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew)
    {
        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
            $field->afterElementSave($this, $isNew);
        }

        // Trigger an 'afterSave' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE)) {
            $this->trigger(self::EVENT_AFTER_SAVE, new ModelEvent([
                'isNew' => $isNew,
            ]));
        }
    }

    /**
     * @inheritdoc
     */
    public function afterPropagate(bool $isNew)
    {
        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
            $field->afterElementPropagate($this, $isNew);
        }

        // Trigger an 'afterPropagate' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_PROPAGATE)) {
            $this->trigger(self::EVENT_AFTER_PROPAGATE, new ModelEvent([
                'isNew' => $isNew,
            ]));
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete(): bool
    {
        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
            if (!$field->beforeElementDelete($this)) {
                return false;
            }
        }

        // Trigger a 'beforeDelete' event
        $event = new ModelEvent();
        $this->trigger(self::EVENT_BEFORE_DELETE, $event);

        return $event->isValid;
    }

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
            $field->afterElementDelete($this);
        }

        // Trigger an 'afterDelete' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE)) {
            $this->trigger(self::EVENT_AFTER_DELETE);
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeRestore(): bool
    {
        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
            if (!$field->beforeElementRestore($this)) {
                return false;
            }
        }

        // Trigger a 'beforeRestore' event
        $event = new ModelEvent();
        $this->trigger(self::EVENT_BEFORE_RESTORE, $event);

        return $event->isValid;
    }

    /**
     * @inheritdoc
     */
    public function afterRestore()
    {
        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
            $field->afterElementRestore($this);
        }

        // Trigger an 'afterRestore' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_RESTORE)) {
            $this->trigger(self::EVENT_AFTER_RESTORE);
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeMoveInStructure(int $structureId): bool
    {
        // Trigger a 'beforeMoveInStructure' event
        $event = new ElementStructureEvent([
            'structureId' => $structureId,
        ]);
        $this->trigger(self::EVENT_BEFORE_MOVE_IN_STRUCTURE, $event);

        return $event->isValid;
    }

    /**
     * @inheritdoc
     */
    public function afterMoveInStructure(int $structureId)
    {
        // Trigger an 'afterMoveInStructure' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_MOVE_IN_STRUCTURE)) {
            $this->trigger(self::EVENT_AFTER_MOVE_IN_STRUCTURE, new ElementStructureEvent([
                'structureId' => $structureId,
            ]));
        }

        // Invalidate caches for this element
        Craft::$app->getElements()->invalidateCachesForElement($this);
    }

    /**
     * Normalizes a field’s value.
     *
     * @param string $fieldHandle The field handle
     * @throws InvalidFieldException if the element doesn’t have a field with the handle specified by `$fieldHandle`
     */
    protected function normalizeFieldValue(string $fieldHandle)
    {
        // Have we already normalized this value?
        if (isset($this->_normalizedFieldValues[$fieldHandle])) {
            return;
        }

        $field = $this->fieldByHandle($fieldHandle);

        if (!$field) {
            throw new InvalidFieldException($fieldHandle);
        }

        $behavior = $this->getBehavior('customFields');
        $behavior->$fieldHandle = $field->normalizeValue($behavior->$fieldHandle, $this);
        $this->_normalizedFieldValues[$fieldHandle] = true;
    }

    /**
     * Finds Element instance(s) by the given condition.
     *
     * This method is internally called by [[findOne()]] and [[findAll()]].
     *
     * @param mixed $criteria Refer to [[findOne()]] and [[findAll()]] for the explanation of this parameter
     * @param bool $one Whether this method is called by [[findOne()]] or [[findAll()]]
     * @return static|static[]|null
     */
    protected static function findByCondition($criteria, bool $one)
    {
        /** @var ElementQueryInterface $query */
        $query = static::find();

        if ($criteria !== null) {
            if (!ArrayHelper::isAssociative($criteria)) {
                $criteria = ['id' => $criteria];
            }
            Craft::configure($query, $criteria);
        }

        if ($one) {
            $result = $query->one();
        } else {
            $result = $query->all();
        }

        return $result;
    }

    /**
     * Returns the field with a given handle.
     *
     * @param string $handle
     * @return FieldInterface|null
     */
    protected function fieldByHandle(string $handle)
    {
        if ($this->_fieldsByHandle !== null && array_key_exists($handle, $this->_fieldsByHandle)) {
            return $this->_fieldsByHandle[$handle];
        }

        $contentService = Craft::$app->getContent();
        $originalFieldContext = $contentService->fieldContext;
        $contentService->fieldContext = $this->getFieldContext();
        $fieldLayout = $this->getFieldLayout();
        $this->_fieldsByHandle[$handle] = $fieldLayout ? $fieldLayout->getFieldByHandle($handle) : null;
        $contentService->fieldContext = $originalFieldContext;

        return $this->_fieldsByHandle[$handle];
    }

    /**
     * Returns each of this element’s fields.
     *
     * @return FieldInterface[] This element’s fields
     */
    protected function fieldLayoutFields(): array
    {
        $fieldLayout = $this->getFieldLayout();

        if ($fieldLayout) {
            return $fieldLayout->getFields();
        }

        return [];
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException if [[siteId]] is invalid
     */
    public function getSite(): Site
    {
        if ($this->siteId !== null) {
            $site = Craft::$app->getSites()->getSiteById($this->siteId, true);
        }

        if (empty($site)) {
            throw new InvalidConfigException('Invalid site ID: ' . $this->siteId);
        }

        return $site;
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getLanguage(): string
    {
        return $this->getSite()->language;
    }

    /**
     * Returns an element right before/after this one, from a given set of criteria.
     *
     * @param mixed $criteria
     * @param int $dir
     * @return ElementInterface|null
     */
    private function _getRelativeElement($criteria, int $dir)
    {
        if ($this->id === null) {
            return null;
        }

        if ($criteria instanceof ElementQueryInterface) {
            /** @var ElementQuery $criteria */
            $query = clone $criteria;
        } else {
            $query = static::find()
                ->siteId($this->siteId);

            if ($criteria) {
                Craft::configure($query, $criteria);
            }
        }

        /** @var ElementQuery $query */
        $elementIds = $query->ids();
        $key = array_search($this->getCanonicalId(), $elementIds, false);

        if ($key === false || !isset($elementIds[$key + $dir])) {
            return null;
        }

        return $query
            ->id($elementIds[$key + $dir])
            ->one();
    }
}
