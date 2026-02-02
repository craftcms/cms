<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use ArrayIterator;
use BadMethodCallException;
use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\base\ElementTrait;
use craft\base\NestedElementInterface;
use craft\behaviors\CustomFieldBehavior;
use craft\elements\db\NestedElementQueryInterface;
use craft\events\DefineAttributeKeywordsEvent;
use craft\events\DefineUrlEvent;
use craft\events\DefineValueEvent;
use craft\events\ModelEvent;
use craft\events\RegisterPreviewTargetsEvent;
use craft\events\RenderElementEvent;
use craft\events\SetElementRouteEvent;
use craft\fieldlayoutelements\BaseField;
use craft\helpers\Cp;
use craft\helpers\ElementHelper;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\web\View;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Concerns\Draftable;
use CraftCms\Cms\Element\Enums\AttributeStatus;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Utils;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Validation\Attributes\Ruleset;
use CraftCms\Cms\Validation\Concerns\ValidatesWithRuleset;
use Deprecated;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Validation\Validator as LaravelValidator;
use Override;
use Stringable;
use Throwable;
use Traversable;
use Twig\Markup;
use yii\base\ArrayableTrait;
use yii\base\Event;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\base\UnknownPropertyException;

use function CraftCms\Cms\t;

/**
 * Element is the base class for classes representing elements in terms of objects.
 *
 * @mixin CustomFieldBehavior
 *
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
 */
#[Ruleset(ElementRules::class)]
abstract class Element extends Component implements ElementInterface
{
    use ArrayableTrait {
        toArray as traitToArray;
    }
    use Concerns\DisplayedInIndex;
    use Concerns\Draftable {
        Draftable::canCreateDrafts as traitCanCreateDrafts;
    }
    use Concerns\Eagerloadable;
    use Concerns\Exportable;
    use Concerns\HasActions;
    use Concerns\HasControlPanelUI;
    use Concerns\HasCustomFields;
    use Concerns\HasGqlType;
    use Concerns\HasSources;
    use Concerns\Queryable;
    use Concerns\Revisionable;
    use Concerns\Structurable;
    use ElementTrait {
        ElementTrait::canCreateDrafts as _;
    }
    use Macroable {
        __call as macroCall;
    }
    use ValidatesWithRuleset;

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

    public const string SCENARIO_DEFAULT = 'default';

    public const string SCENARIO_ESSENTIALS = 'essentials';

    public const string SCENARIO_LIVE = 'live';

    /**
     * {@inheritdoc}
     *
     * @return array<string, array<string>|null>
     */
    #[Override]
    public function scenarios(): array
    {
        return [
            self::SCENARIO_DEFAULT => null,
            self::SCENARIO_LIVE => null,
            self::SCENARIO_ESSENTIALS => null,
        ];
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @event RegisterElementSourcesEvent The event that is triggered when registering the available sources for the element type.
     */
    public const EVENT_REGISTER_SOURCES = 'registerSources';

    /**
     * @event RegisterElementFieldLayoutsEvent The event that is triggered when registering all of the field layouts
     * associated with elements from a given source.
     *
     * @see fieldLayouts()
     * @since 3.5.0
     */
    public const EVENT_REGISTER_FIELD_LAYOUTS = 'registerFieldLayouts';

    /**
     * @event RegisterElementActionsEvent The event that is triggered when registering the available bulk actions for the element type.
     */
    public const EVENT_REGISTER_ACTIONS = 'registerActions';

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
     * use CraftCms\Cms\Element\Element;
     * use CraftCms\Cms\Entry\Elements\Entry;
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
     *
     * @since 5.5.0
     */
    public const EVENT_REGISTER_CARD_ATTRIBUTES = 'registerCardAttributes';

    /**
     * @event RegisterElementCardAttributesEvent The event that is triggered when registering the card attributes for the element type.
     *
     * @since 5.5.0
     */
    public const EVENT_REGISTER_DEFAULT_CARD_ATTRIBUTES = 'registerDefaultCardAttributes';

    /**
     * @event RegisterPreviewTargetsEvent The event that is triggered when registering the element’s preview targets.
     *
     * @since 3.2.0
     */
    public const EVENT_REGISTER_PREVIEW_TARGETS = 'registerPreviewTargets';

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
     */
    #[Deprecated(message: 'in 4.3.0. [[\craft\services\Elements::EVENT_AUTHORIZE_VIEW]] should be used instead.')]
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
     */
    #[Deprecated(message: 'in 4.3.0. [[\craft\services\Elements::EVENT_AUTHORIZE_SAVE]] should be used instead.')]
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
     */
    #[Deprecated(message: 'in 4.3.0. [[\craft\services\Elements::EVENT_AUTHORIZE_CREATE_DRAFTS]] should be used instead.')]
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
     */
    #[Deprecated(message: 'in 4.3.0. [[\craft\services\Elements::EVENT_AUTHORIZE_DUPLICATE]] should be used instead.')]
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
     */
    #[Deprecated(message: 'in 4.3.0. [[\craft\services\Elements::EVENT_AUTHORIZE_DELETE]] should be used instead.')]
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
     */
    #[Deprecated(message: 'in 4.3.0. [[\craft\services\Elements::EVENT_AUTHORIZE_DELETE_FOR_SITE]] should be used instead.')]
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
     *
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
     * use CraftCms\Cms\Element\Element;
     * use CraftCms\Cms\Entry\Elements\Entry;
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
     * use CraftCms\Cms\Element\Element;
     * use CraftCms\Cms\Entry\Elements\Entry;
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
     * use CraftCms\Cms\Element\Element;
     * use CraftCms\Cms\Entry\Elements\Entry;
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
     * use CraftCms\Cms\Element\Element;
     * use CraftCms\Cms\Entry\Elements\Entry;
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
     * use CraftCms\Cms\Element\Element;
     * use CraftCms\Cms\Entry\Elements\Entry;
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
     *
     * @since 3.1.0
     */
    public const EVENT_BEFORE_RESTORE = 'beforeRestore';

    /**
     * @event \yii\base\Event The event that is triggered after the element is restored.
     *
     * @since 3.1.0
     */
    public const EVENT_AFTER_RESTORE = 'afterRestore';

    /**
     * @event RenderElementEvent The event that is triggered before an element is rendered.
     *
     * @since 5.7.5
     *
     * ```php
     * use CraftCms\Cms\Element\Element;
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
     * {@inheritdoc}
     */
    #[Override]
    public static function displayName(): string
    {
        return t('Element');
    }

    /**
     * {@inheritdoc}
     */
    public static function lowerDisplayName(): string
    {
        return mb_strtolower(static::displayName());
    }

    /**
     * {@inheritdoc}
     */
    public static function pluralDisplayName(): string
    {
        return t('Elements');
    }

    /**
     * {@inheritdoc}
     */
    public static function pluralLowerDisplayName(): string
    {
        return mb_strtolower(static::pluralDisplayName());
    }

    /**
     * {@inheritdoc}
     */
    public static function refHandle(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public static function trackChanges(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public static function hasTitles(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public static function hasThumbs(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public static function hasUris(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public static function isLocalized(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public static function hasStatuses(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_ENABLED => t('Enabled'),
            self::STATUS_DISABLED => t('Disabled'),
        ];
    }

    /**
     * @var array<string,int>|null
     *
     * @see validate()
     */
    private ?array $_attributeNames = null;

    /**
     * @see getCanonicalId()
     * @see setCanonicalId()
     * @see getIsCanonical()
     * @see getIsDerivative()
     */
    private ?int $_canonicalId = null;

    /**
     * @see getCanonical()
     */
    private ElementInterface|false|null $_canonical = null;

    /**
     * @see getCanonical()
     */
    private ElementInterface|false|null $_canonicalAnySite = null;

    /**
     * @see getCanonicalUid()
     */
    private ?string $_canonicalUid = null;

    /**
     * @see _outdatedAttributes()
     */
    private ?array $_outdatedAttributes = null;

    /**
     * @see _modifiedAttributes()
     */
    private ?array $_modifiedAttributes = null;

    private bool $_initialized = false;

    /**
     * @var bool Whether all attributes and field values should be considered dirty.
     *
     * @see getDirtyAttributes()
     * @see getDirtyFields()
     * @see isFieldDirty()
     */
    private bool $_allDirty = false;

    /**
     * @var array<string, int|string|bool> Record of dirty attributes.
     *
     * @see getDirtyAttributes()
     * @see isAttributeDirty()
     */
    private array $_dirtyAttributes = [];

    /**
     * @var string|null The initial title value, if there was one.
     *
     * @see getDirtyAttributes()
     */
    private ?string $_savedTitle = null;

    /**
     * @var bool|bool[]
     *
     * @see getEnabledForSite()
     * @see setEnabledForSite()
     */
    private array|bool $_enabledForSite = true;

    /**
     * @see getUiLabel()
     * @see setUiLabel()
     */
    private ?string $_uiLabel = null;

    /**
     * @var string[]
     *
     * @see getUiLabelPath()
     * @see setUiLabelPath()
     */
    private array $_uiLabelPath = [];

    /**
     * @see getIsFresh()
     * @see setIsFresh()
     */
    private ?bool $_isFresh = null;

    /**
     * @see toArray()
     */
    private $_serializeFields = false;

    /**
     * @see getIsCrossSiteCopyable()
     */
    private bool $_isCrossSiteCopyable;

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    #[Override]
    public function __clone()
    {
        parent::__clone();

        // Mark all fields as dirty
        $this->_allDirty = true;
        $this->_hasNewParent = null;
    }

    /**
     * Returns the string representation of the element.
     */
    public function __toString(): string
    {
        if (isset($this->title) && $this->title !== '') {
            return $this->title;
        }

        if (! $this->id || $this->getIsUnpublishedDraft()) {
            return t('New {type}', [
                'type' => static::lowerDisplayName(),
            ]);
        }

        return sprintf('%s %s', static::displayName(), $this->id);
    }

    /**
     * Checks if a property is set.
     *
     * This method will check if $name is one of the following:
     * - "title"
     * - a magic property supported by [[\yii\base\Component::__isset()]]
     * - a custom field handle
     *
     * @param  string  $name  The property name
     * @return bool Whether the property is set
     */
    #[Override]
    public function __isset($name): bool
    {
        // Is this the "field:handle" syntax?
        if (str_starts_with($name, 'field:')) {
            return $this->fieldByHandle(substr($name, 6)) !== null;
        }
        if ($name === 'title') {
            return true;
        }
        if ($this->hasEagerLoadedElements($name)) {
            return true;
        }
        if (parent::__isset($name)) {
            return true;
        }

        return (bool) $this->fieldByHandle($name);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function __get($name)
    {
        // Is $name a set of eager-loaded elements?
        if ($this->hasEagerLoadedElements($name) && ! ($this->_lazyEagerLoadedElements[$name] ?? false)) {
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
     * {@inheritdoc}
     */
    #[Override]
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
     * {@inheritdoc}
     */
    #[Override]
    public function __call($name, $params)
    {
        if (str_starts_with($name, 'isFieldEmpty:')) {
            return $this->isFieldEmpty(substr($name, 13));
        }

        try {
            return $this->macroCall($name, $params);
        } catch (BadMethodCallException) {
            return parent::__call($name, $params);
        }
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

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function init(): void
    {
        parent::init();

        if (! isset($this->siteId) && Cms::isInstalled()) {
            $this->siteId = Sites::getPrimarySite()->id;
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
     * @TODO: Remove parameters once Element no longer extends Yii Model
     */
    #[Override]
    public function getAttributes($names = null, $except = []): array
    {
        $attributes = $this->attributes();
        $values = [];

        try {
            foreach ($attributes as $attribute) {
                $values[$attribute] = $this->$attribute;
            }
        } catch (Throwable) {
            // Skip attributes that throw errors during access (e.g., lazy-loaded relations that fail)
            // This is expected for attributes that may not be accessible in all contexts
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function attributes(): array
    {
        $names = array_flip(Utils::getPublicAttributes($this));

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
            $names['applyingDraft'],
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
            $names['propagateRequired'],
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
     * {@inheritdoc}
     */
    #[Override]
    public function fields(): array
    {
        $fields = parent::fields();

        foreach ($this->fieldLayoutFields() as $field) {
            if (! isset($fields[$field->handle])) {
                if ($this->_serializeFields) {
                    $fields[$field->handle] = function () use ($field) {
                        $value = $this->getFieldValue($field->handle);

                        return $field->serializeValue($value, $this);
                    };
                } else {
                    $fields[$field->handle] = fn () => $this->clonedFieldValue($field->handle);
                }
            }
        }

        return $fields;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    #[Override]
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
     * {@inheritdoc}
     */
    #[Override]
    public function getIterator(): Traversable
    {
        $attributes = $this->getAttributes();

        // Include custom fields
        $fieldLayout = $this->getFieldLayout();

        if ($fieldLayout !== null) {
            foreach ($fieldLayout->getCustomFieldElements() as $layoutElement) {
                $field = $layoutElement->getField();
                if (! isset($attributes[$field->handle])) {
                    $attributes[$field->handle] = $this->getFieldValue($field->handle);
                }
            }
        }

        return new ArrayIterator($attributes);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getAttributeLabel($attribute): string
    {
        // Is this the "field:handle" syntax?
        if (str_starts_with($attribute, 'field:')) {
            $attribute = substr($attribute, 6);
        }

        return parent::getAttributeLabel($attribute);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function attributeLabels(): array
    {
        $labels = [
            'dateCreated' => t('Date Created'),
            'dateUpdated' => t('Date Updated'),
            'id' => t('ID'),
            'slug' => t('Slug'),
            'title' => t('Title'),
            'uid' => t('UID'),
            'uri' => t('URI'),
        ];

        if (Cms::isInstalled()) {
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
     * Returns whether the element's `title` attribute should be validated
     *
     * @since 5.0.0
     */
    public function shouldValidateTitle(): bool
    {
        return static::hasTitles();
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function afterValidate(?LaravelValidator $validator = null): void
    {
        if (
            Cms::isInstalled() &&
            $fieldLayout = $this->getFieldLayout()
        ) {
            $scenario = $this->getScenario();
            $layoutElements = $fieldLayout->getEditableCustomFieldElements($this);

            foreach ($layoutElements as $layoutElement) {
                $field = $layoutElement->getField();
                $attribute = "field:$field->handle";

                if (isset($this->_attributeNames) && ! isset($this->_attributeNames[$attribute])) {
                    continue;
                }

                $isEmpty = fn () => $field->isValueEmpty($this->getFieldValue($field->handle), $this);

                $rules = [];
                if ($scenario === self::SCENARIO_LIVE && $layoutElement->required) {
                    $rules[] = function ($attribute, $value, $fail) use ($isEmpty) {
                        if ($isEmpty()) {
                            $fail(t('validation.required'));
                        }
                    };
                } else {
                    $rules[] = ['nullable'];
                }

                $rules = array_merge($rules, $field->getElementRules($this));

                $value = $field->prepareForElementValidation(
                    $this->getFieldValue($field->handle),
                );

                $this->setFieldValue($field->handle, $value);

                $validator = ValidatorFacade::make(
                    data: [$attribute => $value],
                    rules: [$attribute => $rules],
                    attributes: [$attribute => $field->getUiLabel()]
                );

                if ($validator->fails()) {
                    /**
                     * Map errors from `field:attribute` -> `attribute`
                     */
                    $errors = collect($validator->errors())
                        ->mapWithKeys(fn (array $errors, string $attribute) => [
                            Str::after($attribute, 'field:') => $errors,
                        ])
                        ->all();

                    $this->errors()->merge($errors);
                }
            }
        }

        if (request()->isCpRequest()) {
            $allErrors = $this->errors()->getMessages();

            /**
             * Clear our all errors as we're mapping them
             * to bold the field attribute label.
             */
            foreach ($this->errors()->getMessages() as $attribute => $errors) {
                $this->errors()->forget($attribute);
            }

            $this->errors()->merge(collect($allErrors)->map(function (array $errors, string $attribute) {
                $label = $this->getAttributeLabel($attribute);

                foreach ($errors as &$error) {
                    $error = str_replace($label, "*$label*", $error);
                }

                return $errors;
            })->all());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getIsCanonical(): bool
    {
        return ! isset($this->_canonicalId);
    }

    /**
     * {@inheritdoc}
     */
    public function getIsDerivative(): bool
    {
        return ! $this->getIsCanonical();
    }

    /**
     * {@inheritdoc}
     */
    public function getCanonical(bool $anySite = false): ElementInterface
    {
        if ($this->getIsCanonical()) {
            return $this;
        }

        $prop = $anySite ? '_canonicalAnySite' : '_canonical';

        if (! isset($this->$prop)) {
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
     * {@inheritdoc}
     */
    public function setCanonical(ElementInterface $element): void
    {
        if ($this->getIsCanonical()) {
            throw new NotSupportedException('setCanonical() can only be called on a derivative element.');
        }

        $this->_canonical = $element;
    }

    /**
     * {@inheritdoc}
     */
    public function getCanonicalId(): ?int
    {
        return $this->_canonicalId ?? $this->id;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
        if (! isset($this->_canonicalUid)) {
            $this->_canonicalUid = static::find()
                ->id($this->_canonicalId)
                ->site('*')
                ->status(null)
                ->ignorePlaceholders()
                ->select(['elements.uid'])
                ->one()?->uid;
        }

        return $this->_canonicalUid;
    }

    /**
     * Returns the element’s canonical ID.
     *
     * @since 3.2.0
     */
    #[Deprecated(message: 'in 3.7.0. Use [[getCanonicalId()]] instead.')]
    public function getSourceId(): ?int
    {
        Deprecator::log(__METHOD__,
            'Elements’ `getSourceId()` method has been deprecated. Use `getCanonicalId()` instead.');

        return $this->getCanonicalId();
    }

    /**
     * Returns the element’s canonical UID.
     *
     * @since 3.2.0
     */
    #[Deprecated(message: 'in 3.7.0. Use [[getCanonicalUid()]] instead.')]
    public function getSourceUid(): string
    {
        Deprecator::log(__METHOD__,
            'Elements’ `getSourceUid()` method has been deprecated. Use `getCanonicalUid()` instead.');

        return $this->getCanonicalUid();
    }

    /**
     * {@inheritdoc}
     */
    public function getIsUnpublishedDraft(): bool
    {
        return $this->getIsDraft() && $this->getIsCanonical();
    }

    /**
     * {@inheritdoc}
     */
    public function mergeCanonicalChanges(): void
    {
        if (($canonical = $this->getCanonical()) === $this) {
            return;
        }

        // Update any attributes that were modified upstream
        foreach ($this->getOutdatedAttributes() as $attribute) {
            if (! $this->isAttributeModified($attribute)) {
                $this->$attribute = $canonical->$attribute;
            }
        }

        foreach ($this->getOutdatedFields() as $fieldHandle) {
            if (
                ! $this->isFieldModified($fieldHandle) &&
                ($field = $this->fieldByHandle($fieldHandle)) !== null
            ) {
                $field->copyValue($canonical, $this);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldLayout(): ?FieldLayout
    {
        if ($this->fieldLayoutId) {
            return app(Fields::class)->getLayoutById($this->fieldLayoutId);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedSites(): array
    {
        if (static::isLocalized()) {
            return Sites::getAllSiteIds()->all();
        }

        return [Sites::getPrimarySite()->id];
    }

    /**
     * {@inheritdoc}
     *
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
     *
     * @since 4.1.0
     */
    protected function cacheTags(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getUriFormat(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
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
     *
     * @since 3.5.0
     */
    protected function searchKeywords(string $attribute): string
    {
        return Str::toString($this->$attribute);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoute(): mixed
    {
        // Fire a 'setRoute' event
        if ($this->hasEventHandlers(self::EVENT_SET_ROUTE)) {
            $event = new SetElementRouteEvent;
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
     *
     * @see getRoute()
     */
    protected function route(): array|string|null
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getIsHomepage(): bool
    {
        return $this->uri === self::HOMEPAGE_URI;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl(): ?string
    {
        // Fire a 'beforeDefineUrl' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DEFINE_URL)) {
            $event = new DefineUrlEvent;
            $this->trigger(self::EVENT_BEFORE_DEFINE_URL, $event);
            $url = $event->url;
        } else {
            $url = null;
        }

        // If DefineAssetUrlEvent::$url is set to null, only respect that if $handled is true
        if ($url === null && ! ($event->handled ?? false) && isset($this->uri)) {
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     *
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
                            'hyperlink' => true,
                        ]),
                    ],
                ];
            }
        }

        return $this->crumbs();
    }

    /**
     * {@inheritdoc}
     */
    public function getUiLabel(): string
    {
        return $this->_uiLabel ?? $this->uiLabel() ?? (string) $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setUiLabel(?string $label): void
    {
        $this->_uiLabel = $label;
    }

    /**
     * {@inheritdoc}
     */
    public function getUiLabelPath(): array
    {
        return $this->_uiLabelPath;
    }

    /**
     * {@inheritdoc}
     */
    public function setUiLabelPath(array $path): void
    {
        $this->_uiLabelPath = $path;
    }

    /**
     * Returns the breadcrumbs that lead up to the element.
     *
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
     * @since 3.6.4
     */
    protected function uiLabel(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getChipLabelHtml(): string|Stringable
    {
        return Html::encode($this->getUiLabel());
    }

    /**
     * {@inheritdoc}
     */
    public function showStatusIndicator(): bool
    {
        return static::hasStatuses();
    }

    /**
     * {@inheritdoc}
     */
    public function getCardTitle(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getCardBodyHtml(): ?string
    {
        $this->viewMode = 'cards';
        $html = '';
        $cardElements = $this->getFieldLayout()?->getCardBodyElements($this) ?? [];

        foreach ($cardElements as $item) {
            $html .= Html::tag('div', $item['html'], [
                'class' => 'card-attribute-preview',
            ]);
        }

        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function getRef(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function createAnother(): ?ElementInterface
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function canView(User $user): bool
    {
        return $user->can('view', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function canSave(User $user): bool
    {
        return $user->can('save', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function canDuplicate(User $user): bool
    {
        return $user->can('duplicate', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function canCopy(User $user): bool
    {
        return $user->can('copy', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function canDelete(User $user): bool
    {
        return $user->can('delete', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function canDeleteForSite(User $user): bool
    {
        return $user->can('deleteForSite', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function canCreateDrafts(User $user): bool
    {
        return $user->can('createDrafts', $this);
    }

    /**
     * {@inheritdoc}
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
                $url = trim((string) $view->renderObjectTemplate(Env::parse($previewTarget['urlFormat']), $this));
                if ($url !== '') {
                    $previewTarget['url'] = $url;
                    unset($previewTarget['urlFormat']);
                }
            }
            if (! isset($previewTarget['url'])) {
                // No URL, no preview target
                continue;
            }
            $previewTarget['url'] = UrlHelper::siteUrl($previewTarget['url'], siteId: $this->siteId);
            if (! isset($previewTarget['refresh'])) {
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
     * @see getPreviewTargets()
     * @since 3.2.0
     */
    protected function previewTargets(): array
    {
        $previewTargets = [];

        $url = $this->getUrl();
        if ($url) {
            $previewTargets[] = [
                'label' => t('Primary {type} page', [
                    'type' => static::lowerDisplayName(),
                ]),
                'url' => $url,
            ];
        }

        return $previewTargets;
    }

    /**
     * {@inheritdoc}
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
     * @param  int  $size  The maximum width and height the thumbnail should have.
     *
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
     * @since 4.5.0
     */
    protected function thumbSvg(): ?string
    {
        return null;
    }

    /**
     * Returns alt text for the element’s thumbnail.
     *
     * @since 5.0.0
     */
    protected function thumbAlt(): ?string
    {
        return null;
    }

    /**
     * Returns whether the element’s thumbnail should have a checkered background.
     *
     * @since 5.0.0
     */
    protected function hasCheckeredThumb(): bool
    {
        return false;
    }

    /**
     * Returns whether the element’s thumbnail should be rounded.
     *
     * @since 5.0.0
     */
    protected function hasRoundedThumb(): bool
    {
        return false;
    }

    /**
     * Returns whether the element’s thumbnail is potentially animated.
     *
     * @since 5.7.0
     */
    protected function couldHaveAnimatedThumb(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function setEnabledForSite(array|bool $enabledForSite): void
    {
        if (is_array($enabledForSite)) {
            $this->_enabledForSite = array_map(fn (bool $value) => $value, $enabledForSite);
        } else {
            $this->_enabledForSite = $enabledForSite;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus(): ?string
    {
        if ($this->getIsDraft() && ! $this->isProvisionalDraft) {
            return self::STATUS_DRAFT;
        }

        if ($this->archived) {
            return self::STATUS_ARCHIVED;
        }

        if (! $this->enabled || ! $this->getEnabledForSite()) {
            return self::STATUS_DISABLED;
        }

        return self::STATUS_ENABLED;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     *
     * @since 3.5.0
     */
    public function getLocalized(): ElementQueryInterface|Queries\ElementQuery|ElementCollection
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
     * {@inheritdoc}
     *
     * @param  string|int  $offset
     */
    #[Override]
    public function offsetExists($offset): bool
    {
        if (parent::offsetExists($offset)) {
            return true;
        }

        return (bool) $this->fieldByHandle($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function setAttributesFromRequest(array $values): void
    {
        $this->setAttributes($values);
    }

    #[Override]
    public function safeAttributes(): array
    {
        return array_keys($this->getRuleset()->rules());
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeStatus(string $attribute): ?array
    {
        if ($this->isAttributeModified($attribute)) {
            return [
                AttributeStatus::Modified,
                t('This field has been modified.'),
            ];
        }

        if ($this->isAttributeOutdated($attribute)) {
            return [
                AttributeStatus::Outdated,
                t('This field was updated in the Current revision.'),
            ];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getOutdatedAttributes(): array
    {
        return array_keys($this->_outdatedAttributes());
    }

    /**
     * {@inheritdoc}
     */
    public function isAttributeOutdated(string $name): bool
    {
        return isset($this->_outdatedAttributes()[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getModifiedAttributes(): array
    {
        return array_keys($this->_modifiedAttributes());
    }

    /**
     * {@inheritdoc}
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
        if (! static::trackChanges() || $this->getIsCanonical() || $this->getIsRevision()) {
            return [];
        }

        if (! isset($this->_outdatedAttributes)) {
            $attributes = DB::table(Table::CHANGEDATTRIBUTES)
                ->where('elementId', $this->id)
                ->where('siteId', $this->siteId)
                ->when(
                    value: $this->dateLastMerged,
                    callback: fn (Builder $query) => $query->where('dateUpdated', '>=', $this->dateLastMerged),
                    default: fn (Builder $query) => $query->where('dateUpdated', '>=', $this->dateCreated),
                )
                ->pluck('attribute')
                ->flip()
                ->all();

            $this->_outdatedAttributes = $attributes;
        }

        return $this->_outdatedAttributes;
    }

    /**
     * @return array The attribute names that have been modified for this element
     */
    private function _modifiedAttributes(): array
    {
        if (! static::trackChanges() || $this->getIsCanonical()) {
            return [];
        }

        if (! isset($this->_modifiedAttributes)) {
            $this->_modifiedAttributes = DB::table(Table::CHANGEDATTRIBUTES)
                ->where('elementId', $this->id)
                ->where('siteId', $this->siteId)
                ->pluck('attribute')
                ->flip()
                ->all();
        }

        return $this->_modifiedAttributes;
    }

    /**
     * {@inheritdoc}
     */
    public function isAttributeDirty(string $name): bool
    {
        if ($this->_allDirty()) {
            return true;
        }

        return isset($this->_dirtyAttributes[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDirtyAttributes(): array
    {
        if (static::hasTitles() && $this->title !== $this->_savedTitle) {
            $this->_dirtyAttributes['title'] = true;
        }

        return array_keys($this->_dirtyAttributes);
    }

    /**
     * {@inheritdoc}
     */
    public function setDirtyAttributes(array $names, bool $merge = true): void
    {
        if ($merge && ! empty($this->_dirtyAttributes)) {
            $this->_dirtyAttributes = array_merge($this->_dirtyAttributes, array_flip($names));
        } else {
            $this->_dirtyAttributes = array_flip($names);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIsTitleTranslatable(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitleTranslationDescription(): ?string
    {
        return ElementHelper::translationDescription(Field::TRANSLATION_METHOD_SITE);
    }

    /**
     * {@inheritdoc}
     */
    public function getTitleTranslationKey(): string
    {
        return ElementHelper::translationKey($this, Field::TRANSLATION_METHOD_SITE);
    }

    /**
     * {@inheritdoc}
     */
    public function getIsSlugTranslatable(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getSlugTranslationDescription(): ?string
    {
        return ElementHelper::translationDescription(Field::TRANSLATION_METHOD_SITE);
    }

    /**
     * {@inheritdoc}
     */
    public function getSlugTranslationKey(): string
    {
        return ElementHelper::translationKey($this, Field::TRANSLATION_METHOD_SITE);
    }

    /**
     * Returns whether all fields and attributes should be considered dirty.
     */
    private function _allDirty(): bool
    {
        return $this->_allDirty || $this->resaving;
    }

    /**
     * {@inheritdoc}
     */
    public function markAsDirty(): void
    {
        $this->_allDirty = true;
    }

    /**
     * {@inheritdoc}
     */
    public function markAsClean(): void
    {
        $this->_allDirty = false;
        $this->_dirtyAttributes = [];
        $this->setDirtyFields([], false);

        if (static::hasTitles()) {
            $this->_savedTitle = $this->title;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIsFresh(): bool
    {
        if ($this->errors()->isNotEmpty()) {
            return false;
        }

        if (! isset($this->siteSettingsId)) {
            return true;
        }

        return $this->_isFresh ?? false;
    }

    /**
     * {@inheritdoc}
     */
    public function setIsFresh(bool $isFresh = true): void
    {
        $this->_isFresh = $isFresh;
    }

    /**
     * {@inheritdoc}
     */
    public function getIsCrossSiteCopyable(): bool
    {
        if (! isset($this->_isCrossSiteCopyable)) {
            $this->_isCrossSiteCopyable = (
                Sites::isMultiSite() &&
                // check if user can edit this element in other sites
                count(ElementHelper::editableSiteIdsForElement($this)) > 1 &&
                // also check if the element exists in other sites
                ! empty(array_diff(array_keys(ElementHelper::siteStatusesForElement($this, true)), [$this->siteId]))
            );
        }

        return $this->_isCrossSiteCopyable;
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    // Events
    // -------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     */
    public function beforeSave(bool $isNew): bool
    {
        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
            if (! $field->beforeElementSave($this, $isNew)) {
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
     * {@inheritdoc}
     */
    public function afterSave(bool $isNew): void
    {
        // Update the element’s relation data
        app(ElementRelations::class)->updateRelations($this, $isNew);

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

    /**
     * {@inheritdoc}
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

        $this->handleDraftSave();
    }

    /**
     * {@inheritdoc}
     */
    public function beforeDelete(): bool
    {
        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
            if (! $field->beforeElementDelete($this)) {
                return false;
            }
        }

        // Fire a 'beforeDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE)) {
            $event = new ModelEvent;
            $this->trigger(self::EVENT_BEFORE_DELETE, $event);

            return $event->isValid;
        }

        return true;
    }

    /**
     * {@inheritdoc}
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

        $this->handleRevisionDelete();
        $this->handleDraftDelete();
    }

    /**
     * {@inheritdoc}
     */
    public function beforeDeleteForSite(): bool
    {
        return array_all($this->fieldLayoutFields(), fn ($field) => $field->beforeElementDeleteForSite($this));
    }

    /**
     * {@inheritdoc}
     */
    public function afterDeleteForSite(): void
    {
        // Delete any site-specific relation data
        app(ElementRelations::class)->deleteSiteRelations($this);

        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
            $field->afterElementDeleteForSite($this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function beforeRestore(): bool
    {
        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
            if (! $field->beforeElementRestore($this)) {
                return false;
            }
        }

        // Fire a 'beforeRestore' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_RESTORE)) {
            $event = new ModelEvent;
            $this->trigger(self::EVENT_BEFORE_RESTORE, $event);

            return $event->isValid;
        }

        return true;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     *
     * @throws InvalidConfigException if [[siteId]] is invalid
     */
    public function getSite(): Site
    {
        if (isset($this->siteId)) {
            $site = Sites::getSiteById($this->siteId, true);
        }

        if (! isset($site)) {
            throw new InvalidConfigException('Invalid site ID: '.$this->siteId);
        }

        return $site;
    }

    /**
     * {@inheritdoc}
     *
     * @since 3.5.0
     */
    public function getLanguage(): string
    {
        return $this->getSite()->getLanguage();
    }

    /**
     * {@inheritdoc}
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

        if (! empty($templates)) {
            $view = Craft::$app->getView();
            foreach (Arr::sort($templates, 'priority') as $template) {
                if ($view->doesTemplateExist($template['template'], View::TEMPLATE_MODE_SITE)) {
                    $output = $view->renderTemplate($template['template'], $variables, View::TEMPLATE_MODE_SITE);

                    return new Markup($output, Craft::$app->charset);
                }
            }
        }

        // fallback to the string representation of the element
        $output = Html::tag('p', Html::encode((string) $this));

        return new Markup($output, Craft::$app->charset);
    }

    /**
     * Returns the template paths to check when rendering the element’s partial template.
     *
     * @return array{template:string,priority:int}[]
     *
     * @since 5.8.0
     */
    protected function partialTemplatePathCandidates(): array
    {
        $refHandle = static::refHandle();
        if ($refHandle === null) {
            return [];
        }

        $templates = [];
        $providerHandle = $this->getFieldLayout()?->provider?->getHandle();
        if ($providerHandle !== null) {
            $templates[] = [
                'template' => sprintf('%s/%s/%s', Cms::config()->partialTemplatesPath, $refHandle, $providerHandle),
                'priority' => 1,
            ];
        }

        $templates[] = [
            'template' => sprintf('%s/%s', Cms::config()->partialTemplatesPath, $refHandle),
            'priority' => 10,
        ];

        return $templates;
    }
}
