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
use craft\base\NestedElementInterface;
use craft\behaviors\CustomFieldBehavior;
use craft\controllers\AppController;
use craft\db\QueryAbortedException;
use craft\elements\db\EagerLoadInfo;
use craft\elements\db\EagerLoadPlan;
use craft\elements\db\ElementQuery;
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
use craft\helpers\DateTimeHelper;
use craft\helpers\Db as DbHelper;
use craft\helpers\ElementHelper;
use craft\helpers\Queue;
use craft\models\ElementActivity;
use craft\queue\jobs\UpdateElementSlugsAndUris;
use craft\validators\SlugValidator;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Events\AfterPropagate;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Exceptions\UnsupportedSiteException;
use CraftCms\Cms\Element\Models\Element as ElementModel;
use CraftCms\Cms\Element\Models\ElementSiteSettings;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Exceptions\FieldNotFoundException;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Search\Jobs\FindAndReplace;
use CraftCms\Cms\Shared\Exceptions\OperationAbortedException;
use CraftCms\Cms\Site\Exceptions\SiteNotFoundException;
use CraftCms\Cms\Structure\Enums\Mode;
use CraftCms\Cms\Structure\Models\StructureElement as StructureElementModel;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Search;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Structures;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Support\URL;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Validation\Rules\HandleRule;
use CraftCms\Cms\View\CacheCollectors\DependencyCollector;
use CraftCms\Cms\View\Data\TemplateCacheContext;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use DateTime;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use Tpetry\QueryExpressions\Function\String\Lower;
use Tpetry\QueryExpressions\Language\Alias;
use UnitEnum;
use yii\base\Behavior;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\web\ForbiddenHttpException;
use function CraftCms\Cms\normalizeValue;
use function CraftCms\Cms\renderObjectTemplate;
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
     * If you want to ignore events for drafts or revisions, call [[\craft\helpers\ElementHelper::isDraftOrRevision()]]
     * from your event handler:
     *
     * ```php
     * use craft\events\ElementEvent;
     * use craft\helpers\ElementHelper;
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
     * If you want to ignore events for drafts or revisions, call [[\craft\helpers\ElementHelper::isDraftOrRevision()]]
     * from your event handler:
     *
     * ```php
     * use craft\events\ElementEvent;
     * use craft\helpers\ElementHelper;
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
     * @see getElementByUri()
     */
    private array $_placeholderUris;

    /**
     * @var string[]
     */
    private array $_elementTypesByRefHandle = [];

    /**
     * @var bool|null Whether we should be updating search indexes for elements if not told explicitly.
     * @since 3.1.2
     */
    private ?bool $_updateSearchIndex = null;

    /**
     * Creates an element with a given config.
     *
     * @template T of ElementInterface
     * @param class-string<T>|array $config The element’s class name, or its config, with a `type` value
     *
     * @phpstan-param class-string<T>|array{type:class-string<T>} $config
     * @return T The element
     */
    public function createElement(mixed $config): ElementInterface
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        return ComponentHelper::createComponent($config, ElementInterface::class);
    }

    /**
     * Creates an element query for a given element type.
     *
     * @param class-string<ElementInterface> $elementType The element class
     *
     * @return ElementQueryInterface The element query
     * @throws InvalidArgumentException if $elementType is not a valid element
     * @since 3.5.0
     */
    public function createElementQuery(string $elementType): ElementQueryInterface|\CraftCms\Cms\Element\Queries\ElementQuery
    {
        if (!is_subclass_of($elementType, ElementInterface::class)) {
            throw new InvalidArgumentException("$elementType is not a valid element.");
        }

        return $elementType::find();
    }

    /**
     * @var string the DB connection name that should be used to store element bulk op records.
     * @since 5.3.0
     */
    public string $bulkOpDb = 'db2';

    // Element caches
    // -------------------------------------------------------------------------

    public function getBulkOpConnection(): ConnectionInterface
    {
        return DB::connection($this->bulkOpDb);
    }

    /**
     * Returns whether we are currently collecting element cache invalidation info.
     *
     * @return bool
     * @see startCollectingCacheInfo()
     * @see stopCollectingCacheInfo()
     * @since 4.3.0
     */
    public function getIsCollectingCacheInfo(): bool
    {
        return $this->cacheInfoCollector()->isCollecting();
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
     */
    public function startCollectingCacheInfo(): void
    {
        $this->cacheInfoCollector()->begin(new TemplateCacheContext(
            cacheKey: '',
            global: false,
            resources: false,
        ));
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
     */
    public function collectCacheTags(array $tags): void
    {
        $this->cacheInfoCollector()->collectTags($tags);
    }

    /**
     * Sets a possible cache expiration date that [[stopCollectingCacheInfo()]] should return.
     *
     * The value will only be used if it is less than the currently stored expiration date.
     *
     * @param DateTime $expiryDate
     *
     * @since 4.3.0
     */
    public function setCacheExpiryDate(DateTime $expiryDate): void
    {
        $this->cacheInfoCollector()->setExpiryDate($expiryDate);
    }

    /**
     ** Stores cache invalidation info for a given element.
     *
     * @param ElementInterface $element
     *
     * @since 4.5.0
     */
    public function collectCacheInfoForElement(ElementInterface $element): void
    {
        $this->cacheInfoCollector()->collectElement($element);
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
     */
    public function stopCollectingCacheInfo(): array
    {
        try {
            return $this->cacheInfoCollector()->stop();
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

    private function cacheInfoCollector(): DependencyCollector
    {
        return app(DependencyCollector::class);
    }

    /**
     * Invalidates all element caches.
     *
     * @since 3.5.0
     */
    public function invalidateAllCaches(): void
    {
        $tags = ['element'];
        TagDependency::invalidate($tags);

        // Fire a 'invalidateCaches' event
        if ($this->hasEventHandlers(self::EVENT_INVALIDATE_CACHES)) {
            $this->trigger(self::EVENT_INVALIDATE_CACHES, new InvalidateElementCachesEvent([
                'tags' => $tags,
            ]));
        }
    }

    /**
     * Invalidates caches for the given element type.
     *
     * @param class-string<ElementInterface> $elementType
     *
     * @since 3.5.0
     */
    public function invalidateCachesForElementType(string $elementType): void
    {
        $tags = ["element::$elementType"];
        TagDependency::invalidate($tags);

        // Fire a 'invalidateCaches' event
        if ($this->hasEventHandlers(self::EVENT_INVALIDATE_CACHES)) {
            $this->trigger(self::EVENT_INVALIDATE_CACHES, new InvalidateElementCachesEvent([
                'tags' => $tags,
            ]));
        }
    }

    /**
     * Invalidates caches for the given element.
     *
     * @param ElementInterface $element
     *
     * @since 3.5.0
     */
    public function invalidateCachesForElement(ElementInterface $element): void
    {
        $tags = [
            sprintf('element::%s::*', $element::class),
            sprintf('element::%s', $element->id),
        ];

        $rootElement = $element;

        if ($element instanceof NestedElementInterface) {
            try {
                $owner = $element->getOwner();
            } catch (InvalidConfigException) {
                $owner = null;
            }

            if ($owner) {
                $tags[] = sprintf('element::%s', $owner->id);

                try {
                    $rootElement = ElementHelper::rootElement($owner);
                } catch (Throwable) {
                    $rootElement = $owner;
                }
            }
        }

        if ($rootElement->getIsDraft()) {
            $tags[] = sprintf('element::%s::drafts', $element::class);
        } elseif ($rootElement->getIsRevision()) {
            $tags[] = sprintf('element::%s::revisions', $element::class);
        } else {
            foreach ($element->getCacheTags() as $tag) {
                // tags can be provided fully-formed, or relative to the element type
                if (!str_starts_with($tag, 'element::')) {
                    $tag = sprintf('element::%s::%s', $element::class, $tag);
                }
                $tags[] = $tag;
            }
        }

        TagDependency::invalidate($tags);

        // Fire a 'invalidateCaches' event
        if ($this->hasEventHandlers(self::EVENT_INVALIDATE_CACHES)) {
            $this->trigger(self::EVENT_INVALIDATE_CACHES, new InvalidateElementCachesEvent([
                'tags' => $tags,
                'element' => $element,
            ]));
        }
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
     */
    public function getElementById(
        int $elementId,
        ?string $elementType = null,
        array|int|string|null $siteId = null,
        array $criteria = [],
    ): ?ElementInterface {
        return $this->_elementById('id', $elementId, $elementType, $siteId, $criteria);
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
     */
    public function getElementByUid(
        string $uid,
        ?string $elementType = null,
        array|int|string $siteId = null,
        array $criteria = [],
    ): ?ElementInterface {
        return $this->_elementById('uid', $uid, $elementType, $siteId, $criteria);
    }

    /**
     * Returns an element by its ID or UID.
     *
     * @template T of ElementInterface
     * @param string $property Either `id` or `uid`
     * @param int|string $elementId The element’s ID/UID
     * @param class-string<T>|null $elementType The element class.
     * @param int|string|int[]|null $siteId The site(s) to fetch the element in.
     * Defaults to the current site.
     * @param array $criteria
     *
     * @return T|null The matching element, or `null`.
     */
    private function _elementById(
        string $property,
        int|string $elementId,
        ?string $elementType = null,
        array|int|string $siteId = null,
        array $criteria = [],
    ): ?ElementInterface {
        if (!$elementId) {
            return null;
        }

        if ($elementType === null) {
            $elementType = $this->_elementTypeById($property, $elementId);
        }

        if ($elementType === null || !class_exists($elementType)) {
            return null;
        }

        $query = $this->createElementQuery($elementType)
            ->siteId($siteId)
            ->status(null)
            ->drafts(null)
            ->provisionalDrafts(null)
            ->revisions(null);

        $query->$property = $elementId;
        Typecast::configure($query, $criteria);

        return $query->one();
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
     */
    public function getElementByUri(string $uri, ?int $siteId = null, bool $enabledOnly = false): ?ElementInterface
    {
        if ($uri === '') {
            $uri = Element::HOMEPAGE_URI;
        }

        if ($siteId === null) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $siteId = Sites::getCurrentSite()->id;
        }

        // See if we already have a placeholder for this element URI
        if (isset($this->_placeholderUris[$uri][$siteId])) {
            return $this->_placeholderUris[$uri][$siteId];
        }

        // First get the element ID and type
        $result = DB::table(new Alias(Table::ELEMENTS, 'elements'))
            ->select(['elements.id', 'elements.type'])
            ->join(new Alias(Table::ELEMENTS_SITES, 'elements_sites'), 'elements_sites.elementId', 'elements.id')
            ->where('elements_sites.siteId', $siteId)
            ->whereNull(['elements.draftId', 'elements.revisionId', 'elements.dateDeleted'])
            ->where(new Lower('elements_sites.uri'), mb_strtolower($uri))
            ->when(
                $enabledOnly,
                fn(Builder $query) => $query->where([
                    'elements_sites.enabled' => true,
                    'elements.enabled' => true,
                    'elements.archived' => false,
                ]),
            )
            ->first();

        return $result ? $this->getElementById($result->id, $result->type, $siteId) : null;
    }

    /**
     * Returns the class of an element with a given ID.
     *
     * @param int $elementId The element’s ID
     *
     * @return class-string<ElementInterface>|null The element’s class, or null if it could not be found
     */
    public function getElementTypeById(int $elementId): ?string
    {
        return $this->_elementTypeById('id', $elementId);
    }

    /**
     * Returns the class of an element with a given UID.
     *
     * @param string $uid The element’s UID
     *
     * @return string|null The element’s class, or null if it could not be found
     * @since 3.5.13
     */
    public function getElementTypeByUid(string $uid): ?string
    {
        return $this->_elementTypeById('uid', $uid);
    }

    /**
     * Returns the class of an element with a given ID/UID.
     *
     * @param string $property Either `id` or `uid`
     * @param int|string $elementId The element’s ID/UID
     *
     * @return string|null The element’s class, or null if it could not be found
     */
    private function _elementTypeById(string $property, int|string $elementId): ?string
    {
        return DB::table(Table::ELEMENTS)
            ->where($property, $elementId)
            ->value('type');
    }

    /**
     * Returns the classes of elements with the given IDs.
     *
     * @param int[] $elementIds The elements’ IDs
     *
     * @return string[]
     */
    public function getElementTypesByIds(array $elementIds): array
    {
        return DB::table(Table::ELEMENTS)
            ->whereIn('id', $elementIds)
            ->distinct()
            ->pluck('type')
            ->all();
    }

    /**
     * Returns an element’s URI for a given site.
     *
     * @param int $elementId The element’s ID.
     * @param int $siteId The site to search for the element’s URI in.
     *
     * @return string|null The element’s URI or `null`, or `false` if the element doesn’t exist.
     */
    public function getElementUriForSite(int $elementId, int $siteId): string|null
    {
        return DB::table(Table::ELEMENTS_SITES)
            ->where('elementId', $elementId)
            ->where('siteId', $siteId)
            ->value('uri');
    }

    /**
     * Returns the site IDs that a given element is enabled in.
     *
     * @param int $elementId The element’s ID.
     *
     * @return int[] The site IDs that the element is enabled in. If the element could not be found, an empty array
     * will be returned.
     */
    public function getEnabledSiteIdsForElement(int $elementId): array
    {
        return DB::table(Table::ELEMENTS_SITES)
            ->where('elementId', $elementId)
            ->where('enabled', true)
            ->pluck('siteId')
            ->all();
    }

    // Bulk ops
    // -------------------------------------------------------------------------

    private array $bulkKeys = [];

    /**
     * Returns the active bulk op keys.
     *
     * @return string[]
     * @since 5.7.0
     */
    public function getBulkOpKeys(): array
    {
        return array_keys($this->bulkKeys);
    }

    /**
     * Begins tracking element saves and deletes as part of a bulk operation, identified by a unique key.
     *
     * @return string The bulk operation key
     * @since 5.0.0
     */
    public function beginBulkOp(): string
    {
        $key = Str::random(10);

        if ($this->hasEventHandlers(self::EVENT_BEFORE_BULK_OP)) {
            $this->trigger(self::EVENT_BEFORE_BULK_OP, new BulkOpEvent([
                'key' => $key,
            ]));
        }

        $this->resumeBulkOp($key);
        return $key;
    }

    /**
     * Resumes tracking element saves and deletes as part of a bulk operation.
     *
     * @param string $key The bulk operation key returned by [[beginBulkOp()]].
     *
     * @since 5.0.0
     */
    public function resumeBulkOp(string $key): void
    {
        $this->bulkKeys[$key] = true;
    }

    /**
     * Finishes tracking element saves and deletes as part of a bulk operation.
     *
     * @param string $key The bulk operation key returned by [[beginBulkOp()]].
     *
     * @since 5.0.0
     */
    public function endBulkOp(string $key): void
    {
        unset($this->bulkKeys[$key]);

        if ($this->hasEventHandlers(self::EVENT_AFTER_BULK_OP)) {
            $this->trigger(self::EVENT_AFTER_BULK_OP, new BulkOpEvent([
                'key' => $key,
            ]));
        }

        if (!$this->isMigrationRequest()) {
            $this->getBulkOpConnection()->table(Table::ELEMENTS_BULKOPS)
                ->where('key', $key)
                ->delete();
        }
    }

    /**
     * Tracks an element as being affected by any active bulk operations.
     *
     * @param ElementInterface $element
     *
     * @since 5.0.0
     */
    public function trackElementInBulkOps(ElementInterface $element): void
    {
        if (empty($this->bulkKeys) || $this->isMigrationRequest()) {
            return;
        }

        $timestamp = now();

        $connection = $this->getBulkOpConnection();
        foreach (array_keys($this->bulkKeys) as $key) {
            $connection->table(Table::ELEMENTS_BULKOPS)
                ->upsert([
                    'elementId' => $element->id,
                    'key' => $key,
                    'timestamp' => $timestamp,
                ], ['elementId', 'key']);
        }
    }

    private function isMigrationRequest(): bool
    {
        return (
            // TODO: Still necessary?
            //Craft::$app->controller instanceof MigrateController ||
            //Craft::$app->controller instanceof UpController ||
            (
                Craft::$app->controller instanceof AppController &&
                Craft::$app->controller->action?->id === 'update'
            )
        );
    }

    /**
     * Ensures that we’re tracking element saves and deletes as part of a bulk operation, then executes the given
     * callback function.
     *
     * @param callable $callback
     * @return mixed
     * @since 5.3.0
     */
    public function ensureBulkOp(callable $callback): mixed
    {
        if (empty($this->bulkKeys)) {
            $bulkKey = $this->beginBulkOp();
        }

        try {
            return $callback();
        } finally {
            if (isset($bulkKey)) {
                $this->endBulkOp($bulkKey);
            }
        }
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
        // Force propagation for new elements
        $propagate = !$element->id || $propagate;

        // Not currently being duplicated
        $duplicateOf = $element->duplicateOf;
        $element->duplicateOf = null;

        // Force isNewForSite = false here, in case the element is getting saved recursively
        // (see https://github.com/craftcms/cms/issues/15517)
        $isNewForSite = $element->isNewForSite;
        $element->isNewForSite = false;

        $success = $this->_saveElementInternal(
            $element,
            $runValidation,
            $propagate,
            $updateSearchIndex,
            forceTouch: $forceTouch,
            crossSiteValidate: $crossSiteValidate,
            saveContent: $saveContent,
        );

        $element->duplicateOf = $duplicateOf;
        $element->isNewForSite = $isNewForSite;

        return $success;
    }

    /**
     * Sets the URI on an element.
     *
     * @param ElementInterface $element
     *
     * @throws OperationAbortedException if a unique URI could not be found
     * @since 4.6.0
     */
    public function setElementUri(ElementInterface $element): void
    {
        // Fire a 'setElementUri' event
        if ($this->hasEventHandlers(self::EVENT_SET_ELEMENT_URI)) {
            $event = new ElementEvent(['element' => $element]);
            $this->trigger(self::EVENT_SET_ELEMENT_URI, $event);
            if ($event->handled) {
                return;
            }
        }

        ElementHelper::setUniqueUri($element);
    }

    /**
     * Merges recent canonical element changes into a given derivative, such as a draft.
     *
     * @param ElementInterface $element The derivative element
     *
     * @since 3.7.0
     */
    public function mergeCanonicalChanges(ElementInterface $element): void
    {
        if ($element->getIsCanonical()) {
            throw new InvalidArgumentException('Only a derivative element can be passed to ' . __METHOD__);
        }

        if (!$element::trackChanges()) {
            throw new InvalidArgumentException(get_class($element) . ' elements don’t track their changes');
        }

        // Make sure the derivative element actually supports its own site ID
        $supportedSites = Arr::keyBy(ElementHelper::supportedSitesForElement($element), 'siteId');
        if (!isset($supportedSites[$element->siteId])) {
            throw new Exception('Attempting to merge source changes for a draft in an unsupported site.');
        }

        // Fire a 'beforeMergeCanonical' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_MERGE_CANONICAL_CHANGES)) {
            $this->trigger(self::EVENT_BEFORE_MERGE_CANONICAL_CHANGES, new ElementEvent([
                'element' => $element,
            ]));
        }

        $this->ensureBulkOp(function() use ($element, $supportedSites) {
            DB::transaction(function() use ($element, $supportedSites) {
                // Start with the other sites (if any), so we don't update dateLastMerged until the end
                $otherSiteIds = array_keys(Arr::except($supportedSites, $element->siteId));
                if (!empty($otherSiteIds)) {
                    $siteElements = $this->_localizedElementQuery($element)
                        ->siteId($otherSiteIds)
                        ->status(null)
                        ->all();
                } else {
                    $siteElements = [];
                }

                foreach ($siteElements as $siteElement) {
                    $siteElement->mergeCanonicalChanges();
                    $siteElement->mergingCanonicalChanges = true;
                    $this->_saveElementInternal($siteElement, false, false, null, $supportedSites);
                }

                // Now the $element’s site
                $element->mergeCanonicalChanges();
                $duplicateOf = $element->duplicateOf;
                $element->duplicateOf = null;
                $element->dateLastMerged = DateTimeHelper::now();
                $element->mergingCanonicalChanges = true;
                $this->_saveElementInternal($element, false, false, null, $supportedSites);
                $element->duplicateOf = $duplicateOf;

                // It's now fully merged and propagated
                $element->afterPropagate(false);
            });

            $element->mergingCanonicalChanges = false;
        });

        // Fire an 'afterMergeCanonical' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_MERGE_CANONICAL_CHANGES)) {
            $this->trigger(self::EVENT_AFTER_MERGE_CANONICAL_CHANGES, new ElementEvent([
                'element' => $element,
            ]));
        }
    }

    private function _localizedElementQuery(ElementInterface $element): ElementQueryInterface
    {
        // use getLocalized() unless it’s eager-loaded
        $query = $element->getLocalized();
        if ($query instanceof ElementQueryInterface) {
            return $query;
        }

        return $element::find()
            ->id($element->id ?: false)
            ->structureId($element->structureId)
            ->siteId(['not', $element->siteId])
            ->drafts($element->getIsDraft())
            ->provisionalDrafts($element->isProvisionalDraft)
            ->revisions($element->getIsRevision());
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
     */
    public function updateCanonicalElement(ElementInterface $element, array $newAttributes = []): ElementInterface
    {
        if ($element->getIsCanonical()) {
            throw new InvalidArgumentException('Element was already canonical');
        }

        // we need to check if the entry type is still available for this element's section
        /** @phpstan-ignore-next-line */
        if ($element->hasMethod('isEntryTypeCompatible') && !$element->isEntryTypeCompatible()) {
            throw new InvalidArgumentException('Entry Type is no longer allowed in this section.');
        }

        // "Duplicate" the derivative element with the canonical element’s ID and UID
        $canonical = $element->getCanonical();

        $changedAttributes = DB::table(Table::CHANGEDATTRIBUTES)
            ->select(['siteId', 'attribute', 'propagated', 'userId'])
            ->where('elementId', $element->id)
            ->get();

        $changedFields = DB::table(Table::CHANGEDFIELDS)
            ->select(['siteId', 'fieldId', 'layoutElementUid', 'propagated', 'userId'])
            ->where('elementId', $element->id)
            ->get();

        $newAttributes += [
            'id' => $canonical->id,
            'uid' => $canonical->uid,
            'canonicalId' => $canonical->getCanonicalId(),
            'root' => $canonical->root,
            'lft' => $canonical->lft,
            'rgt' => $canonical->rgt,
            'level' => $canonical->level,
            'dateCreated' => $canonical->dateCreated,
            'dateDeleted' => null,
            'draftId' => null,
            'revisionId' => null,
            'isProvisionalDraft' => false,
            'updatingFromDerivative' => true,
            'dirtyAttributes' => [],
            'dirtyFields' => [],
        ];

        if ($canonical instanceof Entry) {
            $newAttributes['oldStatus'] = $canonical->oldStatus;
        }

        foreach ($changedAttributes as $attribute) {
            $newAttributes['siteAttributes'][$attribute->siteId]['dirtyAttributes'][] = $attribute->attribute;
        }

        foreach ($changedFields as $changedField) {
            $layoutElement = $element->getFieldLayout()?->getElementByUid($changedField->layoutElementUid);
            if ($layoutElement instanceof CustomField) {
                try {
                    $field = $layoutElement->getField();
                } catch (FieldNotFoundException) {
                    continue;
                }
                $newAttributes['siteAttributes'][$changedField->siteId]['dirtyFields'][] = $field->handle;
            }
        }

        // if we're working with a revision, ensure we mark element's custom fields as dirty;
        if ($element->getIsRevision()) {
            $newAttributes['dirtyFields'] = array_map(
                fn(FieldInterface $field) => $field->handle,
                $element->getFieldLayout()?->getCustomFields() ?? [],
            );
        }

        $updatedCanonical = $this->duplicateElement($element, $newAttributes);

        Craft::$app->onAfterRequest(function() use (
            $canonical,
            $updatedCanonical,
            $changedAttributes,
            $changedFields
        ) {
            // Update change tracking for the canonical element
            foreach ($changedAttributes as $attribute) {
                DB::table(Table::CHANGEDATTRIBUTES)
                    ->upsert([
                        'elementId' => $canonical->id,
                        'siteId' => $attribute->siteId,
                        'attribute' => $attribute->attribute,
                        'dateUpdated' => $updatedCanonical->dateUpdated,
                        'propagated' => $attribute->propagated,
                        'userId' => $attribute->userId,
                    ], ['elementId', 'siteId', 'attribute']);
            }

            foreach ($changedFields as $field) {
                DB::table(Table::CHANGEDFIELDS)
                    ->upsert([
                        'elementId' => $canonical->id,
                        'siteId' => $field->siteId,
                        'fieldId' => $field->fieldId,
                        'layoutElementUid' => $field->layoutElementUid,
                        'dateUpdated' => $updatedCanonical->dateUpdated,
                        'propagated' => $field->propagated,
                        'userId' => $field->userId,
                    ], ['elementId', 'siteId', 'fieldId', 'layoutElementUid']);
            }
        });

        return $updatedCanonical;
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
     */
    public function resaveElements(
        ElementQueryInterface $query,
        bool $continueOnError = false,
        bool $skipRevisions = true,
        ?bool $updateSearchIndex = null,
        bool $touch = false,
    ): void {
        /** @var ElementQuery $query */
        // Fire a 'beforeResaveElements' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_RESAVE_ELEMENTS)) {
            $this->trigger(self::EVENT_BEFORE_RESAVE_ELEMENTS, new ElementQueryEvent([
                'query' => $query,
            ]));
        }

        $this->ensureBulkOp(function() use ($query, $skipRevisions, $touch, $updateSearchIndex, $continueOnError) {
            $position = 0;

            try {
                foreach (DbHelper::each($query) as $element) {
                    /** @var ElementInterface $element */
                    $position++;

                    $element->setScenario(Element::SCENARIO_ESSENTIALS);
                    $element->resaving = true;

                    $e = null;
                    try {
                        // Fire a 'beforeResaveElement' event
                        if ($this->hasEventHandlers(self::EVENT_BEFORE_RESAVE_ELEMENT)) {
                            $this->trigger(self::EVENT_BEFORE_RESAVE_ELEMENT, new MultiElementActionEvent([
                                'query' => $query,
                                'element' => $element,
                                'position' => $position,
                            ]));
                        }

                        // Make sure this isn't a revision
                        if ($skipRevisions) {
                            $label = $element->getUiLabel();
                            $label = $label !== '' ? "$label ($element->id)" : sprintf('%s %s',
                                $element::lowerDisplayName(), $element->id);
                            try {
                                if (ElementHelper::isRevision($element)) {
                                    throw new InvalidElementException($element,
                                        "Skipped resaving $label because it's a revision.");
                                }
                            } catch (Throwable $rootException) {
                                throw new InvalidElementException($element,
                                    "Skipped resaving $label due to an error obtaining its root element: " . $rootException->getMessage());
                            }
                        }

                        $this->_saveElementInternal($element, true, true, $updateSearchIndex, forceTouch: $touch,
                            saveContent: true);
                    } catch (Throwable $e) {
                        if (!$continueOnError) {
                            throw $e;
                        }
                        Craft::$app->getErrorHandler()->logException($e);
                    }

                    // Fire an 'afterResaveElement' event
                    if ($this->hasEventHandlers(self::EVENT_AFTER_RESAVE_ELEMENT)) {
                        $this->trigger(self::EVENT_AFTER_RESAVE_ELEMENT, new MultiElementActionEvent([
                            'query' => $query,
                            'element' => $element,
                            'position' => $position,
                            'exception' => $e,
                        ]));
                    }
                }
            } catch (QueryAbortedException) {
                // Fail silently
            }
        });

        // Fire an 'afterResaveElements' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_RESAVE_ELEMENTS)) {
            $this->trigger(self::EVENT_AFTER_RESAVE_ELEMENTS, new ElementQueryEvent([
                'query' => $query,
            ]));
        }
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
     */
    public function propagateElements(
        ElementQueryInterface $query,
        array|int $siteIds = null,
        bool $continueOnError = false,
    ): void {
        /** @var ElementQuery $query */
        // Fire a 'beforePropagateElements' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_PROPAGATE_ELEMENTS)) {
            $this->trigger(self::EVENT_BEFORE_PROPAGATE_ELEMENTS, new ElementQueryEvent([
                'query' => $query,
            ]));
        }

        if ($siteIds !== null) {
            // Typecast to integers
            $siteIds = array_map(fn($siteId) => (int)$siteId, (array)$siteIds);
        }

        $this->ensureBulkOp(function() use ($query, $siteIds, $continueOnError) {
            $position = 0;

            try {
                foreach (DbHelper::each($query) as $element) {
                    /** @var ElementInterface $element */
                    $position++;

                    // Fire a 'beforePropagateElement' event
                    if ($this->hasEventHandlers(self::EVENT_BEFORE_PROPAGATE_ELEMENT)) {
                        $this->trigger(self::EVENT_BEFORE_PROPAGATE_ELEMENT, new MultiElementActionEvent([
                            'query' => $query,
                            'element' => $element,
                            'position' => $position,
                        ]));
                    }

                    $element->setScenario(Element::SCENARIO_ESSENTIALS);
                    $supportedSites = Arr::keyBy(ElementHelper::supportedSitesForElement($element), 'siteId');
                    $supportedSiteIds = array_keys($supportedSites);
                    $elementSiteIds = $siteIds !== null ? array_intersect($siteIds,
                        $supportedSiteIds) : $supportedSiteIds;
                    $elementType = get_class($element);

                    $e = null;
                    try {
                        $element->newSiteIds = [];

                        foreach ($elementSiteIds as $siteId) {
                            if ($siteId != $element->siteId) {
                                // Make sure the site element wasn't updated more recently than the main one
                                $siteElement = $this->getElementById($element->id, $elementType, $siteId);
                                if ($siteElement === null || $siteElement->dateUpdated < $element->dateUpdated) {
                                    $siteElement ??= false;
                                    $this->_propagateElement($element, $supportedSites, $siteId, $siteElement);
                                }
                            }
                        }

                        // It's now fully duplicated and propagated
                        $element->markAsDirty();
                        $element->afterPropagate(false);
                    } catch (Throwable $e) {
                        if (!$continueOnError) {
                            throw $e;
                        }
                        Craft::$app->getErrorHandler()->logException($e);
                    }

                    // Fire an 'afterPropagateElement' event
                    if ($this->hasEventHandlers(self::EVENT_AFTER_PROPAGATE_ELEMENT)) {
                        $this->trigger(self::EVENT_AFTER_PROPAGATE_ELEMENT, new MultiElementActionEvent([
                            'query' => $query,
                            'element' => $element,
                            'position' => $position,
                            'exception' => $e,
                        ]));
                    }

                    // Track this element in bulk operations
                    $this->trackElementInBulkOps($element);

                    // Clear caches
                    $this->invalidateCachesForElement($element);
                }
            } catch (QueryAbortedException) {
                // Fail silently
            }
        });

        // Fire an 'afterPropagateElements' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_PROPAGATE_ELEMENTS)) {
            $this->trigger(self::EVENT_AFTER_PROPAGATE_ELEMENTS, new ElementQueryEvent([
                'query' => $query,
            ]));
        }
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
     */
    public function duplicateElement(
        ElementInterface $element,
        array $newAttributes = [],
        bool $placeInStructure = true,
        bool $asUnpublishedDraft = false,
        bool $checkAuthorization = false,
        bool $copyModifiedFields = false,
    ): ElementInterface {
        // Make sure the element exists
        if (!$element->id) {
            throw new Exception('Attempting to duplicate an unsaved element.');
        }

        // Ensure all fields have been normalized
        $element->getFieldValues();

        // Create our first clone for the $element’s site
        $mainClone = clone $element;
        $mainClone->id = null;
        $mainClone->uid = Str::uuid()->toString();
        $mainClone->draftId = null;
        $mainClone->siteSettingsId = null;
        $mainClone->root = null;
        $mainClone->lft = null;
        $mainClone->rgt = null;
        $mainClone->level = null;
        $mainClone->dateCreated = null;
        $mainClone->dateUpdated = null;
        $mainClone->dateLastMerged = null;
        $mainClone->duplicateOf = $element;
        $mainClone->setCanonicalId(null);

        $behaviors = Arr::pull($newAttributes, 'behaviors', []);
        $mainClone->setRevisionNotes(Arr::pull($newAttributes, 'revisionNotes'));

        // Extract any attributes that are meant for other sites
        $siteAttributes = Arr::pull($newAttributes, 'siteAttributes', []);

        // Note: must use Craft::configure() rather than setAttributes() here,
        // so we're not limited to whatever attributes() returns
        Typecast::configure($mainClone, Arr::merge(
            $newAttributes,
            $siteAttributes[$mainClone->siteId] ?? [],
        ));

        // Attach behaviors
        foreach ($behaviors as $name => $behavior) {
            if ($behavior instanceof Behavior) {
                $behavior = clone $behavior;
            }
            $mainClone->attachBehavior($name, $behavior);
        }

        // Make sure the element actually supports its own site ID
        $supportedSites = Arr::keyBy(ElementHelper::supportedSitesForElement($mainClone), 'siteId');
        if (!isset($supportedSites[$mainClone->siteId])) {
            throw new UnsupportedSiteException($element, $mainClone->siteId,
                'Attempting to duplicate an element in an unsupported site.');
        }

        // Clone any field values that are objects (without affecting the dirty fields)
        $dirtyFields = $mainClone->getDirtyFields();
        foreach ($mainClone->getFieldValues() as $handle => $value) {
            if (is_object($value) && !$value instanceof UnitEnum) {
                $mainClone->setFieldValue($handle, clone $value);
            }
        }
        $mainClone->setDirtyFields($dirtyFields, false);

        // Check authorization?
        if ($checkAuthorization && !($this->canDuplicate($mainClone) && $this->canSave($mainClone))) {
            throw new ForbiddenHttpException('User not authorized to duplicate this element.');
        }

        // If we are duplicating a draft as another draft, create a new draft row
        if ($mainClone->draftId && $mainClone->draftId === $element->draftId) {
            /** @var ElementInterface $element */
            $draftsService = app(Drafts::class);
            // Are we duplicating a draft of a published element?
            if ($element->getIsDerivative()) {
                $mainClone->draftName = $draftsService->generateDraftName($element->getCanonicalId());
            } else {
                $mainClone->draftName = t('First draft');
            }
            $mainClone->draftNotes = null;
            $mainClone->setCanonicalId($element->getCanonicalId());
            $mainClone->draftId = $draftsService->insertDraftRow(
                $mainClone->draftName,
                null,
                Craft::$app->getUser()->getId(),
                $element->getCanonicalId(),
                $mainClone->trackDraftChanges,
            );
        }

        // If we are supposed to save it as new unpublished draft
        if ($asUnpublishedDraft) {
            /** @var ElementInterface $element */
            $draftsService = app(Drafts::class);
            $mainClone->draftName = t('First draft');
            $mainClone->draftNotes = null;
            $mainClone->setCanonicalId(null);
            $mainClone->draftId = $draftsService->insertDraftRow(
                $mainClone->draftName,
                null,
                Craft::$app->getUser()->getId(),
                null,
                $mainClone->trackDraftChanges,
            );
        }

        // Validate
        $mainClone->setScenario(Element::SCENARIO_ESSENTIALS);
        $mainClone->validate();

        // If there are any errors on the URI, re-validate as disabled
        if ($mainClone->errors()->has('uri') && $mainClone->enabled) {
            $mainClone->enabled = false;
            $mainClone->validate();
        }

        if ($mainClone->errors()->isNotEmpty()) {
            throw new InvalidElementException($mainClone,
                'Element ' . $element->id . ' could not be duplicated because it doesn\'t validate.');
        }

        $this->ensureBulkOp(function() use (
            $mainClone,
            $supportedSites,
            $element,
            $copyModifiedFields,
            $placeInStructure,
            $newAttributes,
            $behaviors,
            $siteAttributes,
            $asUnpublishedDraft,
        ) {
            DB::beginTransaction();
            try {
                // Start with $element’s site
                if (!$this->_saveElementInternal($mainClone, false, false, null, $supportedSites, saveContent: true)) {
                    throw new InvalidElementException($mainClone,
                        'Element ' . $element->id . ' could not be duplicated for site ' . $element->siteId);
                }

                if ($copyModifiedFields) {
                    $this->copyModifiedFields($element, $mainClone);
                }

                // Should we add the clone to the source element’s structure?
                if (
                    $placeInStructure &&
                    $mainClone->getIsCanonical() &&
                    !$mainClone->root &&
                    (!$mainClone->structureId || !$element->structureId || $mainClone->structureId == $element->structureId)
                ) {
                    $canonical = $element->getCanonical(true);
                    if ($canonical->structureId && $canonical->root) {
                        $mode = isset($newAttributes['id']) ? Mode::Auto : Mode::Insert;
                        Structures::moveAfter($canonical->structureId, $mainClone, $canonical, $mode);
                    }
                }

                $propagatedTo = [$mainClone->siteId => true];
                $mainClone->newSiteIds = [];

                // Propagate it
                $otherSiteIds = array_keys(Arr::except($supportedSites, $mainClone->siteId));
                if ($element->id && !empty($otherSiteIds)) {
                    $siteElements = $this->_localizedElementQuery($element)
                        ->siteId($otherSiteIds)
                        ->status(null)
                        ->all();

                    foreach ($siteElements as $siteElement) {
                        // Ensure all fields have been normalized
                        $siteElement->getFieldValues();

                        $siteClone = clone $siteElement;
                        $siteClone->duplicateOf = $siteElement;
                        $siteClone->propagating = true;
                        $siteClone->propagatingFrom = $mainClone;
                        $siteClone->id = $mainClone->id;
                        $siteClone->uid = $mainClone->uid;
                        $siteClone->structureId = $mainClone->structureId;
                        $siteClone->root = $mainClone->root;
                        $siteClone->lft = $mainClone->lft;
                        $siteClone->rgt = $mainClone->rgt;
                        $siteClone->level = $mainClone->level;
                        $siteClone->enabled = $mainClone->enabled;
                        $siteClone->siteSettingsId = null;
                        $siteClone->dateCreated = $mainClone->dateCreated;
                        $siteClone->dateUpdated = $mainClone->dateUpdated;
                        $siteClone->dateLastMerged = null;
                        $siteClone->setCanonicalId(null);

                        // Attach behaviors
                        foreach ($behaviors as $name => $behavior) {
                            if ($behavior instanceof Behavior) {
                                $behavior = clone $behavior;
                            }
                            $siteClone->attachBehavior($name, $behavior);
                        }

                        // Note: must use Craft::configure() rather than setAttributes() here,
                        // so we're not limited to whatever attributes() returns
                        Typecast::configure($siteClone, Arr::merge(
                            $newAttributes,
                            $siteAttributes[$siteElement->siteId] ?? [],
                        ));
                        $siteClone->siteId = $siteElement->siteId;

                        // Clone any field values that are objects (without affecting the dirty fields)
                        $dirtyFields = $siteClone->getDirtyFields();
                        foreach ($siteClone->getFieldValues() as $handle => $value) {
                            if (is_object($value) && !$value instanceof UnitEnum) {
                                $siteClone->setFieldValue($handle, clone $value);
                            }
                        }
                        $siteClone->setDirtyFields($dirtyFields, false);

                        if ($element::hasUris()) {
                            // Make sure it has a valid slug
                            (new SlugValidator())->validateAttribute($siteClone, 'slug');
                            if ($siteClone->errors()->has('slug')) {
                                throw new InvalidElementException($siteClone,
                                    "Element $element->id could not be duplicated for site $siteElement->siteId: " . $siteClone->errors()->first('slug'));
                            }

                            // Set a unique URI on the site clone
                            try {
                                $this->setElementUri($siteClone);
                            } catch (OperationAbortedException) {
                                // Oh well, not worth bailing over
                            }
                        }

                        if (!$this->_saveElementInternal($siteClone, false, false, supportedSites: $supportedSites,
                            saveContent: true)) {
                            throw new InvalidElementException($siteClone,
                                "Element $element->id could not be duplicated for site $siteElement->siteId: " . implode(', ',
                                    $siteClone->getFirstErrors()));
                        }

                        if ($copyModifiedFields) {
                            $this->copyModifiedFields($siteElement, $siteClone);
                        }

                        $propagatedTo[$siteClone->siteId] = true;
                        if ($siteClone->isNewForSite) {
                            $mainClone->newSiteIds[] = $siteClone->siteId;
                        }
                    }

                    // Now propagate $mainClone to any sites the source element didn’t already exist in
                    foreach ($supportedSites as $siteId => $siteInfo) {
                        if (!isset($propagatedTo[$siteId]) && $siteInfo['propagate']) {
                            $siteClone = $element->getIsDraft() && !$element->getIsUnpublishedDraft() ? null : false;
                            if (!$this->_propagateElement($mainClone, $supportedSites, $siteId, $siteClone)) {
                                /** @phpstan-ignore-next-line */
                                throw $siteClone
                                    ? new InvalidElementException($siteClone,
                                        "Element $siteClone->id could not be propagated to site $siteId: " . implode(', ',
                                            $siteClone->getFirstErrors()))
                                    : new InvalidElementException($mainClone,
                                        "Element $mainClone->id could not be propagated to site $siteId.");
                            }
                            $propagatedTo[$siteId] = true;
                            $mainClone->newSiteIds[] = $siteId;
                        }
                    }
                }

                // It's now fully duplicated and propagated
                $mainClone->afterPropagate(empty($newAttributes['id']));

                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            // Clean up our tracks
            $mainClone->duplicateOf = null;

            // discard draft from the original element, if it was a provisional draft
            if ($asUnpublishedDraft && $element->isProvisionalDraft) {
                Craft::$app->elements->deleteElementById($element->id);
            }
        });

        return $mainClone;
    }

    private function copyModifiedFields(ElementInterface $from, ElementInterface $to): void
    {
        $modifiedAttributes = [
            ...$from->getModifiedAttributes(),
            ...$from->getDirtyAttributes(),
        ];
        $modifiedFields = [
            ...$from->getModifiedFields(),
            ...$from->getDirtyFields(),
        ];

        if ($from->duplicateOf?->getIsDraft()) {
            $modifiedAttributes += [
                ...$from->duplicateOf->getModifiedAttributes(),
                ...$from->duplicateOf->getDirtyAttributes(),
            ];
            $modifiedFields += [
                ...$from->duplicateOf->getModifiedFields(),
                ...$from->duplicateOf->getDirtyFields(),
            ];
        }

        $modifiedAttributes = array_unique($modifiedAttributes);
        $modifiedFields = array_unique($modifiedFields);

        $userId = Auth::user()?->id;

        if (!empty($modifiedAttributes)) {
            $data = [];

            foreach ($modifiedAttributes as $attribute) {
                $data[] = [
                    'elementId' => $to->id,
                    'siteId' => $to->siteId,
                    'attribute' => $attribute,
                    'dateUpdated' => $to->dateUpdated,
                    'propagated' => false,
                    'userId' => $userId,
                ];
            }

            DB::table(Table::CHANGEDATTRIBUTES)->insert($data);
        }

        if (!empty($modifiedFields)) {
            $data = [];
            $fieldLayout = $to->getFieldLayout();

            foreach ($modifiedFields as $handle) {
                $field = $fieldLayout->getFieldByHandle($handle);
                if ($field) {
                    $data[] = [
                        'elementId' => $to->id,
                        'siteId' => $to->siteId,
                        'fieldId' => $field->id,
                        'layoutElementUid' => $field->layoutElement->uid,
                        'dateUpdated' => $to->dateUpdated,
                        'propagated' => false,
                        'userId' => $userId,
                    ];
                }
            }

            DB::table(Table::CHANGEDFIELDS)->insert($data);
        }
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
     */
    public function updateElementSlugAndUri(
        ElementInterface $element,
        bool $updateOtherSites = true,
        bool $updateDescendants = true,
        bool $queue = false,
    ): void {
        if ($queue) {
            Queue::push(new UpdateElementSlugsAndUris([
                'elementId' => $element->id,
                'elementType' => get_class($element),
                'siteId' => $element->siteId,
                'updateOtherSites' => $updateOtherSites,
                'updateDescendants' => $updateDescendants,
            ]));

            return;
        }

        if ($element::hasUris()) {
            $this->setElementUri($element);
        }

        // Fire a 'beforeUpdateSlugAndUri' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_UPDATE_SLUG_AND_URI)) {
            $this->trigger(self::EVENT_BEFORE_UPDATE_SLUG_AND_URI, new ElementEvent([
                'element' => $element,
            ]));
        }

        DB::table(Table::ELEMENTS_SITES)
            ->where('elementId', $element->id)
            ->where('siteId', $element->siteId)
            ->update([
                'slug' => $element->slug,
                'uri' => $element->uri,
                'dateUpdated' => now(),
            ]);

        // Fire a 'afterUpdateSlugAndUri' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_UPDATE_SLUG_AND_URI)) {
            $this->trigger(self::EVENT_AFTER_UPDATE_SLUG_AND_URI, new ElementEvent([
                'element' => $element,
            ]));
        }

        // Invalidate any caches involving this element
        $this->invalidateCachesForElement($element);

        if ($updateOtherSites) {
            $this->updateElementSlugAndUriInOtherSites($element);
        }

        if ($updateDescendants) {
            $this->updateDescendantSlugsAndUris($element, $updateOtherSites);
        }
    }

    /**
     * Updates an element’s slug and URI, for any sites besides the given one.
     *
     * @param ElementInterface $element The element to update.
     */
    public function updateElementSlugAndUriInOtherSites(ElementInterface $element): void
    {
        foreach (Sites::getAllSiteIds() as $siteId) {
            if ($siteId === $element->siteId) {
                continue;
            }

            $elementInOtherSite = $this->_localizedElementQuery($element)
                ->siteId($siteId)
                ->one();

            if ($elementInOtherSite) {
                $this->updateElementSlugAndUri($elementInOtherSite, false, false);
            }
        }
    }

    /**
     * Updates an element’s descendants’ slugs and URIs.
     *
     * @param ElementInterface $element The element whose descendants should be updated.
     * @param bool $updateOtherSites Whether the element’s other sites should also be updated.
     * @param bool $queue Whether the descendants’ slugs and URIs should be updated via a job in the queue.
     */
    public function updateDescendantSlugsAndUris(
        ElementInterface $element,
        bool $updateOtherSites = true,
        bool $queue = false,
    ): void {
        $query = $this->createElementQuery(get_class($element))
            ->descendantOf($element)
            ->descendantDist(1)
            ->status(null)
            ->siteId($element->siteId);

        if ($queue) {
            $childIds = $query->ids();

            if (!empty($childIds)) {
                Queue::push(new UpdateElementSlugsAndUris([
                    'elementId' => $childIds,
                    'elementType' => get_class($element),
                    'siteId' => $element->siteId,
                    'updateOtherSites' => $updateOtherSites,
                    'updateDescendants' => true,
                ]));
            }
        } else {
            $children = $query->all();

            foreach ($children as $child) {
                $this->updateElementSlugAndUri($child, $updateOtherSites, true, false);
            }
        }
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
     */
    public function mergeElementsByIds(int $mergedElementId, int $prevailingElementId): bool
    {
        // Get the elements
        $mergedElement = $this->getElementById($mergedElementId);
        if (!$mergedElement) {
            throw new ElementNotFoundException("No element exists with the ID '$mergedElementId'");
        }
        $prevailingElement = $this->getElementById($prevailingElementId);
        if (!$prevailingElement) {
            throw new ElementNotFoundException("No element exists with the ID '$prevailingElementId'");
        }

        // Merge them
        return $this->mergeElements($mergedElement, $prevailingElement);
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
     */
    public function mergeElements(ElementInterface $mergedElement, ElementInterface $prevailingElement): bool
    {
        DB::beginTransaction();
        try {
            // Find elements that relate to the merged element
            $data = DB::table(Table::RELATIONS, 'r')
                ->select(['r.sourceId', 'r.sourceSiteId', 'e.type'])
                ->join(new Alias(Table::ELEMENTS, 'e'), 'e.id', 'r.sourceId')
                ->where('r.targetId', $mergedElement->id)
                ->get()
                ->groupBy(['type', fn($r) => $r['sourceSiteId'] ?? '*']);

            foreach ($data as $elementType => $typeData) {
                foreach ($typeData as $siteId => $relations) {
                    /** @var class-string<ElementInterface> $elementType */
                    /** @var ElementCollection $relations */
                    $query = $elementType::find()
                        ->id($relations->pluck('sourceId'))
                        ->siteId($siteId)
                        ->drafts(null)
                        ->revisions(null)
                        ->trashed(null)
                        ->status(null);

                    if ($siteId === '*') {
                        $query->unique();
                    }

                    foreach (DbHelper::each($query) as $element) {
                        /** @var ElementInterface $element */
                        /** @var CustomFieldBehavior $behavior */
                        $behavior = $element->getBehavior('customFields');
                        foreach ($element->getFieldLayout()?->getCustomFields() ?? [] as $field) {
                            if (
                                $field instanceof BaseRelationField &&
                                isset($behavior->{$field->handle}) &&
                                is_array($behavior->{$field->handle}) &&
                                in_array($mergedElement->id, $behavior->{$field->handle})
                            ) {
                                // see if the prevailing element is related too
                                if (in_array($prevailingElement->id, $behavior->{$field->handle})) {
                                    $value = array_values(array_filter($behavior->{$field->handle}, fn($v) => $v != $mergedElement->id));
                                } else {
                                    $value = array_map(fn($v) => $v == $mergedElement->id ? $prevailingElement->id : $v, $behavior->{$field->handle});
                                }
                                $element->setFieldValue($field->handle, $value);
                            }
                        }
                        if (!empty($element->getDirtyFields())) {
                            $element->resaving = true;
                            $this->saveElement($element, false);
                        }
                    }
                }
            }

            // Deal with any remaining relation values
            // (Not all relation field values have been saved since 5.3.0 when relation fields
            // started saving the target element IDs in the content JSON.)
            $relations = DB::table(Table::RELATIONS)
                ->select(['id', 'fieldId', 'sourceId', 'sourceSiteId'])
                ->where('targetId', $mergedElement->id)
                ->get();

            foreach ($relations as $relation) {
                // Make sure the persisting element isn't already selected in the same field
                $persistingElementIsRelatedToo = DB::table(Table::RELATIONS)
                    ->where('fieldId', $relation->fieldId)
                    ->where('sourceId', $relation->sourceId)
                    ->where('sourceSiteId', $relation->sourceSiteId)
                    ->where('targetId', $prevailingElement->id)
                    ->exists();

                if (!$persistingElementIsRelatedToo) {
                    DB::table(Table::RELATIONS)
                        ->where('id', $relation->id)
                        ->update([
                            'targetId' => $prevailingElement->id,
                            'dateUpdated' => now(),
                        ]);
                }
            }

            // Update any structures that the merged element is in
            $structureElements = DB::table(Table::STRUCTUREELEMENTS)
                ->select(['id', 'structureId'])
                ->where('elementId', $mergedElement->id)
                ->get();

            foreach ($structureElements as $structureElement) {
                // Make sure the persisting element isn't already a part of that structure
                $persistingElementIsInStructureToo = DB::table(Table::STRUCTUREELEMENTS)
                    ->where('structureId', $structureElement->structureId)
                    ->where('elementId', $prevailingElement->id)
                    ->exists();

                if (!$persistingElementIsInStructureToo) {
                    DB::table(Table::STRUCTUREELEMENTS)
                        ->where('id', $structureElement->id)
                        ->update([
                            'elementId' => $prevailingElement->id,
                            'dateUpdated' => now(),
                        ]);
                }
            }

            // Update any reference tags
            $elementType = $this->getElementTypeById($prevailingElement->id);

            if ($elementType !== null && ($refHandle = $elementType::refHandle()) !== null) {
                $refTagPrefix = "\{$refHandle:";

                dispatch(new FindAndReplace(
                    find: $refTagPrefix . $mergedElement->id . ':',
                    replace: $refTagPrefix . $prevailingElement->id . ':',
                    description: I18N::prep('Updating element references'),
                ));

                dispatch(new FindAndReplace(
                    find: $refTagPrefix . $mergedElement->id . '}',
                    replace: $refTagPrefix . $prevailingElement->id . ':',
                    description: $refTagPrefix . $prevailingElement->id . '}',
                ));
            }

            // Fire an 'afterMergeElements' event
            if ($this->hasEventHandlers(self::EVENT_AFTER_MERGE_ELEMENTS)) {
                $this->trigger(self::EVENT_AFTER_MERGE_ELEMENTS, new MergeElementsEvent([
                    'mergedElementId' => $mergedElement->id,
                    'prevailingElementId' => $prevailingElement->id,
                ]));
            }

            // Now delete the merged element
            $success = $this->deleteElement($mergedElement);

            DB::commit();

            return $success;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
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
     */
    public function deleteElementById(
        int $elementId,
        ?string $elementType = null,
        ?int $siteId = null,
        bool $hardDelete = false,
    ): bool {
        if ($elementType === null) {
            $elementType = $this->getElementTypeById($elementId);

            if ($elementType === null) {
                return false;
            }
        }

        if ($siteId === null && $elementType::isLocalized() && Sites::isMultiSite()) {
            // Get a site this element is enabled in
            $siteId = (int)DB::table(Table::ELEMENTS_SITES)
                ->where('elementId', $elementId)
                ->value('siteId');

            if ($siteId === 0) {
                return false;
            }
        }

        $element = $this->getElementById($elementId, $elementType, $siteId);

        if (!$element) {
            return false;
        }

        return $this->deleteElement($element, $hardDelete);
    }

    /**
     * Deletes an element.
     *
     * @param ElementInterface $element The element to be deleted
     * @param bool $hardDelete Whether the element should be hard-deleted immediately, instead of soft-deleted
     *
     * @return bool Whether the element was deleted successfully
     * @throws Throwable
     */
    public function deleteElement(ElementInterface $element, bool $hardDelete = false): bool
    {
        // Fire a 'beforeDeleteElement' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_ELEMENT)) {
            $event = new DeleteElementEvent([
                'element' => $element,
                'hardDelete' => $hardDelete,
            ]);
            $this->trigger(self::EVENT_BEFORE_DELETE_ELEMENT, $event);
            $hardDelete = $hardDelete || $event->hardDelete;
        }

        $element->hardDelete = $hardDelete;

        if (!$element->beforeDelete()) {
            return false;
        }

        $this->ensureBulkOp(function() use ($element) {
            DB::beginTransaction();
            try {
                // First delete any structure nodes with this element, so NestedSetBehavior can do its thing.
                while (($record = StructureElementModel::where('elementId', $element->id)->first()) !== null) {
                    // If this element still has any children, move them up before the one getting deleted.
                    while (($child = $record->children(1)->first()) !== null) {
                        /** @var StructureElementModel $child */
                        $child->insertBefore($record);
                        // Re-fetch the record since its lft and rgt attributes just changed
                        $record->refresh();
                    }
                    // Delete this element’s node
                    $record->deleteWithChildren();
                }

                // Invalidate any caches involving this element
                $this->invalidateCachesForElement($element);

                DateTimeHelper::pause();

                if ($element->hardDelete) {
                    DB::table(Table::ELEMENTS)->delete($element->id);
                    DB::table(Table::SEARCHINDEX)
                        ->where('elementId', $element->id)
                        ->delete();
                } else {
                    // Soft delete the elements table row
                    DB::table(Table::ELEMENTS)
                        ->where('id', $element->id)
                        ->update([
                            'dateUpdated' => $now = now(),
                            'dateDeleted' => $now,
                            'deletedWithOwner' => $element->deletedWithOwner,
                        ]);

                    // Also soft delete the element’s drafts & revisions
                    $this->_cascadeDeleteDraftsAndRevisions($element->id);
                }

                $element->dateDeleted = DateTimeHelper::now();
                $element->afterDelete();

                if (!$element->hardDelete) {
                    // Track this element in bulk operations
                    $this->trackElementInBulkOps($element);
                }

                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();
                throw $e;
            } finally {
                DateTimeHelper::resume();
            }
        });

        // Fire an 'afterDeleteElement' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_ELEMENT)) {
            $this->trigger(self::EVENT_AFTER_DELETE_ELEMENT, new ElementEvent([
                'element' => $element,
            ]));
        }

        return true;
    }

    /**
     * Deletes an element in the site it’s loaded in.
     *
     * @param ElementInterface $element
     *
     * @since 4.4.0
     */
    public function deleteElementForSite(ElementInterface $element): void
    {
        $this->deleteElementsForSite([$element]);
    }

    /**
     * Deletes elements in the site they are currently loaded in.
     *
     * @param ElementInterface[] $elements
     *
     * @throws InvalidArgumentException if all elements don’t have the same type and site ID.
     * @since 4.4.0
     */
    public function deleteElementsForSite(array $elements): void
    {
        if (empty($elements)) {
            return;
        }

        // Make sure each element has the same type and site ID
        $firstElement = reset($elements);
        $elementType = get_class($firstElement);

        foreach ($elements as $element) {
            if (get_class($element) !== $elementType || $element->siteId !== $firstElement->siteId) {
                throw new InvalidArgumentException('All elements must have the same type and site ID.');
            }
        }

        // Separate the multi-site elements from the single-site elements
        $multiSiteElementIds = $firstElement::find()
            ->id(array_map(fn(ElementInterface $element) => $element->id, $elements))
            ->status(null)
            ->drafts(null)
            ->siteId(['not', $firstElement->siteId])
            ->unique()
            ->select(['elements.id'])
            ->pluck('id')
            ->all();

        $multiSiteElementIdsIdx = array_flip($multiSiteElementIds);
        $multiSiteElements = [];
        $singleSiteElements = [];

        foreach ($elements as $element) {
            if (isset($multiSiteElementIdsIdx[$element->id])) {
                $multiSiteElements[] = $element;
            } else {
                $singleSiteElements[] = $element;
            }
        }

        if (!empty($multiSiteElements)) {
            // Fire 'beforeDeleteForSite' events
            if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_FOR_SITE)) {
                foreach ($multiSiteElements as $element) {
                    $this->trigger(self::EVENT_BEFORE_DELETE_FOR_SITE, new ElementEvent([
                        'element' => $element,
                    ]));
                }
            }

            foreach ($multiSiteElements as $element) {
                $element->beforeDeleteForSite();
            }

            // Delete the rows in elements_sites
            DB::table(Table::ELEMENTS_SITES)
                ->whereIn('elementId', $multiSiteElementIds)
                ->where('siteId', $firstElement->siteId)
                ->delete();

            // Resave them
            $this->resaveElements(
                $firstElement::find()
                    ->id($multiSiteElementIds)
                    ->status(null)
                    ->drafts(null)
                    ->site('*')
                    ->unique(),
                true,
                updateSearchIndex: false,
            );

            foreach ($multiSiteElements as $element) {
                $element->afterDeleteForSite();
            }

            // Fire 'afterDeleteForSite' events
            if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_FOR_SITE)) {
                foreach ($multiSiteElements as $element) {
                    $this->trigger(self::EVENT_AFTER_DELETE_FOR_SITE, new ElementEvent([
                        'element' => $element,
                    ]));
                }
            }
        }

        // Fully delete any single-site elements
        if (!empty($singleSiteElements)) {
            foreach ($singleSiteElements as $element) {
                $this->deleteElement($element, true);
            }
        }
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
     */
    public function restoreElement(ElementInterface $element): bool
    {
        return $this->restoreElements([$element]);
    }

    /**
     * Restores multiple elements.
     *
     * @param ElementInterface[] $elements
     *
     * @return bool Whether at least one element was restored successfully
     * @throws UnsupportedSiteException if an element is being restored for a site it doesn’t support
     * @throws Throwable if reasons
     */
    public function restoreElements(array $elements): bool
    {
        // Fire "before" events
        foreach ($elements as $element) {
            // Fire a 'beforeRestoreElement' event
            if ($this->hasEventHandlers(self::EVENT_BEFORE_RESTORE_ELEMENT)) {
                $this->trigger(self::EVENT_BEFORE_RESTORE_ELEMENT, new ElementEvent([
                    'element' => $element,
                ]));
            }

            if (!$element->beforeRestore()) {
                return false;
            }
        }

        DB::beginTransaction();
        try {
            // Restore the elements
            foreach ($elements as $element) {
                // Get the sites supported by this element
                $supportedSites = Arr::keyBy(ElementHelper::supportedSitesForElement($element), 'siteId');
                if (empty($supportedSites)) {
                    throw new UnsupportedSiteException($element, $element->siteId,
                        "Element $element->id has no supported sites.");
                }

                // Make sure the element actually supports the site it's being saved in
                if (!isset($supportedSites[$element->siteId])) {
                    throw new UnsupportedSiteException($element, $element->siteId,
                        'Attempting to restore an element in an unsupported site.');
                }

                // Get the element in each supported site
                $otherSiteIds = array_keys(Arr::except($supportedSites, $element->siteId));

                if (!empty($otherSiteIds)) {
                    $siteElements = $this->_localizedElementQuery($element)
                        ->siteId($otherSiteIds)
                        ->status(null)
                        ->trashed(null)
                        ->all();
                } else {
                    $siteElements = [];
                }

                // Make sure it still passes essential validation
                $element->setScenario(Element::SCENARIO_ESSENTIALS);
                if (!$element->validate()) {
                    Log::warning("Unable to restore element $element->id: doesn't pass essential validation: " . print_r($element->errors, true), [__METHOD__]);
                    DB::rollBack();
                    return false;
                }

                foreach ($siteElements as $siteElement) {
                    if ($siteElement !== $element) {
                        $siteElement->setScenario(Element::SCENARIO_ESSENTIALS);
                        if (!$siteElement->validate()) {
                            Log::warning("Unable to restore element $element->id: doesn't pass essential validation for site $element->siteId: " . print_r($element->errors, true), [__METHOD__]);
                            throw new Exception("Element $element->id doesn't pass essential validation for site $element->siteId.");
                        }
                    }
                }

                // Restore it
                DB::table(Table::ELEMENTS)
                    ->where('id', $element->id)
                    ->update([
                        'dateDeleted' => null,
                        'dateUpdated' => now(),
                        'deletedWithOwner' => null,
                    ]);

                // Also restore the element’s drafts & revisions
                $this->_cascadeDeleteDraftsAndRevisions($element->id, false);

                // Restore its search indexes
                Search::indexElementAttributes($element);
                foreach ($siteElements as $siteElement) {
                    Search::indexElementAttributes($siteElement);
                }

                // Invalidate caches
                $this->invalidateCachesForElement($element);
            }

            // Fire "after" events
            foreach ($elements as $element) {
                $element->afterRestore();
                $element->trashed = false;
                $element->dateDeleted = null;
                $element->deletedWithOwner = null;

                // Fire an 'afterRestoreElement' event
                if ($this->hasEventHandlers(self::EVENT_AFTER_RESTORE_ELEMENT)) {
                    $this->trigger(self::EVENT_AFTER_RESTORE_ELEMENT, new ElementEvent([
                        'element' => $element,
                    ]));
                }
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return true;
    }

    /**
     * Returns the recent activity for an element.
     *
     * @param ElementInterface $element
     * @param int|null $excludeUserId
     *
     * @return ElementActivity[]
     * @since 4.5.0
     */
    public function getRecentActivity(ElementInterface $element, ?int $excludeUserId = null): array
    {
        $results = DB::table(Table::ELEMENTACTIVITY)
            ->select(['userId', 'siteId', 'draftId', 'type', 'timestamp'])
            ->where('elementId', $element->getCanonicalId())
            ->where('timestamp', '>', now()->subMinute())
            ->orderByDesc('timestamp')
            ->when(
                $excludeUserId,
                fn(Builder $query) => $query->whereNot('userId', $excludeUserId),
            )->get();

        if ($results->isEmpty()) {
            return [];
        }

        // get all the unique users
        $userIds = $results->pluck('userId')->unique()->all();
        $users = User::find()
            ->id($userIds)
            ->status(null)
            ->get()
            ->keyBy('id')
            ->all();

        /** @var Collection<ElementActivity> $activity */
        $activity = Collection::make();
        /** @var ElementActivity[] $activityByUserId */
        $activityByUserId = [];
        $elements = [];
        $isCanonical = $element->getIsCanonical() || $element->isProvisionalDraft;
        $elements[$isCanonical ? 0 : $element->draftId][$element->siteId] = $element;

        foreach ($results as $result) {
            // do we already have an activity record for this user?
            if (isset($activityByUserId[$result->userId])) {
                $newerRecord = $activityByUserId[$result->userId];
                // edit/save trumps view
                if (
                    $newerRecord->type === ElementActivity::TYPE_VIEW &&
                    $result->type !== ElementActivity::TYPE_VIEW
                ) {
                    $activity = $activity->filter(fn(ElementActivity $record) => $record !== $newerRecord);
                    unset($activityByUserId[$result->userId]);
                } else {
                    continue;
                }
            }

            // fetch the element (draft)
            $elementKey = $result->draftId ?: 0;
            if (!isset($elements[$elementKey][$result->siteId])) {
                $resultElement = $element::find()
                    ->id($result->draftId ? null : $element->getCanonicalId())
                    ->draftId($result->draftId)
                    ->site('*')
                    ->preferSites([$result->siteId, $element->siteId])
                    ->status(null)
                    ->one();

                // just to be safe...
                if (!$resultElement) {
                    Log::warning(sprintf(
                        'Couldn’t load %s element %s%s for site %s',
                        $element::class,
                        $element->getCanonicalId(),
                        $result->draftId ? " (draft {$result->draftId})" : '',
                        $result->siteId,
                    ), [__METHOD__]);
                    continue;
                }

                $elements[$elementKey][$result->siteId] = $resultElement;
            }

            $record = $activityByUserId[$result->userId] = new ElementActivity(
                $users[$result->userId],
                $elements[$elementKey][$result->siteId],
                $result->type,
                DateTimeHelper::toDateTime($result->timestamp),
            );
            $activity->push($record);
        }

        return $activity->values()->all();
    }

    /**
     * Tracks new activity for an element.
     *
     * @param ElementInterface $element
     * @param ElementActivity::TYPE_* $type $type
     * @param User|null $user
     *
     * @since 4.5.0
     */
    public function trackActivity(ElementInterface $element, string $type, ?User $user = null): void
    {
        if ($user === null) {
            $user = Auth::user();
            if (!$user) {
                throw new InvalidArgumentException('$user must be set if no user is signed in.');
            }
        }

        // save => edit, if a provisional draft
        if ($type === ElementActivity::TYPE_SAVE && $element->isProvisionalDraft) {
            $type = ElementActivity::TYPE_EDIT;
        }

        $isCanonical = $element->getIsCanonical() || $element->isProvisionalDraft;

        DB::table(Table::ELEMENTACTIVITY)
            ->upsert([
                'elementId' => $element->getCanonicalId(),
                'userId' => $user->id,
                'siteId' => $element->siteId,
                'draftId' => $isCanonical ? null : $element->draftId,
                'type' => $type,
                'timestamp' => now(),
            ], ['elementId', 'userId', 'type']);
    }

    // Element classes
    // -------------------------------------------------------------------------

    /**
     * Returns all available element classes.
     *
     * @return string[] The available element classes.
     * @phpstan-return class-string<ElementInterface>[]
     */
    public function getAllElementTypes(): array
    {
        $elementTypes = [
            Address::class,
            Asset::class,
            Entry::class,
            User::class,
        ];

        // Fire a 'registerElementTypes' event
        if ($this->hasEventHandlers(self::EVENT_REGISTER_ELEMENT_TYPES)) {
            $event = new RegisterComponentTypesEvent(['types' => $elementTypes]);
            $this->trigger(self::EVENT_REGISTER_ELEMENT_TYPES, $event);
            return $event->types;
        }

        return $elementTypes;
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
                                        $elementsService->getIsCollectingCacheInfo() &&
                                        ($expiryDate = $element->getExpiryDate()) !== null
                                    ) {
                                        $elementsService->setCacheExpiryDate($expiryDate);
                                    }

                                    if ($limit && ++$count == $limit) {
                                        break 2;
                                    }
                                }
                            }
                        }

                        if (!empty($targetElementsForSource)) {
                            if (!empty($criteria['withProvisionalDrafts'])) {
                                ElementHelper::swapInProvisionalDrafts($targetElementsForSource);
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

        $this->ensureBulkOp(function() use ($element, $supportedSites, $siteId, &$siteElement) {
            $this->_propagateElement($element, $supportedSites, $siteId, $siteElement);

            // Track this element in bulk operations
            $this->trackElementInBulkOps($element);
        });

        // Clear caches
        $this->invalidateCachesForElement($element);

        return $siteElement;
    }

    /**
     * Saves an element.
     *
     * @param ElementInterface $element The element that is being saved
     * @param bool $runValidation Whether the element should be validated
     * @param bool $propagate Whether the element should be saved across all of its supported sites
     * @param bool|null $updateSearchIndex Whether to update the element search index for the element
     * (this will happen via a background job if this is a web request)
     * @param array|null $supportedSites The element’s supported site info, indexed by site ID
     * @param bool $forceTouch Whether to force the `dateUpdated` timestamp to be updated for the element,
     * regardless of whether it’s being resaved
     * @param bool $crossSiteValidate Whether the element should be validated across all supported sites
     * @param bool $saveContent Whether all the element’s content should be saved. When false (default) only dirty fields will be saved.
     * @param ElementSiteSettings|null $siteSettingsRecord
     *
     * @return bool
     * @throws ElementNotFoundException if $element has an invalid $id
     * @throws UnsupportedSiteException if the element is being saved for a site it doesn’t support
     * @throws Throwable if reasons
     */
    private function _saveElementInternal(
        ElementInterface $element,
        bool $runValidation = true,
        bool $propagate = true,
        ?bool $updateSearchIndex = null,
        ?array $supportedSites = null,
        bool $forceTouch = false,
        bool $crossSiteValidate = false,
        bool $saveContent = false,
        ?ElementSiteSettings &$siteSettingsRecord = null,
    ): bool {
        /** @var ElementInterface $element */
        $isNewElement = !$element->id;

        // Are we tracking changes?
        $trackChanges = ElementHelper::shouldTrackChanges($element);
        $dirtyAttributes = [];

        // Force propagation for new elements
        $propagate = $propagate && $element::isLocalized() && Sites::isMultiSite();
        $originalPropagateAll = $element->propagateAll;
        $originalFirstSave = $element->firstSave;
        $originalIsNewForSite = $element->isNewForSite;
        $originalDateUpdated = $element->dateUpdated;

        $element->firstSave = (
            !$element->getIsDraft() &&
            !$element->getIsRevision() &&
            ($element->firstSave || $isNewElement)
        );

        if ($isNewElement) {
            // Give it a UID right away
            $element->uid ??= Str::uuid()->toString();

            if (!$element->getIsDraft() && !$element->getIsRevision()) {
                // Let Matrix fields, etc., know they should be duplicating their values across all sites.
                $element->propagateAll = true;
            }
        }

        // Fire a 'beforeSaveElement' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_ELEMENT)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_ELEMENT, new ElementEvent([
                'element' => $element,
                'isNew' => $isNewElement,
            ]));
        }

        if (!$element->beforeSave($isNewElement)) {
            $element->firstSave = $originalFirstSave;
            $element->isNewForSite = $originalIsNewForSite;
            $element->propagateAll = $originalPropagateAll;
            return false;
        }

        // Get the sites supported by this element
        $supportedSites ??= Arr::keyBy(ElementHelper::supportedSitesForElement($element), 'siteId');

        // Make sure the element actually supports the site it's being saved in
        if (!isset($supportedSites[$element->siteId])) {
            $element->firstSave = $originalFirstSave;
            $element->isNewForSite = $originalIsNewForSite;
            $element->propagateAll = $originalPropagateAll;
            throw new UnsupportedSiteException($element, $element->siteId,
                'Attempting to save an element in an unsupported site.');
        }

        // If the element only supports a single site, ensure it's enabled for that site
        if (count($supportedSites) === 1 && !$element->getEnabledForSite()) {
            $element->enabled = false;
            $element->setEnabledForSite(true);
        }

        // If we're skipping validation, at least make sure the title is valid
        if (!$runValidation && $element::hasTitles()) {
            foreach ($element->getActiveValidators('title') as $validator) {
                $validator->validateAttributes($element, ['title']);
            }
            if ($element->errors()->has('title')) {
                // Set a default title
                if ($isNewElement) {
                    $element->title = t('New {type}', ['type' => $element::displayName()]);
                } else {
                    $element->title = $element::displayName() . ' ' . $element->id;
                }
            }
        }

        $fieldLayout = $element->getFieldLayout();
        $dirtyFields = $element->getDirtyFields();

        // Get the element's site record
        if (!$isNewElement && !$element->isNewForSite) {
            $siteSettingsRecord = ElementSiteSettings::query()
                ->where('elementId', $element->id)
                ->where('siteId', $element->siteId)
                ->first();
        }

        $element->isNewForSite = empty($siteSettingsRecord);

        // Validate
        if ($runValidation) {
            // If we're propagating, only validate changed custom fields,
            // unless we're enabling this element
            if ($element->propagating && !(
                    $element->getIsDerivative() &&
                    $element->getIsDraft() &&
                    $element->getEnabledForSite() &&
                    !$element->getCanonical()->getEnabledForSite())
            ) {
                $names = array_map(
                    fn(string $handle) => "field:$handle",
                    array_unique(array_merge($dirtyFields, $element->getModifiedFields())),
                );
            } else {
                $names = null;
            }

            if (($names === null || !empty($names)) && !$element->validate($names)) {
                Log::info('Element not saved due to validation error: ' . print_r($element->errors, true), [__METHOD__]);
                $element->firstSave = $originalFirstSave;
                $element->isNewForSite = $originalIsNewForSite;
                $element->propagateAll = $originalPropagateAll;
                return false;
            }
        }

        $success = $this->ensureBulkOp(function() use (
            $element,
            $isNewElement,
            $originalFirstSave,
            $originalIsNewForSite,
            $originalPropagateAll,
            $forceTouch,
            $saveContent,
            $trackChanges,
            $dirtyAttributes,
            $updateSearchIndex,
            $fieldLayout,
            $propagate,
            $supportedSites,
            $crossSiteValidate,
            $runValidation,
            $originalDateUpdated,
            $dirtyFields,
            &$siteSettingsRecord,
        ) {
            // Figure out whether we will be updating the search index (and memoize that for nested element saves)
            $oldUpdateSearchIndex = $this->_updateSearchIndex;
            $updateSearchIndex = $this->_updateSearchIndex = $updateSearchIndex ?? $this->_updateSearchIndex ?? true;

            $newSiteIds = $element->newSiteIds;
            $element->newSiteIds = [];

            DB::beginTransaction();

            try {
                // No need to save the element record multiple times
                if (!$element->propagating) {
                    // Get the element record
                    if (!$isNewElement) {
                        $elementModel = ElementModel::find($element->id);

                        if (!$elementModel) {
                            $element->firstSave = $originalFirstSave;
                            $element->isNewForSite = $originalIsNewForSite;
                            $element->propagateAll = $originalPropagateAll;
                            throw new ElementNotFoundException("No element exists with the ID '$element->id'");
                        }
                    } else {
                        $elementModel = new ElementModel();
                        $elementModel->type = $element::class;
                    }

                    // Set the attributes
                    $elementModel->uid = $element->uid;
                    $canonicalId = $element->getCanonicalId();
                    $elementModel->canonicalId = $canonicalId !== $element->id ? $canonicalId : null;
                    $elementModel->draftId = (int)$element->draftId ?: null;
                    $elementModel->revisionId = (int)$element->revisionId ?: null;
                    $elementModel->fieldLayoutId = $element->fieldLayoutId = (int)($element->fieldLayoutId ?? $fieldLayout->id ?? 0) ?: null;
                    $elementModel->enabled = (bool)$element->enabled;
                    $elementModel->archived = (bool)$element->archived;
                    $elementModel->dateLastMerged = Query::prepareDateForDb($element->dateLastMerged);
                    $elementModel->dateDeleted = Query::prepareDateForDb($element->dateDeleted);

                    if ($isNewElement) {
                        if (isset($element->dateCreated)) {
                            $elementModel->dateCreated = Query::prepareDateForDb($element->dateCreated);
                        }
                        if (isset($element->dateUpdated)) {
                            $elementModel->dateUpdated = Query::prepareDateForDb($element->dateUpdated);
                        }
                    } elseif (!$element->resaving || $forceTouch) {
                        // Force a new dateUpdated value
                        $elementModel->dateUpdated = now();
                    }

                    // Update our list of dirty attributes
                    if ($trackChanges) {
                        array_push($dirtyAttributes, ...array_keys(Arr::only($elementModel->getDirty(), [
                            'fieldLayoutId',
                            'enabled',
                            'archived',
                        ])));
                    }

                    // Save the element record
                    $elementModel->save();

                    $dateCreated = DateTimeHelper::toDateTime($elementModel->dateCreated);

                    if ($dateCreated === false) {
                        $element->firstSave = $originalFirstSave;
                        $element->isNewForSite = $originalIsNewForSite;
                        $element->propagateAll = $originalPropagateAll;
                        throw new Exception('There was a problem calculating dateCreated.');
                    }

                    $dateUpdated = DateTimeHelper::toDateTime($elementModel->dateUpdated);

                    if ($dateUpdated === false) {
                        throw new Exception('There was a problem calculating dateUpdated.');
                    }

                    // Save the new dateCreated and dateUpdated dates on the model
                    $element->dateCreated = $dateCreated;
                    $element->dateUpdated = $dateUpdated;

                    if ($isNewElement) {
                        // Save the element ID on the element model
                        $element->id = $elementModel->id;

                        // If there's a temp ID, update the URI
                        if ($element->tempId && $element->uri) {
                            $element->uri = str_replace($element->tempId, (string)$element->id, $element->uri);
                            $element->tempId = null;
                        }
                    }
                }

                // Save the element’s site settings record
                if ($siteSettingsRecord === null) {
                    // First time we've saved the element for this site
                    $siteSettingsRecord = new ElementSiteSettings();
                    $siteSettingsRecord->elementId = $element->id;
                    $siteSettingsRecord->siteId = $element->siteId;
                }

                $title = $element::hasTitles() ? $element->title : null;
                $siteSettingsRecord->title = $title !== null && $title !== '' ? $title : null;
                $siteSettingsRecord->slug = $element->slug;
                $siteSettingsRecord->uri = $element->uri;

                // Avoid `enabled` getting marked as dirty if it’s not really changing
                $enabledForSite = $element->getEnabledForSite();
                if (!$siteSettingsRecord->exists || $siteSettingsRecord->enabled !== $enabledForSite) {
                    $siteSettingsRecord->enabled = $enabledForSite;
                }

                // Update our list of dirty attributes
                if ($trackChanges && !$element->isNewForSite) {
                    array_push($dirtyAttributes, ...array_keys(Arr::only($siteSettingsRecord->getDirty(), [
                        'slug',
                        'uri',
                    ])));
                    if ($siteSettingsRecord->isDirty('enabled')) {
                        $dirtyAttributes[] = 'enabledForSite';
                    }
                }

                $saveContent = $saveContent || $element->isNewForSite;
                $generatedFields = $fieldLayout?->getGeneratedFields() ?? [];

                if ($saveContent || !empty($dirtyFields) || !empty($generatedFields)) {
                    $oldContent = $siteSettingsRecord->content ?? []; // we'll need that if we're not saving all the content
                    if (is_string($oldContent)) {
                        $oldContent = $oldContent !== '' ? Json::decode($oldContent) : [];
                    }

                    $content = [];

                    if ($fieldLayout) {
                        $validUids = [];

                        foreach ($fieldLayout->getCustomFields() as $field) {
                            $validUids[$field->layoutElement->uid] = true;

                            if (($saveContent || in_array($field->handle, $dirtyFields)) && $field::dbType() !== null) {
                                $value = $element->getFieldValue($field->handle);
                                if ($element->isNewForSite && $field->isValueEmpty($value, $element)) {
                                    // don't store empty values if element is new for site
                                    // https://github.com/craftcms/cms/issues/16797
                                    continue;
                                }
                                $serializedValue = $field->serializeValueForDb($value, $element);
                                if ($serializedValue !== null) {
                                    $content[$field->layoutElement->uid] = $serializedValue;
                                } elseif (!$saveContent) {
                                    // if serialized value is null, and we're not saving all the content,
                                    // we need to register the fact that the new value is empty
                                    unset($oldContent[$field->layoutElement->uid]);
                                }
                            }
                        }

                        if ($oldContent) {
                            foreach ($generatedFields as $field) {
                                if (isset($oldContent[$field['uid']])) {
                                    $content[$field['uid']] = $oldContent[$field['uid']];
                                }
                            }
                        }
                    }

                    // if we're only saving dirty fields, merge in the existing values,
                    // excluding any UUIDs that are no longer valid (see https://github.com/craftcms/cms/issues/17768)
                    if (!$saveContent && $oldContent) {
                        foreach ($oldContent as $uid => $value) {
                            if (!isset($content[$uid]) && isset($validUids[$uid])) {
                                $content[$uid] = $value;
                            }
                        }
                    }

                    $siteSettingsRecord->content = $content ?: null;
                }

                // Save the site settings record
                if (!$siteSettingsRecord->save()) {
                    $element->firstSave = $originalFirstSave;
                    $element->isNewForSite = $originalIsNewForSite;
                    $element->propagateAll = $originalPropagateAll;
                    throw new Exception('Couldn’t save elements’ site settings record.');
                }

                $element->siteSettingsId = $siteSettingsRecord->id;

                // Set all of the dirty attributes on the element, in case an event listener wants to know
                if ($trackChanges) {
                    array_push($dirtyAttributes, ...$element->getDirtyAttributes());
                    $element->setDirtyAttributes($dirtyAttributes, false);
                }

                // It is now officially saved
                $element->afterSave($isNewElement);

                // Update the list of dirty attributes
                $dirtyAttributes = $element->getDirtyAttributes();

                /** @var array<int,ElementInterface> $siteElements */
                $siteElements = [];
                /** @var array<int,ElementSiteSettings> $siteSettingsRecords */
                $siteSettingsRecords = [];

                // Update the element across the other sites?
                if ($propagate) {
                    $otherSiteIds = array_keys(Arr::except($supportedSites, $element->siteId));

                    if (!empty($otherSiteIds)) {
                        if (!$isNewElement) {
                            $siteElements = $this->_localizedElementQuery($element)
                                ->siteId($otherSiteIds)
                                ->status(null)
                                ->indexBy('siteId')
                                ->all();
                        }

                        foreach (array_keys($supportedSites) as $siteId) {
                            // Skip the initial site
                            if ($siteId != $element->siteId) {
                                $siteElement = $siteElements[$siteId] ?? false;
                                $siteElementRecord = null;
                                if (!$this->_propagateElement(
                                    $element,
                                    $supportedSites,
                                    $siteId,
                                    $siteElement,
                                    crossSiteValidate: $runValidation && $crossSiteValidate,
                                    siteSettingsRecord: $siteElementRecord,
                                )) {
                                    throw new InvalidConfigException();
                                }
                                $siteElements[$siteId] = $siteElement;
                                $siteSettingsRecords[$siteId] = $siteElementRecord;
                            }
                        }
                    }
                }

                // Save the generated fields after the element has been fully propagated,
                // so Matrix/CB/etc. have had a chance to save their data via afterElementPropagate()
                // (see https://github.com/craftcms/cms/issues/17938)
                if (!$element->propagating && !empty($generatedFields)) {
                    $siteElements[$element->siteId] = $element;
                    $siteSettingsRecords[$element->siteId] = $siteSettingsRecord;

                    Event::listen(function(AfterPropagate $event) use ($element, $generatedFields, $siteElements, $siteSettingsRecords) {
                        if ($event->element->id !== $element->id) {
                            return;
                        }

                        foreach ($siteElements as $siteId => $siteElement) {
                            $siteSettingsRecord = $siteSettingsRecords[$siteId];
                            $content = $siteSettingsRecord->content ?? [];
                            if (is_string($content)) {
                                $content = $content !== '' ? Json::decode($content) : [];
                            }
                            $generatedFieldValues = [];
                            $updated = false;

                            foreach ($generatedFields as $field) {
                                $value = renderObjectTemplate($field['template'] ?? '', $siteElement);

                                // handle 'true'/'false'/'null'/int/float values
                                $value = normalizeValue($value) ?? '';

                                if ($value !== ($content[$field['uid']] ?? '')) {
                                    $updated = true;
                                }
                                if ($value !== '') {
                                    $content[$field['uid']] = $value;
                                    if (($field['handle'] ?? '') !== '') {
                                        $generatedFieldValues[$field['handle']] = $value;
                                    }
                                } else {
                                    unset($content[$field['uid']]);
                                }
                            }

                            if ($updated) {
                                $siteSettingsRecord->content = $content;
                                $siteSettingsRecord->save();
                                $siteElement->setGeneratedFieldValues($generatedFieldValues);
                            }
                        }
                    });
                }

                // It's now fully saved and propagated
                if (
                    !$element->propagating &&
                    !$element->duplicateOf &&
                    !$element->mergingCanonicalChanges
                ) {
                    $element->afterPropagate($isNewElement);

                    // Track this element in bulk operations
                    $this->trackElementInBulkOps($element);
                }

                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();
                $element->firstSave = $originalFirstSave;
                $element->isNewForSite = $originalIsNewForSite;
                $element->propagateAll = $originalPropagateAll;
                $element->dateUpdated = $originalDateUpdated;
                if ($e instanceof InvalidConfigException) {
                    return false;
                }
                throw $e;
            } finally {
                $this->_updateSearchIndex = $oldUpdateSearchIndex;
                $element->newSiteIds = $newSiteIds;
            }

            if (!$element->propagating) {
                // Delete the rows that don't need to be there anymore
                if (!$isNewElement) {
                    $deleteCondition = fn(Builder $query) => $query
                        ->where('elementId', $element->id)
                         ->whereNotIn('siteId', array_keys($supportedSites));

                    DB::table(Table::ELEMENTS_SITES)->where($deleteCondition)->delete();
                    DB::table(Table::SEARCHINDEX)->where($deleteCondition)->delete();
                    DB::table(Table::SEARCHINDEXQUEUE)->where($deleteCondition)->delete();
                }

                // Invalidate any caches involving this element
                $this->invalidateCachesForElement($element);
            }

            // Update search index
            if ($updateSearchIndex && !$element->getIsRevision() && !ElementHelper::isRevision($element)) {
                $searchableDirtyFields = array_filter(
                    $dirtyFields,
                    fn(string $handle) => $fieldLayout?->getFieldByHandle($handle)?->searchable,
                );

                if (
                    !$trackChanges ||
                    !empty($searchableDirtyFields) ||
                    !empty(array_intersect($dirtyAttributes, ElementHelper::searchableAttributes($element)))
                ) {
                    // Fire a 'beforeUpdateSearchIndex' event
                    if ($this->hasEventHandlers(self::EVENT_BEFORE_UPDATE_SEARCH_INDEX)) {
                        $event = new ElementEvent(['element' => $element]);
                        $this->trigger(self::EVENT_BEFORE_UPDATE_SEARCH_INDEX, $event);
                        $isValid = $event->isValid;
                    } else {
                        $isValid = true;
                    }

                    if ($isValid) {
                        $this->updateSearchIndex($element, $searchableDirtyFields, $propagate);
                    }
                }
            }

            // Update the changed attributes & fields
            if ($trackChanges) {
                $userId = Craft::$app->getUser()->getId();
                $timestamp = now();

                foreach ($dirtyAttributes as $attributeName) {
                    DB::table(Table::CHANGEDATTRIBUTES)
                        ->upsert([
                            'elementId' => $element->id,
                            'siteId' => $element->siteId,
                            'attribute' => $attributeName,
                            'dateUpdated' => $timestamp,
                            'propagated' => $element->propagating,
                            'userId' => $userId,
                        ], ['elementId', 'siteId', 'attribute']);
                }

                if ($fieldLayout) {
                    foreach ($dirtyFields as $fieldHandle) {
                        if (($field = $fieldLayout->getFieldByHandle($fieldHandle)) !== null) {
                            DB::table(Table::CHANGEDFIELDS)
                                ->upsert([
                                    'elementId' => $element->id,
                                    'siteId' => $element->siteId,
                                    'fieldId' => $field->id,
                                    'layoutElementUid' => $field->layoutElement->uid,
                                    'dateUpdated' => $timestamp,
                                    'propagated' => $element->propagating,
                                    'userId' => $userId,
                                ], ['elementId', 'siteId', 'fieldId', 'layoutElementUid']);
                        }
                    }
                }
            }

            return true;
        });

        if (!$success) {
            return false;
        }

        // Fire an 'afterSaveElement' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_ELEMENT)) {
            $this->trigger(self::EVENT_AFTER_SAVE_ELEMENT, new ElementEvent([
                'element' => $element,
                'isNew' => $isNewElement,
            ]));
        }

        // Clear the element’s record of dirty fields
        $element->markAsClean();
        $element->firstSave = $originalFirstSave;
        $element->isNewForSite = $originalIsNewForSite;
        $element->propagateAll = $originalPropagateAll;

        return true;
    }

    private function updateSearchIndex(
        ElementInterface $element,
        array $searchableDirtyFields,
        bool $propagate,
        ?bool $updateForOwner = null,
    ): void {
        if ($element->updateSearchIndexImmediately ?? app()->runningInConsole()) {
            Search::indexElementAttributes($element, $searchableDirtyFields);
        } else {
            Search::queueIndexElement($element, $searchableDirtyFields);
        }

        $updateForOwner = (
            $element instanceof NestedElementInterface &&
            ($field = $element->getField()) &&
            $field->searchable &&
            ($updateForOwner ??
                $element->getIsCanonical() &&
                isset($element->fieldId) &&
                isset($element->updateSearchIndexForOwner) &&
                $element->updateSearchIndexForOwner
            )
        );

        if ($updateForOwner) {
            /** @var NestedElementInterface $element */
            $owner = $element->getOwner();
            if ($owner) {
                $this->updateSearchIndex($owner, [$field->handle], $propagate, true);
                $this->invalidateCachesForElement($owner);
            }
        }
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
        // Make sure the element actually supports the site it's being saved in
        if (!isset($supportedSites[$siteId])) {
            throw new UnsupportedSiteException($element, $siteId,
                'Attempting to propagate an element to an unsupported site.');
        }

        $siteInfo = $supportedSites[$siteId];

        // Try to fetch the element in this site
        if ($siteElement === null && $element->id) {
            /** @phpstan-ignore-next-line */
            $siteElement = $this->getElementById($element->id, get_class($element), $siteInfo['siteId']);
        } elseif (!$siteElement) {
            /** @phpstan-ignore-next-line */
            $siteElement = null;
        }

        // If it doesn't exist yet, just clone the initial site
        if ($siteElement === null) {
            $siteElement = clone $element;
            $siteElement->siteId = $siteInfo['siteId'];
            $siteElement->siteSettingsId = null;
            $siteElement->setEnabledForSite($siteInfo['enabledByDefault']);
            // set isNewForSite to true unless we're reverting content from a revision
            // in which case, it's possible that the canonical element exists for the site already,
            // but didn't back when the revision was created.
            // (see https://github.com/craftcms/cms/issues/15679)
            $siteElement->isNewForSite = !$siteElement->duplicateOf?->getIsRevision();

            // Keep track of this new site ID
            $element->newSiteIds[] = $siteInfo['siteId'];
        } elseif ($element->propagateAll) {
            $oldSiteElement = $siteElement;
            $siteElement = clone $element;
            $siteElement->siteId = $oldSiteElement->siteId;
            $siteElement->setEnabledForSite($oldSiteElement->getEnabledForSite());
            $siteElement->uri = $oldSiteElement->uri;
        } else {
            $siteElement->enabled = $element->enabled;
            $siteElement->resaving = $element->resaving;
        }

        // Does the main site's element specify a status for this site?
        $enabledForSite = $element->getEnabledForSite($siteElement->siteId);
        if ($enabledForSite !== null) {
            $siteElement->setEnabledForSite($enabledForSite);
        }

        // Copy the timestamps
        $siteElement->dateCreated = $element->dateCreated;
        $siteElement->dateUpdated = $element->dateUpdated;

        // Copy the title value?
        if (
            $element::hasTitles() &&
            (
                $siteElement->getTitleTranslationKey() === $element->getTitleTranslationKey() ||
                ($element->propagateRequired && empty($siteElement->title))
            )
        ) {
            $siteElement->title = $element->title;
        }

        // Copy the slug value?
        if (
            $element->slug !== null &&
            (
                $siteElement->getSlugTranslationKey() === $element->getSlugTranslationKey() ||
                ($element->propagateRequired && empty($siteElement->slug))
            )
        ) {
            $siteElement->slug = $element->slug;
        }

        // Ensure the uri is properly localized
        // see https://github.com/craftcms/cms/issues/13812 for more details
        if (
            $element::hasUris() &&
            (
                $siteElement->isNewForSite ||
                in_array('uri', $element->getDirtyAttributes()) ||
                $element->resaving
            )
        ) {
            // Set a unique URI on the site clone
            try {
                $this->setElementUri($siteElement);
            } catch (OperationAbortedException) {
                // carry on
            }
        }

        // Save it
        $siteElement->setScenario(Element::SCENARIO_ESSENTIALS);

        // validate element against "live" scenario across all sites, if element is enabled for the site
        if (
            ($crossSiteValidate || $element->propagateRequired) &&
            $siteElement->enabled &&
            $siteElement->getEnabledForSite()
        ) {
            $siteElement->setScenario(Element::SCENARIO_LIVE);
        }


        // Copy the dirty attributes (except title, slug and uri, which may be translatable)
        $siteElement->setDirtyAttributes(array_filter($element->getDirtyAttributes(),
            fn(string $attribute): bool => $attribute !== 'title' && $attribute !== 'slug'));

        if ($saveContent) {
            // Copy any non-translatable field values
            if ($siteElement->isNewForSite) {
                // Copy all the field values
                $siteElement->setFieldValues($element->getFieldValues());
            } else {
                $fieldLayout = $element->getFieldLayout();

                if ($fieldLayout !== null) {
                    foreach ($fieldLayout->getCustomFields() as $field) {
                        if (
                            $element->propagateAll ||
                            // If propagateRequired is set, is the field value invalid on the propagated site element?
                            (
                                $element->propagateRequired &&
                                $field->layoutElement->required &&
                                $field->isValueEmpty($siteElement->getFieldValue($field->handle), $siteElement)
                            ) ||
                            // Has this field changed, and does it produce the same translation key as it did for the initial element?
                            (
                                $element->isFieldDirty($field->handle) &&
                                $field->getTranslationKey($siteElement) === $field->getTranslationKey($element)
                            )
                        ) {
                            $field->propagateValue($element, $siteElement);
                        }
                    }
                }
            }
        }

        $siteElement->propagating = true;
        $siteElement->propagatingFrom = $element;

        $success = $this->_saveElementInternal(
            $siteElement,
            $crossSiteValidate,
            false,
            supportedSites: $supportedSites,
            saveContent: $saveContent,
            siteSettingsRecord: $siteSettingsRecord,
        );

        if (!$success) {
            // if the element we're trying to save has validation errors, notify original element about them
            if ($siteElement->errors()->isNotEmpty()) {
                return $this->_crossSiteValidationErrors($siteElement, $element);
            } else {
                // Log the errors
                $error = 'Couldn’t propagate element to other site due to validation errors:';
                foreach ($siteElement->errors()->all() as $attributeError) {
                    $error .= "\n- " . $attributeError;
                }
                Log::error($error);
                throw new Exception('Couldn’t propagate element to other site.');
            }
        }

        return true;
    }

    /**
     * @param ElementInterface $siteElement
     * @param ElementInterface $element
     *
     * @return bool
     * @throws Throwable
     */
    private function _crossSiteValidationErrors(
        ElementInterface $siteElement,
        ElementInterface $element,
    ): bool {
        // get site we're propagating to
        $propagateToSite = Sites::getSiteById($siteElement->siteId);
        $user = Auth::user();
        $message = t('Validation errors for site: “{siteName}“', [
            'siteName' => $propagateToSite?->getName(),
        ]);

        // check user can edit this element for the site that throws validation error on propagation
        if ($user &&
            Sites::isMultiSite() &&
            $user->can("editSite:{$propagateToSite?->uid}") &&
            $siteElement->canSave($user)
        ) {
            $queryParams = Arr::except(Craft::$app->getRequest()->getQueryParams(), 'site');
            $url = URL::url($siteElement->getCpEditUrl(), $queryParams + ['prevalidate' => 1]);
            $message = Html::beginTag('a', [
                    'href' => $url,
                    'class' => 'cross-site-validate',
                    'target' => '_blank',
                ]) .
                $message .
                Html::tag('span', '', [
                    'data-icon' => 'external',
                    'aria-label' => t('Open in a new tab'),
                    'role' => 'img',
                ]) .
                Html::endTag('a');
        }

        $element->errors()->add('global', $message);

        return false;
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
}
