<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use ArrayIterator;
use Craft;
use craft\behaviors\CustomFieldBehavior;
use craft\behaviors\DraftBehavior;
use craft\behaviors\RevisionBehavior;
use craft\cache\ElementQueryTagDependency;
use craft\controllers\ElementsController;
use craft\db\CoalesceColumnsExpression;
use craft\db\Command;
use craft\db\Connection;
use craft\db\ExcludeDescendantIdsExpression;
use craft\db\Query;
use craft\db\Table;
use craft\elements\actions\Delete;
use craft\elements\actions\DeleteActionInterface;
use craft\elements\actions\Duplicate;
use craft\elements\actions\Edit;
use craft\elements\actions\SetStatus;
use craft\elements\actions\View as ViewAction;
use craft\elements\conditions\ElementCondition;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\ContentBlock;
use craft\elements\db\EagerLoadPlan;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\NestedElementQueryInterface;
use craft\elements\ElementCollection;
use craft\elements\Entry;
use craft\elements\exporters\Expanded;
use craft\elements\exporters\Raw;
use craft\elements\User;
use craft\enums\AttributeStatus;
use craft\enums\Color;
use craft\errors\FieldNotFoundException;
use craft\errors\InvalidFieldException;
use craft\events\AuthorizationCheckEvent;
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
use craft\fieldlayoutelements\BaseField;
use craft\fieldlayoutelements\CustomField;
use craft\gql\interfaces\Element as ElementGqlType;
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
use craft\models\FieldLayout;
use craft\models\Site;
use craft\validators\DateTimeValidator;
use craft\validators\ElementUriValidator;
use craft\validators\SiteIdValidator;
use craft\validators\SlugValidator;
use craft\validators\StringValidator;
use craft\web\UploadedFile;
use craft\web\View;
use DateTime;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use ReflectionClass;
use Throwable;
use Traversable;
use Twig\Markup;
use UnitEnum;
use yii\base\ArrayableTrait;
use yii\base\ErrorHandler;
use yii\base\Event;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\base\UnknownPropertyException;
use yii\db\Expression;
use yii\db\ExpressionInterface;
use yii\validators\BooleanValidator;
use yii\validators\RequiredValidator;
use yii\validators\Validator;
use yii\web\Response;

/**
 * Element is the base class for classes representing elements in terms of objects.
 *
 * @mixin CustomFieldBehavior
 * @property int|null $canonicalId The element’s canonical ID
 * @property-read string $canonicalUid The element’s canonical UID
 * @property-read bool $isCanonical Whether this is the canonical element
 * @property-read bool $isDerivative Whether this is a derivative element, such as a draft or revision
 * @property ElementQueryInterface $ancestors The element’s ancestors
 * @property ElementQueryInterface $children The element’s children
 * @property string|null $cpEditUrl The element’s edit URL in the control panel
 * @property ElementQueryInterface $descendants The element’s descendants
 * @property string $editorHtml The HTML for the element’s editor HUD
 * @property bool $enabledForSite Whether the element is enabled for this site
 * @property string $fieldContext The field context this element’s content uses
 * @property FieldLayout|null $fieldLayout The field layout used by this element
 * @property array $fieldParamNamespace The namespace used by custom field params on the request
 * @property array $fieldValues The element’s normalized custom field values, indexed by their handles
 * @property bool $hasDescendants Whether the element has descendants
 * @property array $htmlAttributes Any attributes that should be included in the element’s DOM representation in the control panel
 * @property Markup|null $link An anchor pre-filled with this element’s URL and title
 * @property ElementInterface|null $canonical The canonical element, if one exists for the current site
 * @property ElementInterface|null $next The next element relative to this one, from a given set of criteria
 * @property ElementInterface|null $nextSibling The element’s next sibling
 * @property ElementInterface|null $parent The element’s parent
 * @property int|null $parentId The element’s parent’s ID
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
 * @phpstan-import-type EagerLoadingMap from ElementInterface
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
abstract class Element extends Component implements ElementInterface
{
    use ElementTrait;
    use ArrayableTrait {
        toArray as traitToArray;
    }

    /**
     * @since 3.3.6
     */
    public const HOMEPAGE_URI = '__home__';

    // Statuses
    // -------------------------------------------------------------------------

    public const STATUS_ENABLED = 'enabled';
    public const STATUS_DISABLED = 'disabled';
    public const STATUS_ARCHIVED = 'archived';
    /** @since 5.0.0 */
    public const STATUS_DRAFT = 'draft';

    // Validation scenarios
    // -------------------------------------------------------------------------

    public const SCENARIO_ESSENTIALS = 'essentials';
    public const SCENARIO_LIVE = 'live';

    // Events
    // -------------------------------------------------------------------------

    /**
     * @event RegisterElementSourcesEvent The event that is triggered when registering the available sources for the element type.
     */
    public const EVENT_REGISTER_SOURCES = 'registerSources';

    /**
     * @event RegisterElementFieldLayoutsEvent The event that is triggered when registering all of the field layouts
     * associated with elements from a given source.
     * @see fieldLayouts()
     * @since 3.5.0
     */
    public const EVENT_REGISTER_FIELD_LAYOUTS = 'registerFieldLayouts';

    /**
     * @event RegisterElementActionsEvent The event that is triggered when registering the available bulk actions for the element type.
     */
    public const EVENT_REGISTER_ACTIONS = 'registerActions';

    /**
     * @event RegisterElementExportersEvent The event that is triggered when registering the available exporters for the element type.
     * @since 3.4.0
     */
    public const EVENT_REGISTER_EXPORTERS = 'registerExporters';

    /**
     * @event RegisterElementSearchableAttributesEvent The event that is triggered when registering the searchable attributes for the element type.
     */
    public const EVENT_REGISTER_SEARCHABLE_ATTRIBUTES = 'registerSearchableAttributes';

    /**
     * @event RegisterElementSortOptionsEvent The event that is triggered when registering the sort options for the element type.
     */
    public const EVENT_REGISTER_SORT_OPTIONS = 'registerSortOptions';

    /**
     * @event RegisterElementTableAttributesEvent The event that is triggered when registering the table attributes for the element type.
     */
    public const EVENT_REGISTER_TABLE_ATTRIBUTES = 'registerTableAttributes';

    /**
     * @event RegisterElementTableAttributesEvent The event that is triggered when registering the table attributes for the element type.
     */
    public const EVENT_REGISTER_DEFAULT_TABLE_ATTRIBUTES = 'registerDefaultTableAttributes';

    /**
     * @event ElementIndexTableAttributeEvent The event that is triggered when preparing an element query for an element index, for each
     * attribute present in the table.
     *
     * Paired with [[EVENT_REGISTER_TABLE_ATTRIBUTES]] and [[EVENT_DEFINE_ATTRIBUTE_HTML]], this allows optimization of queries on element indexes.
     *
     * ```php
     * use craft\base\Element;
     * use craft\elements\Entry;
     * use craft\events\DefineAttributeHtmlEvent;
     * use craft\events\ElementIndexTableAttributeEvent;
     * use craft\events\RegisterElementTableAttributesEvent;
     * use craft\helpers\Cp;
     * use yii\base\Event;
     *
     * Event::on(
     *     Entry::class,
     *     Element::EVENT_REGISTER_TABLE_ATTRIBUTES,
     *     function(RegisterElementTableAttributesEvent $e) {
     *         $e->tableAttributes['authorExpertise'] = ['label' => 'Author Expertise'];
     *     }
     * );
     *
     * Event::on(
     *     Entry::class,
     *     Element::EVENT_PREP_QUERY_FOR_TABLE_ATTRIBUTE,
     *     function(ElementIndexTableAttributeEvent $e) {
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
     *     Element::EVENT_DEFINE_ATTRIBUTE_HTML,
     *     function(DefineAttributeHtmlEvent $e) {
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
    public const EVENT_PREP_QUERY_FOR_TABLE_ATTRIBUTE = 'prepQueryForTableAttribute';


    /**
     * @event RegisterElementCardAttributesEvent The event that is triggered when registering the card attributes for the element type.
     * @since 5.5.0
     */
    public const EVENT_REGISTER_CARD_ATTRIBUTES = 'registerCardAttributes';

    /**
     * @event RegisterElementCardAttributesEvent The event that is triggered when registering the card attributes for the element type.
     * @since 5.5.0
     */
    public const EVENT_REGISTER_DEFAULT_CARD_ATTRIBUTES = 'registerDefaultCardAttributes';

    /**
     * @event DefineEagerLoadingMapEvent The event that is triggered when defining an eager-loading map.
     *
     * ```php
     * use craft\base\Element;
     * use craft\base\ElementInterface;
     * use craft\db\Query;
     * use craft\elements\Entry;
     * use craft\events\DefineEagerLoadingMapEvent;
     * use yii\base\Event;
     *
     * // Add support for `with(['bookClub'])` to entries
     * Event::on(
     *     Entry::class,
     *     Element::EVENT_DEFINE_EAGER_LOADING_MAP,
     *     function(DefineEagerLoadingMapEvent $event) {
     *         if ($event->handle === 'bookClub') {
     *             $bookEntryIds = array_map(fn(ElementInterface $element) => $element->id, $event->elements);
     *             $event->elementType = \my\plugin\BookClub::class,
     *             $event->map = (new Query)
     *                 ->select(['source' => 'bookId', 'target' => 'clubId'])
     *                 ->from('{{%bookclub_books}}')
     *                 ->where(['bookId' => $bookEntryIds])
     *                 ->all();
     *             $event->handled = true;
     *         }
     *     }
     * );
     * ```
     *
     * @since 3.1.0
     */
    public const EVENT_DEFINE_EAGER_LOADING_MAP = 'defineEagerLoadingMap';

    /**
     * @event SetEagerLoadedElementsEvent The event that is triggered when setting eager-loaded elements.
     *
     * Set [[Event::$handled]] to `true` to prevent the elements from getting stored to the private
     * `$_eagerLoadedElements` array.
     *
     * @since 3.5.0
     */
    public const EVENT_SET_EAGER_LOADED_ELEMENTS = 'setEagerLoadedElements';

    /**
     * @event RegisterPreviewTargetsEvent The event that is triggered when registering the element’s preview targets.
     * @since 3.2.0
     */
    public const EVENT_REGISTER_PREVIEW_TARGETS = 'registerPreviewTargets';

    /**
     * @event DefineAttributeHtmlEvent The event that is triggered when defining an attribute’s HTML for table and card views.
     * @see getAttributeHtml()
     * @since 5.0.0
     */
    public const EVENT_DEFINE_ATTRIBUTE_HTML = 'defineAttributeHtml';

    /**
     * @event DefineAttributeHtmlEvent The event that is triggered when defining an attribute’s inline input HTML.
     * @see getInlineAttributeInputHtml()
     * @since 5.0.0
     */
    public const EVENT_DEFINE_INLINE_ATTRIBUTE_INPUT_HTML = 'defineInlineAttributeInputHtml';

    /**
     * @event RegisterElementHtmlAttributesEvent The event that is triggered when registering the HTML attributes that should be included in the element’s DOM representation in the control panel.
     */
    public const EVENT_REGISTER_HTML_ATTRIBUTES = 'registerHtmlAttributes';

    /**
     * @event DefineHtmlEvent The event that is triggered when defining additional buttons that should be shown at the top of the element’s edit page.
     * @see getAdditionalButtons()
     * @since 4.0.0
     */
    public const EVENT_DEFINE_ADDITIONAL_BUTTONS = 'defineAdditionalButtons';

    /**
     * @event DefineAltActionsEvent The event that is triggered when defining alternative form actions for the element.
     * @see getAltActions()
     * @since 5.6.0
     */
    public const EVENT_DEFINE_ALT_ACTIONS = 'defineAltActions';

    /**
     * @event DefineMenuItemsEvent The event that is triggered when defining action menu items..
     * @see getActionMenuItems()
     * @since 5.0.0
     */
    public const EVENT_DEFINE_ACTION_MENU_ITEMS = 'defineActionMenuItems';

    /**
     * @event DefineHtmlEvent The event that is triggered when defining the HTML for the editor sidebar.
     * @see getSidebarHtml()
     * @since 3.7.0
     */
    public const EVENT_DEFINE_SIDEBAR_HTML = 'defineSidebarHtml';

    /**
     * @event DefineHtmlEvent The event that is triggered when defining the HTML for meta fields within the editor sidebar.
     * @see metaFieldsHtml()
     * @since 3.7.0
     */
    public const EVENT_DEFINE_META_FIELDS_HTML = 'defineMetaFieldsHtml';

    /**
     * @event DefineMetadataEvent The event that is triggered when defining the element’s metadata info.
     * @see getMetadata()
     * @since 3.7.0
     */
    public const EVENT_DEFINE_METADATA = 'defineMetadata';

    /**
     * @event AuthorizationCheckEvent The event that is triggered when determining whether a user is authorized to view the element’s edit page.
     *
     * To authorize the user, set [[AuthorizationCheckEvent::$authorized]] to `true`.
     *
     * ```php
     * Event::on(
     *     Entry::class,
     *     Element::EVENT_AUTHORIZE_VIEW,
     *     function(AuthorizationCheckEvent $event) {
     *         $event->authorized = true;
     *     }
     * );
     * ```
     *
     * @see canView()
     * @since 4.0.0
     * @deprecated in 4.3.0. [[\craft\services\Elements::EVENT_AUTHORIZE_VIEW]] should be used instead.
     */
    public const EVENT_AUTHORIZE_VIEW = 'authorizeView';

    /**
     * @event AuthorizationCheckEvent The event that is triggered when determining whether a user is authorized to save the element in its current state.
     *
     * To authorize the user, set [[AuthorizationCheckEvent::$authorized]] to `true`.
     *
     * ```php
     * Event::on(
     *     Entry::class,
     *     Element::EVENT_AUTHORIZE_SAVE,
     *     function(AuthorizationCheckEvent $event) {
     *         $event->authorized = true;
     *     }
     * );
     * ```
     *
     * @see canSave()
     * @since 4.0.0
     * @deprecated in 4.3.0. [[\craft\services\Elements::EVENT_AUTHORIZE_SAVE]] should be used instead.
     */
    public const EVENT_AUTHORIZE_SAVE = 'authorizeSave';

    /**
     * @event AuthorizationCheckEvent The event that is triggered when determining whether a user is authorized to create drafts for the element.
     *
     * To authorize the user, set [[AuthorizationCheckEvent::$authorized]] to `true`.
     *
     * ```php
     * Event::on(
     *     Entry::class,
     *     Element::EVENT_AUTHORIZE_CREATE_DRAFTS,
     *     function(AuthorizationCheckEvent $event) {
     *         $event->authorized = true;
     *     }
     * );
     * ```
     *
     * @see canCreateDrafts()
     * @since 4.0.0
     * @deprecated in 4.3.0. [[\craft\services\Elements::EVENT_AUTHORIZE_CREATE_DRAFTS]] should be used instead.
     */
    public const EVENT_AUTHORIZE_CREATE_DRAFTS = 'authorizeCreateDrafts';

    /**
     * @event AuthorizationCheckEvent The event that is triggered when determining whether a user is authorized to duplicate the element.
     *
     * To authorize the user, set [[AuthorizationCheckEvent::$authorized]] to `true`.
     *
     * ```php
     * Event::on(
     *     Entry::class,
     *     Element::EVENT_AUTHORIZE_DUPLICATE,
     *     function(AuthorizationCheckEvent $event) {
     *         $event->authorized = true;
     *     }
     * );
     * ```
     *
     * @see canDuplicate()
     * @since 4.0.0
     * @deprecated in 4.3.0. [[\craft\services\Elements::EVENT_AUTHORIZE_DUPLICATE]] should be used instead.
     */
    public const EVENT_AUTHORIZE_DUPLICATE = 'authorizeDuplicate';

    /**
     * @event AuthorizationCheckEvent The event that is triggered when determining whether a user is authorized to delete the element.
     *
     * To authorize the user, set [[AuthorizationCheckEvent::$authorized]] to `true`.
     *
     * ```php
     * Event::on(
     *     Entry::class,
     *     Element::EVENT_AUTHORIZE_DELETE,
     *     function(AuthorizationCheckEvent $event) {
     *         $event->authorized = true;
     *     }
     * );
     * ```
     *
     * @see canDelete()
     * @since 4.0.0
     * @deprecated in 4.3.0. [[\craft\services\Elements::EVENT_AUTHORIZE_DELETE]] should be used instead.
     */
    public const EVENT_AUTHORIZE_DELETE = 'authorizeDelete';

    /**
     * @event AuthorizationCheckEvent The event that is triggered when determining whether a user is authorized to delete the element for its current site.
     *
     * To authorize the user, set [[AuthorizationCheckEvent::$authorized]] to `true`.
     *
     * ```php
     * Event::on(
     *     Entry::class,
     *     Element::EVENT_AUTHORIZE_DELETE_FOR_SITE,
     *     function(AuthorizationCheckEvent $event) {
     *         $event->authorized = true;
     *     }
     * );
     * ```
     *
     * @see canDeleteForSite()
     * @since 4.0.0
     * @deprecated in 4.3.0. [[\craft\services\Elements::EVENT_AUTHORIZE_DELETE_FOR_SITE]] should be used instead.
     */
    public const EVENT_AUTHORIZE_DELETE_FOR_SITE = 'authorizeDeleteForSite';

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
    public const EVENT_SET_ROUTE = 'setRoute';

    /**
     * @event DefineValueEvent The event that is triggered when defining the cache tags that should be cleared when
     * this element is saved.
     * @see getCacheTags()
     * @since 4.1.0
     */
    public const EVENT_DEFINE_CACHE_TAGS = 'defineCacheTags';

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
    public const EVENT_DEFINE_KEYWORDS = 'defineKeywords';

    /**
     * @event DefineUrlEvent The event that is triggered before defining the element’s URL.
     *
     * It can be used to provide a custom URL, completely bypassing the default URL generation.
     *
     * ```php
     * use craft\base\Element;
     * use craft\elements\Entry;
     * use craft\events\DefineUrlEvent;
     * use craft\helpers\UrlHelper;
     * use yii\base\Event;
     *
     * Event::on(
     *     Entry::class,
     *     Element::EVENT_BEFORE_DEFINE_URL,
     *     function(DefineUrlEvent $e
     * ) {
     *     // @var Entry $entry
     *     $entry = $e->sender;
     *
     *     $event->url = '...';
     * });
     * ```
     *
     * To prevent the element from getting a URL, ensure `$event->url` is set to `null`,
     * and set `$event->handled` to `true`.
     *
     * Note that [[EVENT_DEFINE_URL]] will still be called regardless of what happens with this event.
     *
     * @see getUrl()
     * @since 4.4.6
     */
    public const EVENT_BEFORE_DEFINE_URL = 'beforeDefineUrl';

    /**
     * @event DefineUrlEvent The event that is triggered when defining the element’s URL.
     *
     * ```php
     * use craft\base\Element;
     * use craft\elements\Entry;
     * use craft\events\DefineUrlEvent;
     * use craft\helpers\UrlHelper;
     * use yii\base\Event;
     *
     * Event::on(
     *     Entry::class,
     *     Element::EVENT_DEFINE_URL,
     *     function(DefineUrlEvent $e
     * ) {
     *     // @var Entry $entry
     *     $entry = $e->sender;
     *
     *     // Add a custom query string param to the URL
     *     if ($event->value !== null) {
     *         $event->url = UrlHelper::urlWithParams($event->url, [
     *             'foo' => 'bar',
     *         ]);
     *     }
     * });
     * ```
     *
     * To prevent the element from getting a URL, ensure `$event->url` is set to `null`,
     * and set `$event->handled` to `true`.
     *
     * @see getUrl()
     * @since 4.3.0
     */
    public const EVENT_DEFINE_URL = 'defineUrl';

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
    public const EVENT_BEFORE_SAVE = 'beforeSave';

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
    public const EVENT_AFTER_SAVE = 'afterSave';

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
    public const EVENT_AFTER_PROPAGATE = 'afterPropagate';

    /**
     * @event ModelEvent The event that is triggered before the element is deleted.
     *
     * You may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the element from getting deleted.
     */
    public const EVENT_BEFORE_DELETE = 'beforeDelete';

    /**
     * @event \yii\base\Event The event that is triggered after the element is deleted.
     */
    public const EVENT_AFTER_DELETE = 'afterDelete';

    /**
     * @event ModelEvent The event that is triggered before the element is restored.
     *
     * You may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the element from getting restored.
     * @since 3.1.0
     */
    public const EVENT_BEFORE_RESTORE = 'beforeRestore';

    /**
     * @event \yii\base\Event The event that is triggered after the element is restored.
     * @since 3.1.0
     */
    public const EVENT_AFTER_RESTORE = 'afterRestore';

    /**
     * @event ElementStructureEvent The event that is triggered before the element is moved in a structure.
     *
     * You may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the element from getting moved.
     *
     * @deprecated in 4.5.0. [[\craft\services\Structures::EVENT_BEFORE_INSERT_ELEMENT]] or
     * [[\craft\services\Structures::EVENT_BEFORE_MOVE_ELEMENT|EVENT_BEFORE_MOVE_ELEMENT]]
     * should be used instead.
     */
    public const EVENT_BEFORE_MOVE_IN_STRUCTURE = 'beforeMoveInStructure';

    /**
     * @event ElementStructureEvent The event that is triggered after the element is moved in a structure.
     * @deprecated in 4.5.0. [[\craft\services\Structures::EVENT_AFTER_INSERT_ELEMENT]] or
     * [[\craft\services\Structures::EVENT_AFTER_MOVE_ELEMENT|EVENT_AFTER_MOVE_ELEMENT]]
     * should be used instead.
     */
    public const EVENT_AFTER_MOVE_IN_STRUCTURE = 'afterMoveInStructure';

    /**
     * @event RenderElementEvent The event that is triggered before an element is rendered.
     * @since 5.7.5
     *
     * ```php
     * use craft\base\Element;
     * use craft\events\RenderElementEvent;
     * use yii\base\Event;
     *
     * Event::on(
     *     Element::class,
     *     Element::EVENT_RENDER,
     *     function(RenderElementEvent $event) {
     *         $event->output = '…';
     *     }
     * );
     * ```
     */
    public const EVENT_RENDER = 'render';

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
    public static function refHandle(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public static function hasDrafts(): bool
    {
        return false;
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
    public static function hasTitles(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasThumbs(): bool
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
    public static function findOne(mixed $criteria = null): ?static
    {
        return static::findByCondition($criteria, true);
    }

    /**
     * @inheritdoc
     */
    public static function findAll(mixed $criteria = null): array
    {
        return static::findByCondition($criteria, false);
    }

    /**
     * @interitdoc
     */
    public static function get(int|string $id): ?static
    {
        return static::find()
            ->id($id)
            ->fixedOrder()
            ->drafts(null)
            ->provisionalDrafts(null)
            ->revisions(null)
            ->status(null)
            ->one();
    }

    /**
     * @inheritdoc
     */
    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(ElementCondition::class, [static::class]);
    }

    /**
     * @inheritdoc
     */
    public static function sources(string $context): array
    {
        $sources = static::defineSources($context);

        // Fire a 'registerSources' event
        if (Event::hasHandlers(static::class, self::EVENT_REGISTER_SOURCES)) {
            $event = new RegisterElementSourcesEvent([
                'context' => $context,
                'sources' => $sources,
            ]);
            Event::trigger(static::class, self::EVENT_REGISTER_SOURCES, $event);
            return $event->sources;
        }

        return $sources;
    }

    /**
     * Defines the sources that elements of this type may belong to.
     *
     * @param string $context The context ('index', 'modal', 'field', or 'settings').
     * @return array The sources.
     * @see sources()
     */
    protected static function defineSources(string $context): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function findSource(string $sourceKey, ?string $context): ?array
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public static function sourcePath(string $sourceKey, string $stepKey, ?string $context): ?array
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public static function modifyCustomSource(array $config): array
    {
        return $config;
    }

    /**
     * @inheritdoc
     */
    public static function fieldLayouts(?string $source): array
    {
        $fieldLayouts = static::defineFieldLayouts($source);

        // Fire a 'registerFieldLayouts' event
        if (Event::hasHandlers(static::class, self::EVENT_REGISTER_FIELD_LAYOUTS)) {
            $event = new RegisterElementFieldLayoutsEvent([
                'source' => $source,
                'fieldLayouts' => $fieldLayouts,
            ]);
            Event::trigger(static::class, self::EVENT_REGISTER_FIELD_LAYOUTS, $event);
            return $event->fieldLayouts;
        }

        return $fieldLayouts;
    }

    /**
     * Defines the field layouts associated with elements for a given source.
     *
     * @param string|null $source The selected source’s key, or `null` if all known field layouts should be returned
     * @return FieldLayout[] The associated field layouts
     * @see fieldLayouts()
     * @since 3.5.0
     */
    protected static function defineFieldLayouts(?string $source): array
    {
        // Default to all the field layouts associated with this element type
        return Craft::$app->getFields()->getLayoutsByType(static::class);
    }

    /**
     * @inheritdoc
     */
    public static function actions(string $source): array
    {
        $actions = Collection::make(static::defineActions($source));

        $hasActionType = fn(string $type) => $actions->contains(
            fn($action) => (
                $action === $type ||
                $action instanceof $type ||
                is_subclass_of($action, $type) ||
                (
                    is_array($action) &&
                    isset($action['type']) &&
                    ($action['type'] === $type || is_subclass_of($action['type'], $type))
                )
            )
        );

        // Prepend Duplicate?
        if (!$hasActionType(Duplicate::class)) {
            $actions->prepend(Duplicate::class);
        }

        // Prepend Edit?
        if (!$hasActionType(Edit::class)) {
            $actions->prepend([
                'type' => Edit::class,
                'label' => StringHelper::upperCaseFirst(Craft::t('app', 'Edit {type}', [
                    'type' => static::lowerDisplayName(),
                ])),
            ]);
        }

        // Prepend View?
        if (static::hasUris() && !$hasActionType(ViewAction::class)) {
            $actions->prepend([
                'type' => ViewAction::class,
                'label' => StringHelper::upperCaseFirst(Craft::t('app', 'View {type}', [
                    'type' => static::lowerDisplayName(),
                ])),
            ]);
        }

        // Prepend Set Status?
        if (static::includeSetStatusAction() && !$hasActionType(SetStatus::class)) {
            $actions->prepend(SetStatus::class);
        }

        // Append Delete?
        if (!$hasActionType(DeleteActionInterface::class)) {
            $actions->push(Delete::class);
        }

        $actions = $actions->all();

        // Fire a 'registerActions' event
        if (Event::hasHandlers(static::class, self::EVENT_REGISTER_ACTIONS)) {
            $event = new RegisterElementActionsEvent([
                'source' => $source,
                'actions' => $actions,
            ]);
            Event::trigger(static::class, self::EVENT_REGISTER_ACTIONS, $event);
            return $event->actions;
        }

        return $actions;
    }

    /**
     * Returns whether the Set Status action should be included in [[actions()]] automatically.
     *
     * @return bool
     * @since 4.3.2
     */
    protected static function includeSetStatusAction(): bool
    {
        return false;
    }

    /**
     * Defines the available bulk element actions for a given source.
     *
     * @param string $source The selected source’s key, if any.
     * @return array The available bulk element actions.
     * @see actions()
     */
    protected static function defineActions(string $source): array
    {
        return [];
    }

    /**
     * @inheritdoc
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

        // Fire a 'registerSearchableAttributes' event
        if (Event::hasHandlers(static::class, self::EVENT_REGISTER_SEARCHABLE_ATTRIBUTES)) {
            $event = new RegisterElementSearchableAttributesEvent(['attributes' => $attributes]);
            Event::trigger(static::class, self::EVENT_REGISTER_SEARCHABLE_ATTRIBUTES, $event);
            return $event->attributes;
        }

        return $attributes;
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

    /**
     * @inheritdoc
     */
    public static function baseBulkDuplicateAttributes(): array
    {
        $attributes = [
            'structureId' => null,
            'root' => null,
            'lft' => null,
            'rgt' => null,
            'level' => null,
        ];

        if (is_subclass_of(static::class, NestedElementInterface::class)) {
            $attributes += [
                'fieldId' => null,
                'ownerId' => null,
                'primaryOwnerId' => null,
                'sortOrder' => null,
            ];
        }

        return $attributes;
    }

    // Element index methods
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public static function indexHtml(
        ElementQueryInterface $elementQuery,
        ?array $disabledElementIds,
        array $viewState,
        ?string $sourceKey,
        ?string $context,
        bool $includeContainer,
        bool $selectable,
        bool $sortable,
    ): string {
        $request = Craft::$app->getRequest();
        $static = $viewState['static'] ?? false;
        $variables = [
            'viewMode' => $viewState['mode'],
            'context' => $context,
            'disabledElementIds' => $disabledElementIds,
            'collapsedElementIds' => $request->getParam('collapsedElementIds'),
            'selectable' => !$static && $selectable,
            'sortable' => !$static && $sortable,
            'showHeaderColumn' => $viewState['showHeaderColumn'] ?? false,
            'inlineEditing' => $viewState['inlineEditing'] ?? false,
            'nestedInputNamespace' => $viewState['nestedInputNamespace'] ?? null,
            'tableName' => static::pluralDisplayName(),
            'elementQuery' => self::elementQueryWithAllDescendants($elementQuery),
        ];

        $db = Craft::$app->getDb();

        if (!empty($viewState['order'])) {
            // Special case for sorting by structure
            if ($viewState['order'] === 'structure') {
                $source = ElementHelper::findSource(static::class, $sourceKey, $context);

                if (isset($source['structureId'])) {
                    $elementQuery->orderBy(['lft' => SORT_ASC]);
                    $variables['structure'] = Craft::$app->getStructures()->getStructureById($source['structureId']);

                    // Are they allowed to make changes to this structure?
                    if (in_array($context, ['index', 'embedded-index']) && $variables['structure'] && !empty($source['structureEditable'])) {
                        $variables['structureEditable'] = true;

                        // Let StructuresController know that this user can make changes to the structure
                        Craft::$app->getSession()->authorize('editStructure:' . $variables['structure']->id);
                    }
                } else {
                    unset($viewState['order']);
                }
            } elseif ($orderBy = self::_indexOrderBy($sourceKey, $viewState['order'], $viewState['sort'] ?? 'asc', $db)) {
                $elementQuery->orderBy($orderBy);

                if ((!is_array($orderBy) || !isset($orderBy['score'])) && !empty($viewState['orderHistory'])) {
                    foreach ($viewState['orderHistory'] as $order) {
                        if ($order[0] && $orderBy = self::_indexOrderBy($sourceKey, $order[0], $order[1], $db)) {
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
            $variables['attributes'] = Craft::$app->getElementSources()->getTableAttributes(
                static::class,
                $sourceKey,
                $viewState['tableColumns'] ?? null
            );

            // Prepare the element query for each of the table attributes
            $hasHandlers = Event::hasHandlers(static::class, self::EVENT_PREP_QUERY_FOR_TABLE_ATTRIBUTE);
            foreach ($variables['attributes'] as $attribute) {
                if ($hasHandlers) {
                    // Fire a 'prepQueryForTableAttribute' event
                    $event = new ElementIndexTableAttributeEvent([
                        'query' => $elementQuery,
                        'attribute' => $attribute[0],
                    ]);
                    Event::trigger(static::class, self::EVENT_PREP_QUERY_FOR_TABLE_ATTRIBUTE, $event);
                    if ($event->handled) {
                        continue;
                    }
                }

                static::prepElementQueryForTableAttribute($elementQuery, $attribute[0]);
            }

            if (!$variables['showHeaderColumn'] && count($variables['attributes']) <= 1) {
                $variables['showHeaderColumn'] = true;
            }
        }

        // Only cache if there's no search term or relation param
        if ($elementQuery instanceof ElementQuery && !$elementQuery->search && !$elementQuery->relatedTo) {
            $elementQuery->cache(dependency: new ElementQueryTagDependency($elementQuery, [
                'tags' => [
                    'element-index-query',
                    sprintf('element-index-query::%s', static::class),
                ],
            ]));
        }

        $elements = static::indexElements($elementQuery, $sourceKey);

        if (empty($elements) && !$includeContainer) {
            // load-more request
            return '';
        }

        // See if there are any provisional drafts we should swap these out with
        ElementHelper::swapInProvisionalDrafts($elements);

        if ($request->getParam('prevalidate')) {
            foreach ($elements as $element) {
                if ($element->enabled && $element->getEnabledForSite()) {
                    $element->setScenario(Element::SCENARIO_LIVE);
                }
                $element->validate();
            }
        }

        foreach ($elements as $element) {
            $element->viewMode = $viewState['mode'];
        }

        $variables['elements'] = $elements;
        $template = '_elements/' . $viewState['mode'] . 'view/' . ($includeContainer ? 'container' : 'elements');

        return Craft::$app->getView()->renderTemplate($template, $variables);
    }

    private static function elementQueryWithAllDescendants(ElementQueryInterface $elementQuery): ElementQueryInterface
    {
        if (is_array($elementQuery->where)) {
            foreach ($elementQuery->where as $key => $condition) {
                if ($condition instanceof ExcludeDescendantIdsExpression) {
                    $elementQuery = clone $elementQuery;
                    unset($elementQuery->where[$key]);
                    break;
                }
            }
        } elseif ($elementQuery->where instanceof ExcludeDescendantIdsExpression) {
            $elementQuery = clone $elementQuery;
            $elementQuery->where = null;
        }

        return $elementQuery;
    }

    /**
     * Prepares an element query for an element index that includes a given table attribute.
     *
     * @param ElementQueryInterface $elementQuery
     * @param string $attribute
     */
    protected static function prepElementQueryForTableAttribute(ElementQueryInterface $elementQuery, string $attribute): void
    {
        switch ($attribute) {
            case 'ancestors':
                $elementQuery->andWith(['ancestors', ['status' => null]]);
                break;
            case 'parent':
                $elementQuery->andWith(['parent', ['status' => null]]);
                break;
            case 'revisionNotes':
                $elementQuery->andWith('currentRevision');
                break;
            case 'revisionCreator':
                $elementQuery->andWith('currentRevision.revisionCreator');
                break;
            case 'drafts':
                $elementQuery->andWith(['drafts', ['status' => null, 'orderBy' => ['dateUpdated' => SORT_DESC]]]);
                break;
            default:
                // Is this a custom field?
                if (preg_match('/^field:(.+)/', $attribute, $matches)) {
                    $fieldUid = $matches[1];
                    Craft::$app->getFields()->getFieldByUid($fieldUid)?->modifyElementIndexQuery($elementQuery);
                }
        }
    }

    /**
     * Returns the resulting elements for an element index.
     *
     * @param ElementQueryInterface $elementQuery
     * @param string|null $sourceKey
     * @return ElementInterface[]
     * @since 4.4.0
     */
    protected static function indexElements(ElementQueryInterface $elementQuery, ?string $sourceKey): array
    {
        return $elementQuery->all();
    }

    /**
     * @inheritdoc
     */
    public static function indexElementCount(ElementQueryInterface $elementQuery, ?string $sourceKey): int
    {
        return (int)$elementQuery
            ->select(new Expression('1'))
            ->count();
    }

    /**
     * @inheritdoc
     */
    public static function indexViewModes(): array
    {
        $viewModes = [
            [
                'mode' => 'structure',
                'title' => Craft::t('app', 'Display in a structured table'),
                'icon' => Craft::$app->getLocale()->getOrientation() === 'rtl' ? 'structurertl' : 'structure',
                'structuresOnly' => true,
            ],
            [
                'mode' => 'table',
                'title' => Craft::t('app', 'Display in a table'),
                'icon' => 'list',
                'availableOnMobile' => false,
            ],
        ];

        if (static::hasThumbs()) {
            $viewModes[] = [
                'mode' => 'thumbs',
                'title' => Craft::t('app', 'Display as thumbnails'),
                'icon' => 'grid',
            ];
        }

        $viewModes[] = [
            'mode' => 'cards',
            'title' => Craft::t('app', 'Display as cards'),
            'icon' => 'element-cards',
        ];

        return $viewModes;
    }

    /**
     * @inheritdoc
     */
    public static function sortOptions(): array
    {
        $sortOptions = static::defineSortOptions();

        // Make sure ID is listed first
        $sortOptions = [
            'id' => Craft::t('app', 'ID'),
            ...ArrayHelper::without($sortOptions, 'id'),
        ];

        // Fire a 'registerSortOptions' event
        if (Event::hasHandlers(static::class, self::EVENT_REGISTER_SORT_OPTIONS)) {
            $event = new RegisterElementSortOptionsEvent(['sortOptions' => $sortOptions]);
            Event::trigger(static::class, self::EVENT_REGISTER_SORT_OPTIONS, $event);
            return $event->sortOptions;
        }

        return $sortOptions;
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
        $tableAttributes = Craft::$app->getElementSources()->getAvailableTableAttributes(static::class);
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

        // Fire a 'registerTableAttributes' event
        if (Event::hasHandlers(static::class, self::EVENT_REGISTER_TABLE_ATTRIBUTES)) {
            $event = new RegisterElementTableAttributesEvent(['tableAttributes' => $tableAttributes]);
            Event::trigger(static::class, self::EVENT_REGISTER_TABLE_ATTRIBUTES, $event);
            return $event->tableAttributes;
        }

        return $tableAttributes;
    }

    /**
     * Defines all of the available columns that can be shown in table views.
     *
     * @return array The table attributes.
     * @see tableAttributes()
     */
    protected static function defineTableAttributes(): array
    {
        $attributes = [
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
            'id' => ['label' => Craft::t('app', 'ID')],
            'uid' => ['label' => Craft::t('app', 'UID')],
        ];

        if (static::hasStatuses()) {
            $attributes['status'] = ['label' => Craft::t('app', 'Status')];
        }

        if (static::hasUris()) {
            $attributes = array_merge($attributes, [
                'link' => ['label' => Craft::t('app', 'Link'), 'icon' => 'world'],
                'slug' => ['label' => Craft::t('app', 'Slug')],
                'uri' => ['label' => Craft::t('app', 'URI')],
            ]);
        }

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public static function defaultTableAttributes(string $source): array
    {
        $tableAttributes = static::defineDefaultTableAttributes($source);

        // Fire a 'registerDefaultTableAttributes' event
        if (Event::hasHandlers(static::class, self::EVENT_REGISTER_DEFAULT_TABLE_ATTRIBUTES)) {
            $event = new RegisterElementDefaultTableAttributesEvent([
                'source' => $source,
                'tableAttributes' => $tableAttributes,
            ]);
            Event::trigger(static::class, self::EVENT_REGISTER_DEFAULT_TABLE_ATTRIBUTES, $event);
            return $event->tableAttributes;
        }

        return $tableAttributes;
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

    /**
     * @inheritdoc
     */
    public static function cardAttributes(): array
    {
        $cardAttributes = static::defineCardAttributes();

        // Fire a 'registerCardAttributes' event
        if (Event::hasHandlers(static::class, self::EVENT_REGISTER_CARD_ATTRIBUTES)) {
            $event = new RegisterElementCardAttributesEvent(['cardAttributes' => $cardAttributes]);
            Event::trigger(static::class, self::EVENT_REGISTER_CARD_ATTRIBUTES, $event);
            return $event->cardAttributes;
        }

        return $cardAttributes;
    }

    /**
     * Defines all the available attributes that can be shown in card views along with their default placeholder values.
     *
     * @return array The card attributes.
     * @see cardAttributes()
     * @since 5.5.0
     */
    protected static function defineCardAttributes(): array
    {
        // we're intentionally not including statuses as those already show in cards
        $attributes = [
            'dateCreated' => [
                'label' => Craft::t('app', 'Date Created'),
                'placeholder' => fn() => (new \DateTime())->sub(new \DateInterval('P16D')),
            ],
            'dateUpdated' => [
                'label' => Craft::t('app', 'Date Updated'),
                'placeholder' => fn() => (new \DateTime())->sub(new \DateInterval('P15D')),
            ],
            'id' => [
                'label' => Craft::t('app', 'ID'),
                'placeholder' => fn() => 4321,
            ],
            'uid' => [
                'label' => Craft::t('app', 'UID'),
                'placeholder' => fn() => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
            ],
        ];

        if (static::hasUris()) {
            $attributes = array_merge($attributes, [
                'link' => [
                    'label' => Craft::t('app', 'Link'),
                    'placeholder' => fn() => ElementHelper::linkAttributeHtml('#'),
                ],
                'slug' => [
                    'label' => Craft::t('app', 'Slug'),
                    'placeholder' => fn() => Craft::t('app', 'Slug'),
                ],
                'uri' => [
                    'label' => Craft::t('app', 'URI'),
                    'placeholder' => fn() => ElementHelper::uriAttributeHtml(Craft::t('app', 'link/to/something'), '#'),
                ],
            ]);
        }

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public static function attributePreviewHtml(array $attribute): mixed
    {
        return match ($attribute['value']) {
            'link', 'uri' => $attribute['placeholder'],
            default => ElementHelper::attributeHtml(is_callable($attribute['placeholder'] ?? null)
                ? $attribute['placeholder']()
                : $attribute['placeholder'] ?? $attribute['label']
            ),
        };
    }

    /**
     * @inheritdoc
     */
    public static function defaultCardAttributes(): array
    {
        $cardAttributes = static::defineDefaultCardAttributes();

        // Fire a 'registerDefaultCardAttributes' event
        if (Event::hasHandlers(static::class, self::EVENT_REGISTER_DEFAULT_CARD_ATTRIBUTES)) {
            $event = new RegisterElementDefaultCardAttributesEvent([
                'cardAttributes' => $cardAttributes,
            ]);
            Event::trigger(static::class, self::EVENT_REGISTER_DEFAULT_CARD_ATTRIBUTES, $event);
            return $event->cardAttributes;
        }

        return $cardAttributes;
    }

    /**
     * Returns the list of card attribute keys that should be shown by default.
     *
     * @return string[] The card attributes.
     * @see defaultCardAttributes()
     * @see cardAttributes()
     * @since 5.5.0
     */
    protected static function defineDefaultCardAttributes(): array
    {
        return [];
    }

    // Methods for customizing element queries
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     * @return EagerLoadingMap|null|false
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
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
        if (str_contains($handle, ':')) {
            [$providerHandle, $fieldHandle] = explode(':', $handle, 2);
        } else {
            $providerHandle = null;
            $fieldHandle = $handle;
        }

        // Get all the custom fields by that handle
        $fields = [];
        foreach (static::fieldLayouts(null) as $fieldLayout) {
            if ($providerHandle === null || $fieldLayout->provider?->getHandle() === $providerHandle) {
                $layoutField = $fieldLayout->getFieldByHandle($fieldHandle);
                if ($layoutField) {
                    $fields[] = $layoutField;
                    if ($providerHandle !== null) {
                        break;
                    }
                }
            }
        }

        // If there were any matching fields, find the first one that's actually included in
        // at least one of the source elements’ field layouts
        if (!empty($fields)) {
            foreach ($fields as $field) {
                if (!$field instanceof EagerLoadingFieldInterface) {
                    continue;
                }

                // filter out elements, if field is not part of its layout
                // https://github.com/craftcms/cms/issues/12539
                $fieldSourceElements = array_values(
                    array_filter($sourceElements, function($sourceElement) use ($field) {
                        $layoutField = $sourceElement->getFieldLayout()?->getFieldByHandle($field->handle);
                        return $layoutField && $layoutField->id === $field->id;
                    })
                );

                if (!empty($fieldSourceElements)) {
                    return $field->getEagerLoadingMap($fieldSourceElements);
                }
            }

            // None of the source elements include any of the matching fields
            return false;
        }

        // Fire a 'defineEagerLoadingMap' event
        if (Event::hasHandlers(static::class, self::EVENT_DEFINE_EAGER_LOADING_MAP)) {
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
        }

        // return null so eager-loading is ignored for this handle
        return null;
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
        $elementStructureData = self::_structureDataForElements($sourceElements, $children);

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
                    $descendantStructureDatum['structureId'] == $elementStructureDatum['structureId'] &&
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
        $elementStructureData = self::_structureDataForElements($sourceElements, $parents);

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
                    $ancestorStructureDatum['structureId'] == $elementStructureDatum['structureId'] &&
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
     * @param ElementInterface[] $elements
     * @return array
     */
    private static function _structureDataForElements(array $elements, bool $withLevel): array
    {
        $data = [];
        $fetchDataForIds = [];

        foreach ($elements as $element) {
            if (isset($element->structureId, $element->lft, $element->rgt, $element->level)) {
                $data[] = [
                    'structureId' => $element->structureId,
                    'elementId' => $element->id,
                    'lft' => $element->lft,
                    'rgt' => $element->rgt,
                    'level' => $element->level,
                ];
            } else {
                $fetchDataForIds[] = $element->id;
            }
        }

        if (!empty($fetchDataForIds)) {
            // Get the structure data for these elements
            $selectColumns = ['structureId', 'elementId', 'lft', 'rgt'];

            if ($withLevel) {
                $selectColumns[] = 'level';
            }

            array_push($data, ...(new Query())
                ->select($selectColumns)
                ->from([Table::STRUCTUREELEMENTS])
                ->where(['elementId' => $fetchDataForIds])
                ->all());
        }

        return $data;
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
                'drafts' => null,
                'provisionalDrafts' => null,
                'revisions' => null,
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
        $sourceElementIds = array_map(fn(ElementInterface $element) => $element->id, $sourceElements);

        $map = (new Query())
            ->select([
                'source' => 'se.id',
                'target' => 're.id',
            ])
            ->from(['re' => Table::ELEMENTS])
            ->innerJoin(['r' => Table::REVISIONS], '[[r.id]] = [[re.revisionId]]')
            ->innerJoin(['se' => Table::ELEMENTS], '[[se.id]] = [[r.canonicalId]]')
            ->where('[[re.dateCreated]] = [[se.dateUpdated]]')
            ->andWhere(['se.id' => $sourceElementIds])
            ->all();

        return [
            'elementType' => static::class,
            'map' => $map,
            'criteria' => ['revisions' => true, 'status' => null],
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
        $sourceElementIds = array_map(fn(ElementInterface $element) => $element->id, $sourceElements);

        $map = (new Query())
            ->select([
                'source' => 'd.canonicalId',
                'target' => 'e.id',
            ])
            ->from(['d' => Table::DRAFTS])
            ->innerJoin(['e' => Table::ELEMENTS], '[[e.draftId]] = [[d.id]]')
            ->where(['d.canonicalId' => $sourceElementIds])
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
        $sourceElementIds = array_map(fn(ElementInterface $element) => $element->id, $sourceElements);

        $map = (new Query())
            ->select([
                'source' => 'r.canonicalId',
                'target' => 'e.id',
            ])
            ->from(['r' => Table::REVISIONS])
            ->innerJoin(['e' => Table::ELEMENTS], '[[e.revisionId]] = [[r.id]]')
            ->where(['r.canonicalId' => $sourceElementIds])
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
        $sourceElementIds = array_map(fn(ElementInterface $element) => $element->id, $sourceElements);

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
        $sourceElementIds = array_map(fn(ElementInterface $element) => $element->id, $sourceElements);

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
     */
    public static function baseGqlType(): Type
    {
        return ElementGqlType::getType();
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function gqlScopesByContext(mixed $context): array
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
     * @param Connection $db
     * @return array|ExpressionInterface|false
     */
    private static function _indexOrderBy(
        string $sourceKey,
        string $attribute,
        string $dir,
        Connection $db,
    ): ExpressionInterface|array|false {
        $dir = strcasecmp($dir, 'desc') === 0 ? SORT_DESC : SORT_ASC;
        $columns = self::_indexOrderByColumns($sourceKey, $attribute, $dir, $db);

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
     * @param Connection $db
     * @return bool|string|array|ExpressionInterface
     */
    private static function _indexOrderByColumns(
        string $sourceKey,
        string $attribute,
        int $dir,
        Connection $db,
    ): ExpressionInterface|bool|array|string {
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
                        return $sortOption['orderBy']($dir, $db);
                    }
                    return $sortOption['orderBy'];
                }
            } elseif ($key === $attribute) {
                return $key;
            }
        }

        // See if it's a source-specific sort option
        foreach (Craft::$app->getElementSources()->getSourceSortOptions(static::class, $sourceKey) as $sortOption) {
            if ($sortOption['attribute'] === $attribute) {
                if ($sortOption['orderBy'] instanceof CoalesceColumnsExpression) {
                    $params = [];
                    $sql = $sortOption['orderBy']->getSql($params);
                } elseif (is_string($sortOption['orderBy'])) {
                    $sql = $sortOption['orderBy'];
                } else {
                    return $sortOption['orderBy'];
                }

                return new Expression(sprintf('%s %s', $sql, $dir === SORT_ASC ? 'ASC' : 'DESC'), $params ?? []);
            }
        }

        return false;
    }

    /**
     * @var int|null Revision creator ID to be saved
     * @see setRevisionCreatorId()
     */
    protected ?int $revisionCreatorId = null;

    /**
     * @var string|null Revision notes to be saved
     * @see setRevisionNotes()
     */
    protected ?string $revisionNotes = null;

    /**
     * @var array<string,int>|null
     * @see validate()
     */
    private ?array $_attributeNames = null;

    /**
     * @var int|null
     * @see getCanonicalId()
     * @see setCanonicalId()
     * @see getIsCanonical()
     * @see getIsDerivative()
     */
    private ?int $_canonicalId = null;

    /**
     * @var ElementInterface|false|null
     * @see getCanonical()
     */
    private ElementInterface|false|null $_canonical = null;

    /**
     * @var ElementInterface|false|null
     * @see getCanonical()
     */
    private ElementInterface|false|null $_canonicalAnySite = null;

    /**
     * @var string|null
     * @see getCanonicalUid()
     */
    private ?string $_canonicalUid = null;

    /**
     * @var array|null
     * @see _outdatedAttributes()
     */
    private ?array $_outdatedAttributes = null;

    /**
     * @var array|null
     * @see _modifiedAttributes()
     */
    private ?array $_modifiedAttributes = null;

    /**
     * @var array|null
     * @see _outdatedFields()
     */
    private ?array $_outdatedFields = null;

    /**
     * @var array|null
     * @see _modifiedFields()
     */
    private ?array $_modifiedFields = null;

    /**
     * @var bool
     */
    private bool $_initialized = false;

    /**
     * @var string|null
     */
    private ?string $_fieldParamNamePrefix = null;

    /**
     * @var array|null Record of the fields whose values have already been normalized
     */
    private ?array $_normalizedFieldValues = null;

    /**
     * @var array
     * @see getGeneratedFieldValues()
     * @see setGeneratedFieldValues()
     */
    private array $_generatedFieldValues;

    /**
     * @var bool Whether all attributes and field values should be considered dirty.
     * @see getDirtyAttributes()
     * @see getDirtyFields()
     * @see isFieldDirty()
     */
    private bool $_allDirty = false;

    /**
     * @var array<string, int|string|bool> Record of dirty attributes.
     * @see getDirtyAttributes()
     * @see isAttributeDirty()
     */
    private array $_dirtyAttributes = [];

    /**
     * @var string|null The initial title value, if there was one.
     * @see getDirtyAttributes()
     */
    private ?string $_savedTitle = null;

    /**
     * @var array Record of dirty fields.
     * @see getDirtyFields()
     * @see isFieldDirty()
     */
    private array $_dirtyFields = [];

    /**
     * @var ElementInterface|false
     */
    private ElementInterface|false $_nextElement;

    /**
     * @var ElementInterface|false
     */
    private ElementInterface|false $_prevElement;

    /**
     * @var int|false|null Parent ID
     * @see getParentId()
     * @see setParentId()
     */
    private int|false|null $_parentId = null;

    /**
     * @var ElementInterface|false|null
     * @see getParent()
     * @see setParent()
     */
    private ElementInterface|false|null $_parent = null;

    /**
     * @var bool|null
     * @see hasNewParent()
     */
    private ?bool $_hasNewParent = null;

    /**
     * @var ElementInterface|false|null
     * @see getPrevSibling()
     */
    private ElementInterface|false|null $_prevSibling = null;

    /**
     * @var ElementInterface|false|null
     * @see getNextSibling()
     */
    private ElementInterface|false|null $_nextSibling = null;

    /**
     * @var int[]
     * @see getInvalidNestedElementIds()
     * @see addInvalidNestedElementIds()
     */
    private array $_invalidNestedElementIds = [];

    /**
     * @var array<string,ElementCollection>
     * @see getEagerLoadedElements()
     * @see setEagerLoadedElements()
     */
    private array $_eagerLoadedElements = [];

    /**
     * @var array<string,bool>
     * @see getFieldValue()
     * @see setLazyEagerLoadedElements()
     */
    private array $_lazyEagerLoadedElements = [];

    /**
     * @var array<string,int>
     * @see getEagerLoadedElementCount()
     * @see setEagerLoadedElementCount
     */
    private array $_eagerLoadedElementCounts = [];

    /**
     * @var ElementInterface|false|null
     * @see getCurrentRevision()
     */
    private ElementInterface|false|null $_currentRevision = null;

    /**
     * @var bool|bool[]
     * @see getEnabledForSite()
     * @see setEnabledForSite()
     */
    private array|bool $_enabledForSite = true;

    /**
     * @var string|null
     * @see getUiLabel()
     * @see setUiLabel()
     */
    private ?string $_uiLabel = null;

    /**
     * @var string[]
     * @see getUiLabelPath()
     * @see setUiLabelPath()
     */
    private array $_uiLabelPath = [];

    /**
     * @var bool|null
     * @see getIsFresh()
     * @see setIsFresh()
     */
    private ?bool $_isFresh = null;

    /**
     * @see toArray()
     */
    private $_serializeFields = false;

    /**
     * @var bool
     * @see getIsCrossSiteCopyable()
     */
    private bool $_isCrossSiteCopyable;

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
        parent::__clone();

        // Mark all fields as dirty
        $this->_allDirty = true;
        $this->_hasNewParent = null;
    }

    /**
     * Returns the string representation of the element.
     *
     * @return string
     */
    public function __toString(): string
    {
        if (isset($this->title) && $this->title !== '') {
            return (string)$this->title;
        }

        try {
            if (!$this->id || $this->getIsUnpublishedDraft()) {
                return Craft::t('app', 'New {type}', [
                    'type' => static::lowerDisplayName(),
                ]);
            }

            return sprintf('%s %s', static::displayName(), $this->id);
        } catch (Throwable $e) {
            ErrorHandler::convertExceptionToError($e);
        }
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
        if (str_starts_with($name, 'field:')) {
            return $this->fieldByHandle(substr($name, 6)) !== null;
        }

        return $name === 'title' || $this->hasEagerLoadedElements($name) || parent::__isset($name) || $this->fieldByHandle($name);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        // Is $name a set of eager-loaded elements?
        if ($this->hasEagerLoadedElements($name) && !($this->_lazyEagerLoadedElements[$name] ?? false)) {
            return $this->getEagerLoadedElements($name);
        }

        // Is this the "field:handle" syntax?
        if (str_starts_with($name, 'field:')) {
            return $this->getFieldValue(substr($name, 6));
        }

        // If this is a field, make sure the value has been normalized before returning the CustomFieldBehavior value
        if ($this->fieldByHandle($name) !== null) {
            return $this->clonedFieldValue($name);
        }

        if (isset($this->_generatedFieldValues) && array_key_exists($name, $this->_generatedFieldValues)) {
            return $this->_generatedFieldValues[$name];
        }

        return parent::__get($name);
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        // Is this the "field:handle" syntax?
        if (str_starts_with($name, 'field:')) {
            $this->setFieldValue(substr($name, 6), $value);
            return;
        }

        try {
            parent::__set($name, $value);
        } catch (InvalidCallException|UnknownPropertyException $e) {
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
        if (str_starts_with($name, 'isFieldEmpty:')) {
            return $this->isFieldEmpty(substr($name, 13));
        }

        return parent::__call($name, $params);
    }

    /**
     * @inheritdoc
     */
    protected function defineBehaviors(): array
    {
        return [
            'customFields' => [
                'class' => CustomFieldBehavior::class,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->siteId) && Craft::$app->getIsInstalled()) {
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
    public function attributes(): array
    {
        $names = array_flip(parent::attributes());

        if ($this->structureId) {
            $names['parentId'] = true;
        } else {
            unset(
                $names['level'],
                $names['lft'],
                $names['rgt'],
                $names['root'],
                $names['structureId'],
            );
        }

        unset(
            $names['awaitingFieldValues'],
            $names['duplicateOf'],
            $names['elementQueryResult'],
            $names['firstSave'],
            $names['hardDelete'],
            $names['mergingCanonicalChanges'],
            $names['newSiteIds'],
            $names['isNewForSite'],
            $names['isNewSite'],
            $names['previewing'],
            $names['propagateAll'],
            $names['propagating'],
            $names['propagatingFrom'],
            $names['resaving'],
            $names['saveOwnership'],
            $names['searchScore'],
            $names['updateSearchIndexForOwner'],
            $names['updateSearchIndexImmediately'],
            $names['updatingFromDerivative'],
            $names['viewMode'],
        );

        $names['canonicalId'] = true;
        $names['cpEditUrl'] = true;
        $names['isDraft'] = true;
        $names['isRevision'] = true;
        $names['isUnpublishedDraft'] = true;
        $names['ref'] = true;
        $names['status'] = true;
        $names['structureId'] = true;
        $names['url'] = true;

        return array_keys($names);
    }

    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        $fields = parent::fields();

        foreach ($this->fieldLayoutFields() as $field) {
            if (!isset($fields[$field->handle])) {
                if ($this->_serializeFields) {
                    $fields[$field->handle] = function() use ($field) {
                        $value = $this->getFieldValue($field->handle);
                        return $field->serializeValue($value, $this);
                    };
                } else {
                    $fields[$field->handle] = fn() => $this->clonedFieldValue($field->handle);
                }
            }
        }

        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function toArray(array $fields = [], array $expand = [], $recursive = true): array
    {
        if ($recursive) {
            $this->_serializeFields = true;
        }

        $arr = $this->traitToArray($fields, $expand, $recursive);

        if ($recursive) {
            $this->_serializeFields = false;
        }

        return $arr;
    }

    /**
     * @inheritdoc
     */
    public function extraFields(): array
    {
        return [
            ...parent::extraFields(),
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
    public function getIterator(): Traversable
    {
        $attributes = $this->getAttributes();

        // Include custom fields
        $fieldLayout = $this->getFieldLayout();

        if ($fieldLayout !== null) {
            foreach ($fieldLayout->getCustomFieldElements() as $layoutElement) {
                $field = $layoutElement->getField();
                if (!isset($attributes[$field->handle])) {
                    $attributes[$field->handle] = $this->getFieldValue($field->handle);
                }
            }
        }

        return new ArrayIterator($attributes);
    }

    /**
     * @inheritdoc
     */
    public function getAttributeLabel($attribute): string
    {
        // Is this the "field:handle" syntax?
        if (str_starts_with($attribute, 'field:')) {
            $attribute = substr($attribute, 6);
        }

        return parent::getAttributeLabel($attribute);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
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
                    foreach ($tab->getElements() as $layoutElement) {
                        if ($layoutElement instanceof BaseField && ($label = $layoutElement->label()) !== null) {
                            $labels[$layoutElement->attribute()] = $label;
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
        $rules[] = [['id', 'parentId', 'root', 'lft', 'rgt', 'level'], 'number', 'integerOnly' => true, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]];
        $rules[] = [
            ['siteId'],
            SiteIdValidator::class,
            'allowDisabled' => true,
            'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE, self::SCENARIO_ESSENTIALS],
        ];
        $rules[] = [['dateCreated', 'dateUpdated'], DateTimeValidator::class, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]];
        $rules[] = [['isFresh'], BooleanValidator::class];

        $rules[] = [['title'], 'trim'];
        $rules[] = [
            ['title'],
            StringValidator::class,
            'max' => 255,
            'disallowMb4' => true,
        ];
        $rules[] = [
            ['title'],
            'required',
            'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE],
            'when' => fn() => $this->shouldValidateTitle(),
        ];

        if (static::hasUris()) {
            try {
                $language = $this->getSite()->language;
            } catch (InvalidConfigException) {
                $language = null;
            }

            $rules[] = [['slug'], SlugValidator::class, 'language' => $language, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE, self::SCENARIO_ESSENTIALS]];
            $rules[] = [['slug'], 'string', 'max' => 255, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE, self::SCENARIO_ESSENTIALS]];
            $rules[] = [
                ['slug'],
                'required',
                'when' => fn() => (bool)preg_match('/\bslug\b/', $this->getUriFormat() ?? ''),
                'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE],
            ];
            $rules[] = [['uri'], ElementUriValidator::class, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE, self::SCENARIO_ESSENTIALS]];
        }

        return $rules;
    }

    /**
     * Returns whether the element’s `title` attribute should be validated
     * @return bool
     * @since 5.0.0
     */
    protected function shouldValidateTitle(): bool
    {
        return static::hasTitles();
    }

    /**
     * @inheritdoc
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        $this->_attributeNames = $attributeNames ? array_flip((array)$attributeNames) : null;
        $this->_invalidNestedElementIds = [];
        $result = parent::validate($attributeNames, $clearErrors);
        $this->_attributeNames = null;
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function afterValidate(): void
    {
        if (
            Craft::$app->getIsInstalled() &&
            $fieldLayout = $this->getFieldLayout()
        ) {
            $scenario = $this->getScenario();
            $layoutElements = $fieldLayout->getVisibleCustomFieldElements($this);

            foreach ($layoutElements as $layoutElement) {
                $field = $layoutElement->getField();
                $attribute = "field:$field->handle";

                if (isset($this->_attributeNames) && !isset($this->_attributeNames[$attribute])) {
                    continue;
                }

                $isEmpty = fn() => $field->isValueEmpty($this->getFieldValue($field->handle), $this);

                if ($scenario === self::SCENARIO_LIVE && $layoutElement->required) {
                    (new RequiredValidator(['isEmpty' => $isEmpty]))
                        ->validateAttribute($this, $attribute);
                }

                foreach ($field->getElementValidationRules() as $rule) {
                    $validator = $this->_normalizeFieldValidator($attribute, $rule, $field, $isEmpty);
                    if (
                        in_array($scenario, $validator->on) ||
                        (empty($validator->on) && !in_array($scenario, $validator->except))
                    ) {
                        $validator->validateAttributes($this);
                    }
                }
            }
        }

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $allErrors = $this->getErrors();
            $this->clearErrors();
            foreach ($allErrors as $attribute => &$errors) {
                $label = $this->getAttributeLabel($attribute);
                foreach ($errors as &$error) {
                    $error = str_replace($label, "*$label*", $error);
                }
            }
            $this->addErrors($allErrors);
        }

        parent::afterValidate();
    }

    /**
     * Normalizes a field’s validation rule.
     *
     * @param string $attribute
     * @param mixed $rule
     * @param FieldInterface $field
     * @param callable $isEmpty
     * @return Validator
     * @throws InvalidConfigException
     */
    private function _normalizeFieldValidator(string $attribute, mixed $rule, FieldInterface $field, callable $isEmpty): Validator
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

        if (
            (!is_string($rule[1]) || !isset(Validator::$builtInValidators[$rule[1]])) &&
            (is_callable($rule[1]) || $field->hasMethod($rule[1]))
        ) {
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

        return Validator::createValidator($rule[1], $this, (array)$rule[0], array_slice($rule, 2));
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
    public function validateCustomFieldAttribute(string $attribute, ?array $params = null): void
    {
        /** @var array|null $params */
        [$field, $method, $fieldParams] = $params;

        if (is_string($method) && !is_callable($method)) {
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
     * @inheritdoc
     */
    public function addError($attribute, $error = ''): void
    {
        if (str_starts_with($attribute, 'field:')) {
            $attribute = substr($attribute, 6);
        }

        parent::addError($attribute, $error);
    }

    /**
     * @inheritdoc
     */
    public function getId(): ?int
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
        return !isset($this->_canonicalId);
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

        if (!isset($this->$prop)) {
            $query = static::find()
                ->id($this->_canonicalId)
                ->siteId($anySite ? '*' : $this->siteId)
                ->preferSites([$this->siteId])
                ->structureId($this->structureId)
                ->unique()
                ->status(null)
                ->trashed(null)
                ->ignorePlaceholders();

            if ($this instanceof NestedElementInterface && $query instanceof NestedElementQueryInterface) {
                $query
                    ->fieldId($this->getField()?->id);
            }

            $this->$prop = $query->one();
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
        if (isset($this->_canonical) && $this->_canonical) {
            return $this->_canonical->uid;
        }

        // Just fetch that one value ourselves
        if (!isset($this->_canonicalUid)) {
            $this->_canonicalUid = static::find()
                ->id($this->_canonicalId)
                ->site('*')
                ->status(null)
                ->ignorePlaceholders()
                ->select(['elements.uid'])
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
    public function getSourceId(): ?int
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
     * @inheritdoc
     */
    public function mergeCanonicalChanges(): void
    {
        if (($canonical = $this->getCanonical()) === $this) {
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
    public function getFieldLayout(): ?FieldLayout
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
        $cacheTags = static::cacheTags();

        // Fire a 'defineCacheTags' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_CACHE_TAGS)) {
            $event = new DefineValueEvent(['value' => $cacheTags]);
            $this->trigger(self::EVENT_DEFINE_CACHE_TAGS, $event);
            return $event->value;
        }

        return $cacheTags;
    }

    /**
     * Returns the cache tags that should be cleared when this element is saved.
     *
     * @return string[]
     * @since 4.1.0
     */
    protected function cacheTags(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getUriFormat(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getSearchKeywords(string $attribute): string
    {
        // Fire a 'defineKeywords' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_KEYWORDS)) {
            $event = new DefineAttributeKeywordsEvent(['attribute' => $attribute]);
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
    public function getRoute(): mixed
    {
        // Fire a 'setRoute' event
        if ($this->hasEventHandlers(self::EVENT_SET_ROUTE)) {
            $event = new SetElementRouteEvent();
            $this->trigger(self::EVENT_SET_ROUTE, $event);
            if ($event->handled || $event->route !== null) {
                return $event->route ?: null;
            }
        }

        if ($this instanceof NestedElementInterface) {
            $field = $this->getField();
            if ($field) {
                return $field->getRouteForElement($this);
            }
        }

        return $this->route();
    }

    /**
     * Returns the route that should be used when the element’s URI is requested.
     *
     * @return string|array|null The route that the request should use, or null if no special action should be taken
     * @see getRoute()
     */
    protected function route(): array|string|null
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
    public function getUrl(): ?string
    {
        // Fire a 'beforeDefineUrl' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DEFINE_URL)) {
            $event = new DefineUrlEvent();
            $this->trigger(self::EVENT_BEFORE_DEFINE_URL, $event);
            $url = $event->url;
        } else {
            $url = null;
        }

        // If DefineAssetUrlEvent::$url is set to null, only respect that if $handled is true
        if ($url === null && !($event->handled ?? false) && isset($this->uri)) {
            $path = $this->getIsHomepage() ? '' : $this->uri;
            $url = UrlHelper::siteUrl($path, null, null, $this->siteId);
        }

        // Fire a 'defineUrl' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_URL)) {
            $event = new DefineUrlEvent(['url' => $url]);
            $this->trigger(self::EVENT_DEFINE_URL, $event);
            // If DefineAssetUrlEvent::$url is set to null, only respect that if $handled is true
            if ($event->url !== null || $event->handled) {
                $url = $event->url;
            }
        }

        return $url !== null ? Html::encodeSpaces($url) : $url;
    }

    /**
     * @inheritdoc
     */
    public function getLink(): ?Markup
    {
        if (($url = $this->getUrl()) === null) {
            return null;
        }

        $a = Html::a(Html::encode($this->getUiLabel()), $url);
        return Template::raw($a);
    }

    /**
     * @inheritdoc
     * @see crumbs()
     */
    public function getCrumbs(): array
    {
        if ($this instanceof NestedElementInterface) {
            $owner = $this->getOwner();
            if ($owner) {
                return [
                    ...$owner->getCrumbs(),
                    [
                        'html' => Cp::elementChipHtml($owner, [
                            'showDraftName' => false,
                            'class' => 'chromeless',
                        ]),
                    ],
                ];
            }
        }

        return $this->crumbs();
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
     * @inheritdoc
     */
    public function getUiLabelPath(): array
    {
        return $this->_uiLabelPath;
    }

    /**
     * @inheritdoc
     */
    public function setUiLabelPath(array $path): void
    {
        $this->_uiLabelPath = $path;
    }

    /**
     * Returns the breadcrumbs that lead up to the element.
     *
     * @return array
     * @since 5.0.0
     * @see getCrumbs()
     */
    protected function crumbs(): array
    {
        return [];
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
    public function getChipLabelHtml(): string
    {
        return Html::encode($this->getUiLabel());
    }

    /**
     * @inheritdoc
     */
    public function showStatusIndicator(): bool
    {
        return static::hasStatuses();
    }

    /**
     * @inheritdoc
     */
    public function getCardTitle(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getCardBodyHtml(): ?string
    {
        $this->viewMode = 'cards';
        $html = '';

        foreach ($this->getFieldLayout()?->getCardBodyElements($this) ?? [] as $item) {
            if ($item instanceof BaseField) {
                $itemHtml = $item->previewHtml($this);
            } elseif (is_array($item) && isset($item['html'])) {
                $itemHtml = $item['html'];
            } else {
                $itemHtml = $this->getAttributeHtml($item['value']);
            }

            if ($itemHtml !== '') {
                $html .= Html::tag('div', $itemHtml, [
                    'class' => 'card-attribute-preview',
                ]);
            }
        }

        return $html;
    }

    /**
     * @inheritdoc
     */
    public function getRef(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function createAnother(): ?ElementInterface
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function canView(User $user): bool
    {
        if ($this instanceof NestedElementInterface) {
            $authorized = $this->getField()?->canViewElement($this, $user);
            if ($authorized !== null) {
                return $authorized;
            }
        }

        // Fire an 'authorizeView' event
        if ($this->hasEventHandlers(self::EVENT_AUTHORIZE_VIEW)) {
            $event = new AuthorizationCheckEvent($user);
            $this->trigger(self::EVENT_AUTHORIZE_VIEW, $event);
            return $event->authorized;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function canSave(User $user): bool
    {
        if ($this instanceof NestedElementInterface) {
            $authorized = $this->getField()?->canSaveElement($this, $user);
            if ($authorized !== null) {
                return $authorized;
            }
        }

        // Fire an 'authorizeSave' event
        if ($this->hasEventHandlers(self::EVENT_AUTHORIZE_SAVE)) {
            $event = new AuthorizationCheckEvent($user);
            $this->trigger(self::EVENT_AUTHORIZE_SAVE, $event);
            return $event->authorized;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function canDuplicate(User $user): bool
    {
        if ($this instanceof NestedElementInterface) {
            $authorized = $this->getField()?->canDuplicateElement($this, $user);
            if ($authorized !== null) {
                return $authorized;
            }
        }

        // Fire an 'authorizeDuplicate' event
        if ($this->hasEventHandlers(self::EVENT_AUTHORIZE_DUPLICATE)) {
            $event = new AuthorizationCheckEvent($user);
            $this->trigger(self::EVENT_AUTHORIZE_DUPLICATE, $event);
            return $event->authorized;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function canDuplicateAsDraft(User $user): bool
    {
        // if anything, this will be more lenient than canDuplicate()
        return Craft::$app->getElements()->canDuplicate($this, $user);
    }

    /**
     * @inheritdoc
     */
    public function canCopy(User $user): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function canDelete(User $user): bool
    {
        if ($this instanceof NestedElementInterface) {
            $authorized = $this->getField()?->canDeleteElement($this, $user);
            if ($authorized !== null) {
                return $authorized;
            }
        }

        // Fire an 'authorizeDelete' event
        if ($this->hasEventHandlers(self::EVENT_AUTHORIZE_DELETE)) {
            $event = new AuthorizationCheckEvent($user);
            $this->trigger(self::EVENT_AUTHORIZE_DELETE, $event);
            return $event->authorized;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function canDeleteForSite(User $user): bool
    {
        if ($this instanceof NestedElementInterface) {
            $authorized = $this->getField()?->canDeleteElementForSite($this, $user);
            if ($authorized !== null) {
                return $authorized;
            }
        }

        // Fire an 'authorizeDeleteForSite' event
        if (!$this->hasEventHandlers(self::EVENT_AUTHORIZE_DELETE_FOR_SITE)) {
            $event = new AuthorizationCheckEvent($user);
            $this->trigger(self::EVENT_AUTHORIZE_DELETE_FOR_SITE, $event);
            return $event->authorized;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function canCreateDrafts(User $user): bool
    {
        // Fire an 'authorizeCreateDrafts' event
        if ($this->hasEventHandlers(self::EVENT_AUTHORIZE_CREATE_DRAFTS)) {
            $event = new AuthorizationCheckEvent($user);
            $this->trigger(self::EVENT_AUTHORIZE_CREATE_DRAFTS, $event);
            return $event->authorized;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function hasRevisions(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function prepareEditScreen(Response $response, string $containerId): void
    {
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl(): ?string
    {
        if (!$this->id) {
            return null;
        }

        $url = $this->cpEditUrl();

        if (!$url) {
            return null;
        }

        return ElementHelper::addElementEditorUrlParams($url, $this);
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
    public function getPostEditUrl(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getCpRevisionsUrl(): ?string
    {
        $cpEditUrl = $this->cpRevisionsUrl();

        if (!$cpEditUrl) {
            return null;
        }

        $params = [];

        if (Craft::$app->getIsMultiSite()) {
            $params['site'] = $this->getSite()->handle;
        }

        return UrlHelper::cpUrl($cpEditUrl, $params);
    }

    /**
     * Returns the element’s revisions index URL in the control panel.
     *
     * @return string|null
     * @since 4.4.0
     */
    protected function cpRevisionsUrl(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getAdditionalButtons(): string
    {
        // Fire a 'defineAdditionalButtons' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_ADDITIONAL_BUTTONS)) {
            $event = new DefineHtmlEvent();
            $this->trigger(self::EVENT_DEFINE_ADDITIONAL_BUTTONS, $event);
            return $event->html;
        }

        return '';
    }

    /**
     * @inheritdoc
     */
    public function getAltActions(): array
    {
        $isUnpublishedDraft = $this->getIsUnpublishedDraft();
        $elementsService = Craft::$app->getElements();
        $canSaveCanonical = $elementsService->canSaveCanonical($this);

        $altActions = [
            [
                'label' => $isUnpublishedDraft && $canSaveCanonical
                    ? Craft::t('app', 'Create and continue editing')
                    : Craft::t('app', 'Save and continue editing'),
                'redirect' => '{cpEditUrl}',
                'shortcut' => true,
                'retainScroll' => true,
                'eventData' => ['autosave' => false],
            ],
        ];

        if ($this->getIsCanonical() || $this->isProvisionalDraft) {
            $newElement = $this->createAnother();
            if ($newElement && $elementsService->canSave($newElement)) {
                $altActions[] = [
                    'label' => $isUnpublishedDraft && $canSaveCanonical
                        ? Craft::t('app', 'Create and add another')
                        : Craft::t('app', 'Save and add another'),
                    'shortcut' => true,
                    'shift' => true,
                    'eventData' => ['autosave' => false],
                    'params' => ['addAnother' => 1],
                ];
            }

            if ($canSaveCanonical && $isUnpublishedDraft) {
                $altActions[] = [
                    'label' => StringHelper::upperCaseFirst(Craft::t('app', 'Save {type}', [
                        'type' => Craft::t('app', 'draft'),
                    ])),
                    'action' => 'elements/save-draft',
                    'redirect' => sprintf('%s#', ElementHelper::postEditUrl($this)),
                    'eventData' => ['autosave' => false],
                ];
            }

            if (!$this->getIsRevision() && $elementsService->canDuplicateAsDraft($this)) {
                $altActions[] = [
                    'label' => Craft::t('app', 'Save as a new {type}', [
                        'type' => static::lowerDisplayName(),
                    ]),
                    'action' => 'elements/duplicate',
                    'redirect' => '{cpEditUrl}',
                    'params' => [
                        'asUnpublishedDraft' => true,
                        'deleteProvisionalDraft' => true,
                    ],
                ];
            }
        }

        // Fire a 'defineAltActions' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_ALT_ACTIONS)) {
            $event = new DefineAltActionsEvent([
                'altActions' => $altActions,
            ]);
            $this->trigger(self::EVENT_DEFINE_ALT_ACTIONS, $event);
            return $event->altActions;
        }

        return $altActions;
    }

    /**
     * @inheritdoc
     */
    public function getActionMenuItems(): array
    {
        $items = [
            ...$this->safeActionMenuItems(),
            ...array_map(fn(array $item) => $item + ['destructive' => true], $this->destructiveActionMenuItems()),
        ];

        // Fire a 'defineActionMenuItems' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_ACTION_MENU_ITEMS)) {
            $event = new DefineMenuItemsEvent(['items' => $items]);
            $this->trigger(self::EVENT_DEFINE_ACTION_MENU_ITEMS, $event);
            return $event->items;
        }

        return $items;
    }

    /**
     * Returns action menu items for the element’s edit screens.
     *
     * See [[\craft\helpers\Cp::disclosureMenu()]] for documentation on supported item properties.
     *
     * @return array
     * @see getActionMenuItems()
     * @see Cp::disclosureMenu()
     * @since 5.0.0
     */
    protected function safeActionMenuItems(): array
    {
        $items = [];
        $elementsService = Craft::$app->getElements();
        $view = Craft::$app->getView();

        // Validate
        if (
            !$this->getIsRevision() &&
            !Craft::$app->getRequest()->getHeaders()->has('X-Craft-Container-Id') &&
            Craft::$app->controller instanceof ElementsController &&
            Craft::$app->controller->element === $this
        ) {
            $validateId = sprintf('action-validate-%s', mt_rand());
            $items[] = [
                'id' => $validateId,
                'icon' => 'circle-check',
                'label' => Craft::t('app', 'Validate {type}', [
                    'type' => static::lowerDisplayName(),
                ]),
            ];

            $view->registerJsWithVars(fn($id) => <<<JS
(() => {
  const btn = $('#' + $id);
  btn.on('activate', () => {
    const elementId = btn.closest('.menu').data('disclosureMenu').\$trigger
      .closest('form').data('elementEditor').settings.elementId;
    const form = Craft.createForm()
      .addClass('hidden')
      .append(Craft.getCsrfInput())
      .appendTo(Garnish.\$bod);

    Craft.submitForm(form, {
      action: 'elements/validate',
      retainScroll: true,
      params: {
        elementId,
      },
    });
  });
})();
JS, [
                $view->namespaceInputId($validateId),
            ]);
        }

        // View
        $url = $this->getUrl();
        if ($url) {
            $viewId = sprintf('action-view-%s', mt_rand());
            $items[] = [
                'id' => $viewId,
                'icon' => 'share',
                'label' => Craft::t('app', 'View in a new tab'),
                'url' => $url,
                'attributes' => [
                    'target' => '_blank',
                    'data' => [
                        'view' => true,
                    ],
                ],
            ];
        }

        if ($elementsService->canView($this)) {
            // Edit
            $editId = sprintf('action-edit-%s', mt_rand());
            $items[] = [
                'id' => $editId,
                'icon' => 'edit',
                'label' => StringHelper::upperCaseFirst(Craft::t('app', 'Edit {type}', [
                    'type' => static::lowerDisplayName(),
                ])),
            ];

            $view->registerJsWithVars(fn($id, $elementType, $settings) => <<<JS
$('#' + $id).on('activate', () => {
  Craft.createElementEditor($elementType, $settings);
});
JS, [
                $view->namespaceInputId($editId),
                static::class,
                [
                    'elementId' => $this->isProvisionalDraft ? $this->getCanonicalId() : $this->id,
                    'draftId' => $this->isProvisionalDraft ? null : $this->draftId,
                    'revisionId' => $this->revisionId,
                    'siteId' => $this->siteId,
                    'ownerId' => $this instanceof NestedElementInterface ? $this->getOwnerId() : null,
                ],
            ]);

            // Copy
            if (!$this->getIsRevision() && $elementsService->canCopy($this)) {
                $copyId = sprintf('action-copy-%s', mt_rand());
                $items[] = [
                    'id' => $copyId,
                    'color' => Color::Fuchsia,
                    'icon' => 'clone-dashed',
                    'label' => StringHelper::upperCaseFirst(Craft::t('app', 'Copy {type}', [
                        'type' => static::lowerDisplayName(),
                    ])),
                ];

                $view = Craft::$app->getView();
                $view->registerJsWithVars(fn($id, $elementInfo) => <<<JS
(() => {
  $('#' + $id).on('activate', () => {
    Craft.cp.copyElements([$elementInfo]);
  });
})();
JS, [
                    $view->namespaceInputId($copyId),
                    [
                        'type' => static::class,
                        'id' => $this->isProvisionalDraft ? $this->getCanonicalId() : $this->id,
                        'draftId' => $this->isProvisionalDraft ? null : $this->draftId,
                        'revisionId' => $this->revisionId,
                        'fieldId' => $this instanceof NestedElementInterface ? $this->getField()?->id : null,
                        'ownerId' => $this instanceof NestedElementInterface ? $this->getOwnerId() : null,
                        'siteId' => $this->siteId,
                    ],
                ]);
            }
        }

        return $items;
    }

    /**
     * Returns destructive action menu items for the element’s edit screens.
     *
     * See [[\craft\helpers\Cp::disclosureMenu()]] for documentation on supported item properties.
     *
     * `'destructive' => true` will be automatically added to all returned items.
     *
     * @return array
     * @see getActionMenuItems()
     * @see Cp::disclosureMenu()
     * @since 5.0.0
     */
    protected function destructiveActionMenuItems(): array
    {
        $items = [];

        $elementsService = Craft::$app->getElements();
        $user = Craft::$app->getUser()->getIdentity();

        // Figure out what we're dealing with here
        $isCanonical = $this->getIsCanonical();
        $isDraft = $this->getIsDraft();
        $isUnpublishedDraft = $this->getIsUnpublishedDraft();
        $isCurrent = $isCanonical || $this->isProvisionalDraft;
        $canonical = $this->getCanonical(true);
        $redirectUrl = ElementHelper::postEditUrl($this);

        // Is this a new site that isn’t supported by the canonical element yet?
        if ($isUnpublishedDraft) {
            $isNewSite = true;
        } elseif ($isDraft) {
            $isNewSite = !static::find()
                ->id($this->getCanonicalId())
                ->siteId($this->siteId)
                ->status(null)
                ->exists();
        } else {
            $isNewSite = false;
        }

        // Permissions
        $canDeleteDraft = $isDraft && !$this->isProvisionalDraft && $elementsService->canDelete($this, $user);
        $canDeleteCanonical = $elementsService->canDelete($canonical, $user);
        $canDeleteCanonicalForSite = $elementsService->canDeleteForSite($canonical, $user);
        $canDeleteForSite = (
            ElementHelper::isMultiSite($this) &&
            (($isCurrent && $canDeleteCanonicalForSite) || ($canDeleteDraft && $isNewSite)) &&
            $elementsService->canDeleteForSite($this, $user)
        );

        if ($isCurrent) {
            // Delete for site
            if ($canDeleteForSite) {
                $items[] = [
                    'icon' => 'remove',
                    'label' => StringHelper::upperCaseFirst(Craft::t('app', 'Delete {type} for this site', [
                        'type' => $isUnpublishedDraft ? Craft::t('app', 'draft') : static::lowerDisplayName(),
                    ])),
                    'action' => 'elements/delete-for-site',
                    'params' => [
                        'elementId' => $this->getCanonicalId(),
                        'siteId' => $this->siteId,
                    ],
                    'redirect' => "$redirectUrl#",
                    'confirm' => Craft::t('app', 'Are you sure you want to delete the {type} for this site?', [
                        'type' => $isUnpublishedDraft ? Craft::t('app', 'draft') : static::lowerDisplayName(),
                    ]),
                    'destructive' => true,
                ];
            }

            // Delete
            if ($canDeleteCanonical) {
                $items[] = [
                    'icon' => 'trash',
                    'label' => StringHelper::upperCaseFirst(Craft::t('app', 'Delete {type}', [
                        'type' => $isUnpublishedDraft ? Craft::t('app', 'draft') : static::lowerDisplayName(),
                    ])),
                    'action' => $isUnpublishedDraft ? 'elements/delete-draft' : 'elements/delete',
                    'params' => [
                        'elementId' => $this->getCanonicalId(),
                        'siteId' => $this->siteId,
                    ],
                    'redirect' => "$redirectUrl#",
                    'confirm' => Craft::t('app', 'Are you sure you want to delete this {type}?', [
                        'type' => $isUnpublishedDraft ? Craft::t('app', 'draft') : static::lowerDisplayName(),
                    ]),
                    'destructive' => true,
                ];
            }
        } elseif ($isDraft && $canDeleteDraft) {
            // Delete draft for site
            if ($canDeleteForSite) {
                $items[] = [
                    'icon' => 'remove',
                    'label' => StringHelper::upperCaseFirst(Craft::t('app', 'Delete {type} for this site', [
                        'type' => Craft::t('app', 'draft'),
                    ])),
                    'action' => 'elements/delete-for-site',
                    'params' => [
                        'elementId' => $this->getCanonicalId(),
                        'siteId' => $this->siteId,
                        'draftId' => $this->draftId,
                    ],
                    'redirect' => "$redirectUrl#",
                    'confirm' => Craft::t('app', 'Are you sure you want to delete the {type} for this site?', [
                        'type' => static::lowerDisplayName(),
                    ]),
                    'destructive' => true,
                ];
            }

            // Delete draft
            $items[] = [
                'icon' => 'trash',
                'label' => StringHelper::upperCaseFirst(Craft::t('app', 'Delete {type}', [
                    'type' => Craft::t('app', 'draft'),
                ])),
                'action' => 'elements/delete-draft',
                'params' => [
                    'elementId' => $this->getCanonicalId(),
                    'siteId' => $this->siteId,
                    'draftId' => $this->draftId,
                ],
                'redirect' => $canonical->getCpEditUrl(),
                'confirm' => Craft::t('app', 'Are you sure you want to delete this {type}?', [
                    'type' => Craft::t('app', 'draft'),
                ]),
                'destructive' => true,
            ];
        }

        return $items;
    }

    /**
     * @inheritdoc
     */
    public function getPreviewTargets(): array
    {
        $previewTargets = $this->previewTargets();

        // Fire a 'registerPreviewTargets' event
        if ($this->hasEventHandlers(self::EVENT_REGISTER_PREVIEW_TARGETS)) {
            $event = new RegisterPreviewTargetsEvent(['previewTargets' => $previewTargets]);
            $this->trigger(self::EVENT_REGISTER_PREVIEW_TARGETS, $event);
            $previewTargets = $event->previewTargets;
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
            $previewTarget['url'] = UrlHelper::siteUrl($previewTarget['url'], siteId: $this->siteId);
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
        $previewTargets = [];

        $url = $this->getUrl();
        if ($url) {
            $previewTargets[] = [
                'label' => Craft::t('app', 'Primary {type} page', [
                    'type' => static::lowerDisplayName(),
                ]),
                'url' => $url,
            ];
        }

        return $previewTargets;
    }

    /**
     * @inheritdoc
     */
    public function getThumbHtml(int $size): ?string
    {
        $thumbField = $this->getFieldLayout()?->getThumbField();
        if ($thumbField) {
            $thumbHtml = $thumbField->thumbHtml($this, $size);
            if ($thumbHtml) {
                return $thumbHtml;
            }
        }

        $thumbUrl = $this->thumbUrl($size);

        if ($thumbUrl !== null) {
            return Html::tag('div', '', [
                'class' => array_filter([
                    'thumb',
                    $this->hasCheckeredThumb() ? 'checkered' : null,
                    $this->hasRoundedThumb() ? 'rounded' : null,
                ]),
                'data' => [
                    'sizes' => sprintf('calc(%srem/16)', $size),
                    'srcset' => sprintf('%s %sw, %s %sw', $thumbUrl, $size, $this->thumbUrl($size * 2), $size * 2),
                    'alt' => $this->thumbAlt(),
                    'animated' => $this->couldHaveAnimatedThumb(),
                ],
            ]);
        }

        $thumbSvg = $this->thumbSvg();
        if ($thumbSvg !== null) {
            $thumbSvg = Html::svg($thumbSvg, false, true);
            $alt = $this->thumbAlt();
            if ($alt !== null) {
                $thumbSvg = Html::prependToTag($thumbSvg, Html::tag('title', Html::encode($alt)));
            }
            $thumbSvg = Html::modifyTagAttributes($thumbSvg, ['role' => 'img']);
            return Html::tag('div', $thumbSvg, [
                'class' => array_filter([
                    'thumb',
                    $this->hasRoundedThumb() ? 'rounded' : null,
                ]),
            ]);
        }

        return null;
    }

    /**
     * Returns the URL to the element’s thumbnail, if it has one.
     *
     * @param int $size The maximum width and height the thumbnail should have.
     * @return string|null
     * @since 5.0.0
     */
    protected function thumbUrl(int $size): ?string
    {
        return null;
    }

    /**
     * Returns the element’s thumbnail SVG contents, which should be used as a fallback when [[getThumbUrl()]]
     * returns `null`.
     *
     * @return string|null
     * @since 4.5.0
     */
    protected function thumbSvg(): ?string
    {
        return null;
    }

    /**
     * Returns alt text for the element’s thumbnail.
     *
     * @return string|null
     * @since 5.0.0
     */
    protected function thumbAlt(): ?string
    {
        return null;
    }

    /**
     * Returns whether the element’s thumbnail should have a checkered background.
     *
     * @return bool
     * @since 5.0.0
     */
    protected function hasCheckeredThumb(): bool
    {
        return false;
    }

    /**
     * Returns whether the element’s thumbnail should be rounded.
     *
     * @return bool
     * @since 5.0.0
     */
    protected function hasRoundedThumb(): bool
    {
        return false;
    }

    /**
     * Returns whether the element’s thumbnail is potentially animated.
     *
     * @return boolean
     * @since 5.7.0
     */
    protected function couldHaveAnimatedThumb(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getEnabledForSite(?int $siteId = null): ?bool
    {
        if ($siteId === null) {
            $siteId = $this->siteId;
        }
        if (is_array($this->_enabledForSite)) {
            return $this->_enabledForSite[$siteId] ?? ($siteId == $this->siteId ? true : null);
        }
        if ($siteId == $this->siteId) {
            return $this->_enabledForSite;
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function setEnabledForSite(array|bool $enabledForSite): void
    {
        if (is_array($enabledForSite)) {
            $this->_enabledForSite = array_map(fn($value) => (bool)$value, $enabledForSite);
        } else {
            $this->_enabledForSite = $enabledForSite;
        }
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?string
    {
        if ($this->getIsDraft() && !$this->isProvisionalDraft) {
            return self::STATUS_DRAFT;
        }

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
     */
    public function getRootOwner(): ElementInterface
    {
        if ($this instanceof NestedElementInterface) {
            $owner = $this->getOwner();
            if ($owner) {
                return $owner->getRootOwner();
            }
        }
        return $this;
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getLocalized(): ElementQueryInterface|ElementCollection
    {
        // Eager-loaded?
        if (($localized = $this->getEagerLoadedElements('localized')) !== null) {
            return $localized;
        }

        return static::find()
            ->id($this->id ?: false)
            ->structureId($this->structureId)
            ->siteId(['not', $this->siteId])
            ->drafts(null)
            // the provisionalDraft state could have just changed (e.g. `elements/save-draft`)
            // so don't filter based on one or the other
            ->provisionalDrafts(null)
            ->revisions(null);
    }

    /**
     * @inheritdoc
     */
    public function getNext($criteria = false): ?ElementInterface
    {
        if ($criteria !== false || !isset($this->_nextElement)) {
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
    public function getPrev($criteria = false): ?ElementInterface
    {
        if ($criteria !== false || !isset($this->_prevElement)) {
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
    public function setNext($element): void
    {
        $this->_nextElement = $element;
    }

    /**
     * @inheritdoc
     */
    public function setPrev($element): void
    {
        $this->_prevElement = $element;
    }

    /**
     * Returns the parent ID.
     *
     * @return int|null
     * @since 4.0.0
     */
    public function getParentId(): ?int
    {
        if (isset($this->_parentId)) {
            // If it's false, then we've been explicitly told there's no parent
            return $this->_parentId ?: null;
        }

        return $this->getParent()?->id;
    }

    /**
     * Sets the parent ID.
     *
     * @param int|int[]|string|false|null $parentId
     * @since 4.0.0
     */
    public function setParentId(mixed $parentId): void
    {
        if (is_array($parentId)) {
            $parentId = reset($parentId);
        }

        $this->_parentId = $parentId ?: false;
        $this->_parent = null;
    }

    /**
     * @inheritdoc
     */
    public function getParent(): ?ElementInterface
    {
        if (!isset($this->_parent)) {
            if (isset($this->_parentId)) {
                if ($this->_parentId === false) {
                    return null;
                }

                $this->_parent = static::find()
                        ->id($this->_parentId)
                        ->structureId($this->structureId)
                        ->siteId($this->siteId)
                        ->status(null)
                        ->one() ?? false;
            } else {
                $ancestors = $this->getAncestors(1);
                // Eager-loaded?
                if ($ancestors instanceof ElementCollection) {
                    $this->_parent = $ancestors->first();
                } else {
                    $this->_parent = $ancestors
                            ->status(null)
                            ->one() ?? false;
                }
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
    public function setParent(?ElementInterface $parent): void
    {
        $this->_parent = $parent;

        if ($parent) {
            $this->level = $parent->level + 1;
            $this->_parentId = $parent->id;
        } else {
            $this->level = 1;
            $this->_parentId = false;
        }
    }

    /**
     * Returns whether the element has been assigned a new parent.
     *
     * @return bool
     */
    protected function hasNewParent(): bool
    {
        if (!isset($this->_hasNewParent)) {
            $this->_hasNewParent = $this->_checkForNewParent();
        }

        return $this->_hasNewParent;
    }

    /**
     * Checks if the element has been assigned a new parent.
     *
     * @return bool
     * @see hasNewParent()
     */
    private function _checkForNewParent(): bool
    {
        // Make sure this is a structured element
        if (!$this->structureId) {
            return false;
        }

        // Is it a brand new (non-provisional) element?
        if (!isset($this->id) && !$this->isProvisionalDraft) {
            return true;
        }

        // Was a new parent ID actually submitted?
        if (!isset($this->_parentId)) {
            return false;
        }

        // If this is a provisional draft, but doesn't actually exist in the structure yet, check based on the canonical element
        if ($this->getIsDerivative() && !isset($this->lft)) {
            $element = $this->getCanonical(true);
        } else {
            $element = $this;
        }

        // Is it set to the top level now, but it hadn't been before?
        if (!$this->_parentId && $element->level !== 1) {
            return true;
        }

        // Is it set to be under a parent now, but didn't have one before?
        if ($this->_parentId && $element->level === 1) {
            return true;
        }

        // Is the parentId set to a different element ID than its previous parent?
        return $this->_parentId != static::find()
                ->ancestorOf($element)
                ->ancestorDist(1)
                ->siteId($element->siteId)
                ->status(null)
                ->select('elements.id')
                ->scalar();
    }

    /**
     * @inheritdoc
     */
    public function getAncestors(?int $dist = null): ElementQueryInterface|ElementCollection
    {
        // Eager-loaded?
        if (($ancestors = $this->getEagerLoadedElements('ancestors')) !== null) {
            if ($dist === null) {
                return $ancestors;
            }
            return $ancestors->filter(fn(ElementInterface $element) => $element->level >= $this->level - $dist);
        }

        return $this->ancestors()->ancestorDist($dist);
    }

    /**
     * Returns an element query for fetching the element’s ancestors.
     *
     * @return ElementQueryInterface
     * @since 5.6.8
     */
    protected function ancestors(): ElementQueryInterface
    {
        return static::find()
            ->structureId($this->structureId)
            ->ancestorOf($this)
            ->siteId($this->siteId);
    }

    /**
     * @inheritdoc
     */
    public function getDescendants(?int $dist = null): ElementQueryInterface|ElementCollection
    {
        // Eager-loaded?
        if (($descendants = $this->getEagerLoadedElements('descendants')) !== null) {
            if ($dist === null) {
                return $descendants;
            }
            return $descendants->filter(fn(ElementInterface $element) => $element->level <= $this->level + $dist);
        }

        return $this->descendants()->descendantDist($dist);
    }

    /**
     * Returns an element query for fetching the element’s descendants.
     *
     * @return ElementQueryInterface
     * @since 5.6.8
     */
    protected function descendants(): ElementQueryInterface
    {
        return static::find()
            ->structureId($this->structureId)
            ->descendantOf($this)
            ->siteId($this->siteId);
    }

    /**
     * @inheritdoc
     */
    public function getChildren(): ElementQueryInterface|ElementCollection
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
    public function getSiblings(): ElementQueryInterface|ElementCollection
    {
        return static::find()
            ->structureId($this->structureId)
            ->siblingOf($this)
            ->siteId($this->siteId);
    }

    /**
     * @inheritdoc
     */
    public function getPrevSibling(): ?ElementInterface
    {
        if (!isset($this->_prevSibling)) {
            /** @var ElementQuery $query */
            $query = static::find();
            $query->structureId = $this->structureId;
            $query->prevSiblingOf = $this;
            $query->siteId = $this->siteId;
            $query->status(null);
            $this->_prevSibling = $query->one();

            if (!isset($this->_prevSibling)) {
                $this->_prevSibling = false;
            }
        }

        return $this->_prevSibling ?: null;
    }

    /**
     * @inheritdoc
     */
    public function getNextSibling(): ?ElementInterface
    {
        if (!isset($this->_nextSibling)) {
            /** @var ElementQuery $query */
            $query = static::find();
            $query->structureId = $this->structureId;
            $query->nextSiblingOf = $this;
            $query->siteId = $this->siteId;
            $query->status(null);
            $this->_nextSibling = $query->one();

            if (!isset($this->_nextSibling)) {
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
        if ($descendants instanceof ElementCollection) {
            return $descendants->isNotEmpty();
        }
        return $descendants->exists();
    }

    /**
     * @inheritdoc
     */
    public function getTotalDescendants(): int
    {
        $descendants = $this->getDescendants();
        if ($descendants instanceof ElementCollection) {
            return $descendants->count();
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
        if ($this->root == $element->root && isset($this->level) && $this->level == $element->level) {
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
     * @phpstan-ignore-next-line
     */
    public function offsetExists($offset): bool
    {
        return (
            $offset === 'title' ||
            /** @phpstan-ignore-next-line */
            ($this->hasEagerLoadedElements($offset) && !($this->_lazyEagerLoadedElements[$offset] ?? false)) ||
            parent::offsetExists($offset) ||
            /** @phpstan-ignore-next-line */
            $this->fieldByHandle($offset)
        );
    }

    /**
     * @inheritdoc
     */
    public function setAttributesFromRequest(array $values): void
    {
        $this->setAttributes($values);
    }

    /**
     * @inheritdoc
     */
    public function getAttributeStatus(string $attribute): ?array
    {
        if ($this->isAttributeModified($attribute)) {
            return [
                AttributeStatus::Modified,
                Craft::t('app', 'This field has been modified.'),
            ];
        }

        if ($this->isAttributeOutdated($attribute)) {
            return [
                AttributeStatus::Outdated,
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
        if (!static::trackChanges() || $this->getIsCanonical() || $this->getIsRevision()) {
            return [];
        }

        if (!isset($this->_outdatedAttributes)) {
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

        if (!isset($this->_modifiedAttributes)) {
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
    public function setDirtyAttributes(array $names, bool $merge = true): void
    {
        if ($merge && !empty($this->_dirtyAttributes)) {
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
    public function getTitleTranslationDescription(): ?string
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
    public function getIsSlugTranslatable(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getSlugTranslationDescription(): ?string
    {
        return ElementHelper::translationDescription(Field::TRANSLATION_METHOD_SITE);
    }

    /**
     * @inheritdoc
     */
    public function getSlugTranslationKey(): string
    {
        return ElementHelper::translationKey($this, Field::TRANSLATION_METHOD_SITE);
    }

    /**
     * @inheritdoc
     */
    public function getFieldValues(?array $fieldHandles = null): array
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
    public function getSerializedFieldValues(?array $fieldHandles = null): array
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
    public function getSerializedFieldValuesForDb(?array $fieldHandles = null): array
    {
        $serializedValues = [];

        foreach ($this->fieldLayoutFields() as $field) {
            if ($fieldHandles === null || in_array($field->handle, $fieldHandles, true)) {
                $value = $this->getFieldValue($field->handle);
                $serializedValues[$field->handle] = $field->serializeValueForDb($value, $this);
            }
        }

        return $serializedValues;
    }

    /**
     * @inheritdoc
     */
    public function setFieldValues(array $values): void
    {
        foreach ($values as $fieldHandle => $value) {
            $this->setFieldValue($fieldHandle, $value);
        }
    }

    private function clonedFieldValue(string $fieldHandle): mixed
    {
        $value = $this->getFieldValue($fieldHandle);
        if (is_object($value) && !$value instanceof UnitEnum && !$value instanceof ContentBlock) {
            return clone $value;
        }
        return $value;
    }

    /**
     * @inheritdoc
     */
    public function getFieldValue(string $fieldHandle): mixed
    {
        // Was this field’s value eager-loaded?
        if ($this->hasEagerLoadedElements($fieldHandle) && !($this->_lazyEagerLoadedElements[$fieldHandle] ?? false)) {
            return $this->getEagerLoadedElements($fieldHandle);
        }

        // Make sure the value has been normalized
        $this->normalizeFieldValue($fieldHandle);

        return $this->getBehavior('customFields')->$fieldHandle;
    }

    /**
     * @inheritdoc
     */
    public function setFieldValue(string $fieldHandle, mixed $value): void
    {
        $behavior = $this->getBehavior('customFields');
        $behavior->$fieldHandle = $value;

        // Don't assume that $value has been normalized
        unset($this->_normalizedFieldValues[$fieldHandle]);

        // If the element is fully initialized, mark the value as dirty
        if ($this->_initialized) {
            $this->_dirtyFields[$fieldHandle] = true;
        }

        // If the field value was previously eager-loaded, undo that
        unset($this->_eagerLoadedElements[$fieldHandle]);
        unset($this->_eagerLoadedElementCounts[$fieldHandle]);
    }

    /**
     * @inheritdoc
     */
    public function setFieldValueFromRequest(string $fieldHandle, mixed $value): void
    {
        $field = $this->fieldByHandle($fieldHandle);

        if (!$field) {
            throw new InvalidFieldException($fieldHandle);
        }

        // Normalize it now in case the system language changes later
        // (we'll do this with the value directly rather than using setFieldValue() + normalizeFieldValue(),
        // because it's slightly more efficient, and to workaround an infinite loop bug caused by Matrix
        // needing to render an object template on the owner element during normalization, which would in turn
        // cause the Matrix field value to be (re-)normalized based on the POST data, and on and on...)
        $value = $field->normalizeValueFromRequest($value, $this);
        $this->setFieldValue($field->handle, $value);
        $this->_normalizedFieldValues[$field->handle] = true;
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
        if (!static::trackChanges() || $this->getIsCanonical() || $this->getIsRevision()) {
            return [];
        }

        if (!isset($this->_outdatedFields)) {
            $query = (new Query())
                ->select(['layoutElementUid'])
                ->from(Table::CHANGEDFIELDS)
                ->where([
                    'elementId' => $this->getCanonicalId(),
                    'siteId' => $this->siteId,
                ]);

            if ($this->dateLastMerged) {
                $query->andWhere(['>=', 'dateUpdated', Db::prepareDateForDb($this->dateLastMerged)]);
            } else {
                $query->andWhere(['>=', 'dateUpdated', Db::prepareDateForDb($this->dateCreated)]);
            }

            $this->_outdatedFields = $this->_layoutElementUids2fieldHandles($query->column());
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
                ->select('layoutElementUid')
                ->from(Table::CHANGEDFIELDS)
                ->where(['elementId' => $this->id]);

            if (!$anySite) {
                $query->andWhere(['siteId' => $this->siteId]);
            }

            $this->_modifiedFields[$key] = $this->_layoutElementUids2fieldHandles($query->column());
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
            return array_map(fn(FieldInterface $field) => $field->handle, $this->fieldLayoutFields());
        }

        return array_keys($this->_dirtyFields);
    }

    /**
     * @inheritdoc
     */
    public function setDirtyFields(array $fieldHandles, bool $merge = true): void
    {
        if ($merge && !empty($this->_dirtyFields)) {
            $this->_dirtyFields = array_merge($this->_dirtyFields, array_flip($fieldHandles));
        } else {
            $this->_dirtyFields = array_flip($fieldHandles);
        }

        $this->_allDirty = false;
    }

    /**
     * Returns field handles based on a list of field layout element UUIDs.
     *
     * @param string[] $uids
     * @return array
     */
    private function _layoutElementUids2fieldHandles(array $uids): array
    {
        $uids = array_flip($uids);
        $handles = [];

        if (!empty($uids)) {
            foreach ($this->getFieldLayout()->getCustomFieldElements() as $layoutElement) {
                if (isset($uids[$layoutElement->uid])) {
                    $handles[$layoutElement->attribute()] = true;
                }
            }
        }

        return $handles;
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
    public function markAsDirty(): void
    {
        $this->_allDirty = true;
    }

    /**
     * @inheritdoc
     */
    public function markAsClean(): void
    {
        $this->_allDirty = false;
        $this->_dirtyAttributes = [];
        $this->_dirtyFields = [];

        if (static::hasTitles()) {
            $this->_savedTitle = $this->title;
        }
    }

    /**
     * @inheritdoc
     */
    public function setFieldValuesFromRequest(string $paramNamespace = ''): void
    {
        $this->setFieldParamNamespace($paramNamespace);

        if (isset($this->_fieldParamNamePrefix)) {
            $values = Craft::$app->getRequest()->getBodyParam($paramNamespace, []);
        } else {
            $values = Craft::$app->getRequest()->getBodyParams();
        }

        // Run through this multiple times, in case any fields become visible as a result of other field value changes
        $processedFields = [];
        do {
            $processedAnyFields = false;

            foreach ($this->fieldLayoutFields(editableOnly: true) as $field) {
                // Have we already processed this field?
                if (isset($processedFields[$field->handle])) {
                    continue;
                }

                $processedFields[$field->handle] = true;
                $processedAnyFields = true;

                // Do we have any post data for this field?
                if (isset($values[$field->handle])) {
                    $value = $values[$field->handle];
                } elseif (
                    isset($this->_fieldParamNamePrefix) &&
                    UploadedFile::getInstancesByName("$this->_fieldParamNamePrefix.$field->handle")
                ) {
                    // A file was uploaded for this field
                    $value = null;
                } else {
                    continue;
                }

                $this->setFieldValueFromRequest($field->handle, $value);
            }
        } while ($processedAnyFields);
    }

    /**
     * @inheritdoc
     */
    public function getFieldParamNamespace(): ?string
    {
        return $this->_fieldParamNamePrefix;
    }

    /**
     * @inheritdoc
     */
    public function setFieldParamNamespace(string $namespace): void
    {
        $this->_fieldParamNamePrefix = $namespace !== '' ? $namespace : null;
    }

    /**
     * @inheritdoc
     */
    public function getFieldContext(): string
    {
        return Craft::$app->getFields()->fieldContext;
    }

    /**
     * @inheritdoc
     */
    public function getGeneratedFieldValues(): array
    {
        return $this->_generatedFieldValues ?? [];
    }

    /**
     * @inheritdoc
     */
    public function setGeneratedFieldValues(array $values): void
    {
        $this->_generatedFieldValues = $values;
    }

    /**
     * @inheritdoc
     */
    public function getInvalidNestedElementIds(): array
    {
        return $this->_invalidNestedElementIds;
    }

    /**
     * @inheritdoc
     */
    public function addInvalidNestedElementIds(array $ids): void
    {
        array_push($this->_invalidNestedElementIds, ...$ids);
    }

    /**
     * @inheritdoc
     */
    public function hasEagerLoadedElements(string $handle): bool
    {
        if (!isset($this->_eagerLoadedElements[$handle])) {
            // See if we have it stored with the field layout provider’s handle
            $providerHandle = $this->providerHandle();
            if ($providerHandle !== null && isset($this->_eagerLoadedElements["$providerHandle:$handle"])) {
                $handle = "$providerHandle:$handle";
            }
        }

        return isset($this->_eagerLoadedElements[$handle]);
    }

    /**
     * @inheritdoc
     */
    public function getEagerLoadedElements(string $handle): ?ElementCollection
    {
        if (!isset($this->_eagerLoadedElements[$handle])) {
            // See if we have it stored with the field layout provider’s handle
            $providerHandle = $this->providerHandle();
            if ($providerHandle !== null && isset($this->_eagerLoadedElements["$providerHandle:$handle"])) {
                $handle = "$providerHandle:$handle";
            } else {
                return null;
            }
        }

        $elements = $this->_eagerLoadedElements[$handle];
        ElementHelper::setNextPrevOnElements($elements);
        return $elements;
    }

    /**
     * @inheritdoc
     */
    public function setEagerLoadedElements(string $handle, array $elements, EagerLoadPlan $plan): void
    {
        switch ($plan->handle) {
            case 'parent':
                $this->_parent = $elements[0] ?? false;
                break;
            case 'currentRevision':
                $this->_currentRevision = $elements[0] ?? false;
                break;
            case 'draftCreator':
                if ($behavior = $this->getBehavior('draft')) {
                    /** @var DraftBehavior $behavior */
                    /** @var User[] $elements */
                    $behavior->setCreator($elements[0] ?? null);
                }
                break;
            case 'revisionCreator':
                if ($behavior = $this->getBehavior('revision')) {
                    /** @var RevisionBehavior $behavior */
                    /** @var User[] $elements */
                    $behavior->setCreator($elements[0] ?? null);
                }
                break;
            default:
                // Fire a 'setEagerLoadedElements' event
                if ($this->hasEventHandlers(self::EVENT_SET_EAGER_LOADED_ELEMENTS)) {
                    $event = new SetEagerLoadedElementsEvent([
                        'handle' => $handle,
                        'elements' => $elements,
                        'plan' => $plan,
                    ]);
                    $this->trigger(self::EVENT_SET_EAGER_LOADED_ELEMENTS, $event);
                    if ($event->handled) {
                        break;
                    }
                }

                // No takers. Just store it in the internal array then.
                $this->_eagerLoadedElements[$handle] = ElementCollection::make($elements);
        }
    }

    /**
     * @inheritdoc
     */
    public function setLazyEagerLoadedElements(string $handle, bool $value = true): void
    {
        $this->_lazyEagerLoadedElements[$handle] = $value;
    }

    /**
     * @inheritdoc
     */
    public function getEagerLoadedElementCount(string $handle): ?int
    {
        if (!isset($this->_eagerLoadedElementCounts[$handle])) {
            // See if we have it stored with the field layout provider’s handle
            $providerHandle = $this->providerHandle();
            if ($providerHandle !== null && isset($this->_eagerLoadedElements["$providerHandle:$handle"])) {
                $handle = "$providerHandle:$handle";
            }
        }

        return $this->_eagerLoadedElementCounts[$handle] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function setEagerLoadedElementCount(string $handle, int $count): void
    {
        $this->_eagerLoadedElementCounts[$handle] = $count;
    }

    private function providerHandle(): ?string
    {
        try {
            return $this->getFieldLayout()?->provider?->getHandle();
        } catch (InvalidConfigException) {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function getIsFresh(): bool
    {
        if ($this->hasErrors()) {
            return false;
        }

        if (!isset($this->siteSettingsId)) {
            return true;
        }

        return $this->_isFresh ?? false;
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
    public function setRevisionCreatorId(?int $creatorId): void
    {
        $this->revisionCreatorId = $creatorId;
    }

    /**
     * @inheritdoc
     */
    public function setRevisionNotes(?string $notes): void
    {
        $this->revisionNotes = $notes;
    }

    /**
     * @inheritdoc
     */
    public function getCurrentRevision(): ?ElementInterface
    {
        if (!$this->id) {
            return null;
        }

        if (!isset($this->_currentRevision)) {
            $canonical = $this->getCanonical(true);
            $this->_currentRevision = static::find()
                ->siteId($canonical->siteId)
                ->revisionOf($canonical->id)
                ->dateCreated($canonical->dateUpdated)
                ->status(null)
                ->orderBy(['num' => SORT_DESC])
                ->one() ?: false;
        }

        return $this->_currentRevision ?: null;
    }

    /**
     * @inheritdoc
     */
    public function getIsCrossSiteCopyable(): bool
    {
        if (!isset($this->_isCrossSiteCopyable)) {
            $this->_isCrossSiteCopyable = (
                Craft::$app->getIsMultiSite() &&
                // check if user can edit this element in other sites
                count(ElementHelper::editableSiteIdsForElement($this)) > 1 &&
                // also check if the element exists in other sites
                !empty(array_diff(array_keys(ElementHelper::siteStatusesForElement($this, true)), [$this->siteId]))
            );
        }

        return $this->_isCrossSiteCopyable;
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function getHtmlAttributes(string $context): array
    {
        $htmlAttributes = ArrayHelper::merge($this->htmlAttributes($context), [
            'data' => [
                'disallow-status' => !$this->showStatusField(),
            ],
        ]);

        // Fire a 'registerHtmlAttributes' event
        if ($this->hasEventHandlers(self::EVENT_REGISTER_HTML_ATTRIBUTES)) {
            $event = new RegisterElementHtmlAttributesEvent(['htmlAttributes' => $htmlAttributes]);
            $this->trigger(self::EVENT_REGISTER_HTML_ATTRIBUTES, $event);
            return $event->htmlAttributes;
        }

        return $htmlAttributes;
    }

    /**
     * Returns any attributes that should be included in the element’s chips and cards.
     *
     * @param string $context The context that the element is being rendered in ('index', 'modal', 'field', or 'settings'.)
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
    public function getAttributeHtml(string $attribute): string
    {
        // Fire a 'defineAttributeHtml' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_ATTRIBUTE_HTML)) {
            $event = new DefineAttributeHtmlEvent([
                'attribute' => $attribute,
            ]);
            $this->trigger(self::EVENT_DEFINE_ATTRIBUTE_HTML, $event);
            if (isset($event->html)) {
                return $event->html;
            }
        }

        return $this->attributeHtml($attribute);
    }

    /**
     * @inheritdoc
     */
    public function getInlineAttributeInputHtml(string $attribute): string
    {
        // Fire a 'defineInlineAttributeInputHtml' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_INLINE_ATTRIBUTE_INPUT_HTML)) {
            $event = new DefineAttributeHtmlEvent(['attribute' => $attribute]);
            $this->trigger(self::EVENT_DEFINE_INLINE_ATTRIBUTE_INPUT_HTML, $event);
            if (isset($event->html)) {
                return $event->html;
            }
        }

        return $this->inlineAttributeInputHtml($attribute);
    }

    /**
     * Returns the HTML that should be shown for a given attribute in table and card views.
     *
     * For example, if your elements have an `email` attribute that you want to wrap in a `mailto:` link, your
     * `attributeHtml()` method could do this:
     *
     * ```php
     * return match ($attribute) {
     *     'email' => $this->email ? Html::mailto(Html::encode($this->email)) : '',
     *     default => parent::attributeHtml($attribute),
     * };
     * ```
     *
     * ::: warning
     * All untrusted text should be passed through [[Html::encode()]] to prevent XSS attacks.
     * :::
     *
     * By default, the following will be returned:
     *
     * - If the attribute name is `link` or `uri`, it will be linked to the front-end URL.
     * - If the attribute is a custom field handle, it will pass the responsibility off to the field type.
     * - If the attribute value is a [[DateTime]] object, the date will be formatted with a localized date format.
     * - For anything else, it will output the attribute value as a string.
     *
     * @param string $attribute The attribute name.
     * @return string The HTML that should be shown for a given attribute in table and card views.
     * @throws InvalidConfigException
     * @see getAttributeHtml()
     * @since 5.0.0
     */
    protected function attributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'id':
                return (string)$this->getCanonicalId();
            case 'uid':
                return $this->getCanonicalUid();
            case 'ancestors':
                $element = $this->isProvisionalDraft ? $this->getCanonical() : $this;
                $ancestors = $element->getAncestors();
                if (!$ancestors instanceof ElementCollection || $ancestors->isEmpty()) {
                    return '';
                }
                $html = Html::beginTag('ul', ['class' => 'path']);
                foreach ($ancestors as $ancestor) {
                    $html .= Html::tag('li', Cp::elementChipHtml($ancestor));
                }
                return $html . Html::endTag('ul');

            case 'parent':
                $element = $this->isProvisionalDraft ? $this->getCanonical() : $this;
                $parent = $element->getParent();
                return $parent ? Cp::elementChipHtml($parent) : '';

            case 'status':
                return Cp::componentStatusLabelHtml($this);

            case 'link':
                $element = $this->isProvisionalDraft ? $this->getCanonical() : $this;
                if (ElementHelper::isDraftOrRevision($element)) {
                    return '';
                }

                $url = $element->getUrl();

                if ($url !== null) {
                    return ElementHelper::linkAttributeHtml($url);
                }

                return '';

            case 'uri':
                $element = $this->isProvisionalDraft ? $this->getCanonical() : $this;
                if ($element->getIsDraft() && ElementHelper::isTempSlug($element->slug)) {
                    return '';
                }

                $url = $element->getUrl();

                if ($url !== null) {
                    if ($element->getIsHomepage()) {
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

                        $value = str_replace($find, $replace, $element->uri);
                    }

                    return ElementHelper::uriAttributeHtml($value, $url);
                }

                return '';

            case 'slug':
                if ($this->getIsDraft() && ElementHelper::isTempSlug($this->slug)) {
                    return '';
                }

                return Html::encode($this->slug);

            case 'revisionNotes':
                $element = $this->isProvisionalDraft ? $this->getCanonical() : $this;
                $revision = $element->getCurrentRevision();
                if (!$revision) {
                    return '';
                }
                /** @var RevisionBehavior|null $behavior */
                $behavior = $revision->getBehavior('revision');
                if (!$behavior) {
                    return '';
                }
                return Html::encode($behavior->revisionNotes);

            case 'revisionCreator':
                $element = $this->isProvisionalDraft ? $this->getCanonical() : $this;
                $revision = $element->getCurrentRevision();
                if (!$revision) {
                    return '';
                }
                /** @var RevisionBehavior|null $behavior */
                $behavior = $revision->getBehavior('revision');
                if (!$behavior) {
                    return '';
                }
                $creator = $behavior->getCreator();
                return $creator ? Cp::elementChipHtml($creator) : '';

            case 'drafts':
                $element = $this->isProvisionalDraft ? $this->getCanonical() : $this;
                if (!$element->hasEagerLoadedElements('drafts')) {
                    return '';
                }

                $drafts = $element->getEagerLoadedElements('drafts')->all();

                foreach ($drafts as $draft) {
                    /** @var ElementInterface|DraftBehavior $draft */
                    $draft->setUiLabel($draft->draftName);
                }

                return Cp::elementPreviewHtml(
                    $drafts,
                    showThumb: false,
                    showDraftName: false,
                );

            default:
                // Is this a custom field?
                if (preg_match('/^(field|fieldInstance):(.+)/', $attribute, $matches)) {
                    $uid = $matches[2];
                    if ($matches[1] === 'field') {
                        $field = Craft::$app->getFields()->getFieldByUid($uid);
                    } else {
                        $layoutElement = $this->getFieldLayout()?->getElementByUid($uid);
                        if ($layoutElement instanceof CustomField) {
                            try {
                                $field = $layoutElement->getField();
                            } catch (FieldNotFoundException) {
                            }
                        }
                        $field ??= null;
                    }

                    if ($field instanceof PreviewableFieldInterface) {
                        // Was this field value eager-loaded?
                        if ($field instanceof EagerLoadingFieldInterface && $this->hasEagerLoadedElements($field->handle)) {
                            $value = $this->getEagerLoadedElements($field->handle);
                        } else {
                            // The field might not actually belong to this element
                            try {
                                $value = $this->getFieldValue($field->handle);
                            } catch (InvalidFieldException) {
                                return '';
                            }
                        }

                        return $field->getPreviewHtml($value, $this);
                    }

                    return '';
                }

                return ElementHelper::attributeHtml($this->$attribute);
        }
    }

    /**
     * Returns the HTML that should be shown for a given attribute’s inline input.
     *
     * @param string $attribute The attribute name.
     * @return string The HTML that should be shown for a given attribute’s inline input.
     * @see getInlineAttributeInputHtml()
     * @since 5.0.0
     */
    protected function inlineAttributeInputHtml(string $attribute): string
    {
        // Is this a custom field?
        $field = null;
        if (preg_match('/^field:(.+)/', $attribute, $matches)) {
            $fieldUid = $matches[1];
            $field = Craft::$app->getFields()->getFieldByUid($fieldUid);
        } elseif (preg_match('/^fieldInstance:(.+)/', $attribute, $matches)) {
            $instanceUid = $matches[1];
            $layoutElement = $this->getFieldLayout()?->getElementByUid($instanceUid);
            if ($layoutElement instanceof CustomField) {
                try {
                    $field = $layoutElement->getField();
                } catch (FieldNotFoundException) {
                }
            }
        }

        if ($field !== null) {
            if ($field instanceof InlineEditableFieldInterface) {
                // Was this field value eager-loaded?
                if ($field instanceof EagerLoadingFieldInterface && $this->hasEagerLoadedElements($field->handle)) {
                    $value = $this->getEagerLoadedElements($field->handle);
                } else {
                    // The field might not actually belong to this element
                    try {
                        $value = $this->getFieldValue($field->handle);
                    } catch (InvalidFieldException) {
                        return '';
                    }
                }

                return $field->getInlineInputHtml($value, $this);
            }

            return $this->getAttributeHtml($attribute);
        }

        // just go with the static output by default
        return $this->attributeHtml($attribute);
    }

    /**
     * @inheritdoc
     */
    public function getSidebarHtml(bool $static): string
    {
        $components = [];

        $metaFieldsHtml = trim($this->metaFieldsHtml($static));
        if ($metaFieldsHtml !== '') {
            $components[] = Html::tag('div', $metaFieldsHtml, ['class' => 'meta']) .
                Html::tag('h2', Craft::t('app', 'Metadata'), ['class' => 'visually-hidden']);
        }

        if (!$static && static::hasStatuses() && $this->showStatusField()) {
            // Is this a multi-site element?
            $components[] = $this->statusFieldHtml();
        }

        if ($this->hasRevisions() && !$this->getIsRevision()) {
            $components[] = $this->notesFieldHtml();
        }

        $html = implode("\n", $components);

        // Fire a 'defineSidebarHtml' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_SIDEBAR_HTML)) {
            $event = new DefineHtmlEvent(['html' => $html]);
            $this->trigger(self::EVENT_DEFINE_SIDEBAR_HTML, $event);
            return $event->html;
        }

        return $html;
    }

    /**
     * Returns the HTML for any meta fields that should be shown within the editor sidebar.
     *
     * @param bool $static Whether the fields should be static (non-interactive)
     * @return string
     * @since 3.7.0
     */
    protected function metaFieldsHtml(bool $static): string
    {
        // Fire a 'defineMetaFieldsHtml' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_META_FIELDS_HTML)) {
            $event = new DefineHtmlEvent(['static' => $static]);
            $this->trigger(self::EVENT_DEFINE_META_FIELDS_HTML, $event);
            return $event->html;
        }

        return '';
    }

    /**
     * Returns the HTML for the element’s Slug field.
     *
     * @param bool $static Whether the fields should be static (non-interactive)
     * @return string
     * @since 3.7.0
     */
    protected function slugFieldHtml(bool $static): string
    {
        $slug = isset($this->slug) && !ElementHelper::isTempSlug($this->slug) ? $this->slug : null;

        return Cp::textFieldHtml([
            'status' => $this->getAttributeStatus('slug'),
            'label' => Craft::t('app', 'Slug'),
            'siteId' => $this->siteId,
            'translatable' => $this->getIsSlugTranslatable(),
            'translationDescription' => $this->getSlugTranslationDescription(),
            'id' => 'slug',
            'name' => 'slug',
            'autocorrect' => false,
            'autocapitalize' => false,
            'value' => $slug,
            'disabled' => $static,
            'errors' => array_merge($this->getErrors('slug'), $this->getErrors('uri')),
        ]);
    }

    /**
     * Returns whether the Status field should be shown for this element.
     *
     *  If set to `false`, the element’s status can't be updated via edit forms, the Set Status action, or `resave/*` commands.
     *
     * @return bool
     * @since 4.5.0
     */
    protected function showStatusField(): bool
    {
        return true;
    }

    /**
     * Returns the status field HTML for the sidebar.
     *
     * @return string
     * @since 4.0.0
     */
    protected function statusFieldHtml(): string
    {
        $supportedSites = ElementHelper::supportedSitesForElement($this, true);
        $allEditableSiteIds = Craft::$app->getSites()->getEditableSiteIds();
        $propSites = array_values(array_filter($supportedSites, fn($site) => $site['propagate']));
        $propSiteIds = array_column($propSites, 'siteId');
        $propEditableSiteIds = array_intersect($propSiteIds, $allEditableSiteIds);
        $addlEditableSites = array_values(array_filter($supportedSites, fn($site) => !$site['propagate'] && in_array($site['siteId'], $allEditableSiteIds)));

        if (count($supportedSites) > 1) {
            $expandStatusBtn = (count($propEditableSiteIds) > 1 || $addlEditableSites)
                ? Html::button('', [
                    'class' => ['expand-status-btn', 'btn'],
                    'data' => [
                        'icon' => 'ellipsis',
                    ],
                    'title' => Craft::t('app', 'Update status for individual sites'),
                    'aria' => [
                        'expanded' => 'false',
                        'label' => Craft::t('app', 'Update status for individual sites'),
                    ],
                ])
                : '';
            $statusField = Cp::lightswitchFieldHtml([
                'fieldClass' => "enabled-for-site-$this->siteId-field",
                'label' => Craft::t('site', $this->getSite()->getName()),
                'headingSuffix' => $expandStatusBtn,
                'name' => "enabledForSite[$this->siteId]",
                'on' => $this->enabled && $this->getEnabledForSite(),
                'status' => $this->getAttributeStatus('enabled'),
            ]);
        } else {
            $statusField = Cp::lightswitchFieldHtml([
                'id' => 'enabled',
                'label' => Craft::t('app', 'Enabled'),
                'name' => 'enabled',
                'on' => $this->enabled,
                'disabled' => $this->getIsRevision(),
                'status' => $this->getAttributeStatus('enabled'),
            ]);
        }

        return Html::beginTag('fieldset') .
            Html::tag('legend', Craft::t('app', 'Status'), ['class' => 'h6']) .
            Html::tag('div', $statusField, ['class' => 'meta']) .
            Html::endTag('fieldset');
    }

    /**
     * Returns the notes field HTML for the sidebar.
     *
     * @return string
     * @since 4.0.0
     */
    protected function notesFieldHtml(): string
    {
        // todo: this should accept a $static arg
        /**
         * @var static|DraftBehavior $this
         * @phpstan-ignore varTag.nativeType
         */
        return Cp::textareaFieldHtml([
            'label' => Craft::t('app', 'Notes about your changes'),
            'labelClass' => 'h6',
            'class' => ['nicetext', 'notes'],
            'name' => 'notes',
            'value' => $this->getIsDraft() ? $this->draftNotes : $this->revisionNotes,
            'rows' => 1,
            'inputAttributes' => [
                'aria' => [
                    'label' => Craft::t('app', 'Notes about your changes'),
                ],
            ],
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

        // Fire a 'defineMetadata' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_METADATA)) {
            $event = new DefineMetadataEvent(['metadata' => $metadata]);
            $this->trigger(self::EVENT_DEFINE_METADATA, $event);
            $metadata = $event->metadata;
        }

        $formatter = Craft::$app->getFormatter();

        return array_merge([
            Craft::t('app', 'ID') => fn() => $this->id ?? false,
            Craft::t('app', 'Status') => function() {
                if (!static::hasStatuses()) {
                    return false;
                }
                if ($this->getIsDraft() && !$this->isProvisionalDraft) {
                    $icon = Html::tag('span', '', [
                        'data' => ['icon' => 'draft'],
                        'aria' => ['hidden' => 'true'],
                    ]);
                    $label = Craft::t('app', 'Draft');
                } else {
                    $status = $this->getStatus();
                    $statusDef = static::statuses()[$status] ?? null;
                    $color = $statusDef['color'] ?? $status;
                    if ($color instanceof Color) {
                        $color = $color->value;
                    }
                    $icon = Html::tag('span', '', ['class' => ['status', $color]]);
                    $label = $statusDef['label'] ?? $statusDef ?? ucfirst($status);
                }
                return $icon . Html::tag('span', $label);
            },
        ], $metadata, [
            Craft::t('app', 'Created at') => $this->dateCreated && !$this->getIsUnpublishedDraft()
                ? $formatter->asDatetime($this->dateCreated, Formatter::FORMAT_WIDTH_SHORT)
                : false,
            Craft::t('app', 'Updated at') => $this->dateUpdated && !$this->getIsUnpublishedDraft()
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
                if ($behavior->revisionNotes === null || $behavior->revisionNotes === '') {
                    return false;
                }
                return Html::encode($behavior->revisionNotes);
            },
        ]);
    }

    /**
     * Returns element metadata that should be shown within the editor sidebar.
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
        // Default to the short class name
        return (new ReflectionClass($this))->getShortName();
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

        // Fire a 'beforeSave' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE)) {
            $event = new ModelEvent(['isNew' => $isNew]);
            $this->trigger(self::EVENT_BEFORE_SAVE, $event);
            return $event->isValid;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew): void
    {
        // Update the element’s relation data
        $this->updateRelations($isNew);

        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
            $field->afterElementSave($this, $isNew);
        }

        // Fire an 'afterSave' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE)) {
            $this->trigger(self::EVENT_AFTER_SAVE, new ModelEvent([
                'isNew' => $isNew,
            ]));
        }
    }

    private function updateRelations(bool $isNew): void
    {
        if (!$this->hasFieldLayout()) {
            return;
        }

        $fields = $this->relationalFields();

        /** @var int[] $skipFieldIds */
        $skipFieldIds = [];
        /** @var array<int,int|null> $sourceSiteIds */
        $sourceSiteIds = [];
        /** @var array<int,array<int,int>> $relationData */
        $relationData = [];

        foreach ($fields as $fieldId => $instances) {
            $localizeRelations = $instances[0]->localizeRelations();
            $include = false;

            foreach ($instances as $field) {
                // Skip if nothing changed, or the element is just propagating and we're not localizing relations
                if (
                    $isNew || (
                        ($this->duplicateOf || $this->isFieldDirty($field->handle) || $field->forceUpdateRelations($this)) &&
                        (!$this->propagating || $localizeRelations)
                    )
                ) {
                    $include = true;
                    break;
                }
            }

            if ($include) {
                // Create target ID => sort order mappings for the field
                foreach ($instances as $field) {
                    $sourceSiteIds[$field->id] = $localizeRelations ? $this->siteId : null;
                    $relationData[$field->id] ??= [];
                    foreach ($field->getRelationTargetIds($this) as $targetId) {
                        if (!isset($relationData[$field->id][$targetId])) {
                            $relationData[$field->id][$targetId] = count($relationData[$field->id]) + 1;
                        }
                    }
                }
            } else {
                $skipFieldIds[] = $fieldId;
            }
        }

        // Get the old relations
        $db = Craft::$app->getDb();
        $query = (new Query())
            ->select(['id', 'fieldId', 'sourceSiteId', 'targetId', 'sortOrder'])
            ->from([Table::RELATIONS])
            ->where(['sourceId' => $this->id])
            ->andWhere(['or', ['sourceSiteId' => null], ['sourceSiteId' => $this->siteId]]);
        if (!empty(($skipFieldIds))) {
            // Exclude the skipped fields rather than listing included fields,
            // so we also get any relations for fields that aren't part of the layout
            // (https://github.com/craftcms/cms/issues/13956)
            $query->andWhere(['not', ['fieldId' => $skipFieldIds]]);
        }
        $oldRelations = $query->all($db);

        /** @var Command[] $updateCommands */
        $updateCommands = [];
        $deleteIds = [];

        foreach ($oldRelations as $relation) {
            [$relationId, $fieldId, $oldSourceSiteId, $targetId, $oldSortOrder] = [
                $relation['id'],
                $relation['fieldId'],
                $relation['sourceSiteId'],
                $relation['targetId'],
                $relation['sortOrder'],
            ];

            // Does this relation still exist?
            if (isset($relationData[$fieldId][$targetId])) {
                // Anything to update?
                if ($oldSourceSiteId != $sourceSiteIds[$fieldId] || $oldSortOrder != $relationData[$fieldId][$targetId]) {
                    $updateCommands[] = $db->createCommand()->update(Table::RELATIONS, [
                        'sourceSiteId' => $sourceSiteIds[$fieldId],
                        'sortOrder' => $relationData[$fieldId][$targetId],
                    ], ['id' => $relationId]);
                }

                // Avoid re-inserting it
                unset($relationData[$fieldId][$targetId]);
                if (empty($relationData[$fieldId])) {
                    unset($relationData[$fieldId]);
                }
            } else {
                $deleteIds[] = $relationId;
            }
        }

        if (empty($updateCommands) && empty($deleteIds) && empty($relationData)) {
            // Nothing to do here
            return;
        }

        $db->transaction(function() use ($updateCommands, $deleteIds, $relationData, $sourceSiteIds, $db) {
            foreach ($updateCommands as $command) {
                $command->execute();
            }

            // Add the new ones
            if (!empty($relationData)) {
                $values = [];
                foreach ($relationData as $fieldId => $targetIds) {
                    foreach ($targetIds as $targetId => $sortOrder) {
                        $values[] = [
                            $fieldId,
                            $this->id,
                            $sourceSiteIds[$fieldId],
                            $targetId,
                            $sortOrder,
                        ];
                    }
                }
                Db::batchInsert(Table::RELATIONS, ['fieldId', 'sourceId', 'sourceSiteId', 'targetId', 'sortOrder'], $values, $db);
            }

            if (!empty($deleteIds)) {
                Db::delete(Table::RELATIONS, [
                    'id' => $deleteIds,
                ], [], $db);
            }
        });
    }

    /**
     * @return array<int,RelationalFieldInterface[]>
     */
    private function relationalFields(): array
    {
        $fields = [];
        foreach ($this->fieldLayoutFields() as $field) {
            if ($field instanceof RelationalFieldInterface) {
                $fields[$field->id][] = $field;
            }
        }
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function afterPropagate(bool $isNew): void
    {
        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
            $field->afterElementPropagate($this, $isNew);
        }

        // Fire an 'afterPropagate' event
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

        // Fire a 'beforeDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE)) {
            $event = new ModelEvent();
            $this->trigger(self::EVENT_BEFORE_DELETE, $event);
            return $event->isValid;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterDelete(): void
    {
        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
            $field->afterElementDelete($this);
        }

        // Fire an 'afterDelete' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE)) {
            $this->trigger(self::EVENT_AFTER_DELETE);
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeDeleteForSite(): bool
    {
        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
            if (!$field->beforeElementDeleteForSite($this)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterDeleteForSite(): void
    {
        // Delete any site-specific relation data
        $this->deleteSiteRelations();

        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
            $field->afterElementDeleteForSite($this);
        }
    }

    private function deleteSiteRelations(): void
    {
        if ($this->hasFieldLayout()) {
            Db::delete(Table::RELATIONS, [
                'sourceSiteId' => $this->siteId,
                'sourceId' => $this->id,
            ]);
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

        // Fire a 'beforeRestore' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_RESTORE)) {
            $event = new ModelEvent();
            $this->trigger(self::EVENT_BEFORE_RESTORE, $event);
            return $event->isValid;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterRestore(): void
    {
        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
            $field->afterElementRestore($this);
        }

        // Fire an 'afterRestore' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_RESTORE)) {
            $this->trigger(self::EVENT_AFTER_RESTORE);
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeMoveInStructure(int $structureId): bool
    {
        // Fire a 'beforeMoveInStructure' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_MOVE_IN_STRUCTURE)) {
            $event = new ElementStructureEvent(['structureId' => $structureId]);
            $this->trigger(self::EVENT_BEFORE_MOVE_IN_STRUCTURE, $event);
            return $event->isValid;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterMoveInStructure(int $structureId): void
    {
        // Fire an 'afterMoveInStructure' event
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
    protected function normalizeFieldValue(string $fieldHandle): void
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
    protected static function findByCondition(mixed $criteria, bool $one): array|static|null
    {
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
    protected function fieldByHandle(string $handle): ?FieldInterface
    {
        // ignore if it's not a custom field handle
        if (!isset(CustomFieldBehavior::$fieldHandles[$handle])) {
            return null;
        }

        $field = $this->getFieldLayout()?->getFieldByHandle($handle);

        // nullify values for custom fields that are not part of this layout
        // https://github.com/craftcms/cms/issues/12539
        if (!$field) {
            $behavior = $this->getBehavior('customFields');
            if (isset($behavior->$handle)) {
                $behavior->$handle = null;
            }
        }

        return $field;
    }

    /**
     * Returns each of this element’s fields.
     *
     * @param bool $visibleOnly Whether to only return fields that are visible for this element
     * @param bool $editableOnly Whether to only return fields that the current user can edit
     * @return FieldInterface[] This element’s fields
     */
    protected function fieldLayoutFields(bool $visibleOnly = false, bool $editableOnly = false): array
    {
        try {
            $fieldLayout = $this->getFieldLayout();
        } catch (InvalidConfigException $e) {
            return [];
        }

        if ($fieldLayout) {
            if ($editableOnly) {
                return $fieldLayout->getEditableCustomFields($this);
            }
            if ($visibleOnly) {
                return $fieldLayout->getVisibleCustomFields($this);
            }
            return $fieldLayout->getCustomFields();
        }

        return [];
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException if [[siteId]] is invalid
     */
    public function getSite(): Site
    {
        if (isset($this->siteId)) {
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
    private function _getRelativeElement(mixed $criteria, int $dir): ?ElementInterface
    {
        if (!isset($this->id)) {
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
        $elementIds = $query->cache()->ids();
        $key = array_search($this->getCanonicalId(), $elementIds, false);

        if ($key === false || !isset($elementIds[$key + $dir])) {
            return null;
        }

        return $query
            ->id($elementIds[$key + $dir])
            ->one();
    }

    /**
     * @inheritdoc
     */
    public function render(array $variables = []): Markup
    {
        $templates = $this->partialTemplatePathCandidates();

        $refHandle = static::refHandle();
        if ($refHandle !== null) {
            $variables[$refHandle] = $this;
        }

        if ($this->hasEventHandlers(self::EVENT_RENDER)) {
            $event = new RenderElementEvent([
                'templates' => $templates,
                'variables' => $variables,
            ]);
            $this->trigger(self::EVENT_RENDER, $event);
            if (isset($event->output)) {
                return new Markup($event->output, Craft::$app->charset);
            }
            $templates = $event->templates;
            $variables = $event->variables;
        }

        if (!empty($templates)) {
            $view = Craft::$app->getView();
            foreach (Arr::sort($templates, 'priority') as $template) {
                if ($view->doesTemplateExist($template['template'], View::TEMPLATE_MODE_SITE)) {
                    $output = $view->renderTemplate($template['template'], $variables, View::TEMPLATE_MODE_SITE);
                    return new Markup($output, Craft::$app->charset);
                }
            }
        }

        // fallback to the string representation of the element
        $output = Html::tag('p', Html::encode((string)$this));
        return new Markup($output, Craft::$app->charset);
    }

    /**
     * Returns the template paths to check when rendering the element’s partial template.
     *
     * @return array{template:string,priority:int}[]
     * @since 5.8.0
     */
    protected function partialTemplatePathCandidates(): array
    {
        $refHandle = static::refHandle();
        if ($refHandle === null) {
            return [];
        }

        $templates = [];
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        $providerHandle = $this->getFieldLayout()?->provider?->getHandle();
        if ($providerHandle !== null) {
            $templates[] = [
                'template' => sprintf('%s/%s/%s', $generalConfig->partialTemplatesPath, $refHandle, $providerHandle),
                'priority' => 1,
            ];
        }

        $templates[] = [
            'template' => sprintf('%s/%s', $generalConfig->partialTemplatesPath, $refHandle),
            'priority' => 10,
        ];

        return $templates;
    }
}
