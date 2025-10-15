<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Element;
use craft\base\ElementActionInterface;
use craft\base\ElementExporterInterface;
use craft\base\ElementInterface;
use craft\base\ExpirableElementInterface;
use craft\base\FieldInterface;
use craft\base\NestedElementInterface;
use craft\behaviors\CustomFieldBehavior;
use craft\behaviors\DraftBehavior;
use craft\console\controllers\MigrateController;
use craft\console\controllers\UpController;
use craft\controllers\AppController;
use craft\db\Connection;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\db\Table;
use craft\elements\Address;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\db\EagerLoadInfo;
use craft\elements\db\EagerLoadPlan;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\ElementCollection;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\Tag;
use craft\elements\User;
use craft\errors\ElementNotFoundException;
use craft\errors\FieldNotFoundException;
use craft\errors\InvalidElementException;
use craft\errors\OperationAbortedException;
use craft\errors\SiteNotFoundException;
use craft\errors\UnsupportedSiteException;
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
use craft\fieldlayoutelements\CustomField;
use craft\fields\BaseRelationField;
use craft\helpers\ArrayHelper;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Queue;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\i18n\Translation;
use craft\models\ElementActivity;
use craft\queue\jobs\FindAndReplace;
use craft\queue\jobs\UpdateElementSlugsAndUris;
use craft\records\Element as ElementRecord;
use craft\records\Element_SiteSettings as Element_SiteSettingsRecord;
use craft\records\StructureElement as StructureElementRecord;
use craft\validators\HandleValidator;
use craft\validators\SlugValidator;
use DateTime;
use Illuminate\Support\Collection;
use Throwable;
use UnitEnum;
use yii\base\Behavior;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\caching\TagDependency;
use yii\di\Instance;
use yii\web\ForbiddenHttpException;

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
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->bulkOpDb = Instance::ensure($this->bulkOpDb, Connection::class);
    }

    /**
     * Creates an element with a given config.
     *
     * @template T of ElementInterface
     * @param class-string<T>|array $config The element’s class name, or its config, with a `type` value
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
     * @return ElementQueryInterface The element query
     * @throws InvalidArgumentException if $elementType is not a valid element
     * @since 3.5.0
     */
    public function createElementQuery(string $elementType): ElementQueryInterface
    {
        if (!is_subclass_of($elementType, ElementInterface::class)) {
            throw new InvalidArgumentException("$elementType is not a valid element.");
        }

        return $elementType::find();
    }

    /**
     * @var Connection|array|string the DB connection object or the application component ID of the DB connection
     * that should be used to store element bulk op records.
     * @since 5.3.0
     */
    public Connection|array|string $bulkOpDb = 'db2';

    // Element caches
    // -------------------------------------------------------------------------

    /**
     * @var array[]
     */
    private array $_cacheTagBuffers = [];

    /**
     * @var string[]|null
     */
    private ?array $_cacheTags = null;

    /**
     * @var array
     * @phpstan-var array<int|null>
     */
    private array $_cacheDurationBuffers = [];

    private ?int $_cacheDuration = null;

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
        return isset($this->_cacheTags);
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
        // Save any currently-collected info into new buffers
        if (isset($this->_cacheTags)) {
            $this->_cacheTagBuffers[] = $this->_cacheTags;
            $this->_cacheDurationBuffers[] = $this->_cacheDuration;
        }

        $this->_cacheTags = [];
        $this->_cacheDuration = null;
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
     * @since 3.5.0
     */
    public function collectCacheTags(array $tags): void
    {
        // Ignore if we're not currently collecting tags
        if (!isset($this->_cacheTags)) {
            return;
        }

        // Element query tags
        foreach ($tags as $tag) {
            $this->_cacheTags[$tag] = true;
        }
    }

    /**
     * Sets a possible cache expiration date that [[stopCollectingCacheInfo()]] should return.
     *
     * The value will only be used if it is less than the currently stored expiration date.
     *
     * @param DateTime $expiryDate
     * @since 4.3.0
     */
    public function setCacheExpiryDate(DateTime $expiryDate): void
    {
        if (!isset($this->_cacheTags)) {
            return;
        }

        $duration = $expiryDate->getTimestamp() - DateTimeHelper::currentTimeStamp();

        if ($duration > 0 && (!$this->_cacheDuration || $duration < $this->_cacheDuration)) {
            $this->_cacheDuration = $duration;
        }
    }

    /**
     ** Stores cache invalidation info for a given element.
     *
     * @param ElementInterface $element
     * @since 4.5.0
     */
    public function collectCacheInfoForElement(ElementInterface $element): void
    {
        // Ignore if we're not currently collecting tags
        if (!isset($this->_cacheTags)) {
            return;
        }

        $class = get_class($element);
        $this->collectCacheTags([
            'element',
            "element::$class",
            "element::$element->id",
        ]);

        // If the element is expirable, register its expiry date
        if (
            $element instanceof ExpirableElementInterface &&
            ($expiryDate = $element->getExpiryDate()) !== null
        ) {
            $this->setCacheExpiryDate($expiryDate);
        }
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
        if (!isset($this->_cacheTags)) {
            throw new InvalidCallException('Element cache invalidation tags are not currently being collected.');
        }

        $tags = $this->_cacheTags;
        $duration = $this->_cacheDuration;

        // Was there another active collection?
        if (!empty($this->_cacheTagBuffers)) {
            $this->_cacheTags = array_merge(array_pop($this->_cacheTagBuffers), $tags);

            // Override the parent duration if ours is shorter
            $this->_cacheDuration = array_pop($this->_cacheDurationBuffers);
            if ($duration && $duration < $this->_cacheDuration) {
                $this->_cacheDuration = $duration;
            }
        } else {
            $this->_cacheTags = null;
            $this->_cacheDuration = null;
        }

        if (empty($tags)) {
            return [null, null];
        }

        // Only use the duration if it's less than the cacheDuration config setting
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if ($generalConfig->cacheDuration) {
            if ($duration) {
                $duration = min($duration, $generalConfig->cacheDuration);
            } else {
                $duration = $generalConfig->cacheDuration;
            }
        }

        $dep = new TagDependency([
            'tags' => array_keys($tags),
        ]);

        return [$dep, $duration];
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
     */
    public function invalidateAllCaches(): void
    {
        $tags = ['element'];
        TagDependency::invalidate(Craft::$app->getCache(), $tags);

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
     * @since 3.5.0
     */
    public function invalidateCachesForElementType(string $elementType): void
    {
        $tags = ["element::$elementType"];
        TagDependency::invalidate(Craft::$app->getCache(), $tags);

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

        TagDependency::invalidate(Craft::$app->getCache(), $tags);

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
     * @return T|null The matching element, or `null`.
     */
    public function getElementById(int $elementId, ?string $elementType = null, array|int|string $siteId = null, array $criteria = []): ?ElementInterface
    {
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
     * @return T|null The matching element, or `null`.
     * @since 3.5.13
     */
    public function getElementByUid(string $uid, ?string $elementType = null, array|int|string $siteId = null, array $criteria = []): ?ElementInterface
    {
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
     * @return T|null The matching element, or `null`.
     */
    private function _elementById(string $property, int|string $elementId, ?string $elementType = null, array|int|string $siteId = null, array $criteria = []): ?ElementInterface
    {
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
        Craft::configure($query, $criteria);

        return $query->one();
    }

    /**
     * Returns an element by its URI.
     *
     * @param string $uri The element’s URI.
     * @param int|null $siteId The site to look for the URI in, and to return the element in.
     * Defaults to the current site.
     * @param bool $enabledOnly Whether to only look for an enabled element. Defaults to `false`.
     * @return ElementInterface|null The matching element, or `null`.
     */
    public function getElementByUri(string $uri, ?int $siteId = null, bool $enabledOnly = false): ?ElementInterface
    {
        if ($uri === '') {
            $uri = Element::HOMEPAGE_URI;
        }

        if ($siteId === null) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        }

        // See if we already have a placeholder for this element URI
        if (isset($this->_placeholderUris[$uri][$siteId])) {
            return $this->_placeholderUris[$uri][$siteId];
        }

        // First get the element ID and type
        $query = (new Query())
            ->select(['elements.id', 'elements.type'])
            ->from(['elements' => Table::ELEMENTS])
            ->innerJoin(['elements_sites' => Table::ELEMENTS_SITES], '[[elements_sites.elementId]] = [[elements.id]]')
            ->where([
                'elements.draftId' => null,
                'elements.revisionId' => null,
                'elements.dateDeleted' => null,
                'elements_sites.siteId' => $siteId,
            ]);

        if (Craft::$app->getDb()->getIsMysql()) {
            $query->andWhere([
                'elements_sites.uri' => $uri,
            ]);
        } else {
            $query->andWhere([
                'lower([[elements_sites.uri]])' => mb_strtolower($uri),
            ]);
        }

        if ($enabledOnly) {
            $query->andWhere([
                'elements_sites.enabled' => true,
                'elements.enabled' => true,
                'elements.archived' => false,
            ]);
        }

        $result = $query->one();
        return $result ? $this->getElementById($result['id'], $result['type'], $siteId) : null;
    }

    /**
     * Returns the class of an element with a given ID.
     *
     * @param int $elementId The element’s ID
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
     * @return string|null The element’s class, or null if it could not be found
     */
    private function _elementTypeById(string $property, int|string $elementId): ?string
    {
        $class = (new Query())
            ->select(['type'])
            ->from([Table::ELEMENTS])
            ->where([$property => $elementId])
            ->scalar();

        return $class !== false ? $class : null;
    }

    /**
     * Returns the classes of elements with the given IDs.
     *
     * @param int[] $elementIds The elements’ IDs
     * @return string[]
     */
    public function getElementTypesByIds(array $elementIds): array
    {
        return (new Query())
            ->select(['type'])
            ->distinct(true)
            ->from([Table::ELEMENTS])
            ->where(['id' => $elementIds])
            ->column();
    }

    /**
     * Returns an element’s URI for a given site.
     *
     * @param int $elementId The element’s ID.
     * @param int $siteId The site to search for the element’s URI in.
     * @return string|null|false The element’s URI or `null`, or `false` if the element doesn’t exist.
     */
    public function getElementUriForSite(int $elementId, int $siteId): string|null|false
    {
        return (new Query())
            ->select(['uri'])
            ->from([Table::ELEMENTS_SITES])
            ->where(['elementId' => $elementId, 'siteId' => $siteId])
            ->scalar();
    }

    /**
     * Returns the site IDs that a given element is enabled in.
     *
     * @param int $elementId The element’s ID.
     * @return int[] The site IDs that the element is enabled in. If the element could not be found, an empty array
     * will be returned.
     */
    public function getEnabledSiteIdsForElement(int $elementId): array
    {
        return (new Query())
            ->select(['siteId'])
            ->from([Table::ELEMENTS_SITES])
            ->where(['elementId' => $elementId, 'enabled' => true])
            ->column();
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
        $key = StringHelper::randomString(10);

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
            Db::delete(Table::ELEMENTS_BULKOPS, ['key' => $key], db: $this->bulkOpDb);
        }
    }

    /**
     * Tracks an element as being affected by any active bulk operations.
     *
     * @param ElementInterface $element
     * @since 5.0.0
     */
    public function trackElementInBulkOps(ElementInterface $element): void
    {
        if (empty($this->bulkKeys) || $this->isMigrationRequest()) {
            return;
        }

        $timestamp = Db::prepareDateForDb(DateTimeHelper::now());

        foreach (array_keys($this->bulkKeys) as $key) {
            Db::upsert(Table::ELEMENTS_BULKOPS, [
                'elementId' => $element->id,
                'key' => $key,
                'timestamp' => $timestamp,
            ], db: $this->bulkOpDb);
        }
    }

    private function isMigrationRequest(): bool
    {
        return (
            Craft::$app->controller instanceof MigrateController ||
            Craft::$app->controller instanceof UpController ||
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
     * @since 5.3.0
     */
    public function ensureBulkOp(callable $callback): void
    {
        if (empty($this->bulkKeys)) {
            $bulkKey = $this->beginBulkOp();
        }

        try {
            $callback();
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
     *     Craft::error('Couldn’t save the entry "'.$entry->title.'"', __METHOD__);
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
        $supportedSites = ArrayHelper::index(ElementHelper::supportedSitesForElement($element), 'siteId');
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
            Craft::$app->getDb()->transaction(function() use ($element, $supportedSites) {
                // Start with the other sites (if any), so we don't update dateLastMerged until the end
                $otherSiteIds = ArrayHelper::withoutValue(array_keys($supportedSites), $element->siteId);
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

        $changedAttributes = (new Query())
            ->select(['siteId', 'attribute', 'propagated', 'userId'])
            ->from([Table::CHANGEDATTRIBUTES])
            ->where(['elementId' => $element->id])
            ->all();

        $changedFields = (new Query())
            ->select(['siteId', 'fieldId', 'layoutElementUid', 'propagated', 'userId'])
            ->from([Table::CHANGEDFIELDS])
            ->where(['elementId' => $element->id])
            ->all();

        $newAttributes += [
            'id' => $canonical->id,
            'uid' => $canonical->uid,
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
            $newAttributes['siteAttributes'][$attribute['siteId']]['dirtyAttributes'][] = $attribute['attribute'];
        }

        foreach ($changedFields as $changedField) {
            $layoutElement = $element->getFieldLayout()?->getElementByUid($changedField['layoutElementUid']);
            if ($layoutElement instanceof CustomField) {
                try {
                    $field = $layoutElement->getField();
                } catch (FieldNotFoundException) {
                    continue;
                }
                $newAttributes['siteAttributes'][$changedField['siteId']]['dirtyFields'][] = $field->handle;
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

        Craft::$app->onAfterRequest(function() use ($canonical, $updatedCanonical, $changedAttributes, $changedFields) {
            // Update change tracking for the canonical element
            $timestamp = Db::prepareDateForDb($updatedCanonical->dateUpdated);

            foreach ($changedAttributes as $attribute) {
                Db::upsert(Table::CHANGEDATTRIBUTES, [
                    'elementId' => $canonical->id,
                    'siteId' => $attribute['siteId'],
                    'attribute' => $attribute['attribute'],
                    'dateUpdated' => $timestamp,
                    'propagated' => $attribute['propagated'],
                    'userId' => $attribute['userId'],
                ]);
            }

            foreach ($changedFields as $field) {
                Db::upsert(Table::CHANGEDFIELDS, [
                    'elementId' => $canonical->id,
                    'siteId' => $field['siteId'],
                    'fieldId' => $field['fieldId'],
                    'layoutElementUid' => $field['layoutElementUid'],
                    'dateUpdated' => $timestamp,
                    'propagated' => $field['propagated'],
                    'userId' => $field['userId'],
                ]);
            }
        });

        return $updatedCanonical;
    }

    /**
     * Resaves all elements that match a given element query.
     *
     * @param ElementQueryInterface $query The element query to fetch elements with
     * @param bool $continueOnError Whether to continue going if an error occurs
     * @param bool $skipRevisions Whether elements that are (or belong to) a revision should be skipped
     * @param bool|null $updateSearchIndex Whether to update the element search index for the element
     * (this will happen via a background job if this is a web request)
     * @param bool $touch Whether to update the `dateUpdated` timestamps for the elements
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
                foreach (Db::each($query) as $element) {
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
                            $label = $label !== '' ? "$label ($element->id)" : sprintf('%s %s', $element::lowerDisplayName(), $element->id);
                            try {
                                if (ElementHelper::isRevision($element)) {
                                    throw new InvalidElementException($element, "Skipped resaving $label because it's a revision.");
                                }
                            } catch (Throwable $rootException) {
                                throw new InvalidElementException($element, "Skipped resaving $label due to an error obtaining its root element: " . $rootException->getMessage());
                            }
                        }

                        $this->_saveElementInternal($element, true, true, $updateSearchIndex, forceTouch: $touch, saveContent: true);
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
     * @throws Throwable if reasons
     * propagated to all supported sites, except the one they were queried in.
     * @since 3.2.0
     */
    public function propagateElements(ElementQueryInterface $query, array|int $siteIds = null, bool $continueOnError = false): void
    {
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
                foreach (Db::each($query) as $element) {
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
                    $supportedSites = ArrayHelper::index(ElementHelper::supportedSitesForElement($element), 'siteId');
                    $supportedSiteIds = array_keys($supportedSites);
                    $elementSiteIds = $siteIds !== null ? array_intersect($siteIds, $supportedSiteIds) : $supportedSiteIds;
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
        $mainClone->uid = StringHelper::UUID();
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

        $behaviors = ArrayHelper::remove($newAttributes, 'behaviors', []);
        $mainClone->setRevisionNotes(ArrayHelper::remove($newAttributes, 'revisionNotes'));

        // Extract any attributes that are meant for other sites
        $siteAttributes = ArrayHelper::remove($newAttributes, 'siteAttributes') ?? [];

        // Note: must use Craft::configure() rather than setAttributes() here,
        // so we're not limited to whatever attributes() returns
        Craft::configure($mainClone, ArrayHelper::merge(
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
        $supportedSites = ArrayHelper::index(ElementHelper::supportedSitesForElement($mainClone), 'siteId');
        if (!isset($supportedSites[$mainClone->siteId])) {
            throw new UnsupportedSiteException($element, $mainClone->siteId, 'Attempting to duplicate an element in an unsupported site.');
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
            /** @var ElementInterface&DraftBehavior $element */
            /** @var DraftBehavior $draftBehavior */
            $draftBehavior = $mainClone->getBehavior('draft');
            $draftsService = Craft::$app->getDrafts();
            // Are we duplicating a draft of a published element?
            if ($element->getIsDerivative()) {
                $draftBehavior->draftName = $draftsService->generateDraftName($element->getCanonicalId());
            } else {
                $draftBehavior->draftName = Craft::t('app', 'First draft');
            }
            $draftBehavior->draftNotes = null;
            $mainClone->setCanonicalId($element->getCanonicalId());
            $mainClone->draftId = $draftsService->insertDraftRow(
                $draftBehavior->draftName,
                null,
                Craft::$app->getUser()->getId(),
                $element->getCanonicalId(),
                $draftBehavior->trackChanges
            );
        }

        // If we are supposed to save it as new unpublished draft
        if ($asUnpublishedDraft) {
            /** @var ElementInterface&DraftBehavior $element */
            /** @var DraftBehavior $draftBehavior */
            // check if draftBehavior is attached - if not, attach it
            $draftBehavior = $mainClone->getBehavior('draft') ?? $mainClone->attachBehavior('draft', new DraftBehavior());
            $draftsService = Craft::$app->getDrafts();
            $draftBehavior->draftName = Craft::t('app', 'First draft');
            $draftBehavior->draftNotes = null;
            $mainClone->setCanonicalId(null);
            $mainClone->draftId = $draftsService->insertDraftRow(
                $draftBehavior->draftName,
                null,
                Craft::$app->getUser()->getId(),
                null,
                $draftBehavior->trackChanges,
            );
        }

        // Validate
        $mainClone->setScenario(Element::SCENARIO_ESSENTIALS);
        $mainClone->validate();

        // If there are any errors on the URI, re-validate as disabled
        if ($mainClone->hasErrors('uri') && $mainClone->enabled) {
            $mainClone->enabled = false;
            $mainClone->validate();
        }

        if ($mainClone->hasErrors()) {
            throw new InvalidElementException($mainClone, 'Element ' . $element->id . ' could not be duplicated because it doesn\'t validate.');
        }

        $this->ensureBulkOp(function() use (
            $mainClone,
            $supportedSites,
            $element,
            $placeInStructure,
            $newAttributes,
            $behaviors,
            $siteAttributes,
            $asUnpublishedDraft,
        ) {
            $transaction = Craft::$app->getDb()->beginTransaction();
            try {
                // Start with $element’s site
                if (!$this->_saveElementInternal($mainClone, false, false, null, $supportedSites, saveContent: true)) {
                    throw new InvalidElementException($mainClone, 'Element ' . $element->id . ' could not be duplicated for site ' . $element->siteId);
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
                        $mode = isset($newAttributes['id']) ? Structures::MODE_AUTO : Structures::MODE_INSERT;
                        Craft::$app->getStructures()->moveAfter($canonical->structureId, $mainClone, $canonical, $mode);
                    }
                }

                $propagatedTo = [$mainClone->siteId => true];
                $mainClone->newSiteIds = [];

                // Propagate it
                $otherSiteIds = ArrayHelper::withoutValue(array_keys($supportedSites), $mainClone->siteId);
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
                        Craft::configure($siteClone, ArrayHelper::merge(
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
                            if ($siteClone->hasErrors('slug')) {
                                throw new InvalidElementException($siteClone, "Element $element->id could not be duplicated for site $siteElement->siteId: " . $siteClone->getFirstError('slug'));
                            }

                            // Set a unique URI on the site clone
                            try {
                                $this->setElementUri($siteClone);
                            } catch (OperationAbortedException) {
                                // Oh well, not worth bailing over
                            }
                        }

                        if (!$this->_saveElementInternal($siteClone, false, false, supportedSites: $supportedSites, saveContent: true)) {
                            throw new InvalidElementException($siteClone, "Element $element->id could not be duplicated for site $siteElement->siteId: " . implode(', ', $siteClone->getFirstErrors()));
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
                                    ? new InvalidElementException($siteClone, "Element $siteClone->id could not be propagated to site $siteId: " . implode(', ', $siteClone->getFirstErrors()))
                                    : new InvalidElementException($mainClone, "Element $mainClone->id could not be propagated to site $siteId.");
                            }
                            $propagatedTo[$siteId] = true;
                            $mainClone->newSiteIds[] = $siteId;
                        }
                    }
                }

                // It's now fully duplicated and propagated
                $mainClone->afterPropagate(empty($newAttributes['id']));

                $transaction->commit();
            } catch (Throwable $e) {
                $transaction->rollBack();
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

    /**
     * Updates an element’s slug and URI, along with any descendants.
     *
     * @param ElementInterface $element The element to update.
     * @param bool $updateOtherSites Whether the element’s other sites should also be updated.
     * @param bool $updateDescendants Whether the element’s descendants should also be updated.
     * @param bool $queue Whether the element’s slug and URI should be updated via a job in the queue.
     * @throws OperationAbortedException if a unique URI can’t be generated based on the element’s URI format
     */
    public function updateElementSlugAndUri(ElementInterface $element, bool $updateOtherSites = true, bool $updateDescendants = true, bool $queue = false): void
    {
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

        Db::update(Table::ELEMENTS_SITES, [
            'slug' => $element->slug,
            'uri' => $element->uri,
        ], [
            'elementId' => $element->id,
            'siteId' => $element->siteId,
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
        foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
            if ($siteId == $element->siteId) {
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
    public function updateDescendantSlugsAndUris(ElementInterface $element, bool $updateOtherSites = true, bool $queue = false): void
    {
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
     * @return bool Whether the elements were merged successfully.
     * @throws Throwable if reasons
     * @since 3.1.31
     */
    public function mergeElements(ElementInterface $mergedElement, ElementInterface $prevailingElement): bool
    {
        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Find elements that relate to the merged element
            $data = (new Query())
                ->select(['r.sourceId', 'r.sourceSiteId', 'e.type'])
                ->innerJoin(['e' => Table::ELEMENTS], '[[e.id]] = [[r.sourceId]]')
                ->from(['r' => Table::RELATIONS])
                ->where(['r.targetId' => $mergedElement->id])
                ->collect()
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

                    foreach (Db::each($query) as $element) {
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
            $relations = (new Query())
                ->select(['id', 'fieldId', 'sourceId', 'sourceSiteId'])
                ->from([Table::RELATIONS])
                ->where(['targetId' => $mergedElement->id])
                ->all();

            foreach ($relations as $relation) {
                // Make sure the persisting element isn't already selected in the same field
                $persistingElementIsRelatedToo = (new Query())
                    ->from([Table::RELATIONS])
                    ->where([
                        'fieldId' => $relation['fieldId'],
                        'sourceId' => $relation['sourceId'],
                        'sourceSiteId' => $relation['sourceSiteId'],
                        'targetId' => $prevailingElement->id,
                    ])
                    ->exists();

                if (!$persistingElementIsRelatedToo) {
                    Db::update(Table::RELATIONS, [
                        'targetId' => $prevailingElement->id,
                    ], [
                        'id' => $relation['id'],
                    ]);
                }
            }

            // Update any structures that the merged element is in
            $structureElements = (new Query())
                ->select(['id', 'structureId'])
                ->from([Table::STRUCTUREELEMENTS])
                ->where(['elementId' => $mergedElement->id])
                ->all();

            foreach ($structureElements as $structureElement) {
                // Make sure the persisting element isn't already a part of that structure
                $persistingElementIsInStructureToo = (new Query())
                    ->from([Table::STRUCTUREELEMENTS])
                    ->where([
                        'structureId' => $structureElement['structureId'],
                        'elementId' => $prevailingElement->id,
                    ])
                    ->exists();

                if (!$persistingElementIsInStructureToo) {
                    Db::update(Table::STRUCTUREELEMENTS, [
                        'elementId' => $prevailingElement->id,
                    ], [
                        'id' => $structureElement['id'],
                    ]);
                }
            }

            // Update any reference tags
            $elementType = $this->getElementTypeById($prevailingElement->id);

            if ($elementType !== null && ($refHandle = $elementType::refHandle()) !== null) {
                $refTagPrefix = "\{$refHandle:";

                Queue::push(new FindAndReplace([
                    'description' => Translation::prep('app', 'Updating element references'),
                    'find' => $refTagPrefix . $mergedElement->id . ':',
                    'replace' => $refTagPrefix . $prevailingElement->id . ':',
                ]));

                Queue::push(new FindAndReplace([
                    'description' => Translation::prep('app', 'Updating element references'),
                    'find' => $refTagPrefix . $mergedElement->id . '}',
                    'replace' => $refTagPrefix . $prevailingElement->id . '}',
                ]));
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

            $transaction->commit();

            return $success;
        } catch (Throwable $e) {
            $transaction->rollBack();
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
     * @return bool Whether the element was deleted successfully
     * @throws Throwable
     */
    public function deleteElementById(int $elementId, ?string $elementType = null, ?int $siteId = null, bool $hardDelete = false): bool
    {
        if ($elementType === null) {
            $elementType = $this->getElementTypeById($elementId);

            if ($elementType === null) {
                return false;
            }
        }

        if ($siteId === null && $elementType::isLocalized() && Craft::$app->getIsMultiSite()) {
            // Get a site this element is enabled in
            $siteId = (int)(new Query())
                ->select('siteId')
                ->from(Table::ELEMENTS_SITES)
                ->where(['elementId' => $elementId])
                ->scalar();

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
            $db = Craft::$app->getDb();
            $transaction = $db->beginTransaction();
            try {
                // First delete any structure nodes with this element, so NestedSetBehavior can do its thing.
                while (($record = StructureElementRecord::findOne(['elementId' => $element->id])) !== null) {
                    // If this element still has any children, move them up before the one getting deleted.
                    while (($child = $record->children(1)->one()) !== null) {
                        /** @var StructureElementRecord $child */
                        $child->insertBefore($record);
                        // Re-fetch the record since its lft and rgt attributes just changed
                        $record = StructureElementRecord::findOne($record->id);
                    }
                    // Delete this element’s node
                    $record->deleteWithChildren();
                }

                // Invalidate any caches involving this element
                $this->invalidateCachesForElement($element);

                DateTimeHelper::pause();

                if ($element->hardDelete) {
                    Db::delete(Table::ELEMENTS, [
                        'id' => $element->id,
                    ]);
                    Db::delete(Table::SEARCHINDEX, [
                        'elementId' => $element->id,
                    ]);
                } else {
                    // Soft delete the elements table row
                    Db::update(Table::ELEMENTS, [
                        'dateDeleted' => Db::prepareDateForDb(new DateTime()),
                        'deletedWithOwner' => $element->deletedWithOwner,
                    ], ['id' => $element->id]);

                    // Also soft delete the element’s drafts & revisions
                    $this->_cascadeDeleteDraftsAndRevisions($element->id);
                }

                $element->dateDeleted = DateTimeHelper::now();
                $element->afterDelete();

                if (!$element->hardDelete) {
                    // Track this element in bulk operations
                    $this->trackElementInBulkOps($element);
                }

                $transaction->commit();
            } catch (Throwable $e) {
                $transaction->rollBack();
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
            ->column();

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
            Db::delete(Table::ELEMENTS_SITES, [
                'elementId' => $multiSiteElementIds,
                'siteId' => $firstElement->siteId,
            ]);

            // Resave them
            $this->resaveElements(
                $firstElement::find()
                    ->id($multiSiteElementIds)
                    ->status(null)
                    ->drafts(null)
                    ->site('*')
                    ->unique(),
                true,
                updateSearchIndex: false
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

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();
        try {
            // Restore the elements
            foreach ($elements as $element) {
                // Get the sites supported by this element
                $supportedSites = ArrayHelper::index(ElementHelper::supportedSitesForElement($element), 'siteId');
                if (empty($supportedSites)) {
                    throw new UnsupportedSiteException($element, $element->siteId, "Element $element->id has no supported sites.");
                }

                // Make sure the element actually supports the site it's being saved in
                if (!isset($supportedSites[$element->siteId])) {
                    throw new UnsupportedSiteException($element, $element->siteId, 'Attempting to restore an element in an unsupported site.');
                }

                // Get the element in each supported site
                $otherSiteIds = ArrayHelper::withoutValue(array_keys($supportedSites), $element->siteId);

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
                    Craft::warning("Unable to restore element $element->id: doesn't pass essential validation: " . print_r($element->errors, true), __METHOD__);
                    $transaction->rollBack();
                    return false;
                }

                foreach ($siteElements as $siteElement) {
                    if ($siteElement !== $element) {
                        $siteElement->setScenario(Element::SCENARIO_ESSENTIALS);
                        if (!$siteElement->validate()) {
                            Craft::warning("Unable to restore element $element->id: doesn't pass essential validation for site $element->siteId: " . print_r($element->errors, true), __METHOD__);
                            throw new Exception("Element $element->id doesn't pass essential validation for site $element->siteId.");
                        }
                    }
                }

                // Restore it
                Db::update(Table::ELEMENTS, [
                    'dateDeleted' => null,
                    'deletedWithOwner' => null,
                ], ['id' => $element->id]);

                // Also restore the element’s drafts & revisions
                $this->_cascadeDeleteDraftsAndRevisions($element->id, false);

                // Restore its search indexes
                $searchService = Craft::$app->getSearch();
                $searchService->indexElementAttributes($element);
                foreach ($siteElements as $siteElement) {
                    $searchService->indexElementAttributes($siteElement);
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

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return true;
    }

    /**
     * Returns the recent activity for an element.
     *
     * @param ElementInterface $element
     * @param int|null $excludeUserId
     * @return ElementActivity[]
     * @since 4.5.0
     */
    public function getRecentActivity(ElementInterface $element, ?int $excludeUserId = null): array
    {
        $query = (new Query())
            ->select(['userId', 'siteId', 'draftId', 'type', 'timestamp'])
            ->from(Table::ELEMENTACTIVITY)
            ->where(['elementId' => $element->getCanonicalId()])
            ->andWhere(['>', 'timestamp', Db::prepareDateForDb(DateTimeHelper::now()->modify('-1 minute'))])
            ->orderBy(['timestamp' => SORT_DESC]);

        if ($excludeUserId) {
            $query->andWhere(['not', ['userId' => $excludeUserId]]);
        }

        $results = $query->all();

        if (empty($results)) {
            return [];
        }

        // get all the unique users
        $userIds = array_unique(array_map(fn(array $result) => $result['userId'], $results));
        $users = User::find()
            ->id($userIds)
            ->status(null)
            ->indexBy('id')
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
            if (isset($activityByUserId[$result['userId']])) {
                $newerRecord = $activityByUserId[$result['userId']];
                // edit/save trumps view
                if (
                    $newerRecord->type === ElementActivity::TYPE_VIEW &&
                    $result['type'] !== ElementActivity::TYPE_VIEW
                ) {
                    $activity = $activity->filter(fn(ElementActivity $record) => $record !== $newerRecord);
                    unset($activityByUserId[$result['userId']]);
                } else {
                    continue;
                }
            }

            // fetch the element (draft)
            $elementKey = $result['draftId'] ?: 0;
            if (!isset($elements[$elementKey][$result['siteId']])) {
                $resultElement = $element::find()
                    ->id($result['draftId'] ? null : $element->getCanonicalId())
                    ->draftId($result['draftId'])
                    ->site('*')
                    ->preferSites([$result['siteId'], $element->siteId])
                    ->status(null)
                    ->one();

                // just to be safe...
                if (!$resultElement) {
                    Craft::warning(sprintf(
                        'Couldn’t load %s element %s%s for site %s',
                        $element::class,
                        $element->getCanonicalId(),
                        $result['draftId'] ? " (draft {$result['draftId']})" : '',
                        $result['siteId'],
                    ), __METHOD__);
                    continue;
                }

                $elements[$elementKey][$result['siteId']] = $resultElement;
            }

            $record = $activityByUserId[$result['userId']] = new ElementActivity(
                $users[$result['userId']],
                $elements[$elementKey][$result['siteId']],
                $result['type'],
                DateTimeHelper::toDateTime($result['timestamp']),
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
     * @since 4.5.0
     */
    public function trackActivity(ElementInterface $element, string $type, ?User $user = null): void
    {
        if ($user === null) {
            $user = Craft::$app->getUser()->getIdentity();
            if (!$user) {
                throw new InvalidArgumentException('$user must be set if no user is signed in.');
            }
        }

        // save => edit, if a provisional draft
        if ($type === ElementActivity::TYPE_SAVE && $element->isProvisionalDraft) {
            $type = ElementActivity::TYPE_EDIT;
        }

        $isCanonical = $element->getIsCanonical() || $element->isProvisionalDraft;

        Db::upsert(Table::ELEMENTACTIVITY, [
            'elementId' => $element->getCanonicalId(),
            'userId' => $user->id,
            'siteId' => $element->siteId,
            'draftId' => $isCanonical ? null : $element->draftId,
            'type' => $type,
            'timestamp' => Db::prepareDateForDb(new DateTime()),
        ]);
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
            Category::class,
            Entry::class,
            GlobalSet::class,
            Tag::class,
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
     * @return string|null The element class, or null if it could not be found
     */
    public function getElementTypeByRefHandle(string $refHandle): ?string
    {
        if (!isset($this->_elementTypesByRefHandle[$refHandle])) {
            $class = $this->elementTypeByRefHandle($refHandle);

            // Special cases for categories/tags/globals, if they’ve been entrified
            if (
                ($class === Category::class && empty(Craft::$app->getCategories()->getAllGroups())) ||
                ($class === Tag::class && empty(Craft::$app->getTags()->getAllTagGroups())) ||
                ($class === GlobalSet::class && empty(Craft::$app->getGlobals()->getAllSets()))
            ) {
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
     * @return string The parsed string
     */
    public function parseRefs(string $str, ?int $defaultSiteId = null): string
    {
        if (!StringHelper::contains($str, '{')) {
            return $str;
        }

        // First catalog all of the ref tags by element type, ref type ('id' or 'ref'), and ref name,
        // and replace them with placeholder tokens
        $sitesService = Craft::$app->getSites();
        $allRefTagTokens = [];
        $str = preg_replace_callback(
            '/\{([\w\\\\]+)\:([^@\:\}]+)(?:@([^\:\}]+))?(?:\:([^\}\| ]+))?(?: *\|\| *([^\}]+))?\}/',
            function(array $matches) use (
                $defaultSiteId,
                $sitesService,
                &$allRefTagTokens
            ) {
                $matches = array_pad($matches, 6, null);
                [$fullMatch, $elementType, $ref, $siteId, $attribute, $fallback] = $matches;
                if ($fallback === null) {
                    $fallback = $fullMatch;
                }

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
                            if (StringHelper::isUUID($siteId)) {
                                $site = $sitesService->getSiteByUid($siteId);
                            } else {
                                $site = $sitesService->getSiteByHandle($siteId);
                            }
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
                $token = '{' . StringHelper::randomString(9) . '}';
                $allRefTagTokens[$siteId][$elementType][$refType][$ref][] = [$token, $attribute, $fallback, $fullMatch];

                return $token;
            }, $str, -1, $count);

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
                    } else {
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
     * @phpstan-param string|array<EagerLoadPlan|array|string> $with
     * @return EagerLoadPlan[]
     * @since 3.5.0
     */
    public function createEagerLoadingPlans(string|array $with): array
    {
        // Normalize the paths and group based on the top level eager loading handle
        if (is_string($with)) {
            $with = StringHelper::split($with);
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
                $count = $path['count'] ?? ArrayHelper::remove($criteria, 'count', false);
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
            if (preg_match('/^([a-zA-Z][a-zA-Z0-9_:]*)\s+as\s+(' . HandleValidator::$handlePattern . ')$/', $handle, $match)) {
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
    public function eagerLoadElements(string $elementType, array $elements, array|string $with): void
    {
        // Bail if there aren't even any elements
        if (empty($elements)) {
            return;
        }

        $elementsBySite = ArrayHelper::index($elements, null, ['siteId']);
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
                        $query->orderBy = null;
                        $query->offset = null;
                        $query->limit = null;

                        $criteria = array_merge(
                            $map['criteria'] ?? [],
                            $plan->criteria
                        );

                        // Save the offset & limit params for later
                        $offset = ArrayHelper::remove($criteria, 'offset', 0);
                        $limit = ArrayHelper::remove($criteria, 'limit');

                        Craft::configure($query, $criteria);

                        if (!$query->siteId) {
                            $query->siteId = $siteId;
                        }

                        if (!$query->id) {
                            $query->id = array_keys($uniqueTargetElementIds);
                        } else {
                            $query->andWhere([
                                'elements.id' => array_keys($uniqueTargetElementIds),
                            ]);
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

                    $targetElementData = $query ? ArrayHelper::index($query->asArray()->all(), null, ['id']) : [];
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
                                            $targetElements[$targetSiteId][$elementId] = $map['createElement']($query, $result, $sourceElement);
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
                                $sourceElement->setEagerLoadedElementCount($plan->alias, count($targetElementsForSource));
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
                        $query->afterPopulate($flatTargetElements);
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
                $types = (new Query())
                    ->select(['id', 'type'])
                    ->from(Table::ELEMENTS)
                    ->where(['id' => $ids])
                    ->pairs();
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
        $supportedSites = ArrayHelper::index(ElementHelper::supportedSitesForElement($element), 'siteId');

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
    ): bool {
        /** @var ElementInterface&DraftBehavior $element */
        $isNewElement = !$element->id;

        // Are we tracking changes?
        $trackChanges = ElementHelper::shouldTrackChanges($element);
        $dirtyAttributes = [];

        // Force propagation for new elements
        $propagate = $propagate && $element::isLocalized() && Craft::$app->getIsMultiSite();
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
            if (!$element->uid) {
                $element->uid = StringHelper::UUID();
            }

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
        $supportedSites ??= ArrayHelper::index(ElementHelper::supportedSitesForElement($element), 'siteId');

        // Make sure the element actually supports the site it's being saved in
        if (!isset($supportedSites[$element->siteId])) {
            $element->firstSave = $originalFirstSave;
            $element->isNewForSite = $originalIsNewForSite;
            $element->propagateAll = $originalPropagateAll;
            throw new UnsupportedSiteException($element, $element->siteId, 'Attempting to save an element in an unsupported site.');
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
            if ($element->hasErrors('title')) {
                // Set a default title
                if ($isNewElement) {
                    $element->title = Craft::t('app', 'New {type}', ['type' => $element::displayName()]);
                } else {
                    $element->title = $element::displayName() . ' ' . $element->id;
                }
            }
        }

        $fieldLayout = $element->getFieldLayout();
        $dirtyFields = $element->getDirtyFields();

        // Get the element's site record
        if (!$isNewElement && !$element->isNewForSite) {
            $siteSettingsRecord = Element_SiteSettingsRecord::findOne([
                'elementId' => $element->id,
                'siteId' => $element->siteId,
            ]);
        } else {
            $siteSettingsRecord = null;
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
                    array_unique(array_merge($dirtyFields, $element->getModifiedFields()))
                );
            } else {
                $names = null;
            }

            if (($names === null || !empty($names)) && !$element->validate($names)) {
                Craft::info('Element not saved due to validation error: ' . print_r($element->errors, true), __METHOD__);
                $element->firstSave = $originalFirstSave;
                $element->isNewForSite = $originalIsNewForSite;
                $element->propagateAll = $originalPropagateAll;
                return false;
            }
        }

        $this->ensureBulkOp(function() use (
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
            $siteSettingsRecord,
        ) {
            // Figure out whether we will be updating the search index (and memoize that for nested element saves)
            $oldUpdateSearchIndex = $this->_updateSearchIndex;
            $updateSearchIndex = $this->_updateSearchIndex = $updateSearchIndex ?? $this->_updateSearchIndex ?? true;

            $newSiteIds = $element->newSiteIds;
            $element->newSiteIds = [];

            $transaction = Craft::$app->getDb()->beginTransaction();

            try {
                // No need to save the element record multiple times
                if (!$element->propagating) {
                    // Get the element record
                    if (!$isNewElement) {
                        $elementRecord = ElementRecord::findOne($element->id);

                        if (!$elementRecord) {
                            $element->firstSave = $originalFirstSave;
                            $element->isNewForSite = $originalIsNewForSite;
                            $element->propagateAll = $originalPropagateAll;
                            throw new ElementNotFoundException("No element exists with the ID '$element->id'");
                        }
                    } else {
                        $elementRecord = new ElementRecord();
                        $elementRecord->type = get_class($element);
                    }

                    // Set the attributes
                    $elementRecord->uid = $element->uid;
                    $canonicalId = $element->getCanonicalId();
                    $elementRecord->canonicalId = $canonicalId !== $element->id ? $canonicalId : null;
                    $elementRecord->draftId = (int)$element->draftId ?: null;
                    $elementRecord->revisionId = (int)$element->revisionId ?: null;
                    $elementRecord->fieldLayoutId = $element->fieldLayoutId = (int)($element->fieldLayoutId ?? $fieldLayout->id ?? 0) ?: null;
                    $elementRecord->enabled = (bool)$element->enabled;
                    $elementRecord->archived = (bool)$element->archived;
                    $elementRecord->dateLastMerged = Db::prepareDateForDb($element->dateLastMerged);
                    $elementRecord->dateDeleted = Db::prepareDateForDb($element->dateDeleted);

                    if ($isNewElement) {
                        if (isset($element->dateCreated)) {
                            $elementRecord->dateCreated = Db::prepareValueForDb($element->dateCreated);
                        }
                        if (isset($element->dateUpdated)) {
                            $elementRecord->dateUpdated = Db::prepareValueForDb($element->dateUpdated);
                        }
                    } elseif ($element->resaving && !$forceTouch) {
                        // Prevent ActiveRecord::prepareForDb() from changing the dateUpdated
                        $elementRecord->markAttributeDirty('dateUpdated');
                    } else {
                        // Force a new dateUpdated value
                        $elementRecord->dateUpdated = Db::prepareValueForDb(DateTimeHelper::now());
                    }

                    // Update our list of dirty attributes
                    if ($trackChanges) {
                        array_push($dirtyAttributes, ...array_keys($elementRecord->getDirtyAttributes([
                            'fieldLayoutId',
                            'enabled',
                            'archived',
                        ])));
                    }

                    // Save the element record
                    $elementRecord->save(false);

                    $dateCreated = DateTimeHelper::toDateTime($elementRecord->dateCreated);

                    if ($dateCreated === false) {
                        $element->firstSave = $originalFirstSave;
                        $element->isNewForSite = $originalIsNewForSite;
                        $element->propagateAll = $originalPropagateAll;
                        throw new Exception('There was a problem calculating dateCreated.');
                    }

                    $dateUpdated = DateTimeHelper::toDateTime($elementRecord->dateUpdated);

                    if ($dateUpdated === false) {
                        throw new Exception('There was a problem calculating dateUpdated.');
                    }

                    // Save the new dateCreated and dateUpdated dates on the model
                    $element->dateCreated = $dateCreated;
                    $element->dateUpdated = $dateUpdated;

                    if ($isNewElement) {
                        // Save the element ID on the element model
                        $element->id = $elementRecord->id;

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
                    $siteSettingsRecord = new Element_SiteSettingsRecord();
                    $siteSettingsRecord->elementId = $element->id;
                    $siteSettingsRecord->siteId = $element->siteId;
                }

                $title = $element::hasTitles() ? $element->title : null;
                $siteSettingsRecord->title = $title !== null && $title !== '' ? $title : null;
                $siteSettingsRecord->slug = $element->slug;
                $siteSettingsRecord->uri = $element->uri;

                // Avoid `enabled` getting marked as dirty if it’s not really changing
                $enabledForSite = $element->getEnabledForSite();
                if ($siteSettingsRecord->getIsNewRecord() || $siteSettingsRecord->enabled != $enabledForSite) {
                    $siteSettingsRecord->enabled = $enabledForSite;
                }

                // Update our list of dirty attributes
                if ($trackChanges && !$element->isNewForSite) {
                    array_push($dirtyAttributes, ...array_keys($siteSettingsRecord->getDirtyAttributes([
                        'slug',
                        'uri',
                    ])));
                    if ($siteSettingsRecord->isAttributeChanged('enabled')) {
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
                    $view = Craft::$app->getView();

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

                        $generatedFieldValues = [];
                        foreach ($generatedFields as $field) {
                            $validUids[$field['uid']] = true;

                            $value = $view->renderObjectTemplate($field['template'] ?? '', $element);
                            if ($value !== '') {
                                $content[$field['uid']] = $value;
                                if (($field['handle'] ?? '') !== '') {
                                    $generatedFieldValues[$field['handle']] = $value;
                                }
                            } elseif (!$saveContent) {
                                unset($oldContent[$field['uid']]);
                            }
                        }
                        $element->setGeneratedFieldValues($generatedFieldValues);
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
                if (!$siteSettingsRecord->save(false)) {
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

                // Update the element across the other sites?
                if ($propagate) {
                    $otherSiteIds = ArrayHelper::withoutValue(array_keys($supportedSites), $element->siteId);

                    if (!empty($otherSiteIds)) {
                        if (!$isNewElement) {
                            $siteElements = $this->_localizedElementQuery($element)
                                ->siteId($otherSiteIds)
                                ->status(null)
                                ->indexBy('siteId')
                                ->all();
                        } else {
                            $siteElements = [];
                        }

                        foreach (array_keys($supportedSites) as $siteId) {
                            // Skip the initial site
                            if ($siteId != $element->siteId) {
                                $siteElement = $siteElements[$siteId] ?? false;
                                if (!$this->_propagateElement(
                                    $element,
                                    $supportedSites,
                                    $siteId,
                                    $siteElement,
                                    crossSiteValidate: $runValidation && $crossSiteValidate,
                                    saveContent: true,
                                )) {
                                    throw new InvalidConfigException();
                                }
                            }
                        }
                    }
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

                $transaction->commit();
            } catch (Throwable $e) {
                $transaction->rollBack();
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
                    Db::deleteIfExists(
                        Table::ELEMENTS_SITES,
                        [
                            'and',
                            ['elementId' => $element->id],
                            ['not', ['siteId' => array_keys($supportedSites)]],
                        ]
                    );
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
                $timestamp = Db::prepareDateForDb(DateTimeHelper::now());

                foreach ($dirtyAttributes as $attributeName) {
                    Db::upsert(Table::CHANGEDATTRIBUTES, [
                        'elementId' => $element->id,
                        'siteId' => $element->siteId,
                        'attribute' => $attributeName,
                        'dateUpdated' => $timestamp,
                        'propagated' => $element->propagating,
                        'userId' => $userId,
                    ]);
                }

                if ($fieldLayout) {
                    foreach ($dirtyFields as $fieldHandle) {
                        if (($field = $fieldLayout->getFieldByHandle($fieldHandle)) !== null) {
                            Db::upsert(Table::CHANGEDFIELDS, [
                                'elementId' => $element->id,
                                'siteId' => $element->siteId,
                                'fieldId' => $field->id,
                                'layoutElementUid' => $field->layoutElement->uid,
                                'dateUpdated' => $timestamp,
                                'propagated' => $element->propagating,
                                'userId' => $userId,
                            ]);
                        }
                    }
                }
            }
        });

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
        if ($element->updateSearchIndexImmediately ?? Craft::$app->getRequest()->getIsConsoleRequest()) {
            Craft::$app->getSearch()->indexElementAttributes($element, $searchableDirtyFields);
        } else {
            Craft::$app->getSearch()->queueIndexElement($element, $searchableDirtyFields);
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
    ): bool {
        // Make sure the element actually supports the site it's being saved in
        if (!isset($supportedSites[$siteId])) {
            throw new UnsupportedSiteException($element, $siteId, 'Attempting to propagate an element to an unsupported site.');
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
            $siteElement->getTitleTranslationKey() === $element->getTitleTranslationKey()
        ) {
            $siteElement->title = $element->title;
        }

        // Copy the slug value?
        if (
            $element->slug !== null &&
            $siteElement->getSlugTranslationKey() === $element->getSlugTranslationKey()
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

        // Copy the dirty attributes (except title, slug and uri, which may be translatable)
        $siteElement->setDirtyAttributes(array_filter($element->getDirtyAttributes(), fn(string $attribute): bool => $attribute !== 'title' && $attribute !== 'slug'));

        if ($saveContent) {
            // Copy any non-translatable field values
            if ($siteElement->isNewForSite) {
                // Copy all the field values
                $siteElement->setFieldValues($element->getFieldValues());
            } else {
                $fieldLayout = $element->getFieldLayout();

                if ($fieldLayout !== null) {
                    // Only copy the non-translatable field values
                    foreach ($fieldLayout->getCustomFields() as $field) {
                        // Has this field changed, and does it produce the same translation key as it did for the initial element?
                        if (
                            $element->propagateAll ||
                            (
                                $element->isFieldDirty($field->handle) &&
                                $field->getTranslationKey($siteElement) === $field->getTranslationKey($element)
                            )
                        ) {
                            // Copy the initial element’s value over
                            $siteElement->setFieldValue($field->handle, $element->getFieldValue($field->handle));
                        }
                    }
                }
            }
        }

        // Save it
        $siteElement->setScenario(Element::SCENARIO_ESSENTIALS);

        // validate element against "live" scenario across all sites, if element is enabled for the site
        if ($crossSiteValidate && $siteElement->enabled && $siteElement->getEnabledForSite()) {
            $siteElement->setScenario(Element::SCENARIO_LIVE);
        }

        $siteElement->propagating = true;
        $siteElement->propagatingFrom = $element;

        $success = $this->_saveElementInternal(
            $siteElement,
            $crossSiteValidate,
            false,
            supportedSites: $supportedSites,
            saveContent: $saveContent
        );

        if (!$success) {
            // if the element we're trying to save has validation errors, notify original element about them
            if ($siteElement->hasErrors()) {
                return $this->_crossSiteValidationErrors($siteElement, $element);
            } else {
                // Log the errors
                $error = 'Couldn’t propagate element to other site due to validation errors:';
                foreach ($siteElement->getFirstErrors() as $attributeError) {
                    $error .= "\n- " . $attributeError;
                }
                Craft::error($error);
                throw new Exception('Couldn’t propagate element to other site.');
            }
        }

        return true;
    }

    /**
     * @param ElementInterface $siteElement
     * @param ElementInterface $element
     * @return bool
     * @throws Throwable
     */
    private function _crossSiteValidationErrors(
        ElementInterface $siteElement,
        ElementInterface $element,
    ): bool {
        // get site we're propagating to
        $propagateToSite = Craft::$app->getSites()->getSiteById($siteElement->siteId);
        $user = Craft::$app->getUser()->getIdentity();
        $message = Craft::t('app', 'Validation errors for site: “{siteName}“', [
            'siteName' => $propagateToSite?->name,
        ]);

        // check user can edit this element for the site that throws validation error on propagation
        if ($user &&
            Craft::$app->getIsMultiSite() &&
            $user->can("editSite:{$propagateToSite?->uid}") &&
            $siteElement->canSave($user)
        ) {
            $queryParams = ArrayHelper::without(Craft::$app->getRequest()->getQueryParams(), 'site');
            $url = UrlHelper::url($siteElement->getCpEditUrl(), $queryParams + ['prevalidate' => 1]);
            $message = Html::beginTag('a', [
                'href' => $url,
                'class' => 'cross-site-validate',
                'target' => '_blank',
            ]) .
                $message .
                Html::tag('span', '', [
                    'data-icon' => 'external',
                    'aria-label' => Craft::t('app', 'Open in a new tab'),
                    'role' => 'img',
                ]) .
                Html::endTag('a');
        }

        $element->addError('global', $message);

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
        $params = [
            'dateDeleted' => $delete ? Db::prepareDateForDb(DateTimeHelper::now()) : null,
            'canonicalId' => $canonicalId,
        ];

        $db = Craft::$app->getDb();
        $elementsTable = Table::ELEMENTS;

        foreach (['draftId' => Table::DRAFTS, 'revisionId' => Table::REVISIONS] as $fk => $table) {
            if ($db->getIsMysql()) {
                $sql = <<<SQL
UPDATE $elementsTable [[e]]
INNER JOIN $table [[t]] ON [[t.id]] = [[e.$fk]]
SET [[e.dateDeleted]] = :dateDeleted
WHERE [[t.canonicalId]] = :canonicalId
SQL;
            } else {
                $sql = <<<SQL
UPDATE $elementsTable [[e]]
SET [[dateDeleted]] = :dateDeleted
FROM $table [[t]]
WHERE [[t.id]] = [[e.$fk]]
AND [[t.canonicalId]] = :canonicalId
SQL;
            }

            $db->createCommand($sql, $params)->execute();
        }
    }

    /**
     * Returns the replacement for a given reference tag.
     *
     * @param ElementInterface|null $element
     * @param string|null $attribute
     * @param string $fallback
     * @param string $fullMatch
     * @return string
     * @see parseRefs()
     */
    private function _getRefTokenReplacement(?ElementInterface $element, ?string $attribute, string $fallback, string $fullMatch): string
    {
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
            Craft::error("An exception was thrown when parsing the ref tag \"$fullMatch\":\n" . $e->getMessage(), __METHOD__);

            // Replace the token with the default value
            return $fallback;
        }
    }

    /**
     * Returns whether a user is authorized to view the given element’s edit page.
     *
     * @param ElementInterface $element
     * @param User|null $user
     * @return bool
     * @since 4.3.0
     */
    public function canView(ElementInterface $element, ?User $user = null): bool
    {
        if (!$user) {
            $user = Craft::$app->getUser()->getIdentity();
            if (!$user) {
                return false;
            }
        }

        return (
            $this->_siteAuthCheck($element, $user) &&
            ($this->_authCheck($element, $user, self::EVENT_AUTHORIZE_VIEW) ?? $element->canView($user))
        );
    }

    /**
     * Returns whether a user is authorized to save the given element in its current form.
     *
     * @param ElementInterface $element
     * @param User|null $user
     * @return bool
     * @since 4.3.0
     */
    public function canSave(ElementInterface $element, ?User $user = null): bool
    {
        if (!$user) {
            $user = Craft::$app->getUser()->getIdentity();
            if (!$user) {
                return false;
            }
        }

        return (
            $this->_siteAuthCheck($element, $user) &&
            ($this->_authCheck($element, $user, self::EVENT_AUTHORIZE_SAVE) ?? $element->canSave($user))
        );
    }

    /**
     * Returns whether a user is authorized to save the canonical version of the given element.
     *
     * @param ElementInterface $element
     * @param User|null $user
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
     * @return bool
     * @since 4.3.0
     */
    public function canDuplicate(ElementInterface $element, ?User $user = null): bool
    {
        if (!$user) {
            $user = Craft::$app->getUser()->getIdentity();
            if (!$user) {
                return false;
            }
        }

        return $this->_authCheck($element, $user, self::EVENT_AUTHORIZE_DUPLICATE) ?? $element->canDuplicate($user);
    }

    /**
     * Returns whether a user is authorized to duplicate the given element as an unpublished draft.
     *
     * @param ElementInterface $element
     * @param User|null $user
     * @return bool
     * @since 5.0.0
     */
    public function canDuplicateAsDraft(ElementInterface $element, ?User $user = null): bool
    {
        if (!$user) {
            $user = Craft::$app->getUser()->getIdentity();
            if (!$user) {
                return false;
            }
        }

        return $this->_authCheck($element, $user, self::EVENT_AUTHORIZE_DUPLICATE_AS_DRAFT)
            ?? $element->canDuplicateAsDraft($user);
    }

    /**
     * Returns whether a user is authorized to copy the given element, to be duplicated elsewhere.
     *
     *  This should always be called in conjunction with [[canView()]].
     *
     * @param ElementInterface $element
     * @param User|null $user
     * @return bool
     * @since 5.7.0
     */
    public function canCopy(ElementInterface $element, ?User $user = null): bool
    {
        if (!$user) {
            $user = Craft::$app->getUser()->getIdentity();
            if (!$user) {
                return false;
            }
        }

        return $this->_authCheck($element, $user, self::EVENT_AUTHORIZE_COPY) ?? $element->canCopy($user);
    }

    /**
     * Returns whether a user is authorized to delete the given element.
     *
     * This should always be called in conjunction with [[canView()]] or [[canSave()]].
     *
     * @param ElementInterface $element
     * @param User|null $user
     * @return bool
     * @since 4.3.0
     */
    public function canDelete(ElementInterface $element, ?User $user = null): bool
    {
        if (!$user) {
            $user = Craft::$app->getUser()->getIdentity();
            if (!$user) {
                return false;
            }
        }

        return $this->_authCheck($element, $user, self::EVENT_AUTHORIZE_DELETE) ?? $element->canDelete($user);
    }

    /**
     * Returns whether a user is authorized to delete the given element for its current site.
     *
     * This should always be called in conjunction with [[canView()]] or [[canSave()]].
     *
     * @param ElementInterface $element
     * @param User|null $user
     * @return bool
     * @since 4.3.0
     */
    public function canDeleteForSite(ElementInterface $element, ?User $user = null): bool
    {
        if (!$user) {
            $user = Craft::$app->getUser()->getIdentity();
            if (!$user) {
                return false;
            }
        }

        return $this->_authCheck($element, $user, self::EVENT_AUTHORIZE_DELETE_FOR_SITE) ?? $element->canDeleteForSite($user);
    }

    /**
     * Returns whether a user is authorized to create drafts for the given element.
     *
     * This should always be called in conjunction with [[canView()]] or [[canSave()]].
     *
     * @param ElementInterface $element
     * @param User|null $user
     * @return bool
     * @since 4.3.0
     */
    public function canCreateDrafts(ElementInterface $element, ?User $user = null): bool
    {
        if (!$user) {
            $user = Craft::$app->getUser()->getIdentity();
            if (!$user) {
                return false;
            }
        }

        return $this->_authCheck($element, $user, self::EVENT_AUTHORIZE_CREATE_DRAFTS) ?? $element->canCreateDrafts($user);
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

    private function _siteAuthCheck(ElementInterface $element, User $user): bool
    {
        return (
            !$element::isLocalized() ||
            !Craft::$app->getIsMultiSite() ||
            $user->can(sprintf('editSite:%s', $element->getSite()->uid))
        );
    }
}
