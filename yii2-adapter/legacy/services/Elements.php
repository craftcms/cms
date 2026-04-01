<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\ElementActionInterface;
use craft\base\ElementExporterInterface;
use craft\base\ElementInterface;
use craft\base\ExpirableElementInterface;
use craft\elements\db\EagerLoadInfo;
use craft\elements\db\EagerLoadPlan;
use craft\errors\ElementNotFoundException;
use craft\events\AuthorizationCheckEvent;
use craft\events\BulkOpEvent;
use craft\events\DeleteElementEvent;
use craft\events\EagerLoadElementsEvent;
use craft\events\ElementEvent;
use craft\events\ElementQueryEvent;
use craft\events\InvalidateElementCachesEvent;
use craft\events\MergeElementsEvent;
use craft\events\MultiElementActionEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\Queue;
use craft\models\ElementActivity;
use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Actions\PropagateElementAction;
use CraftCms\Cms\Element\BulkOp\Events\AfterBulkOp;
use CraftCms\Cms\Element\BulkOp\Events\BeforeBulkOp;
use CraftCms\Cms\Element\Data\ElementActivity as ElementActivityData;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementActivity as ElementActivityService;
use CraftCms\Cms\Element\ElementCaches as ElementCachesService;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Enums\ElementActivityType;
use CraftCms\Cms\Element\Events\AfterDeleteElement;
use CraftCms\Cms\Element\Events\AfterDeleteForSite;
use CraftCms\Cms\Element\Events\AfterMergeCanonicalChanges;
use CraftCms\Cms\Element\Events\AfterMergeElements;
use CraftCms\Cms\Element\Events\AfterPropagateElement;
use CraftCms\Cms\Element\Events\AfterPropagateElements;
use CraftCms\Cms\Element\Events\AfterResaveElement;
use CraftCms\Cms\Element\Events\AfterResaveElements;
use CraftCms\Cms\Element\Events\AfterRestoreElement;
use CraftCms\Cms\Element\Events\AfterSaveElement;
use CraftCms\Cms\Element\Events\AfterUpdateSlugAndUri;
use CraftCms\Cms\Element\Events\BeforeDeleteElement;
use CraftCms\Cms\Element\Events\BeforeDeleteForSite;
use CraftCms\Cms\Element\Events\BeforeMergeCanonicalChanges;
use CraftCms\Cms\Element\Events\BeforePropagateElement;
use CraftCms\Cms\Element\Events\BeforePropagateElements;
use CraftCms\Cms\Element\Events\BeforeResaveElement;
use CraftCms\Cms\Element\Events\BeforeResaveElements;
use CraftCms\Cms\Element\Events\BeforeRestoreElement;
use CraftCms\Cms\Element\Events\BeforeSaveElement;
use CraftCms\Cms\Element\Events\BeforeUpdateSearchIndex;
use CraftCms\Cms\Element\Events\BeforeUpdateSlugAndUri;
use CraftCms\Cms\Element\Events\InvalidateElementCaches;
use CraftCms\Cms\Element\Events\RegisterElementTypes;
use CraftCms\Cms\Element\Events\SetElementUri;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Exceptions\UnsupportedSiteException;
use CraftCms\Cms\Element\Models\ElementSiteSettings;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Shared\Exceptions\OperationAbortedException;
use CraftCms\Cms\Site\Exceptions\SiteNotFoundException;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\BulkOps;
use CraftCms\Cms\Support\Facades\ElementCaches;
use CraftCms\Cms\Support\Facades\Elements as ElementsFacade;
use CraftCms\Cms\Support\Facades\Search;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Structures;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Validation\Rules\HandleRule;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use DateTime;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use Tpetry\QueryExpressions\Language\Alias;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidCallException;
use yii\web\ForbiddenHttpException;
use function CraftCms\Cms\t;

/**
 * The Elements service provides APIs for managing elements.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getElements()|`Craft::$app->getElements()`]].
 *
 * @phpstan-import-type EagerLoadingMap from ElementInterface
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Elements extends Component
{
    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering element types.
     *
     * Element types must implement [[ElementInterface]]. [[Element]] provides a base implementation.
     *
     * See [Element Types](https://craftcms.com/docs/5.x/extend/element-types.html) for documentation on creating element types.
     * ---
     * ```php
     * use craft\events\RegisterComponentTypesEvent;
     * use craft\services\Elements;
     * use yii\base\Event;
     *
     * Event::on(Elements::class,
     *     Elements::EVENT_REGISTER_ELEMENT_TYPES,
     *     function(RegisterComponentTypesEvent $event) {
     *         $event->types[] = MyElementType::class;
     *     }
     * );
     * ```
     */
    public const EVENT_REGISTER_ELEMENT_TYPES = 'registerElementTypes';

    /**
     * @event EagerLoadElementsEvent The event that is triggered before elements are eager-loaded.
     * @since 3.5.0
     */
    public const EVENT_BEFORE_EAGER_LOAD_ELEMENTS = 'beforeEagerLoadElements';

    /**
     * @event BulkOpEvent The event that is triggered before a bulk element operation has started.
     *
     * Note that this won’t necessarily fire from the same request as [[EVENT_AFTER_BULK_OP]].
     *
     * @since 5.0.0
     */
    public const EVENT_BEFORE_BULK_OP = 'beforeBulkOp';

    /**
     * @event BulkOpEvent The event that is triggered after a bulk element operation is completed.
     *
     * Note that this won’t necessarily fire from the same request as [[EVENT_BEFORE_BULK_OP]].
     *
     * @since 5.0.0
     */
    public const EVENT_AFTER_BULK_OP = 'afterBulkOp';

    /**
     * @event MergeElementsEvent The event that is triggered after two elements are merged together.
     */
    public const EVENT_AFTER_MERGE_ELEMENTS = 'afterMergeElements';

    /**
     * @event DeleteElementEvent The event that is triggered before an element is deleted.
     */
    public const EVENT_BEFORE_DELETE_ELEMENT = 'beforeDeleteElement';

    /**
     * @event ElementEvent The event that is triggered after an element is deleted.
     */
    public const EVENT_AFTER_DELETE_ELEMENT = 'afterDeleteElement';

    /**
     * @event ElementEvent The event that is triggered before an element is restored.
     * @since 3.1.0
     */
    public const EVENT_BEFORE_RESTORE_ELEMENT = 'beforeRestoreElement';

    /**
     * @event ElementEvent The event that is triggered after an element is restored.
     * @since 3.1.0
     */
    public const EVENT_AFTER_RESTORE_ELEMENT = 'afterRestoreElement';

    /**
     * @event ElementEvent The event that is triggered before an element is saved.
     *
     * If you want to ignore events for drafts or revisions, call [[\CraftCms\Cms\Element\ElementHelper::isDraftOrRevision()]]
     * from your event handler:
     *
     * ```php
     * use craft\events\ElementEvent;
     * use CraftCms\Cms\Element\ElementHelper;
     * use craft\services\Elements;
     *
     * Craft::$app->elements->on(Elements::EVENT_BEFORE_SAVE_ELEMENT, function(ElementEvent $e) {
     *     if (ElementHelper::isDraftOrRevision($e->element)) {
     *         return;
     *     }
     *
     *     // ...
     * });
     * ```
     */
    public const EVENT_BEFORE_SAVE_ELEMENT = 'beforeSaveElement';

    /**
     * @event ElementEvent The event that is triggered after an element is saved.
     *
     * If you want to ignore events for drafts or revisions, call [[\CraftCms\Cms\Element\ElementHelper::isDraftOrRevision()]]
     * from your event handler:
     *
     * ```php
     * use craft\events\ElementEvent;
     * use CraftCms\Cms\Element\ElementHelper;
     * use craft\services\Elements;
     *
     * Craft::$app->elements->on(Elements::EVENT_AFTER_SAVE_ELEMENT, function(ElementEvent $e) {
     *     if (ElementHelper::isDraftOrRevision($e->element)) {
     *         return;
     *     }
     *
     *     // ...
     * });
     * ```
     */
    public const EVENT_AFTER_SAVE_ELEMENT = 'afterSaveElement';

    /**
     * @event ElementEvent The event that is triggered when setting a unique URI on an element.
     *
     * Event handlers must set `$event->handled` to `true` for their change to take effect.
     *
     * @see setElementUri()
     * @since 4.6.0
     */
    public const EVENT_SET_ELEMENT_URI = 'setElementUri';

    /**
     * @event ElementEvent The event that is triggered before indexing an element’s search keywords,
     * or queuing the element’s search keywords to be updated.
     *
     * You may set [[\craft\events\CancelableEvent::$isValid]] to `false` to prevent the search index from being updated.
     *
     * @since 3.7.12
     */
    public const EVENT_BEFORE_UPDATE_SEARCH_INDEX = 'beforeUpdateSearchIndex';

    /**
     * @event ElementQueryEvent The event that is triggered before resaving a batch of elements.
     */
    public const EVENT_BEFORE_RESAVE_ELEMENTS = 'beforeResaveElements';

    /**
     * @event ElementQueryEvent The event that is triggered after resaving a batch of elements.
     */
    public const EVENT_AFTER_RESAVE_ELEMENTS = 'afterResaveElements';

    /**
     * @event MultiElementActionEvent The event that is triggered before an element is resaved.
     */
    public const EVENT_BEFORE_RESAVE_ELEMENT = 'beforeResaveElement';

    /**
     * @event MultiElementActionEvent The event that is triggered after an element is resaved.
     */
    public const EVENT_AFTER_RESAVE_ELEMENT = 'afterResaveElement';

    /**
     * @event ElementQueryEvent The event that is triggered before propagating a batch of elements.
     */
    public const EVENT_BEFORE_PROPAGATE_ELEMENTS = 'beforePropagateElements';

    /**
     * @event ElementQueryEvent The event that is triggered after propagating a batch of elements.
     */
    public const EVENT_AFTER_PROPAGATE_ELEMENTS = 'afterPropagateElements';

    /**
     * @event MultiElementActionEvent The event that is triggered before an element is propagated.
     */
    public const EVENT_BEFORE_PROPAGATE_ELEMENT = 'beforePropagateElement';

    /**
     * @event MultiElementActionEvent The event that is triggered after an element is propagated.
     */
    public const EVENT_AFTER_PROPAGATE_ELEMENT = 'afterPropagateElement';

    /**
     * @event ElementEvent The event that is triggered before an element’s slug and URI are updated, usually following a Structure move.
     */
    public const EVENT_BEFORE_UPDATE_SLUG_AND_URI = 'beforeUpdateSlugAndUri';

    /**
     * @event ElementEvent The event that is triggered after an element’s slug and URI are updated, usually following a Structure move.
     */
    public const EVENT_AFTER_UPDATE_SLUG_AND_URI = 'afterUpdateSlugAndUri';

    /**
     * @event \craft\events\ElementActionEvent The event that is triggered before an element action is performed.
     *
     * You may set [[\craft\events\CancelableEvent::$isValid]] to `false` to prevent the action from being performed.
     */
    public const EVENT_BEFORE_PERFORM_ACTION = 'beforePerformAction';

    /**
     * @event \craft\events\ElementActionEvent The event that is triggered after an element action is performed.
     */
    public const EVENT_AFTER_PERFORM_ACTION = 'afterPerformAction';

    /**
     * @event ElementEvent The event that is triggered before canonical element changes are merged into a derivative.
     * @since 3.7.0
     */
    public const EVENT_BEFORE_MERGE_CANONICAL_CHANGES = 'beforeMergeCanonical';

    /**
     * @event ElementEvent The event that is triggered after canonical element changes are merged into a derivative.
     * @since 3.7.0
     */
    public const EVENT_AFTER_MERGE_CANONICAL_CHANGES = 'afterMergeCanonical';

    /**
     * @event InvalidateElementCachesEvent The event that is triggered when element caches are invalidated.
     * @since 4.2.0
     */
    public const EVENT_INVALIDATE_CACHES = 'invalidateCaches';

    /**
     * @event AuthorizationCheckEvent The event that is triggered when determining whether a user is authorized to view an element’s edit page.
     *
     * To authorize the user, set [[AuthorizationCheckEvent::$authorized]] to `true`.
     *
     * ```php
     * use craft\events\AuthorizationCheckEvent;
     * use craft\services\Elements;
     * use yii\base\Event;
     *
     * Event::on(
     *     Elements::class,
     *     Elements::EVENT_AUTHORIZE_VIEW,
     *     function(AuthorizationCheckEvent $event) {
     *         $event->authorized = true;
     *     }
     * );
     * ```
     *
     * @see canView()
     * @since 4.3.0
     */
    public const EVENT_AUTHORIZE_VIEW = 'authorizeView';

    /**
     * @event AuthorizationCheckEvent The event that is triggered when determining whether a user is authorized to save an element in its current state.
     *
     * To authorize the user, set [[AuthorizationCheckEvent::$authorized]] to `true`.
     *
     * ```php
     * use craft\events\AuthorizationCheckEvent;
     * use craft\services\Elements;
     * use yii\base\Event;
     *
     * Event::on(
     *     Elements::class,
     *     Elements::EVENT_AUTHORIZE_SAVE,
     *     function(AuthorizationCheckEvent $event) {
     *         $event->authorized = true;
     *     }
     * );
     * ```
     *
     * @see canSave()
     * @since 4.3.0
     */
    public const EVENT_AUTHORIZE_SAVE = 'authorizeSave';

    /**
     * @event AuthorizationCheckEvent The event that is triggered when determining whether a user is authorized to create drafts for an element.
     *
     * To authorize the user, set [[AuthorizationCheckEvent::$authorized]] to `true`.
     *
     * ```php
     * use craft\events\AuthorizationCheckEvent;
     * use craft\services\Elements;
     * use yii\base\Event;
     *
     * Event::on(
     *     Elements::class,
     *     Elements::EVENT_AUTHORIZE_CREATE_DRAFTS,
     *     function(AuthorizationCheckEvent $event) {
     *         $event->authorized = true;
     *     }
     * );
     * ```
     *
     * @see canCreateDrafts()
     * @since 4.3.0
     */
    public const EVENT_AUTHORIZE_CREATE_DRAFTS = 'authorizeCreateDrafts';

    /**
     * @event AuthorizationCheckEvent The event that is triggered when determining whether a user is authorized to duplicate an element.
     *
     * To authorize the user, set [[AuthorizationCheckEvent::$authorized]] to `true`.
     *
     * ```php
     * use craft\events\AuthorizationCheckEvent;
     * use craft\services\Elements;
     * use yii\base\Event;
     *
     * Event::on(
     *     Elements::class,
     *     Elements::EVENT_AUTHORIZE_DUPLICATE,
     *     function(AuthorizationCheckEvent $event) {
     *         $event->authorized = true;
     *     }
     * );
     * ```
     *
     * @see canDuplicate()
     * @since 4.3.0
     */
    public const EVENT_AUTHORIZE_DUPLICATE = 'authorizeDuplicate';

    /**
     * @event AuthorizationCheckEvent The event that is triggered when determining whether a user is authorized to
     * duplicate an element as an unpublished draft.
     *
     * To authorize the user, set [[AuthorizationCheckEvent::$authorized]] to `true`.
     *
     * ```php
     * use craft\events\AuthorizationCheckEvent;
     * use craft\services\Elements;
     * use yii\base\Event;
     *
     * Event::on(
     *     Elements::class,
     *     Elements::EVENT_AUTHORIZE_DUPLICATE,
     *     function(AuthorizationCheckEvent $event) {
     *         $event->authorized = true;
     *     }
     * );
     * ```
     *
     * @see canDuplicateAsDraft()
     * @since 5.0.0
     */
    public const EVENT_AUTHORIZE_DUPLICATE_AS_DRAFT = 'authorizeDuplicateAsDraft';

    /**
     * @event AuthorizationCheckEvent The event that is triggered when determining whether a user is authorized to copy an element, to be duplicated elsewhere.
     *
     * To authorize the user, set [[AuthorizationCheckEvent::$authorized]] to `true`.
     *
     * ```php
     * use craft\events\AuthorizationCheckEvent;
     * use craft\services\Elements;
     * use yii\base\Event;
     *
     * Event::on(
     *     Elements::class,
     *     Elements::EVENT_AUTHORIZE_COPY,
     *     function(AuthorizationCheckEvent $event) {
     *         $event->authorized = true;
     *     }
     * );
     * ```
     *
     * @see canCopy()
     * @since 5.7.0
     */
    public const EVENT_AUTHORIZE_COPY = 'authorizeCopy';

    /**
     * @event AuthorizationCheckEvent The event that is triggered when determining whether a user is authorized to delete an element.
     *
     * To authorize the user, set [[AuthorizationCheckEvent::$authorized]] to `true`.
     *
     * ```php
     * use craft\events\AuthorizationCheckEvent;
     * use craft\services\Elements;
     * use yii\base\Event;
     *
     * Event::on(
     *     Elements::class,
     *     Elements::EVENT_AUTHORIZE_DELETE,
     *     function(AuthorizationCheckEvent $event) {
     *         $event->authorized = true;
     *     }
     * );
     * ```
     *
     * @see canDelete()
     * @since 4.3.0
     */
    public const EVENT_AUTHORIZE_DELETE = 'authorizeDelete';

    /**
     * @event AuthorizationCheckEvent The event that is triggered when determining whether a user is authorized to delete an element for its current site.
     *
     * To authorize the user, set [[AuthorizationCheckEvent::$authorized]] to `true`.
     *
     * ```php
     * use craft\events\AuthorizationCheckEvent;
     * use craft\services\Elements;
     * use yii\base\Event;
     *
     * Event::on(
     *     Elements::class,
     *     Elements::EVENT_AUTHORIZE_DELETE_FOR_SITE,
     *     function(AuthorizationCheckEvent $event) {
     *         $event->authorized = true;
     *     }
     * );
     * ```
     *
     * @see canDeleteForSite()
     * @since 4.3.0
     */
    public const EVENT_AUTHORIZE_DELETE_FOR_SITE = 'authorizeDeleteForSite';

    /**
     * @event ElementEvent The event that is triggered before deleting an element for a single site.
     * @see deleteElementForSite()
     * @see deleteElementsForSite()
     * @since 4.4.0
     */
    public const EVENT_BEFORE_DELETE_FOR_SITE = 'beforeDeleteForSite';

    /**
     * @event ElementEvent The event that is triggered after deleting an element for a single site.
     * @see deleteElementForSite()
     * @see deleteElementsForSite()
     * @since 4.4.0
     */
    public const EVENT_AFTER_DELETE_FOR_SITE = 'afterDeleteForSite';

    /**
     * @var array|null
     */
    private ?array $_placeholderElements = null;

    /**
     * @var array
     * @see setPlaceholderElement()
     */
    private array $_placeholderUris;

    /**
     * @var string[]
     */
    private array $_elementTypesByRefHandle = [];

    /**
     * Creates an element with a given config.
     *
     * @template T of ElementInterface
     * @param class-string<T>|array $config The element’s class name, or its config, with a `type` value
     *
     * @phpstan-param class-string<T>|array{type:class-string<T>} $config
     * @return T The element
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::createElement()} instead.
     */
    public function createElement(mixed $config): ElementInterface
    {
        return ElementsFacade::createElement($config);
    }

    /**
     * Creates an element query for a given element type.
     *
     * @param class-string<ElementInterface> $elementType The element class
     *
     * @return ElementQueryInterface The element query
     * @throws InvalidArgumentException if $elementType is not a valid element
     * @since 3.5.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::createElementQuery()} instead.
     */
    public function createElementQuery(string $elementType): ElementQueryInterface|ElementQuery
    {
        return ElementsFacade::createElementQuery($elementType);
    }

    /**
     * @var string the DB connection name that should be used to store element bulk op records.
     * @since 5.3.0
     */
    public string $bulkOpDb = 'db2';

    // Element caches
    // -------------------------------------------------------------------------

    /**
     * Returns whether we are currently collecting element cache invalidation info.
     *
     * @return bool
     * @see startCollectingCacheInfo()
     * @see stopCollectingCacheInfo()
     * @since 4.3.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementCaches::isCollectingCacheInfo()} instead.
     */
    public function getIsCollectingCacheInfo(): bool
    {
        return $this->elementCaches()->isCollectingCacheInfo();
    }

    /**
     * Returns whether we are currently collecting element cache invalidation tags.
     *
     * @return bool
     * @since 3.5.0
     * @deprecated in 4.3.0. [[getIsCollectingCacheInfo()]] should be used instead.
     */
    public function getIsCollectingCacheTags(): bool
    {
        return $this->getIsCollectingCacheInfo();
    }

    /**
     * Starts collecting element cache invalidation info.
     *
     * @since 4.3.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementCaches::startCollectingCacheInfo()} instead.
     */
    public function startCollectingCacheInfo(): void
    {
        $this->elementCaches()->startCollectingCacheInfo();
    }

    /**
     * Starts collecting element cache invalidation tags.
     *
     * @since 3.5.0
     * @deprecated in 4.3.0. [[startCollectingCacheInfo()]] should be used instead.
     */
    public function startCollectingCacheTags(): void
    {
        $this->startCollectingCacheInfo();
    }

    /**
     * Adds element cache invalidation tags to the current collection.
     *
     * @param string[] $tags
     *
     * @since 3.5.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementCaches::collectCacheTags()} instead.
     */
    public function collectCacheTags(array $tags): void
    {
        $this->elementCaches()->collectCacheTags($tags);
    }

    /**
     * Sets a possible cache expiration date that [[stopCollectingCacheInfo()]] should return.
     *
     * The value will only be used if it is less than the currently stored expiration date.
     *
     * @param DateTime $expiryDate
     *
     * @since 4.3.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementCaches::setCacheExpiryDate()} instead.
     */
    public function setCacheExpiryDate(DateTime $expiryDate): void
    {
        $this->elementCaches()->setCacheExpiryDate($expiryDate);
    }

    /**
     ** Stores cache invalidation info for a given element.
     *
     * @param ElementInterface $element
     *
     * @since 4.5.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementCaches::collectCacheInfoForElement()} instead.
     */
    public function collectCacheInfoForElement(ElementInterface $element): void
    {
        $this->elementCaches()->collectCacheInfoForElement($element);
    }

    /**
     * Stops collecting element invalidation info, and returns a [[TagDependency]] and recommended max cache duration
     * that should be used when saving the cache data.
     *
     * If no cache tags were registered, `[null, null]` will be returned.
     *
     * @return array
     * @phpstan-return array{TagDependency|null,int|null}
     * @since 4.3.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementCaches::stopCollectingCacheInfo()} instead.
     */
    public function stopCollectingCacheInfo(): array
    {
        try {
            return $this->elementCaches()->stopCollectingCacheInfo();
        } catch (RuntimeException $e) {
            throw new InvalidCallException($e->getMessage(), previous: $e);
        }
    }

    /**
     * Stops collecting element cache invalidation tags, and returns a cache dependency object.
     *
     * @return TagDependency
     * @since 3.5.0
     * @deprecated in 4.3.0. [[stopCollectingCacheInfo()]] should be used instead.
     */
    public function stopCollectingCacheTags(): TagDependency
    {
        [$dep] = $this->stopCollectingCacheInfo();
        return $dep ?? new TagDependency();
    }

    /**
     * Invalidates all element caches.
     *
     * @since 3.5.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementCaches::invalidateAll()} instead.
     */
    public function invalidateAllCaches(): void
    {
        $this->elementCaches()->invalidateAll();
    }

    /**
     * Invalidates caches for the given element type.
     *
     * @param class-string<ElementInterface> $elementType
     *
     * @since 3.5.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementCaches::invalidateForElementType()} instead.
     */
    public function invalidateCachesForElementType(string $elementType): void
    {
        $this->elementCaches()->invalidateForElementType($elementType);
    }

    /**
     * Invalidates caches for the given element.
     *
     * @param ElementInterface $element
     *
     * @since 3.5.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementCaches::invalidateForElement()} instead.
     */
    public function invalidateCachesForElement(ElementInterface $element): void
    {
        $this->elementCaches()->invalidateForElement($element);
    }

    private function elementCaches(): ElementCachesService
    {
        return app(ElementCachesService::class);
    }

    // Finding Elements
    // -------------------------------------------------------------------------

    /**
     * Returns an element by its ID.
     *
     * If no element type is provided, the method will first have to run a DB query to determine what type of element
     * the $id is, so you should definitely pass it if it’s known.
     * The element’s status will not be a factor when using this method.
     *
     * @template T of ElementInterface
     * @param int $elementId The element’s ID.
     * @param class-string<T>|null $elementType The element class.
     * @param int|string|int[]|null $siteId The site(s) to fetch the element in.
     * Defaults to the current site.
     * @param array $criteria
     *
     * @return T|null The matching element, or `null`.
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::getElementById()} instead.
     */
    public function getElementById(
        int $elementId,
        ?string $elementType = null,
        array|int|string|null $siteId = null,
        array $criteria = [],
    ): ?ElementInterface {
        return ElementsFacade::getElementById($elementId, $elementType, $siteId, $criteria);
    }

    /**
     * Returns an element by its UID.
     *
     * If no element type is provided, the method will first have to run a DB query to determine what type of element
     * the $uid is, so you should definitely pass it if it’s known.
     * The element’s status will not be a factor when using this method.
     *
     * @template T of ElementInterface
     * @param string $uid The element’s UID.
     * @param class-string<T>|null $elementType The element class.
     * @param int|string|int[]|null $siteId The site(s) to fetch the element in.
     * Defaults to the current site.
     * @param array $criteria
     *
     * @return T|null The matching element, or `null`.
     * @since 3.5.13
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::getElementByUid()} instead.
     */
    public function getElementByUid(
        string $uid,
        ?string $elementType = null,
        array|int|string $siteId = null,
        array $criteria = [],
    ): ?ElementInterface {
        return ElementsFacade::getElementByUId($uid, $elementType, $siteId, $criteria);
    }

    /**
     * Returns an element by its URI.
     *
     * @param string $uri The element’s URI.
     * @param int|null $siteId The site to look for the URI in, and to return the element in.
     * Defaults to the current site.
     * @param bool $enabledOnly Whether to only look for an enabled element. Defaults to `false`.
     *
     * @return ElementInterface|null The matching element, or `null`.
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::getElementByUri()} instead.
     */
    public function getElementByUri(string $uri, ?int $siteId = null, bool $enabledOnly = false): ?ElementInterface
    {
        return ElementsFacade::getElementByUri($uri, $siteId, $enabledOnly);
    }

    /**
     * Returns the class of an element with a given ID.
     *
     * @param int $elementId The element’s ID
     *
     * @return class-string<ElementInterface>|null The element’s class, or null if it could not be found
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::getElementTypeById()} instead.
     */
    public function getElementTypeById(int $elementId): ?string
    {
        return ElementsFacade::getElementTypeById($elementId);
    }

    /**
     * Returns the class of an element with a given UID.
     *
     * @param string $uid The element’s UID
     *
     * @return string|null The element’s class, or null if it could not be found
     * @since 3.5.13
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::getElementTypeByUid()} instead.
     */
    public function getElementTypeByUid(string $uid): ?string
    {
        return ElementsFacade::getElementTypeByUid($uid);
    }

    /**
     * Returns the classes of elements with the given IDs.
     *
     * @param int[] $elementIds The elements’ IDs
     *
     * @return string[]
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::getElementTypesByIds()} instead.
     */
    public function getElementTypesByIds(array $elementIds): array
    {
        return ElementsFacade::getElementTypesByIds($elementIds);
    }

    /**
     * Returns an element’s URI for a given site.
     *
     * @param int $elementId The element’s ID.
     * @param int $siteId The site to search for the element’s URI in.
     *
     * @return string|null The element’s URI or `null` if the element doesn’t exist.
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::getElementUriForSite()} instead.
     */
    public function getElementUriForSite(int $elementId, int $siteId): ?string
    {
        return ElementsFacade::getElementUriForSite($elementId, $siteId);
    }

    /**
     * Returns the site IDs that a given element is enabled in.
     *
     * @param int $elementId The element’s ID.
     *
     * @return int[] The site IDs that the element is enabled in. If the element could not be found, an empty array
     * will be returned.
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::getEnabledSiteIdsForElement()} instead.
     */
    public function getEnabledSiteIdsForElement(int $elementId): array
    {
        return ElementsFacade::getEnabledSiteIdsForElement($elementId);
    }

    // Bulk ops
    // -------------------------------------------------------------------------

    /**
     * Returns the active bulk op keys.
     *
     * @return string[]
     * @since 5.7.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\BulkOp\BulkOps::activeKeys()} instead.
     */
    public function getBulkOpKeys(): array
    {
        return BulkOps::activeKeys();
    }

    /**
     * Begins tracking element saves and deletes as part of a bulk operation, identified by a unique key.
     *
     * @return string The bulk operation key
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\BulkOp\BulkOps::start()} instead.
     */
    public function beginBulkOp(): string
    {
        return BulkOps::start();
    }

    /**
     * Resumes tracking element saves and deletes as part of a bulk operation.
     *
     * @param string $key The bulk operation key returned by [[beginBulkOp()]].
     *
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\BulkOp\BulkOps::resume()} instead.
     */
    public function resumeBulkOp(string $key): void
    {
        BulkOps::resume($key);
    }

    /**
     * Finishes tracking element saves and deletes as part of a bulk operation.
     *
     * @param string $key The bulk operation key returned by [[beginBulkOp()]].
     *
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\BulkOp\BulkOps::end()} instead.
     */
    public function endBulkOp(string $key): void
    {
        BulkOps::end($key);
    }

    /**
     * Tracks an element as being affected by any active bulk operations.
     *
     * @param ElementInterface $element
     *
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\BulkOp\BulkOps::trackElement()} instead.
     */
    public function trackElementInBulkOps(ElementInterface $element): void
    {
        BulkOps::trackElement($element);
    }

    /**
     * Ensures that we’re tracking element saves and deletes as part of a bulk operation, then executes the given
     * callback function.
     *
     * @param callable $callback
     *
     * @return mixed
     * @since 5.3.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\BulkOp\BulkOps::ensure()} instead.
     */
    public function ensureBulkOp(callable $callback): mixed
    {
        return BulkOps::ensure($callback);
    }

    // Saving Elements
    // -------------------------------------------------------------------------

    /**
     * Handles all of the routine tasks that go along with saving elements.
     *
     * Those tasks include:
     *
     * - Validating its content (if $validateContent is `true`, or it’s left as `null` and the element is enabled)
     * - Ensuring the element has a title if its type [[Element::hasTitles()|has titles]], and giving it a
     *   default title in the event that $validateContent is set to `false`
     * - Saving a row in the `elements` table
     * - Assigning the element’s ID on the element model, if it’s a new element
     * - Assigning the element’s ID on the element’s content model, if there is one and it’s a new set of content
     * - Updating the search index with new keywords from the element’s content
     * - Setting a unique URI on the element, if it’s supposed to have one.
     * - Saving the element’s row(s) in the `elements_sites` and `content` tables
     * - Deleting any rows in the `elements_sites` and `content` tables that no longer need to be there
     * - Cleaning any template caches that the element was involved in
     *
     * The function will fire `beforeElementSave` and `afterElementSave` events, and will call `beforeSave()`
     *  and `afterSave()` methods on the passed-in element, giving the element opportunities to hook into the
     * save process.
     *
     * Example usage - creating a new entry:
     *
     * ```php
     * $entry = new Entry();
     * $entry->sectionId = 10;
     * $entry->typeId = 1;
     * $entry->authorId = 5;
     * $entry->enabled = true;
     * $entry->title = "Hello World!";
     * $entry->setFieldValues([
     *     'body' => "<p>I can’t believe I literally just called this “Hello World!”.</p>",
     * ]);
     * $success = Craft::$app->elements->saveElement($entry);
     * if (!$success) {
     *     \Illuminate\Support\Facades\Log::error('Couldn’t save the entry "'.$entry->title.'"', [__METHOD__]);
     * }
     * ```
     *
     * @param ElementInterface $element The element that is being saved
     * @param bool $runValidation Whether the element should be validated
     * @param bool $propagate Whether the element should be saved across all of its supported sites
     * (this can only be disabled when updating an existing element)
     * @param bool|null $updateSearchIndex Whether to update the element search index for the element
     * (this will happen via a background job if this is a web request)
     * @param bool $forceTouch Whether to force the `dateUpdated` timestamp to be updated for the element,
     * regardless of whether it’s being resaved
     * @param bool|null $crossSiteValidate Whether the element should be validated across all supported sites
     * @param bool $saveContent Whether all the element’s content should be saved. When false (default) only dirty fields will be saved.
     *
     * @return bool
     * @throws ElementNotFoundException if $element has an invalid $id
     * @throws Exception if the $element doesn’t have any supported sites
     * @throws Throwable if reasons
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::saveElement()} instead.
     */
    public function saveElement(
        ElementInterface $element,
        bool $runValidation = true,
        bool $propagate = true,
        ?bool $updateSearchIndex = null,
        bool $forceTouch = false,
        ?bool $crossSiteValidate = false,
        bool $saveContent = false,
    ): bool {
        return ElementsFacade::saveElement($element, $runValidation, $propagate, $updateSearchIndex, $forceTouch, $crossSiteValidate, $saveContent);
    }

    /**
     * Sets the URI on an element.
     *
     * @param ElementInterface $element
     *
     * @throws OperationAbortedException if a unique URI could not be found
     * @since 4.6.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::setElementUri()} instead.
     */
    public function setElementUri(ElementInterface $element): void
    {
        ElementsFacade::setElementUri($element);
    }

    /**
     * Merges recent canonical element changes into a given derivative, such as a draft.
     *
     * @param ElementInterface $element The derivative element
     *
     * @since 3.7.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::mergeCanonicalChanges()} instead.
     */
    public function mergeCanonicalChanges(ElementInterface $element): void
    {
        ElementsFacade::mergeCanonicalChanges($element);
    }

    /**
     * Updates the canonical element from a given derivative, such as a draft or revision.
     *
     * @template T of ElementInterface
     * @param T $element The derivative element
     * @param array $newAttributes Any attributes to apply to the canonical element
     *
     * @return T The updated canonical element
     * @throws InvalidArgumentException if the element is already a canonical element
     * @since 3.7.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::updateCanonicalElement()} instead.
     */
    public function updateCanonicalElement(ElementInterface $element, array $newAttributes = []): ElementInterface
    {
        return ElementsFacade::updateCanonicalElement($element, $newAttributes);
    }

    /**
     * Resaves all elements that match a given element query.
     *
     * @param ElementQueryInterface|\CraftCms\Cms\Element\Queries\ElementQuery $query The element query to fetch elements with
     * @param bool $continueOnError Whether to continue going if an error occurs
     * @param bool $skipRevisions Whether elements that are (or belong to) a revision should be skipped
     * @param bool|null $updateSearchIndex Whether to update the element search index for the element
     * (this will happen via a background job if this is a web request)
     * @param bool $touch Whether to update the `dateUpdated` timestamps for the elements
     *
     * @throws Throwable if reasons
     * @since 3.2.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::resaveElements()} instead.
     */
    public function resaveElements(
        ElementQueryInterface $query,
        bool $continueOnError = false,
        bool $skipRevisions = true,
        ?bool $updateSearchIndex = null,
        bool $touch = false,
    ): void {
        ElementsFacade::resaveElements($query, $continueOnError, $skipRevisions, $updateSearchIndex, $touch);
    }

    /**
     * Propagates all elements that match a given element query to another site(s).
     *
     * @param ElementQueryInterface $query The element query to fetch elements with
     * @param int|int[]|null $siteIds The site ID(s) that the elements should be propagated to. If null, elements will be
     * @param bool $continueOnError Whether to continue going if an error occurs
     *
     * @throws Throwable if reasons
     * propagated to all supported sites, except the one they were queried in.
     * @since 3.2.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::propagateElements()} instead.
     */
    public function propagateElements(
        ElementQueryInterface $query,
        array|int|null $siteIds = null,
        bool $continueOnError = false,
    ): void {
        ElementsFacade::propagateElements($query, $siteIds, $continueOnError);
    }

    /**
     * Duplicates an element.
     *
     * @template T of ElementInterface
     * @param T $element the element to duplicate
     * @param array $newAttributes any attributes to apply to the duplicate. This can contain a `siteAttributes` key,
     * set to an array of site-specific attribute array, indexed by site IDs.
     * @param bool $placeInStructure whether to position the cloned element after the original one in its structure.
     * (This will only happen if the duplicated element is canonical.)
     * @param bool $asUnpublishedDraft whether the duplicate should be created as unpublished draft
     * @param bool $checkAuthorization whether to ensure the current user is authorized to save the new element,
     * once its new attributes have been applied to it
     * @param bool $copyModifiedFields whether to copy modified attribute/field data over to the duplicated element
     *
     * @return T the duplicated element
     * @throws UnsupportedSiteException if the element is being duplicated into a site it doesn’t support
     * @throws InvalidElementException if saveElement() returns false for any of the sites
     * @throws ForbiddenHttpException if the user isn't authorized to save the duplicated element
     * @throws Throwable if reasons
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::duplicateElement()} instead.
     */
    public function duplicateElement(
        ElementInterface $element,
        array $newAttributes = [],
        bool $placeInStructure = true,
        bool $asUnpublishedDraft = false,
        bool $checkAuthorization = false,
        bool $copyModifiedFields = false,
    ): ElementInterface {
        return ElementsFacade::duplicateElement($element, $newAttributes, $placeInStructure, $asUnpublishedDraft, $checkAuthorization, $copyModifiedFields);
    }

    /**
     * Updates an element’s slug and URI, along with any descendants.
     *
     * @param ElementInterface $element The element to update.
     * @param bool $updateOtherSites Whether the element’s other sites should also be updated.
     * @param bool $updateDescendants Whether the element’s descendants should also be updated.
     * @param bool $queue Whether the element’s slug and URI should be updated via a job in the queue.
     *
     * @throws OperationAbortedException if a unique URI can’t be generated based on the element’s URI format
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::updateElementSlugAndUri()} instead.
     */
    public function updateElementSlugAndUri(
        ElementInterface $element,
        bool $updateOtherSites = true,
        bool $updateDescendants = true,
        bool $queue = false,
    ): void {
        ElementsFacade::updateElementSlugAndUri($element, $updateOtherSites, $updateDescendants, $queue);
    }

    /**
     * Updates an element’s slug and URI, for any sites besides the given one.
     *
     * @param ElementInterface $element The element to update.
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::updateElementSlugAndUriInOtherSites()} instead.
     */
    public function updateElementSlugAndUriInOtherSites(ElementInterface $element): void
    {
        ElementsFacade::updateElementSlugAndUriInOtherSites($element);
    }

    /**
     * Updates an element’s descendants’ slugs and URIs.
     *
     * @param ElementInterface $element The element whose descendants should be updated.
     * @param bool $updateOtherSites Whether the element’s other sites should also be updated.
     * @param bool $queue Whether the descendants’ slugs and URIs should be updated via a job in the queue.
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::updateDescendantSlugsAndUris()} instead.
     */
    public function updateDescendantSlugsAndUris(
        ElementInterface $element,
        bool $updateOtherSites = true,
        bool $queue = false,
    ): void {
        ElementsFacade::updateDescendantSlugsAndUris($element, $updateOtherSites, $queue);
    }

    /**
     * Merges two elements together by their IDs.
     *
     * This method will update the following:
     * - Any relations involving the merged element
     * - Any structures that contain the merged element
     * - Any reference tags in textual custom fields referencing the merged element
     *
     * @param int $mergedElementId The ID of the element that is going away.
     * @param int $prevailingElementId The ID of the element that is sticking around.
     *
     * @return bool Whether the elements were merged successfully.
     * @throws ElementNotFoundException if one of the element IDs don’t exist.
     * @throws Throwable if reasons
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::mergeElementsByIds()} instead.
     */
    public function mergeElementsByIds(int $mergedElementId, int $prevailingElementId): bool
    {
        return ElementsFacade::mergeElementsByIds($mergedElementId, $prevailingElementId);
    }

    /**
     * Merges two elements together.
     *
     * This method will update the following:
     * - Any relations involving the merged element
     * - Any structures that contain the merged element
     * - Any reference tags in textual custom fields referencing the merged element
     *
     * @param ElementInterface $mergedElement The element that is going away.
     * @param ElementInterface $prevailingElement The element that is sticking around.
     *
     * @return bool Whether the elements were merged successfully.
     * @throws Throwable if reasons
     * @since 3.1.31
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::mergeElements()} instead.
     */
    public function mergeElements(ElementInterface $mergedElement, ElementInterface $prevailingElement): bool
    {
        return ElementsFacade::mergeElements($mergedElement, $prevailingElement);
    }

    /**
     * Deletes an element by its ID.
     *
     * @param int $elementId The element’s ID
     * @param class-string<ElementInterface>|null $elementType The element class.
     * @param int|null $siteId The site to fetch the element in.
     * Defaults to the current site.
     * @param bool $hardDelete Whether the element should be hard-deleted immediately, instead of soft-deleted
     *
     * @return bool Whether the element was deleted successfully
     * @throws Throwable
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::deleteElementById()} instead.
     */
    public function deleteElementById(
        int $elementId,
        ?string $elementType = null,
        ?int $siteId = null,
        bool $hardDelete = false,
    ): bool {
        return ElementsFacade::deleteElementById($elementId, $elementType, $siteId, $hardDelete);
    }

    /**
     * Deletes an element.
     *
     * @param ElementInterface $element The element to be deleted
     * @param bool $hardDelete Whether the element should be hard-deleted immediately, instead of soft-deleted
     *
     * @return bool Whether the element was deleted successfully
     * @throws Throwable
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::deleteElement()} instead.
     */
    public function deleteElement(ElementInterface $element, bool $hardDelete = false): bool
    {
        return ElementsFacade::deleteElement($element, $hardDelete);
    }

    /**
     * Deletes an element in the site it’s loaded in.
     *
     * @param ElementInterface $element
     *
     * @since 4.4.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::deleteElementForSite()} instead.
     */
    public function deleteElementForSite(ElementInterface $element): void
    {
        ElementsFacade::deleteElementForSite($element);
    }

    /**
     * Deletes elements in the site they are currently loaded in.
     *
     * @param ElementInterface[] $elements
     *
     * @throws InvalidArgumentException if all elements don’t have the same type and site ID.
     * @since 4.4.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::deleteElementsForSite()} instead.
     */
    public function deleteElementsForSite(array $elements): void
    {
        ElementsFacade::deleteElementsForSite($elements);
    }

    /**
     * Restores an element.
     *
     * @param ElementInterface $element
     *
     * @return bool Whether the element was restored successfully
     * @throws Exception if the $element doesn’t have any supported sites
     * @throws Throwable if reasons
     * @since 3.1.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::restoreElement()} instead.
     */
    public function restoreElement(ElementInterface $element): bool
    {
        return ElementsFacade::restoreElement($element);
    }

    /**
     * Restores multiple elements.
     *
     * @param ElementInterface[] $elements
     *
     * @return bool Whether at least one element was restored successfully
     * @throws UnsupportedSiteException if an element is being restored for a site it doesn’t support
     * @throws Throwable if reasons
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::restoreElements()} instead.
     */
    public function restoreElements(array $elements): bool
    {
        return ElementsFacade::restoreElements($elements);
    }

    /**
     * Returns the recent activity for an element.
     *
     * @param ElementInterface $element
     * @param int|null $excludeUserId
     *
     * @return ElementActivity[]
     * @since 4.5.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementActivity::getRecentActivity()} instead.
     */
    public function getRecentActivity(ElementInterface $element, ?int $excludeUserId = null): array
    {
        return collect(app(ElementActivityService::class)->getRecentActivity($element, $excludeUserId))
            ->map(fn(ElementActivityData $activity) => self::activityToLegacyActivity($activity))
            ->all();
    }

    /**
     * Tracks new activity for an element.
     *
     * @param ElementInterface $element
     * @param 'view'|'edit'|'save' $type $type
     * @param User|null $user
     *
     * @since 4.5.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementActivity::trackActivity()} instead.
     */
    public function trackActivity(ElementInterface $element, string $type, ?User $user = null): void
    {
        $type = ElementActivityType::from($type);

        app(ElementActivityService::class)->trackActivity($element, $type, $user);
    }

    private static function activityToLegacyActivity(ElementActivityData $activity): ElementActivity
    {
        return new ElementActivity(
            $activity->user,
            $activity->element,
            $activity->type->value,
            $activity->timestamp,
        );
    }

    // Element classes
    // -------------------------------------------------------------------------

    /**
     * Returns all available element classes.
     *
     * @return string[] The available element classes.
     * @phpstan-return class-string<ElementInterface>[]
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::getAllElementTypes()} instead.
     */
    public function getAllElementTypes(): array
    {
        return ElementsFacade::getAllElementTypes();
    }

    // Element Actions & Exporters
    // -------------------------------------------------------------------------

    /**
     * Creates an element action with a given config.
     *
     * @template T of ElementActionInterface
     * @param class-string<T>|array $config The element action’s class name, or its config, with a `type` value and optionally a `settings` value
     *
     * @phpstan-param class-string<T>|array{type:class-string<T>} $config
     * @return T The element action
     */
    public function createAction(mixed $config): ElementActionInterface
    {
        return ComponentHelper::createComponent($config, ElementActionInterface::class);
    }

    /**
     * Creates an element exporter with a given config.
     *
     * @template T of ElementExporterInterface
     * @param class-string<T>|array $config The element exporter’s class name, or its config, with a `type` value and optionally a `settings` value
     *
     * @phpstan-param class-string<T>|array{type:class-string<T>} $config
     * @return T The element exporter
     */
    public function createExporter(mixed $config): ElementExporterInterface
    {
        return ComponentHelper::createComponent($config, ElementExporterInterface::class);
    }

    // Misc
    // -------------------------------------------------------------------------

    /**
     * Returns an element class by its handle.
     *
     * @param string $refHandle The element class handle
     *
     * @return string|null The element class, or null if it could not be found
     */
    public function getElementTypeByRefHandle(string $refHandle): ?string
    {
        if (!isset($this->_elementTypesByRefHandle[$refHandle])) {
            $class = $this->elementTypeByRefHandle($refHandle);

            // Special cases for categories/tags/globals, if they've been removed
            if ($class === false && in_array($refHandle, ['category', 'tag', 'globalset'])) {
                $class = Entry::class;
            }

            $this->_elementTypesByRefHandle[$refHandle] = $class;
        }

        return $this->_elementTypesByRefHandle[$refHandle] ?: null;
    }

    private function elementTypeByRefHandle(string $refHandle): string|false
    {
        if (is_subclass_of($refHandle, ElementInterface::class)) {
            return $refHandle;
        }

        foreach ($this->getAllElementTypes() as $class) {
            /** @var class-string<ElementInterface> $class */
            if (
                ($elementRefHandle = $class::refHandle()) !== null &&
                strcasecmp($elementRefHandle, $refHandle) === 0
            ) {
                return $class;
            }
        }

        return false;
    }

    /**
     * Parses a string for element [reference tags](https://craftcms.com/docs/5.x/system/reference-tags.html).
     *
     * @param string $str The string to parse
     * @param int|null $defaultSiteId The default site ID to query the elements in
     *
     * @return string The parsed string
     */
    public function parseRefs(string $str, ?int $defaultSiteId = null): string
    {
        if (!str_contains($str, '{')) {
            return $str;
        }

        // First catalog all of the ref tags by element type, ref type ('id' or 'ref'), and ref name,
        // and replace them with placeholder tokens
        $allRefTagTokens = [];
        $str = preg_replace_callback(
            '/
                \{                                      # Tags always begin with a {
                    (?P<elementType>[\w\\\\]+)          # Ref handle or element type class
                    \:(?P<ref>[^@\:\}\|]+)              # Identifier (ID, or another format supported by the element type)
                    (?:@(?P<site>[^\:\}\|]+))?          # [Optional] Site handle, ID, or UUID
                    (?:\:(?P<attr>[^\}\| ]+))?          # [Optional] Attribute, property, or field
                    (?:\ *\|\|\ *(?P<fallback>[^\}]+))? # [Optional] Fallback text (if the ref fails to resolve)
                \}                                      # Tags always close with a }
            /x',
            function(array $matches) use (
                $defaultSiteId,
                &$allRefTagTokens
            ) {
                $fullMatch = $matches[0];
                $elementType = $matches['elementType'];
                $ref = $matches['ref'];
                $siteId = $matches['site'] ?? null;
                $attribute = $matches['attr'] ?? null;
                $fallback = $matches['fallback'] ?? $fullMatch;

                // Swap out the ref handle for the element type
                $elementType = $this->getElementTypeByRefHandle($elementType);

                // Use the fallback if we couldn't find an element type
                if ($elementType === null) {
                    return $fallback;
                }

                // Get the site
                if (!empty($siteId)) {
                    if (is_numeric($siteId)) {
                        $siteId = (int)$siteId;
                    } else {
                        try {
                            $site = Str::isUuid($siteId)
                                ? Sites::getSiteByUid($siteId)
                                : Sites::getSiteByHandle($siteId);
                        } catch (SiteNotFoundException) {
                            $site = null;
                        }
                        if (!$site) {
                            return $fallback;
                        }
                        $siteId = $site->id;
                    }
                } else {
                    $siteId = $defaultSiteId;
                }

                $refType = is_numeric($ref) ? 'id' : 'ref';
                $token = '{' . Str::random(9) . '}';
                $allRefTagTokens[$siteId][$elementType][$refType][$ref][] = [$token, $attribute, $fallback, $fullMatch];

                return $token;
            },
            $str,
            -1,
            $count,
        );

        if ($count === 0) {
            // No ref tags
            return $str;
        }

        // Now swap them with the resolved values
        $search = [];
        $replace = [];

        foreach ($allRefTagTokens as $siteId => $siteTokens) {
            foreach ($siteTokens as $elementType => $tokensByType) {
                foreach ($tokensByType as $refType => $tokensByName) {
                    // Get the elements, indexed by their ref value
                    $refNames = array_keys($tokensByName);
                    $elementQuery = $this->createElementQuery($elementType)
                        ->siteId($siteId)
                        ->status(null);

                    if ($refType === 'id') {
                        $elementQuery->id($refNames);
                    } elseif (method_exists($elementQuery, 'ref')) {
                        $elementQuery->ref($refNames);
                    }

                    $elements = [];
                    foreach ($elementQuery->all() as $element) {
                        $ref = $refType === 'id' ? $element->id : $element->getRef();
                        $elements[$ref] = $element;

                        // if the reference contains a slash (e.g. section/slug),
                        // also index it by just whatever comes after it
                        if ($refType === 'ref' && ($slash = strrpos($ref, '/')) !== false) {
                            $elements[substr($ref, $slash + 1)] ??= $element;
                        }
                    }

                    // Now append new token search/replace strings
                    foreach ($tokensByName as $refName => $tokens) {
                        $element = $elements[$refName] ?? null;

                        foreach ($tokens as [$token, $attribute, $fallback, $fullMatch]) {
                            $search[] = $token;
                            $replace[] = $this->_getRefTokenReplacement($element, $attribute, $fallback, $fullMatch);
                        }
                    }
                }
            }
        }

        // Swap the tokens with the references
        return str_replace($search, $replace, $str);
    }

    /**
     * Stores a placeholder element that element queries should use instead of populating a new element with a
     * matching ID and site ID.
     *
     * This is used by Live Preview and Sharing features.
     *
     * @param ElementInterface $element The element currently being edited by Live Preview.
     *
     * @throws InvalidArgumentException if the element is missing an ID
     * @see getPlaceholderElement()
     */
    public function setPlaceholderElement(ElementInterface $element): void
    {
        // Won't be able to do anything with this if it doesn't have an ID or site ID
        if (!$element->id || !$element->siteId) {
            throw new InvalidArgumentException('Placeholder element is missing an ID');
        }

        $this->_placeholderElements[$element->getCanonicalId()][$element->siteId] = $element;

        if ($element->uri) {
            $this->_placeholderUris[$element->uri][$element->siteId] = $element;
        }
    }

    /**
     * Returns all placeholder elements.
     *
     * @return ElementInterface[]
     * @since 3.2.5
     */
    public function getPlaceholderElements(): array
    {
        if (!isset($this->_placeholderElements)) {
            return [];
        }

        return call_user_func_array('array_merge', $this->_placeholderElements);
    }

    /**
     * Returns a placeholder element by its ID and site ID.
     *
     * @param int $sourceId The element’s ID
     * @param int $siteId The element’s site ID
     *
     * @return ElementInterface|null The placeholder element if one exists, or null.
     * @see setPlaceholderElement()
     */
    public function getPlaceholderElement(int $sourceId, int $siteId): ?ElementInterface
    {
        return $this->_placeholderElements[$sourceId][$siteId] ?? null;
    }

    /**
     * Normalizes a `with` element query param into an array of eager-loading plans.
     *
     * @param string|array $with
     *
     * @phpstan-param string|array<EagerLoadPlan|array|string> $with
     * @return EagerLoadPlan[]
     * @since 3.5.0
     */
    public function createEagerLoadingPlans(string|array $with): array
    {
        // Normalize the paths and group based on the top level eager loading handle
        if (is_string($with)) {
            $with = str($with)->explode(',');
        }

        $plans = [];
        $nestedWiths = [];

        foreach ($with as $path) {
            // Is this already an EagerLoadPlan object?
            if ($path instanceof EagerLoadPlan) {
                // Make sure $all is true if $count is false
                if (!$path->count && !$path->all) {
                    $path->all = true;
                }
                // ...recursively for any nested plans
                $path->nested = $this->createEagerLoadingPlans($path->nested);

                // Don't index the plan by its alias, as two plans w/ different `when` filters could be using the same alias.
                // Side effect: mixing EagerLoadPlan objects and arrays could result in redundant element queries,
                // but that would be a weird thing to do.
                $plans[] = $path;
                continue;
            }

            // Separate the path and the criteria
            if (is_array($path)) {
                $criteria = $path['criteria'] ?? $path[1] ?? null;
                $count = $path['count'] ?? Arr::pull($criteria, 'count', false);
                $when = $path['when'] ?? null;
                $path = $path['path'] ?? $path[0];
            } else {
                $criteria = null;
                $count = false;
                $when = null;
            }

            // Split the path into the top segment and subpath
            if (($dot = strpos($path, '.')) !== false) {
                $handle = substr($path, 0, $dot);
                $subpath = substr($path, $dot + 1);
            } else {
                $handle = $path;
                $subpath = null;
            }

            // Get the handle & alias
            if (preg_match('/^([a-zA-Z][a-zA-Z0-9_:]*)\s+as\s+(' . HandleRule::$handlePattern . ')$/', $handle,
                $match)) {
                $handle = $match[1];
                $alias = $match[2];
            } else {
                $alias = $handle;
            }

            if (!isset($plans[$alias])) {
                $plan = $plans[$alias] = new EagerLoadPlan([
                    'handle' => $handle,
                    'alias' => $alias,
                ]);
            } else {
                $plan = $plans[$alias];
            }

            // Only set the criteria if there's no subpath
            if ($subpath === null) {
                if ($criteria !== null) {
                    $plan->criteria = $criteria;
                }

                if ($count) {
                    $plan->count = true;
                } else {
                    $plan->all = true;
                }

                if ($when !== null) {
                    $plan->when = $when;
                }
            } else {
                // We are for sure going to need to query the elements
                $plan->all = true;

                // Add this as a nested "with"
                $nestedWiths[$alias][] = [
                    'path' => $subpath,
                    'criteria' => $criteria,
                    'count' => $count,
                    'when' => $when,
                ];
            }
        }

        foreach ($nestedWiths as $alias => $withs) {
            $plans[$alias]->nested = $this->createEagerLoadingPlans($withs);
        }

        return array_values($plans);
    }

    /**
     * Eager-loads additional elements onto a given set of elements.
     *
     * @param class-string<ElementInterface> $elementType The root element type class
     * @param ElementInterface[] $elements The root element models that should be updated with the eager-loaded elements
     * @param array<string|array>|string|EagerLoadPlan[] $with Dot-delimited paths of the elements that should be eager-loaded into the root elements
     */
    public function eagerLoadElements(string $elementType, array|Collection $elements, array|string $with): void
    {
        $elements = collect($elements);

        // Bail if there aren't even any elements
        if ($elements->isEmpty()) {
            return;
        }

        $elementsBySite = $elements
            ->groupBy(fn(ElementInterface $element) => $element->siteId)
            ->map(fn(Collection $elements) => $elements->all())
            ->all();
        $with = $this->createEagerLoadingPlans($with);
        $this->_eagerLoadElementsInternal($elementType, $elementsBySite, $with);
    }

    /**
     * @param class-string<ElementInterface> $elementType
     * @param ElementInterface[][] $elementsBySite
     * @param EagerLoadPlan[] $with
     */
    private function _eagerLoadElementsInternal(string $elementType, array $elementsBySite, array $with): void
    {
        $elementsService = Craft::$app->getElements();
        $hasEventHandlers = $this->hasEventHandlers(self::EVENT_BEFORE_EAGER_LOAD_ELEMENTS);

        foreach ($elementsBySite as $siteId => $elements) {
            $elements = array_values($elements);
            // Fire a 'beforeEagerLoadElements' event
            if ($hasEventHandlers) {
                $event = new EagerLoadElementsEvent([
                    'elementType' => $elementType,
                    'elements' => $elements,
                    'with' => $with,
                ]);
                $this->trigger(self::EVENT_BEFORE_EAGER_LOAD_ELEMENTS, $event);
                $with = $event->with;
            }

            foreach ($with as $plan) {
                // Filter out any elements that the plan doesn't like
                if ($plan->when !== null) {
                    $filteredElements = array_values(array_filter($elements, $plan->when));
                    if (empty($filteredElements)) {
                        continue;
                    }
                } else {
                    $filteredElements = $elements;
                }

                // Get the eager-loading map from the source element type
                $maps = $elementType::eagerLoadingMap($filteredElements, $plan->handle);

                if ($maps === null) {
                    // Null means to skip eager-loading this segment
                    continue;
                }

                // Set everything to empty results as a starting point
                foreach ($filteredElements as $sourceElement) {
                    if ($plan->count) {
                        $sourceElement->setEagerLoadedElementCount($plan->alias, 0);
                    }
                    if ($plan->all) {
                        $sourceElement->setEagerLoadedElements($plan->alias, [], $plan);
                        $sourceElement->setLazyEagerLoadedElements($plan->alias, $plan->lazy);
                    }
                }

                $maps = $this->normalizeEagerLoadingMaps($maps);

                foreach ($maps as $map) {
                    $targetElementIdsBySourceIds = null;
                    $query = null;
                    $offset = 0;
                    $limit = null;

                    if (!empty($map['map'])) {
                        // Loop through the map to find:
                        // - unique target element IDs
                        // - target element IDs indexed by source element IDs
                        $uniqueTargetElementIds = [];
                        $targetElementIdsBySourceIds = [];

                        foreach ($map['map'] as $mapping) {
                            if (!empty($mapping['target'])) {
                                $uniqueTargetElementIds[$mapping['target']] = true;
                                $targetElementIdsBySourceIds[$mapping['source']][$mapping['target']] = true;
                            }
                        }

                        // Get the target elements
                        $query = $this->createElementQuery($map['elementType']);

                        // Default to no order, offset, or limit, but allow the element type/path criteria to override
                        $query->reorder();
                        $query->offset(null);
                        $query->limit(null);

                        $criteria = array_merge(
                            $map['criteria'] ?? [],
                            $plan->criteria,
                        );

                        // Save the offset & limit params for later
                        $offset = Arr::pull($criteria, 'offset', 0);
                        $limit = Arr::pull($criteria, 'limit');

                        Typecast::configure($query, $criteria);

                        if (!$query->siteId) {
                            $query->siteId = $siteId;
                        }

                        if (!$query->id) {
                            $query->id = array_keys($uniqueTargetElementIds);
                        } else {
                            $query->whereIn('elements.id', array_keys($uniqueTargetElementIds));
                        }
                    }

                    // Do we just need the count?
                    if ($plan->count && !$plan->all) {
                        // Just fetch the target elements’ IDs
                        $targetElementIdCounts = [];
                        if ($query) {
                            foreach ($query->ids() as $id) {
                                if (!isset($targetElementIdCounts[$id])) {
                                    $targetElementIdCounts[$id] = 1;
                                } else {
                                    $targetElementIdCounts[$id]++;
                                }
                            }
                        }

                        // Loop through the source elements and count up their targets
                        foreach ($filteredElements as $sourceElement) {
                            if (!empty($targetElementIdCounts) && isset($targetElementIdsBySourceIds[$sourceElement->id])) {
                                $count = 0;
                                foreach (array_keys($targetElementIdsBySourceIds[$sourceElement->id]) as $targetElementId) {
                                    if (isset($targetElementIdCounts[$targetElementId])) {
                                        $count += $targetElementIdCounts[$targetElementId];
                                    }
                                }
                                if ($count !== 0) {
                                    $sourceElement->setEagerLoadedElementCount($plan->alias, $count);
                                }
                            }
                        }

                        continue;
                    }

                    $targetElementData = $query ? Collection::make($query->asArray()->all())->groupBy('id')->all() : [];
                    $targetElements = [];

                    // Tell the source elements about their eager-loaded elements
                    foreach ($filteredElements as $sourceElement) {
                        $targetElementIdsForSource = [];
                        $targetElementsForSource = [];

                        if (isset($targetElementIdsBySourceIds[$sourceElement->id])) {
                            // Does the path mapping want a custom order?
                            if (!empty($criteria['orderBy']) || !empty($criteria['order'])) {
                                // Assign the elements in the order they were returned from the query
                                foreach (array_keys($targetElementData) as $targetElementId) {
                                    if (isset($targetElementIdsBySourceIds[$sourceElement->id][$targetElementId])) {
                                        $targetElementIdsForSource[] = $targetElementId;
                                    }
                                }
                            } else {
                                // Assign the elements in the order defined by the map
                                foreach (array_keys($targetElementIdsBySourceIds[$sourceElement->id]) as $targetElementId) {
                                    if (isset($targetElementData[$targetElementId])) {
                                        $targetElementIdsForSource[] = $targetElementId;
                                    }
                                }
                            }

                            if (!empty($criteria['inReverse'])) {
                                $targetElementIdsForSource = array_reverse($targetElementIdsForSource);
                            }

                            // Create the elements
                            $currentOffset = 0;
                            $count = 0;
                            foreach ($targetElementIdsForSource as $elementId) {
                                foreach ($targetElementData[$elementId] as $result) {
                                    if ($offset && $currentOffset < $offset) {
                                        $currentOffset++;
                                        continue;
                                    }
                                    $targetSiteId = $result['siteId'];
                                    if (!isset($targetElements[$targetSiteId][$elementId])) {
                                        if (isset($map['createElement'])) {
                                            $targetElements[$targetSiteId][$elementId] = $map['createElement']($query,
                                                $result, $sourceElement);
                                        } else {
                                            $targetElements[$targetSiteId][$elementId] = $query->createElement($result);
                                        }
                                    }
                                    $targetElementsForSource[] = $element = $targetElements[$targetSiteId][$elementId];

                                    // If we're collecting cache info and the element is expirable, register its expiry date
                                    if (
                                        $element instanceof ExpirableElementInterface &&
                                        ElementCaches::isCollectingCacheInfo() &&
                                        ($expiryDate = $element->getExpiryDate()) !== null
                                    ) {
                                        ElementCaches::setCacheExpiryDate($expiryDate);
                                    }

                                    if ($limit && ++$count == $limit) {
                                        break 2;
                                    }
                                }
                            }
                        }

                        if (!empty($targetElementsForSource)) {
                            if (!empty($criteria['withProvisionalDrafts'])) {
                                $targetElementsForSource = app(Drafts::class)->withProvisionalDrafts($targetElementsForSource);
                            }

                            $sourceElement->setEagerLoadedElements($plan->alias, $targetElementsForSource, $plan);

                            if ($plan->count) {
                                $sourceElement->setEagerLoadedElementCount($plan->alias,
                                    count($targetElementsForSource));
                            }
                        }
                    }

                    if (!empty($targetElements)) {
                        /** @var ElementInterface[] $flatTargetElements */
                        $flatTargetElements = array_merge(...array_values($targetElements));

                        // Set the eager loading info on each of the target elements,
                        // in case it's needed for lazy eager loading
                        $eagerLoadResult = new EagerLoadInfo($plan, $filteredElements);
                        foreach ($flatTargetElements as $element) {
                            $element->eagerLoadInfo = $eagerLoadResult;
                        }

                        // Pass the instantiated elements to afterPopulate()
                        $query->asArray = false;
                        if ($query instanceof ElementQueryInterface) {
                            $query->afterHydrate(collect($flatTargetElements));
                        }
                    }

                    // Now eager-load any sub paths
                    if (!empty($map['map']) && !empty($plan->nested)) {
                        $this->_eagerLoadElementsInternal(
                            $map['elementType'],
                            array_map('array_values', $targetElements),
                            $plan->nested,
                        );
                    }
                }
            }
        }
    }

    /**
     * @param EagerLoadingMap|EagerLoadingMap[]|false $map
     *
     * @return EagerLoadingMap[]|false[]
     */
    private function normalizeEagerLoadingMaps(array|false $map): array
    {
        if (isset($map['elementType']) || $map === false) {
            // a normal, one-dimensional map
            return [$map];
        }

        if (isset($map['map'])) {
            // no single element type was provided, so split it up into multiple maps - one for each unique type
            /** @phpstan-ignore-next-line */
            $maps = $this->groupMapsByElementType($map['map']);
            if (isset($map['criteria']) || isset($map['createElement'])) {
                foreach ($maps as &$m) {
                    $m['criteria'] ??= $map['criteria'] ?? [];
                    $m['createElement'] ??= $map['createElement'] ?? null;
                }
            }
            return $maps;
        }

        // multiple maps were provided, so normalize and return each of them
        $maps = [];
        foreach ($map as $m) {
            if (isset($m['map'])) {
                /** @phpstan-ignore-next-line */
                $maps += $this->normalizeEagerLoadingMaps($m);
            }
        }
        return $maps;
    }

    /**
     * @param array{source:int,target:int,elementType?:class-string<ElementInterface>}[] $map
     *
     * @return EagerLoadingMap[]
     */
    private function groupMapsByElementType(array $map): array
    {
        if (empty($map)) {
            return [];
        }

        $maps = [];
        $untypedMaps = [];
        $untypedTargetIds = [];

        foreach ($map as $m) {
            if (isset($m['elementType'])) {
                $elementType = $m['elementType'];
                $maps[$elementType] ??= ['elementType' => $elementType];
                $maps[$elementType]['map'][] = $m;
            } else {
                $untypedMaps[] = $m;
                $untypedTargetIds[] = $m['target'];
            }
        }

        if (!empty($untypedMaps)) {
            $elementTypesById = [];

            foreach (array_chunk($untypedTargetIds, 100) as $ids) {
                $types = DB::table(Table::ELEMENTS)
                    ->whereIn('id', $ids)
                    ->pluck('type', 'id');

                // we need to preserve the numeric keys, so array_merge() won't work here
                foreach ($types as $id => $type) {
                    $elementTypesById[$id] = $type;
                }
            }

            foreach ($untypedMaps as $m) {
                if (!isset($elementTypesById[$m['target']])) {
                    continue;
                }
                $elementType = $elementTypesById[$m['target']];
                $maps[$elementType] ??= ['elementType' => $elementType];
                $maps[$elementType]['map'][] = $m;
            }
        }

        return array_values($maps);
    }

    /**
     * Propagates an element to a different site.
     *
     * @param ElementInterface $element The element to propagate
     * @param int $siteId The site ID that the element should be propagated to
     * @param ElementInterface|false|null $siteElement The element loaded for the propagated site (only pass this if you
     * already had a reason to load it). Set to `false` if it is known to not exist yet.
     *
     * @return ElementInterface The element in the target site
     * @throws Exception if the element couldn't be propagated
     * @throws UnsupportedSiteException if the element doesn’t support `$siteId`
     * @since 3.0.13
     */
    public function propagateElement(
        ElementInterface $element,
        int $siteId,
        ElementInterface|false|null $siteElement = null,
    ): ElementInterface {
        $supportedSites = Arr::keyBy(ElementHelper::supportedSitesForElement($element), 'siteId');

        BulkOps::ensure(function() use ($element, $supportedSites, $siteId, &$siteElement) {
            app(PropagateElementAction::class)->handle($element, $supportedSites, $siteId, $siteElement);

            // Track this element in bulk operations
            BulkOps::trackElement($element);
        });

        // Clear caches
        $this->elementCaches()->invalidateForElement($element);

        return $siteElement;
    }

    /**
     * Propagates an element to a different site
     *
     * @param ElementInterface $element
     * @param array $supportedSites The element’s supported site info, indexed by site ID
     * @param int $siteId The site ID being propagated to
     * @param ElementInterface|false|null $siteElement The element loaded for the propagated site
     * @param-out ElementInterface $siteElement
     * @param bool $crossSiteValidate Whether the element should be validated across all supported sites
     * @param bool $saveContent Whether the element’s content should be saved
     * @param ElementSiteSettings|null $siteSettingsRecord
     *
     * @retrun bool
     * @throws Exception if the element couldn't be propagated
     */
    private function _propagateElement(
        ElementInterface $element,
        array $supportedSites,
        int $siteId,
        ElementInterface|false|null &$siteElement = null,
        bool $crossSiteValidate = false,
        bool $saveContent = true,
        ?ElementSiteSettings &$siteSettingsRecord = null,
    ): bool {
    }



    /**
     * Soft-deletes or restores the drafts and revisions of the given element.
     *
     * @param int $canonicalId The canonical element ID
     * @param bool $delete `true` if the drafts/revisions should be soft-deleted; `false` if they should be restored
     */
    private function _cascadeDeleteDraftsAndRevisions(int $canonicalId, bool $delete = true): void
    {
        foreach (['draftId' => Table::DRAFTS, 'revisionId' => Table::REVISIONS] as $fk => $table) {
            DB::table(new Alias(Table::ELEMENTS, 'e'))
                ->whereIn(
                    "e.$fk",
                    DB::table(new Alias($table, 't'))
                        ->select('t.id')
                        ->where('t.canonicalId', $canonicalId),
                )
                ->update([
                    'dateDeleted' => $delete ? now() : null,
                ]);
        }
    }

    /**
     * Returns the replacement for a given reference tag.
     *
     * @param ElementInterface|null $element
     * @param string|null $attribute
     * @param string $fallback
     * @param string $fullMatch
     *
     * @return string
     * @see parseRefs()
     */
    private function _getRefTokenReplacement(
        ?ElementInterface $element,
        ?string $attribute,
        string $fallback,
        string $fullMatch,
    ): string {
        if ($element === null) {
            // Put the ref tag back
            return $fallback;
        }

        if (empty($attribute) || !isset($element->$attribute)) {
            // Default to the URL
            return (string)$element->getUrl();
        }

        try {
            $value = $element->$attribute;

            if (is_object($value) && !method_exists($value, '__toString')) {
                throw new Exception('Object of class ' . get_class($value) . ' could not be converted to string');
            }

            return $this->parseRefs((string)$value);
        } catch (Throwable $e) {
            // Log it
            Log::error("An exception was thrown when parsing the ref tag \"$fullMatch\":\n" . $e->getMessage(), [__METHOD__]);

            // Replace the token with the default value
            return $fallback;
        }
    }

    /**
     * Returns whether a user is authorized to view the given element’s edit page.
     *
     * @param ElementInterface $element
     * @param User|null $user
     *
     * @return bool
     * @since 4.3.0
     */
    public function canView(ElementInterface $element, ?User $user = null): bool
    {
        if (!$user) {
            $user = Auth::user();
            if (!$user) {
                return false;
            }
        }

        // Fire deprecated Yii events for plugin compatibility
        $eventResult = $this->_authCheck($element, $user, self::EVENT_AUTHORIZE_VIEW);
        if ($eventResult !== null) {
            return $eventResult;
        }

        // Delegate to Laravel Gate
        return Gate::forUser($user)->allows('view', $element);
    }

    /**
     * Returns whether a user is authorized to save the given element in its current form.
     *
     * @param ElementInterface $element
     * @param User|null $user
     *
     * @return bool
     * @since 4.3.0
     */
    public function canSave(ElementInterface $element, ?User $user = null): bool
    {
        if (!$user) {
            $user = Auth::user();
            if (!$user) {
                return false;
            }
        }

        // Fire deprecated Yii events for plugin compatibility
        $eventResult = $this->_authCheck($element, $user, self::EVENT_AUTHORIZE_SAVE);
        if ($eventResult !== null) {
            return $eventResult;
        }

        // Delegate to Laravel Gate
        return Gate::forUser($user)->allows('save', $element);
    }

    /**
     * Returns whether a user is authorized to save the canonical version of the given element.
     *
     * @param ElementInterface $element
     * @param User|null $user
     *
     * @return bool
     * @since 5.6.0
     */
    public function canSaveCanonical(ElementInterface $element, ?User $user = null): bool
    {
        if ($element->getIsUnpublishedDraft()) {
            $fakeCanonical = clone $element;
            $fakeCanonical->draftId = null;
            return $this->canSave($fakeCanonical, $user);
        }

        return $this->canSave($element->getCanonical(true), $user);
    }

    /**
     * Returns whether a user is authorized to duplicate the given element.
     *
     * This should always be called in conjunction with [[canView()]] or [[canSave()]].
     *
     * @param ElementInterface $element
     * @param User|null $user
     *
     * @return bool
     * @since 4.3.0
     */
    public function canDuplicate(ElementInterface $element, ?User $user = null): bool
    {
        if (!$user) {
            $user = Auth::user();
            if (!$user) {
                return false;
            }
        }

        // Fire deprecated Yii events for plugin compatibility
        $eventResult = $this->_authCheck($element, $user, self::EVENT_AUTHORIZE_DUPLICATE);
        if ($eventResult !== null) {
            return $eventResult;
        }

        // Delegate to Laravel Gate
        return Gate::forUser($user)->allows('duplicate', $element);
    }

    /**
     * Returns whether a user is authorized to duplicate the given element as an unpublished draft.
     *
     * @param ElementInterface $element
     * @param User|null $user
     *
     * @return bool
     * @since 5.0.0
     */
    public function canDuplicateAsDraft(ElementInterface $element, ?User $user = null): bool
    {
        if (!$user) {
            $user = Auth::user();
            if (!$user) {
                return false;
            }
        }

        // Fire deprecated Yii events for plugin compatibility
        $eventResult = $this->_authCheck($element, $user, self::EVENT_AUTHORIZE_DUPLICATE_AS_DRAFT);
        if ($eventResult !== null) {
            return $eventResult;
        }

        // Delegate to Laravel Gate
        return Gate::forUser($user)->allows('duplicateAsDraft', $element);
    }

    /**
     * Returns whether a user is authorized to copy the given element, to be duplicated elsewhere.
     *
     *  This should always be called in conjunction with [[canView()]].
     *
     * @param ElementInterface $element
     * @param User|null $user
     *
     * @return bool
     * @since 5.7.0
     */
    public function canCopy(ElementInterface $element, ?User $user = null): bool
    {
        if (!$user) {
            $user = Auth::user();
            if (!$user) {
                return false;
            }
        }

        // Fire deprecated Yii events for plugin compatibility
        $eventResult = $this->_authCheck($element, $user, self::EVENT_AUTHORIZE_COPY);
        if ($eventResult !== null) {
            return $eventResult;
        }

        // Delegate to Laravel Gate
        return Gate::forUser($user)->allows('copy', $element);
    }

    /**
     * Returns whether a user is authorized to delete the given element.
     *
     * This should always be called in conjunction with [[canView()]] or [[canSave()]].
     *
     * @param ElementInterface $element
     * @param User|null $user
     *
     * @return bool
     * @since 4.3.0
     */
    public function canDelete(ElementInterface $element, ?User $user = null): bool
    {
        if (!$user) {
            $user = Auth::user();
            if (!$user) {
                return false;
            }
        }

        // Fire deprecated Yii events for plugin compatibility
        $eventResult = $this->_authCheck($element, $user, self::EVENT_AUTHORIZE_DELETE);
        if ($eventResult !== null) {
            return $eventResult;
        }

        // Delegate to Laravel Gate
        return Gate::forUser($user)->allows('delete', $element);
    }

    /**
     * Returns whether a user is authorized to delete the given element for its current site.
     *
     * This should always be called in conjunction with [[canView()]] or [[canSave()]].
     *
     * @param ElementInterface $element
     * @param User|null $user
     *
     * @return bool
     * @since 4.3.0
     */
    public function canDeleteForSite(ElementInterface $element, ?User $user = null): bool
    {
        if (!$user) {
            $user = Auth::user();
            if (!$user) {
                return false;
            }
        }

        // Fire deprecated Yii events for plugin compatibility
        $eventResult = $this->_authCheck($element, $user, self::EVENT_AUTHORIZE_DELETE_FOR_SITE);
        if ($eventResult !== null) {
            return $eventResult;
        }

        // Delegate to Laravel Gate
        return Gate::forUser($user)->allows('deleteForSite', $element);
    }

    /**
     * Returns whether a user is authorized to create drafts for the given element.
     *
     * This should always be called in conjunction with [[canView()]] or [[canSave()]].
     *
     * @param ElementInterface $element
     * @param User|null $user
     *
     * @return bool
     * @since 4.3.0
     */
    public function canCreateDrafts(ElementInterface $element, ?User $user = null): bool
    {
        if (!$user) {
            $user = Auth::user();
            if (!$user) {
                return false;
            }
        }

        // Fire deprecated Yii events for plugin compatibility
        $eventResult = $this->_authCheck($element, $user, self::EVENT_AUTHORIZE_CREATE_DRAFTS);
        if ($eventResult !== null) {
            return $eventResult;
        }

        // Delegate to Laravel Gate
        return Gate::forUser($user)->allows('createDrafts', $element);
    }

    private function _authCheck(ElementInterface $element, User $user, string $eventName): ?bool
    {
        if (!$this->hasEventHandlers($eventName)) {
            return null;
        }

        $event = new AuthorizationCheckEvent($user, [
            'element' => $element,
            'authorized' => null,
        ]);

        $this->trigger($eventName, $event);
        return $event->authorized;
    }

    public static function registerEvents(): void
    {
        Event::listen(function(BeforeBulkOp $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_BEFORE_BULK_OP)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_BEFORE_BULK_OP, new BulkOpEvent([
                'key' => $event->key,
            ]));
        });

        Event::listen(function(AfterBulkOp $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_AFTER_BULK_OP)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_AFTER_BULK_OP, new BulkOpEvent([
                'key' => $event->key,
            ]));
        });

        Event::listen(function(InvalidateElementCaches $event) {
            // Fire a 'invalidateCaches' event
            if (Craft::$app->getElements()->hasEventHandlers(self::EVENT_INVALIDATE_CACHES)) {
                Craft::$app->getElements()->trigger(self::EVENT_INVALIDATE_CACHES, new InvalidateElementCachesEvent([
                    'tags' => $event->tags,
                    'element' => $event->element,
                ]));
            }
        });

        $elementEvents = [
            BeforeSaveElement::class => self::EVENT_BEFORE_SAVE_ELEMENT,
            AfterSaveElement::class => self::EVENT_AFTER_SAVE_ELEMENT,
            BeforeUpdateSearchIndex::class => self::EVENT_BEFORE_UPDATE_SEARCH_INDEX,
            SetElementUri::class => self::EVENT_SET_ELEMENT_URI,
            BeforeMergeCanonicalChanges::class => self::EVENT_BEFORE_MERGE_CANONICAL_CHANGES,
            AfterMergeCanonicalChanges::class => self::EVENT_AFTER_MERGE_CANONICAL_CHANGES,
            BeforeUpdateSlugAndUri::class => self::EVENT_BEFORE_UPDATE_SLUG_AND_URI,
            AfterUpdateSlugAndUri::class => self::EVENT_AFTER_UPDATE_SLUG_AND_URI,
            AfterDeleteElement::class => self::EVENT_AFTER_DELETE_ELEMENT,
            BeforeDeleteForSite::class => self::EVENT_BEFORE_DELETE_FOR_SITE,
            AfterDeleteForSite::class => self::EVENT_AFTER_DELETE_FOR_SITE,
            BeforeRestoreElement::class => self::EVENT_BEFORE_RESTORE_ELEMENT,
            AfterRestoreElement::class => self::EVENT_AFTER_RESTORE_ELEMENT,
        ];

        foreach ($elementEvents as $newEventClass => $yiiEventClass) {
            Event::listen($newEventClass, function($event) use ($yiiEventClass) {
                if (!Craft::$app->getElements()->hasEventHandlers($yiiEventClass)) {
                    return;
                }

                $yiiEvent = new ElementEvent([
                    'element' => $event->element,
                ]);

                if (property_exists($event, 'isNew')) {
                    $yiiEvent->isNew = $event->isNew;
                }

                Craft::$app->getElements()->trigger($yiiEventClass, $yiiEvent);

                if (property_exists($event, 'isValid')) {
                    $event->isValid = $yiiEvent->isValid;
                }

                if (property_exists($event, 'handled')) {
                    $event->handled = $yiiEvent->handled;
                }
            });
        }

        Event::listen(function(BeforeResaveElements $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_BEFORE_RESAVE_ELEMENTS)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_BEFORE_RESAVE_ELEMENTS, new ElementQueryEvent([
                'query' => $event->query,
            ]));
        });

        Event::listen(function(BeforeResaveElement $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_BEFORE_RESAVE_ELEMENT)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_BEFORE_RESAVE_ELEMENT, new MultiElementActionEvent([
                'query' => $event->query,
                'element' => $event->element,
                'position' => $event->position,
            ]));
        });

        Event::listen(function(AfterResaveElement $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_AFTER_RESAVE_ELEMENT)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_AFTER_RESAVE_ELEMENT, new MultiElementActionEvent([
                'query' => $event->query,
                'element' => $event->element,
                'position' => $event->position,
                'exception' => $event->exception,
            ]));
        });

        Event::listen(function(AfterResaveElements $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_AFTER_RESAVE_ELEMENTS)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_AFTER_RESAVE_ELEMENTS, new ElementQueryEvent([
                'query' => $event->query,
            ]));
        });

        Event::listen(function(BeforePropagateElements $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_BEFORE_PROPAGATE_ELEMENTS)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_BEFORE_PROPAGATE_ELEMENTS, new ElementQueryEvent([
                'query' => $event->query,
            ]));
        });

        Event::listen(function(BeforePropagateElement $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_BEFORE_PROPAGATE_ELEMENT)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_BEFORE_PROPAGATE_ELEMENT, new MultiElementActionEvent([
                'query' => $event->query,
                'element' => $event->element,
                'position' => $event->position,
            ]));
        });

        Event::listen(function(AfterPropagateElement $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_AFTER_PROPAGATE_ELEMENT)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_AFTER_PROPAGATE_ELEMENT, new MultiElementActionEvent([
                'query' => $event->query,
                'element' => $event->element,
                'position' => $event->position,
                'exception' => $event->exception,
            ]));
        });

        Event::listen(function(AfterPropagateElements $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_AFTER_PROPAGATE_ELEMENTS)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_AFTER_PROPAGATE_ELEMENTS, new ElementQueryEvent([
                'query' => $event->query,
            ]));
        });

        Event::listen(function(AfterMergeElements $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_AFTER_MERGE_ELEMENTS)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_AFTER_MERGE_ELEMENTS, new MergeElementsEvent([
                'mergedElementId' => $event->mergedElementId,
                'prevailingElementId' => $event->prevailingElementId,
            ]));
        });

        Event::listen(function(BeforeDeleteElement $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_BEFORE_DELETE_ELEMENT)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_BEFORE_DELETE_ELEMENT, $yiiEvent = new DeleteElementEvent([
                'element' => $event->element,
                'hardDelete' => $event->hardDelete,
            ]));

            $event->hardDelete = $yiiEvent->hardDelete;
        });

        Event::listen(function(RegisterElementTypes $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_REGISTER_ELEMENT_TYPES)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_REGISTER_ELEMENT_TYPES, $yiiEvent = new RegisterComponentTypesEvent([
                'types' => $event->types,
            ]));

            $event->types = $yiiEvent->types;
        });
    }
}
