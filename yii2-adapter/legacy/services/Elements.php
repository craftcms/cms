<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\errors\ElementNotFoundException;
use craft\events\AuthorizationCheckEvent;
use craft\events\BulkOpEvent;
use craft\events\DeleteElementEvent;
use craft\events\EagerLoadElementsEvent;
use craft\events\ElementActionEvent;
use craft\events\ElementEvent;
use craft\events\ElementQueryEvent;
use craft\events\InvalidateElementCachesEvent;
use craft\events\MergeElementsEvent;
use craft\events\MultiElementActionEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\Component as ComponentHelper;
use craft\models\ElementActivity;
use CraftCms\Cms\Element\BulkOp\Events\BulkOpCompleted;
use CraftCms\Cms\Element\BulkOp\Events\BulkOpStarting;
use CraftCms\Cms\Element\Contracts\ElementActionInterface;
use CraftCms\Cms\Element\Contracts\ElementExporterInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Data\EagerLoadPlan;
use CraftCms\Cms\Element\Data\ElementActivity as ElementActivityData;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementActions;
use CraftCms\Cms\Element\ElementActivity as ElementActivityService;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\ElementCaches as ElementCachesService;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Enums\ElementActivityType;
use CraftCms\Cms\Element\Events\CanonicalChangesMerged;
use CraftCms\Cms\Element\Events\CanonicalChangesMerging;
use CraftCms\Cms\Element\Events\ElementActionPerformed;
use CraftCms\Cms\Element\Events\ElementActionPerforming;
use CraftCms\Cms\Element\Events\ElementCachesInvalidated;
use CraftCms\Cms\Element\Events\ElementDeleted;
use CraftCms\Cms\Element\Events\ElementDeletedForSite;
use CraftCms\Cms\Element\Events\ElementDeleting;
use CraftCms\Cms\Element\Events\ElementDeletingForSite;
use CraftCms\Cms\Element\Events\ElementPropagated;
use CraftCms\Cms\Element\Events\ElementPropagating;
use CraftCms\Cms\Element\Events\ElementResaved;
use CraftCms\Cms\Element\Events\ElementResaving;
use CraftCms\Cms\Element\Events\ElementRestored;
use CraftCms\Cms\Element\Events\ElementRestoring;
use CraftCms\Cms\Element\Events\ElementSaved;
use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\Element\Events\ElementsEagerLoading;
use CraftCms\Cms\Element\Events\ElementSearchIndexUpdating;
use CraftCms\Cms\Element\Events\ElementSlugAndUriUpdated;
use CraftCms\Cms\Element\Events\ElementSlugAndUriUpdating;
use CraftCms\Cms\Element\Events\ElementsMerged;
use CraftCms\Cms\Element\Events\ElementsPropagated;
use CraftCms\Cms\Element\Events\ElementsPropagating;
use CraftCms\Cms\Element\Events\ElementsResaved;
use CraftCms\Cms\Element\Events\ElementsResaving;
use CraftCms\Cms\Element\Events\ElementTypesResolving;
use CraftCms\Cms\Element\Events\SetElementUri;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Exceptions\UnsupportedSiteException;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Shared\Exceptions\OperationAbortedException;
use CraftCms\Cms\Support\Facades\BulkOps;
use CraftCms\Cms\Support\Facades\ElementExporters;
use CraftCms\Cms\Support\Facades\Elements as ElementsFacade;
use CraftCms\Cms\User\Elements\User;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use DateTime;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
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
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
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
     *
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
     *
     * @since 3.1.0
     */
    public const EVENT_BEFORE_RESTORE_ELEMENT = 'beforeRestoreElement';

    /**
     * @event ElementEvent The event that is triggered after an element is restored.
     *
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
     * @see SetElementUri()
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
     *
     * @since 3.7.0
     */
    public const EVENT_BEFORE_MERGE_CANONICAL_CHANGES = 'beforeMergeCanonical';

    /**
     * @event ElementEvent The event that is triggered after canonical element changes are merged into a derivative.
     *
     * @since 3.7.0
     */
    public const EVENT_AFTER_MERGE_CANONICAL_CHANGES = 'afterMergeCanonical';

    /**
     * @event InvalidateElementCachesEvent The event that is triggered when element caches are invalidated.
     *
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
     *
     * @see deleteElementForSite()
     * @see deleteElementsForSite()
     * @since 4.4.0
     */
    public const EVENT_BEFORE_DELETE_FOR_SITE = 'beforeDeleteForSite';

    /**
     * @event ElementEvent The event that is triggered after deleting an element for a single site.
     *
     * @see deleteElementForSite()
     * @see deleteElementsForSite()
     * @since 4.4.0
     */
    public const EVENT_AFTER_DELETE_FOR_SITE = 'afterDeleteForSite';

    /**
     * Creates an element with a given config.
     *
     * @template T of ElementInterface
     *
     * @param  class-string<T>|array  $config  The element’s class name, or its config, with a `type` value
     *
     * @phpstan-param class-string<T>|array{type:class-string<T>} $config
     *
     * @return T The element
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::createElement()} instead.
     */
    public function createElement(mixed $config): ElementInterface
    {
        return ElementsFacade::createElement($config);
    }

    /**
     * Creates an element query for a given element type.
     *
     * @param  class-string<ElementInterface>  $elementType  The element class
     * @return ElementQueryInterface The element query
     *
     * @throws InvalidArgumentException if $elementType is not a valid element
     *
     * @since 3.5.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::createElementQuery()} instead.
     */
    public function createElementQuery(string $elementType): ElementQueryInterface|ElementQuery
    {
        return ElementsFacade::createElementQuery($elementType);
    }

    /**
     * @var string the DB connection name that should be used to store element bulk op records.
     *
     * @since 5.3.0
     */
    public string $bulkOpDb = 'db2';

    // Element caches
    // -------------------------------------------------------------------------

    /**
     * Returns whether we are currently collecting element cache invalidation info.
     *
     * @see startCollectingCacheInfo()
     * @see stopCollectingCacheInfo()
     * @since 4.3.0
     * @deprecated 6.0.0 use {@see ElementCaches::isCollectingCacheInfo()} instead.
     */
    public function getIsCollectingCacheInfo(): bool
    {
        return $this->elementCaches()->isCollectingCacheInfo();
    }

    /**
     * Returns whether we are currently collecting element cache invalidation tags.
     *
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
     * @deprecated 6.0.0 use {@see ElementCaches::startCollectingCacheInfo()} instead.
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
     * @param  string[]  $tags
     *
     * @since 3.5.0
     * @deprecated 6.0.0 use {@see ElementCaches::collectCacheTags()} instead.
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
     *
     * @since 4.3.0
     * @deprecated 6.0.0 use {@see ElementCaches::setCacheExpiryDate()} instead.
     */
    public function setCacheExpiryDate(DateTime $expiryDate): void
    {
        $this->elementCaches()->setCacheExpiryDate($expiryDate);
    }

    /**
     ** Stores cache invalidation info for a given element.
     *
     *
     * @since 4.5.0
     * @deprecated 6.0.0 use {@see ElementCaches::collectCacheInfoForElement()} instead.
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
     * @phpstan-return array{TagDependency|null,int|null}
     *
     * @since 4.3.0
     * @deprecated 6.0.0 use {@see ElementCaches::stopCollectingCacheInfo()} instead.
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
     * @deprecated 6.0.0 use {@see ElementCaches::invalidateAll()} instead.
     */
    public function invalidateAllCaches(): void
    {
        $this->elementCaches()->invalidateAll();
    }

    /**
     * Invalidates caches for the given element type.
     *
     * @param  class-string<ElementInterface>  $elementType
     *
     * @since 3.5.0
     * @deprecated 6.0.0 use {@see ElementCaches::invalidateForElementType()} instead.
     */
    public function invalidateCachesForElementType(string $elementType): void
    {
        $this->elementCaches()->invalidateForElementType($elementType);
    }

    /**
     * Invalidates caches for the given element.
     *
     *
     * @since 3.5.0
     * @deprecated 6.0.0 use {@see ElementCaches::invalidateForElement()} instead.
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
     *
     * @param  int  $elementId  The element’s ID.
     * @param  class-string<T>|null  $elementType  The element class.
     * @param  int|string|int[]|null  $siteId  The site(s) to fetch the element in.
     *                                         Defaults to the current site.
     * @return T|null The matching element, or `null`.
     *
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
     *
     * @param  string  $uid  The element’s UID.
     * @param  class-string<T>|null  $elementType  The element class.
     * @param  int|string|int[]|null  $siteId  The site(s) to fetch the element in.
     *                                         Defaults to the current site.
     * @return T|null The matching element, or `null`.
     *
     * @since 3.5.13
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::getElementByUid()} instead.
     */
    public function getElementByUid(
        string $uid,
        ?string $elementType = null,
        array|int|string|null $siteId = null,
        array $criteria = [],
    ): ?ElementInterface {
        return ElementsFacade::getElementByUId($uid, $elementType, $siteId, $criteria);
    }

    /**
     * Returns an element by its URI.
     *
     * @param  string  $uri  The element’s URI.
     * @param  int|null  $siteId  The site to look for the URI in, and to return the element in.
     *                            Defaults to the current site.
     * @param  bool  $enabledOnly  Whether to only look for an enabled element. Defaults to `false`.
     * @return ElementInterface|null The matching element, or `null`.
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::getElementByUri()} instead.
     */
    public function getElementByUri(string $uri, ?int $siteId = null, bool $enabledOnly = false): ?ElementInterface
    {
        return ElementsFacade::getElementByUri($uri, $siteId, $enabledOnly);
    }

    /**
     * Returns the class of an element with a given ID.
     *
     * @param  int  $elementId  The element’s ID
     * @return class-string<ElementInterface>|null The element’s class, or null if it could not be found
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::getElementTypeById()} instead.
     */
    public function getElementTypeById(int $elementId): ?string
    {
        return ElementsFacade::getElementTypeById($elementId);
    }

    /**
     * Returns the class of an element with a given UID.
     *
     * @param  string  $uid  The element’s UID
     * @return string|null The element’s class, or null if it could not be found
     *
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
     * @param  int[]  $elementIds  The elements’ IDs
     * @return string[]
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::getElementTypesByIds()} instead.
     */
    public function getElementTypesByIds(array $elementIds): array
    {
        return ElementsFacade::getElementTypesByIds($elementIds);
    }

    /**
     * Returns an element’s URI for a given site.
     *
     * @param  int  $elementId  The element’s ID.
     * @param  int  $siteId  The site to search for the element’s URI in.
     * @return string|null The element’s URI or `null` if the element doesn’t exist.
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::getElementUriForSite()} instead.
     */
    public function getElementUriForSite(int $elementId, int $siteId): ?string
    {
        return ElementsFacade::getElementUriForSite($elementId, $siteId);
    }

    /**
     * Returns the site IDs that a given element is enabled in.
     *
     * @param  int  $elementId  The element’s ID.
     * @return int[] The site IDs that the element is enabled in. If the element could not be found, an empty array
     *               will be returned.
     *
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
     *
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
     *
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
     * @param  string  $key  The bulk operation key returned by [[beginBulkOp()]].
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
     * @param  string  $key  The bulk operation key returned by [[beginBulkOp()]].
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
     *
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
     * @param  ElementInterface  $element  The element that is being saved
     * @param  bool  $runValidation  Whether the element should be validated
     * @param  bool  $propagate  Whether the element should be saved across all of its supported sites
     *                           (this can only be disabled when updating an existing element)
     * @param  bool|null  $updateSearchIndex  Whether to update the element search index for the element
     *                                        (this will happen via a background job if this is a web request)
     * @param  bool  $forceTouch  Whether to force the `dateUpdated` timestamp to be updated for the element,
     *                            regardless of whether it’s being resaved
     * @param  bool|null  $crossSiteValidate  Whether the element should be validated across all supported sites
     * @param  bool  $saveContent  Whether all the element’s content should be saved. When false (default) only dirty fields will be saved.
     *
     * @throws ElementNotFoundException if $element has an invalid $id
     * @throws Exception if the $element doesn’t have any supported sites
     * @throws Throwable if reasons
     *
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
     *
     * @throws OperationAbortedException if a unique URI could not be found
     *
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
     * @param  ElementInterface  $element  The derivative element
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
     *
     * @param  T  $element  The derivative element
     * @param  array  $newAttributes  Any attributes to apply to the canonical element
     * @return T The updated canonical element
     *
     * @throws InvalidArgumentException if the element is already a canonical element
     *
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
     * @param  ElementQueryInterface|ElementQuery  $query  The element query to fetch elements with
     * @param  bool  $continueOnError  Whether to continue going if an error occurs
     * @param  bool  $skipRevisions  Whether elements that are (or belong to) a revision should be skipped
     * @param  bool|null  $updateSearchIndex  Whether to update the element search index for the element
     *                                        (this will happen via a background job if this is a web request)
     * @param  bool  $touch  Whether to update the `dateUpdated` timestamps for the elements
     *
     * @throws Throwable if reasons
     *
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
     * @param  ElementQueryInterface  $query  The element query to fetch elements with
     * @param  int|int[]|null  $siteIds  The site ID(s) that the elements should be propagated to. If null, elements will be
     * @param  bool  $continueOnError  Whether to continue going if an error occurs
     *
     * @throws Throwable if reasons
     *                   propagated to all supported sites, except the one they were queried in.
     *
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
     *
     * @param  T  $element  the element to duplicate
     * @param  array  $newAttributes  any attributes to apply to the duplicate. This can contain a `siteAttributes` key,
     *                                set to an array of site-specific attribute array, indexed by site IDs.
     * @param  bool  $placeInStructure  whether to position the cloned element after the original one in its structure.
     *                                  (This will only happen if the duplicated element is canonical.)
     * @param  bool  $asUnpublishedDraft  whether the duplicate should be created as unpublished draft
     * @param  bool  $checkAuthorization  whether to ensure the current user is authorized to save the new element,
     *                                    once its new attributes have been applied to it
     * @param  bool  $copyModifiedFields  whether to copy modified attribute/field data over to the duplicated element
     * @return T the duplicated element
     *
     * @throws UnsupportedSiteException if the element is being duplicated into a site it doesn’t support
     * @throws InvalidElementException if saveElement() returns false for any of the sites
     * @throws ForbiddenHttpException if the user isn't authorized to save the duplicated element
     * @throws Throwable if reasons
     *
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
     * @param  ElementInterface  $element  The element to update.
     * @param  bool  $updateOtherSites  Whether the element’s other sites should also be updated.
     * @param  bool  $updateDescendants  Whether the element’s descendants should also be updated.
     * @param  bool  $queue  Whether the element’s slug and URI should be updated via a job in the queue.
     *
     * @throws OperationAbortedException if a unique URI can’t be generated based on the element’s URI format
     *
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
     * @param  ElementInterface  $element  The element to update.
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::updateElementSlugAndUriInOtherSites()} instead.
     */
    public function updateElementSlugAndUriInOtherSites(ElementInterface $element): void
    {
        ElementsFacade::updateElementSlugAndUriInOtherSites($element);
    }

    /**
     * Updates an element’s descendants’ slugs and URIs.
     *
     * @param  ElementInterface  $element  The element whose descendants should be updated.
     * @param  bool  $updateOtherSites  Whether the element’s other sites should also be updated.
     * @param  bool  $queue  Whether the descendants’ slugs and URIs should be updated via a job in the queue.
     *
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
     * @param  int  $mergedElementId  The ID of the element that is going away.
     * @param  int  $prevailingElementId  The ID of the element that is sticking around.
     * @return bool Whether the elements were merged successfully.
     *
     * @throws ElementNotFoundException if one of the element IDs don’t exist.
     * @throws Throwable if reasons
     *
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
     * @param  ElementInterface  $mergedElement  The element that is going away.
     * @param  ElementInterface  $prevailingElement  The element that is sticking around.
     * @return bool Whether the elements were merged successfully.
     *
     * @throws Throwable if reasons
     *
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
     * @param  int  $elementId  The element’s ID
     * @param  class-string<ElementInterface>|null  $elementType  The element class.
     * @param  int|null  $siteId  The site to fetch the element in.
     *                            Defaults to the current site.
     * @param  bool  $hardDelete  Whether the element should be hard-deleted immediately, instead of soft-deleted
     * @return bool Whether the element was deleted successfully
     *
     * @throws Throwable
     *
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
     * @param  ElementInterface  $element  The element to be deleted
     * @param  bool  $hardDelete  Whether the element should be hard-deleted immediately, instead of soft-deleted
     * @return bool Whether the element was deleted successfully
     *
     * @throws Throwable
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::deleteElement()} instead.
     */
    public function deleteElement(ElementInterface $element, bool $hardDelete = false): bool
    {
        return ElementsFacade::deleteElement($element, $hardDelete);
    }

    /**
     * Deletes an element in the site it’s loaded in.
     *
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
     * @param  ElementInterface[]  $elements
     *
     * @throws InvalidArgumentException if all elements don’t have the same type and site ID.
     *
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
     *
     * @return bool Whether the element was restored successfully
     *
     * @throws Exception if the $element doesn’t have any supported sites
     * @throws Throwable if reasons
     *
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
     * @param  ElementInterface[]  $elements
     * @return bool Whether at least one element was restored successfully
     *
     * @throws UnsupportedSiteException if an element is being restored for a site it doesn’t support
     * @throws Throwable if reasons
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::restoreElements()} instead.
     */
    public function restoreElements(array $elements): bool
    {
        return ElementsFacade::restoreElements($elements);
    }

    /**
     * Returns the recent activity for an element.
     *
     *
     * @return ElementActivity[]
     *
     * @since 4.5.0
     * @deprecated 6.0.0 use {@see ElementActivityService::getRecentActivity()} instead.
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
     * @param  'view'|'edit'|'save'  $type  $type
     *
     * @since 4.5.0
     * @deprecated 6.0.0 use {@see ElementActivityService::trackActivity()} instead.
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
     *
     * @phpstan-return class-string<ElementInterface>[]
     *
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
     *
     * @param  class-string<T>|array  $config  The element action’s class name, or its config, with a `type` value and optionally a `settings` value
     *
     * @phpstan-param class-string<T>|array{type:class-string<T>} $config
     *
     * @return T The element action
     *
     * @deprecated 6.0.0 use {@see ElementActions::createAction()} instead.
     */
    public function createAction(mixed $config): ElementActionInterface
    {
        return ComponentHelper::createComponent($config, ElementActionInterface::class);
    }

    /**
     * Creates an element exporter with a given config.
     *
     * @template T of ElementExporterInterface
     *
     * @param  class-string<T>|array  $config  The element exporter’s class name, or its config, with a `type` value and optionally a `settings` value
     *
     * @phpstan-param class-string<T>|array{type:class-string<T>} $config
     *
     * @return T The element exporter
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\ElementExporters::createExporter()} instead.
     */
    public function createExporter(mixed $config): ElementExporterInterface
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        /** @var T $exporter */
        $exporter = ElementExporters::createExporter($config, $config['elementType'] ?? Element::class);

        return $exporter;
    }

    // Misc
    // -------------------------------------------------------------------------

    /**
     * Returns an element class by its handle.
     *
     * @param  string  $refHandle  The element class handle
     * @return string|null The element class, or null if it could not be found
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::getElementTypeByRefHandle()} instead.
     */
    public function getElementTypeByRefHandle(string $refHandle): ?string
    {
        return ElementsFacade::getElementTypeByRefHandle($refHandle);
    }

    /**
     * Parses a string for element [reference tags](https://craftcms.com/docs/5.x/system/reference-tags.html).
     *
     * @param  string  $str  The string to parse
     * @param  int|null  $defaultSiteId  The default site ID to query the elements in
     * @return string The parsed string
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::parseRefs()} instead.
     */
    public function parseRefs(string $str, ?int $defaultSiteId = null): string
    {
        return ElementsFacade::parseRefs($str, $defaultSiteId);
    }

    /**
     * Stores a placeholder element that element queries should use instead of populating a new element with a
     * matching ID and site ID.
     *
     * This is used by Live Preview and Sharing features.
     *
     * @param  ElementInterface  $element  The element currently being edited by Live Preview.
     *
     * @throws InvalidArgumentException if the element is missing an ID
     *
     * @see getPlaceholderElement()
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::setPlaceholderElement()} instead.
     */
    public function setPlaceholderElement(ElementInterface $element): void
    {
        ElementsFacade::setPlaceholderElement($element);
    }

    /**
     * Returns all placeholder elements.
     *
     * @return ElementInterface[]
     *
     * @since 3.2.5
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::getPlaceholderElements()} instead.
     */
    public function getPlaceholderElements(): array
    {
        return ElementsFacade::getPlaceholderElements();
    }

    /**
     * Returns a placeholder element by its ID and site ID.
     *
     * @param  int  $sourceId  The element’s ID
     * @param  int  $siteId  The element’s site ID
     * @return ElementInterface|null The placeholder element if one exists, or null.
     *
     * @see setPlaceholderElement()
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::getPlaceholderElement()} instead.
     */
    public function getPlaceholderElement(int $sourceId, int $siteId): ?ElementInterface
    {
        return ElementsFacade::getPlaceholderElement($sourceId, $siteId);
    }

    /**
     * Normalizes a `with` element query param into an array of eager-loading plans.
     *
     *
     * @phpstan-param string|array<EagerLoadPlan|array|string> $with
     *
     * @return EagerLoadPlan[]
     *
     * @since 3.5.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::createEagerLoadingPlans()} instead.
     */
    public function createEagerLoadingPlans(string|array $with): array
    {
        return ElementsFacade::createEagerLoadingPlans($with);
    }

    /**
     * Eager-loads additional elements onto a given set of elements.
     *
     * @param  class-string<ElementInterface>  $elementType  The root element type class
     * @param  ElementInterface[]  $elements  The root element models that should be updated with the eager-loaded elements
     * @param  array<string|array>|string|EagerLoadPlan[]  $with  Dot-delimited paths of the elements that should be eager-loaded into the root elements
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::eagerLoadElements()} instead.
     */
    public function eagerLoadElements(string $elementType, array|Collection $elements, array|string $with): void
    {
        ElementsFacade::eagerLoadElements($elementType, $elements, $with);
    }

    /**
     * Propagates an element to a different site.
     *
     * @param  ElementInterface  $element  The element to propagate
     * @param  int  $siteId  The site ID that the element should be propagated to
     * @param  ElementInterface|false|null  $siteElement  The element loaded for the propagated site (only pass this if you
     *                                                    already had a reason to load it). Set to `false` if it is known to not exist yet.
     * @return ElementInterface The element in the target site
     *
     * @throws Exception if the element couldn't be propagated
     * @throws UnsupportedSiteException if the element doesn’t support `$siteId`
     *
     * @since 3.0.13
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Elements::propagateElement()} instead.
     */
    public function propagateElement(
        ElementInterface $element,
        int $siteId,
        ElementInterface|false|null $siteElement = null,
    ): ElementInterface {
        return ElementsFacade::propagateElement($element, $siteId, $siteElement);
    }

    /**
     * Returns whether a user is authorized to view the given element’s edit page.
     *
     *
     * @since 4.3.0
     * @deprecated 6.0.0 use `Gate::forUser($user)->check('view', $element)` instead.
     */
    public function canView(ElementInterface $element, ?User $user = null): bool
    {
        return $this->_checkAuthorization($element, 'view', $user);
    }

    /**
     * Returns whether a user is authorized to save the given element in its current form.
     *
     *
     * @since 4.3.0
     * @deprecated 6.0.0 use `Gate::forUser($user)->check('save', $element)` instead.
     */
    public function canSave(ElementInterface $element, ?User $user = null): bool
    {
        return $this->_checkAuthorization($element, 'save', $user);
    }

    /**
     * Returns whether a user is authorized to save the canonical version of the given element.
     *
     *
     * @since 5.6.0
     * @deprecated 6.0.0 use `Gate::forUser($user)->check('saveCanonical', $element)` instead.
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
     *
     * @since 4.3.0
     * @deprecated 6.0.0 use `Gate::forUser($user)->check('duplicate', $element)` instead.
     */
    public function canDuplicate(ElementInterface $element, ?User $user = null): bool
    {
        return $this->_checkAuthorization($element, 'duplicate', $user);
    }

    /**
     * Returns whether a user is authorized to duplicate the given element as an unpublished draft.
     *
     *
     * @since 5.0.0
     * @deprecated 6.0.0 use `Gate::forUser($user)->check('duplicateAsDraft', $element)` instead.
     */
    public function canDuplicateAsDraft(ElementInterface $element, ?User $user = null): bool
    {
        return $this->_checkAuthorization($element, 'duplicateAsDraft', $user);
    }

    /**
     * Returns whether a user is authorized to copy the given element, to be duplicated elsewhere.
     *
     *  This should always be called in conjunction with [[canView()]].
     *
     *
     * @since 5.7.0
     * @deprecated 6.0.0 use `Gate::forUser($user)->check('copy', $element)` instead.
     */
    public function canCopy(ElementInterface $element, ?User $user = null): bool
    {
        return $this->_checkAuthorization($element, 'copy', $user);
    }

    /**
     * Returns whether a user is authorized to delete the given element.
     *
     * This should always be called in conjunction with [[canView()]] or [[canSave()]].
     *
     *
     * @since 4.3.0
     * @deprecated 6.0.0 use `Gate::forUser($user)->check('delete', $element)` instead.
     */
    public function canDelete(ElementInterface $element, ?User $user = null): bool
    {
        return $this->_checkAuthorization($element, 'delete', $user);
    }

    /**
     * Returns whether a user is authorized to delete the given element for its current site.
     *
     * This should always be called in conjunction with [[canView()]] or [[canSave()]].
     *
     *
     * @since 4.3.0
     * @deprecated 6.0.0 use `Gate::forUser($user)->check('deleteForSite', $element)` instead.
     */
    public function canDeleteForSite(ElementInterface $element, ?User $user = null): bool
    {
        return $this->_checkAuthorization($element, 'deleteForSite', $user);
    }

    /**
     * Returns whether a user is authorized to create drafts for the given element.
     *
     * This should always be called in conjunction with [[canView()]] or [[canSave()]].
     *
     *
     * @since 4.3.0
     * @deprecated 6.0.0 use `Gate::forUser($user)->check('createDrafts', $element)` instead.
     */
    public function canCreateDrafts(ElementInterface $element, ?User $user = null): bool
    {
        return $this->_checkAuthorization($element, 'createDrafts', $user);
    }

    private function _checkAuthorization(ElementInterface $element, string $ability, ?User $user = null): bool
    {
        $user ??= Auth::user();

        if (!$user) {
            return false;
        }

        return Gate::forUser($user)->check($ability, $element);
    }

    private static function _authCheck(ElementInterface $element, User $user, string $eventName): ?bool
    {
        if (!Craft::$app->getElements()->hasEventHandlers($eventName)) {
            return null;
        }

        $event = new AuthorizationCheckEvent($user, [
            'element' => $element,
            'authorized' => null,
        ]);

        Craft::$app->getElements()->trigger($eventName, $event);

        return $event->authorized;
    }

    public static function registerEvents(): void
    {
        Event::listen(function(BulkOpStarting $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_BEFORE_BULK_OP)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_BEFORE_BULK_OP, new BulkOpEvent([
                'key' => $event->key,
            ]));
        });

        Event::listen(function(BulkOpCompleted $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_AFTER_BULK_OP)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_AFTER_BULK_OP, new BulkOpEvent([
                'key' => $event->key,
            ]));
        });

        Event::listen(function(ElementCachesInvalidated $event) {
            // Fire a 'invalidateCaches' event
            if (Craft::$app->getElements()->hasEventHandlers(self::EVENT_INVALIDATE_CACHES)) {
                Craft::$app->getElements()->trigger(self::EVENT_INVALIDATE_CACHES, new InvalidateElementCachesEvent([
                    'tags' => $event->tags,
                    'element' => $event->element,
                ]));
            }
        });

        $elementEvents = [
            ElementSaving::class => self::EVENT_BEFORE_SAVE_ELEMENT,
            ElementSaved::class => self::EVENT_AFTER_SAVE_ELEMENT,
            ElementSearchIndexUpdating::class => self::EVENT_BEFORE_UPDATE_SEARCH_INDEX,
            SetElementUri::class => self::EVENT_SET_ELEMENT_URI,
            CanonicalChangesMerging::class => self::EVENT_BEFORE_MERGE_CANONICAL_CHANGES,
            CanonicalChangesMerged::class => self::EVENT_AFTER_MERGE_CANONICAL_CHANGES,
            ElementSlugAndUriUpdating::class => self::EVENT_BEFORE_UPDATE_SLUG_AND_URI,
            ElementSlugAndUriUpdated::class => self::EVENT_AFTER_UPDATE_SLUG_AND_URI,
            ElementDeleted::class => self::EVENT_AFTER_DELETE_ELEMENT,
            ElementDeletingForSite::class => self::EVENT_BEFORE_DELETE_FOR_SITE,
            ElementDeletedForSite::class => self::EVENT_AFTER_DELETE_FOR_SITE,
            ElementRestoring::class => self::EVENT_BEFORE_RESTORE_ELEMENT,
            ElementRestored::class => self::EVENT_AFTER_RESTORE_ELEMENT,
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

        Event::listen(function(ElementsResaving $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_BEFORE_RESAVE_ELEMENTS)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_BEFORE_RESAVE_ELEMENTS, new ElementQueryEvent([
                'query' => $event->query,
            ]));
        });

        Event::listen(function(ElementResaving $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_BEFORE_RESAVE_ELEMENT)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_BEFORE_RESAVE_ELEMENT, new MultiElementActionEvent([
                'query' => $event->query,
                'element' => $event->element,
                'position' => $event->position,
            ]));
        });

        Event::listen(function(ElementResaved $event) {
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

        Event::listen(function(ElementsResaved $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_AFTER_RESAVE_ELEMENTS)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_AFTER_RESAVE_ELEMENTS, new ElementQueryEvent([
                'query' => $event->query,
            ]));
        });

        Event::listen(function(ElementsPropagating $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_BEFORE_PROPAGATE_ELEMENTS)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_BEFORE_PROPAGATE_ELEMENTS, new ElementQueryEvent([
                'query' => $event->query,
            ]));
        });

        Event::listen(function(ElementPropagating $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_BEFORE_PROPAGATE_ELEMENT)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_BEFORE_PROPAGATE_ELEMENT, new MultiElementActionEvent([
                'query' => $event->query,
                'element' => $event->element,
                'position' => $event->position,
            ]));
        });

        Event::listen(function(ElementPropagated $event) {
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

        Event::listen(function(ElementsPropagated $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_AFTER_PROPAGATE_ELEMENTS)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_AFTER_PROPAGATE_ELEMENTS, new ElementQueryEvent([
                'query' => $event->query,
            ]));
        });

        Event::listen(function(ElementsMerged $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_AFTER_MERGE_ELEMENTS)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_AFTER_MERGE_ELEMENTS, new MergeElementsEvent([
                'mergedElementId' => $event->mergedElementId,
                'prevailingElementId' => $event->prevailingElementId,
            ]));
        });

        Event::listen(function(ElementDeleting $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_BEFORE_DELETE_ELEMENT)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_BEFORE_DELETE_ELEMENT, $yiiEvent = new DeleteElementEvent([
                'element' => $event->element,
                'hardDelete' => $event->hardDelete,
            ]));

            $event->hardDelete = $yiiEvent->hardDelete;
        });

        Event::listen(function(ElementActionPerforming $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_BEFORE_PERFORM_ACTION)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_BEFORE_PERFORM_ACTION, $yiiEvent = new ElementActionEvent([
                'action' => $event->action,
                'criteria' => $event->query,
                'message' => $event->message,
            ]));

            $event->isValid = $yiiEvent->isValid;
            $event->message = $yiiEvent->message;
        });

        Event::listen(function(ElementActionPerformed $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_AFTER_PERFORM_ACTION)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_AFTER_PERFORM_ACTION, new ElementActionEvent([
                'action' => $event->action,
                'criteria' => $event->query,
                'message' => $event->message,
            ]));
        });

        Event::listen(function(ElementTypesResolving $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_REGISTER_ELEMENT_TYPES)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_REGISTER_ELEMENT_TYPES, $yiiEvent = new RegisterComponentTypesEvent([
                'types' => $event->types,
            ]));

            $event->types = $yiiEvent->types;
        });

        Event::listen(function(ElementsEagerLoading $event) {
            if (!Craft::$app->getElements()->hasEventHandlers(self::EVENT_BEFORE_EAGER_LOAD_ELEMENTS)) {
                return;
            }

            Craft::$app->getElements()->trigger(self::EVENT_BEFORE_EAGER_LOAD_ELEMENTS, $yiiEvent = new EagerLoadElementsEvent([
                'elementType' => $event->elementType,
                'elements' => $event->elements,
                'with' => $event->with,
            ]));

            $event->with = $yiiEvent->with;
        });

        // Fire deprecated Yii auth events for plugin compatibility
        Gate::before(function(User $user, string $ability, mixed $arguments) {
            $element = $arguments[0] ?? null;

            if (!$element instanceof ElementInterface) {
                return null;
            }

            $event = [
                'view' => self::EVENT_AUTHORIZE_VIEW,
                'save' => self::EVENT_AUTHORIZE_SAVE,
                'createDrafts' => self::EVENT_AUTHORIZE_CREATE_DRAFTS,
                'duplicate' => self::EVENT_AUTHORIZE_DUPLICATE,
                'duplicateAsDraft' => self::EVENT_AUTHORIZE_DUPLICATE_AS_DRAFT,
                'copy' => self::EVENT_AUTHORIZE_COPY,
                'delete' => self::EVENT_AUTHORIZE_DELETE,
                'deleteForSite' => self::EVENT_AUTHORIZE_DELETE_FOR_SITE,
            ][$ability] ?? null;

            if (!$event) {
                return null;
            }

            return self::_authCheck($element, $user, $event);
        });
    }
}
