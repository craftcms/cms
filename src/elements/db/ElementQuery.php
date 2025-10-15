<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\ExpirableElementInterface;
use craft\base\FieldInterface;
use craft\behaviors\CustomFieldBehavior;
use craft\behaviors\DraftBehavior;
use craft\behaviors\RevisionBehavior;
use craft\cache\ElementQueryTagDependency;
use craft\db\CoalesceColumnsExpression;
use craft\db\Connection;
use craft\db\FixedOrderExpression;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\db\QueryParam;
use craft\db\Table;
use craft\elements\ElementCollection;
use craft\elements\User;
use craft\errors\SiteNotFoundException;
use craft\events\CancelableEvent;
use craft\events\DefineValueEvent;
use craft\events\PopulateElementEvent;
use craft\events\PopulateElementsEvent;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\models\Site;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Twig\Markup;
use yii\base\ArrayableTrait;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\InvalidValueException;
use yii\base\NotSupportedException;
use yii\db\Connection as YiiConnection;
use yii\db\Expression;
use yii\db\ExpressionInterface;
use yii\db\QueryBuilder;
use yii\db\Schema;

/**
 * ElementQuery represents a SELECT SQL statement for elements in a way that is independent of DBMS.
 *
 * @template TKey of array-key
 * @template TElement of ElementInterface
 * @extends Query<TKey,TElement>
 *
 * @property-write string|string[]|Site $site The site(s) that resulting elements must be returned in
 * @mixin CustomFieldBehavior
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ElementQuery extends Query implements ElementQueryInterface
{
    use ArrayableTrait;

    /**
     * @event CancelableEvent An event that is triggered at the beginning of preparing an element query for the query builder.
     */
    public const EVENT_BEFORE_PREPARE = 'beforePrepare';

    /**
     * @event CancelableEvent An event that is triggered at the end of preparing an element query for the query builder.
     */
    public const EVENT_AFTER_PREPARE = 'afterPrepare';

    /**
     * @event DefineValueEvent An event that is triggered when defining the cache tags that should be associated with the query.
     * @see getCacheTags()
     * @since 4.1.0
     */
    public const EVENT_DEFINE_CACHE_TAGS = 'defineCacheTags';

    /**
     * @event PopulateElementEvent The event that is triggered before an element is populated from a database row.
     *
     * If [[PopulateElementEvent::$element]] is set by an event handler, the replacement will be returned by [[createElement()]] instead of the original.
     *
     * @since 4.5.0
     */
    public const EVENT_BEFORE_POPULATE_ELEMENT = 'beforePopulateElement';

    /**
     * @event PopulateElementEvent The event that is triggered after an element is populated from a database row.
     *
     * If [[PopulateElementEvent::$element]] is replaced by an event handler, the replacement will be returned by [[createElement()]] instead of the original.
     */
    public const EVENT_AFTER_POPULATE_ELEMENT = 'afterPopulateElement';

    /**
     * @event PopulateElementEvent The event triggered after all elements are populated.
     *
     * The query’s results are assigned to [[PopulateElementsEvent::$elements]], and are returned after all handlers are invoked. Handlers may add, remove, modify, and replace populated elements.
     */
    public const EVENT_AFTER_POPULATE_ELEMENTS = 'afterPopulateElements';

    // Base config attributes
    // -------------------------------------------------------------------------

    /**
     * @var class-string<TElement> The name of the [[ElementInterface]] class.
     */
    public string $elementType;

    /**
     * @var Query|null The query object created by [[prepare()]]
     * @see prepare()
     */
    public ?Query $query = null;

    /**
     * @var Query|null The subselect’s query object created by [[prepare()]]
     * @see prepare()
     */
    public ?Query $subQuery = null;

    /**
     * @var FieldInterface[]|null The fields that may be involved in this query.
     */
    public ?array $customFields = null;

    /**
     * @var array|null The generated field handles that may be involved in this query.
     */
    public ?array $generatedFields = null;

    // Result formatting attributes
    // -------------------------------------------------------------------------

    /**
     * @var bool Whether the results should be queried in reverse.
     * @used-by inReverse()
     */
    public bool $inReverse = false;

    /**
     * @var bool Whether to return each element as an array. If false (default), an object
     * of [[elementType]] will be created to represent each element.
     * @used-by asArray()
     */
    public bool $asArray = false;

    /**
     * @var bool Whether to replace canonical elements with provisional drafts,
     * when they exist for the current user.
     * @used-by withProvisionalDrafts()
     * @since 5.6.0
     */
    public bool $withProvisionalDrafts = false;

    /**
     * @var bool Whether to ignore placeholder elements when populating the results.
     * @used-by ignorePlaceholders()
     * @since 3.2.9
     */
    public bool $ignorePlaceholders = false;

    // Drafts and revisions
    // -------------------------------------------------------------------------

    /**
     * @var bool|null Whether draft elements should be returned.
     * @since 3.2.0
     */
    public ?bool $drafts = false;

    /**
     * @var bool|null Whether provisional drafts should be returned.
     * @since 3.7.0
     */
    public ?bool $provisionalDrafts = false;

    /**
     * @var int|null The ID of the draft to return (from the `drafts` table)
     * @since 3.2.0
     */
    public ?int $draftId = null;

    /**
     * @var mixed The source element ID that drafts should be returned for.
     *
     * This can be set to one of the following:
     *
     * - A source element ID – matches drafts of that element
     * - A source element
     *  - An array of source elements or element IDs
     * - `'*'` – matches drafts of any source element
     * - `false` – matches unpublished drafts that have no source element
     *
     * @since 3.2.0
     */
    public mixed $draftOf = null;

    /**
     * @var int|null The drafts’ creator ID
     * @since 3.2.0
     */
    public ?int $draftCreator = null;

    /**
     * @var bool Whether only canonical elements should be included in the
     * results, including elements that reference another canonical element via
     * `canonicalId` so long as they aren’t a draft.
     * @since 5.7.0
     */
    public bool $canonicalsOnly = false;

    /**
     * @var bool Whether only unpublished drafts which have been saved after initial creation should be included in the results.
     * @since 3.6.6
     */
    public bool $savedDraftsOnly = false;

    /**
     * @var bool|null Whether revision elements should be returned.
     * @since 3.2.0
     */
    public ?bool $revisions = false;

    /**
     * @var int|null The ID of the revision to return (from the `revisions` table)
     * @since 3.2.0
     */
    public ?int $revisionId = null;

    /**
     * @var int|null The source element ID that revisions should be returned for
     * @since 3.2.0
     */
    public ?int $revisionOf = null;

    /**
     * @var int|null The revisions’ creator ID
     * @since 3.2.0
     */
    public ?int $revisionCreator = null;

    // General parameters
    // -------------------------------------------------------------------------

    /**
     * @var mixed The element ID(s). Prefix IDs with `'not '` to exclude them.
     * @used-by id()
     */
    public mixed $id = null;

    /**
     * @var mixed The element UID(s). Prefix UIDs with `'not '` to exclude them.
     * @used-by uid()
     */
    public mixed $uid = null;

    /**
     * @var mixed The element ID(s) in the `elements_sites` table. Prefix IDs with `'not '` to exclude them.
     * @used-by siteSettingsId()
     * @since 3.7.0
     */
    public mixed $siteSettingsId = null;

    /**
     * @var bool Whether results should be returned in the order specified by [[id]].
     * @used-by fixedOrder()
     */
    public bool $fixedOrder = false;

    /**
     * @var string|string[]|null The status(es) that the resulting elements must have.
     * @used-by status()
     */
    public array|string|null $status = [
        Element::STATUS_ENABLED,
    ];

    /**
     * @var bool Whether to return only archived elements.
     * @used-by archived()
     */
    public bool $archived = false;

    /**
     * @var bool|null Whether to return trashed (soft-deleted) elements.
     * If this is set to `null`, then both trashed and non-trashed elements will be returned.
     * @used-by trashed()
     * @since 3.1.0
     */
    public ?bool $trashed = false;

    /**
     * @var mixed When the resulting elements must have been created.
     * @used-by dateCreated()
     */
    public mixed $dateCreated = null;

    /**
     * @var mixed When the resulting elements must have been last updated.
     * @used-by dateUpdated()
     */
    public mixed $dateUpdated = null;

    /**
     * @var mixed The site ID(s) that the elements should be returned in, or `'*'` if elements
     * should be returned in all supported sites.
     * @used-by site()
     * @used-by siteId()
     */
    public mixed $siteId = null;

    /**
     * @var bool Whether only elements with unique IDs should be returned by the query.
     * @used-by unique()
     * @since 3.2.0
     */
    public bool $unique = false;

    /**
     * @var array|null Determines which site should be selected when querying multi-site elements.
     * @used-by preferSites()
     * @since 3.2.0
     */
    public ?array $preferSites = null;

    /**
     * @var bool Whether the elements must be “leaves” in the structure.
     * @used-by leaves()
     */
    public bool $leaves = false;

    /**
     * @var mixed The element relation criteria.
     *
     * See [Relations](https://craftcms.com/docs/5.x/system/relations.html) for supported syntax options.
     *
     * @used-by relatedTo()
     */
    public mixed $relatedTo = null;

    /**
     * @var mixed The element relation criteria.
     *
     * See [Relations](https://craftcms.com/docs/5.x/system/relations.html) for supported syntax options.
     *
     * @used-by notRelatedTo()
     * @since 5.4.0
     */
    public mixed $notRelatedTo = null;

    /**
     * @var mixed The title that resulting elements must have.
     * @used-by title()
     */
    public mixed $title = null;

    /**
     * @var mixed The slug that resulting elements must have.
     * @used-by slug()
     */
    public mixed $slug = null;

    /**
     * @var mixed The URI that the resulting element must have.
     * @used-by uri()
     */
    public mixed $uri = null;

    /**
     * @var mixed The search term to filter the resulting elements by.
     *
     * See [Searching](https://craftcms.com/docs/5.x/system/searching.html) for supported syntax options.
     *
     * @used-by ElementQuery::search()
     */
    public mixed $search = null;

    /**
     * @var string|null The bulk element operation key that the resulting elements were involved in.
     *
     * @used-by ElementQuery::inBulkOp()
     * @since 5.0.0
     */
    public ?string $inBulkOp = null;

    /**
     * @var mixed The reference code(s) used to identify the element(s).
     *
     * This property is set when accessing elements via their reference tags, e.g. `{entry:section/slug}`.
     *
     * @used-by ElementQuery::ref()
     */
    public mixed $ref = null;

    // Eager-loading
    // -------------------------------------------------------------------------

    /**
     * @var string|array|null The eager-loading declaration.
     *
     * See [Eager-Loading Elements](https://craftcms.com/docs/5.x/development/eager-loading.html) for supported syntax options.
     *
     * @used-by with()
     * @used-by andWith()
     */
    public array|string|null $with = null;

    /**
     * @var ElementInterface|null The source element that this query is fetching relations for.
     * @since 5.0.0
     */
    public ?ElementInterface $eagerLoadSourceElement = null;

    /**
     * @var string|null The handle that could be used to eager-load the query's target elmeents.
     * @since 5.0.0
     */
    public ?string $eagerLoadHandle = null;

    /**
     * @var string|null The eager-loading alias that should be used.
     * @since 5.0.0
     */
    public ?string $eagerLoadAlias = null;

    /**
     * @var bool Whether the query should be used to eager-load results for the [[$eagerSourceElement|source element]]
     * and any other elements in its collection.
     * @used-by eagerly()
     * @since 5.0.0
     */
    public bool $eagerly = false;

    /**
     * @var bool Whether custom fields should be factored into the query.
     * @used-by withCustomFields()
     * @since 5.2.0
     */
    public bool $withCustomFields = true;

    // Structure parameters
    // -------------------------------------------------------------------------

    /**
     * @var bool|null Whether element structure data should automatically be left-joined into the query.
     * @used-by withStructure()
     */
    public ?bool $withStructure = null;

    /**
     * @var mixed The structure ID that should be used to join in the structureelements table.
     * @used-by structureId()
     */
    public mixed $structureId = null;

    /**
     * @var mixed The element’s level within the structure
     * @used-by level()
     */
    public mixed $level = null;

    /**
     * @var bool|null Whether the resulting elements must have descendants.
     * @used-by hasDescendants()
     * @since 3.0.4
     */
    public ?bool $hasDescendants = null;

    /**
     * @var int|ElementInterface|null The element (or its ID) that results must be an ancestor of.
     * @used-by ancestorOf()
     */
    public ElementInterface|int|null $ancestorOf = null;

    /**
     * @var int|null The maximum number of levels that results may be separated from [[ancestorOf]].
     * @used-by ancestorDist()
     */
    public ?int $ancestorDist = null;

    /**
     * @var int|ElementInterface|null The element (or its ID) that results must be a descendant of.
     * @used-by descendantOf()
     */
    public ElementInterface|int|null $descendantOf = null;

    /**
     * @var int|null The maximum number of levels that results may be separated from [[descendantOf]].
     * @used-by descendantDist()
     */
    public ?int $descendantDist = null;

    /**
     * @var int|ElementInterface|null The element (or its ID) that the results must be a sibling of.
     * @used-by siblingOf()
     */
    public ElementInterface|int|null $siblingOf = null;

    /**
     * @var int|ElementInterface|null The element (or its ID) that the result must be the previous sibling of.
     * @used-by prevSiblingOf()
     */
    public ElementInterface|int|null $prevSiblingOf = null;

    /**
     * @var int|ElementInterface|null The element (or its ID) that the result must be the next sibling of.
     * @used-by nextSiblingOf()
     */
    public ElementInterface|int|null $nextSiblingOf = null;

    /**
     * @var int|ElementInterface|null The element (or its ID) that the results must be positioned before.
     * @used-by positionedBefore()
     */
    public ElementInterface|int|null $positionedBefore = null;

    /**
     * @var int|ElementInterface|null The element (or its ID) that the results must be positioned after.
     * @used-by positionedAfter()
     */
    public ElementInterface|int|null $positionedAfter = null;

    /**
     * @var array The default [[orderBy]] value to use if [[orderBy]] is empty but not null.
     */
    protected array $defaultOrderBy = [
        'elements.dateCreated' => SORT_DESC,
        'elements.id' => SORT_DESC,
    ];

    // For internal use
    // -------------------------------------------------------------------------

    /**
     * @var mixed The placeholder condition for this query.
     * @see _placeholderCondition()
     */
    private mixed $_placeholderCondition = null;

    /**
     * @var mixed The [[siteId]] param used at the time the placeholder condition was generated.
     * @see _placeholderCondition()
     */
    private mixed $_placeholderSiteIds = null;

    /**
     * @var ElementInterface[]|null The cached element query result
     * @see setCachedResult()
     */
    private ?array $_result = null;

    /**
     * @var array|null The criteria params that were set when the cached element query result was set
     * @see setCachedResult()
     */
    private ?array $_resultCriteria = null;

    /**
     * @var array<string,int>|null
     * @see _applySearchParam()
     * @see _applyOrderByParams()
     * @see populate()
     */
    private ?array $_searchResults = null;

    /**
     * @var string[]|null
     * @see getCacheTags()
     */
    private array|null $_cacheTags = null;

    /**
     * @var array<string,string|string[]> Column alias => name mapping
     * @see prepare()
     * @see joinElementTable()
     * @see _applyOrderByParams()
     * @see _applySelectParam()
     */
    private array $_columnMap = [];

    /**
     * @var bool Whether an element table has been joined for the query
     * @see prepare()
     * @see joinElementTable()
     */
    private bool $_joinedElementTable = false;

    /**
     * @var array<string,string|string[]> Column alias => cast type
     * @see prepare()
     * @see _applyOrderByParams()
     */
    private array $_columnsToCast = [];

    /**
     * Constructor
     *
     * @param class-string<TElement> $elementType The element type class associated with this query
     * @param array $config Configurations to be applied to the newly created query object
     */
    public function __construct(string $elementType, array $config = [])
    {
        $this->elementType = $elementType;

        // Use ** as a placeholder for "all the default columns"
        $config['select'] ??= ['**' => '**'];

        // Set a placeholder for the default `orderBy` param
        if (!isset($this->orderBy)) {
            $this->orderBy(new OrderByPlaceholderExpression());
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'site':
                $this->site($value);
                break;
            default:
                parent::__set($name, $value);
        }
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return self::class;
    }

    /**
     * @inheritdoc
     */
    public function offsetExists(mixed $offset): bool
    {
        // Cached?
        if (is_numeric($offset)) {
            $cachedResult = $this->getCachedResult();
            if ($cachedResult !== null) {
                return $offset < count($cachedResult);
            }
        }

        return parent::offsetExists($offset);
    }

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['customFields'] = [
            'class' => CustomFieldBehavior::class,
            'hasMethods' => true,
        ];
        return $behaviors;
    }

    // Element criteria parameter setters
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     * @uses $inReverse
     */
    public function inReverse(bool $value = true): static
    {
        $this->inReverse = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $asArray
     */
    public function asArray(bool $value = true): static
    {
        $this->asArray = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $withProvisionalDrafts
     */
    public function withProvisionalDrafts(bool $value = true): static
    {
        $this->withProvisionalDrafts = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $asArray
     */
    public function ignorePlaceholders(bool $value = true): static
    {
        $this->ignorePlaceholders = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $drafts
     */
    public function drafts(?bool $value = true): static
    {
        $this->drafts = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $draftId
     * @uses $drafts
     */
    public function draftId(?int $value = null): static
    {
        $this->draftId = $value;
        if ($value !== null && $this->drafts === false) {
            $this->drafts = true;
        }
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $draftOf
     * @uses $drafts
     */
    public function draftOf($value): static
    {
        $valid = false;
        if ($value instanceof ElementInterface) {
            $this->draftOf = $value->getCanonicalId();
            $valid = true;
        } elseif (
            is_numeric($value) ||
            (is_array($value) && ArrayHelper::isNumeric($value)) ||
            $value === '*' ||
            $value === false ||
            $value === null
        ) {
            $this->draftOf = $value;
            $valid = true;
        } elseif (is_array($value) && !empty($value)) {
            $c = Collection::make($value);
            if ($c->every(fn($v) => $v instanceof ElementInterface || is_numeric($v))) {
                $this->draftOf = $c->map(fn($v) => $v instanceof ElementInterface ? $v->id : $v)->all();
                $valid = true;
            }
        }
        if (!$valid) {
            throw new InvalidArgumentException('Invalid draftOf value');
        }
        if ($value !== null && $this->drafts === false) {
            $this->drafts = true;
        }
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $draftCreator
     * @uses $drafts
     */
    public function draftCreator($value): static
    {
        if ($value instanceof User) {
            $this->draftCreator = $value->id;
        } elseif (is_numeric($value) || $value === null) {
            $this->draftCreator = $value;
        } else {
            throw new InvalidArgumentException('Invalid draftCreator value');
        }
        if ($value !== null && $this->drafts === false) {
            $this->drafts = true;
        }
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $provisionalDrafts
     * @uses $drafts
     */
    public function provisionalDrafts(?bool $value = true): static
    {
        $this->provisionalDrafts = $value;
        if ($value === true && $this->drafts === false) {
            $this->drafts = true;
        }
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $canonicalsOnly
     */
    public function canonicalsOnly(bool $value = true): static
    {
        $this->canonicalsOnly = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $savedDraftsOnly
     */
    public function savedDraftsOnly(bool $value = true): static
    {
        $this->savedDraftsOnly = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $revisions
     */
    public function revisions(?bool $value = true): static
    {
        $this->revisions = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $revisionId
     * @uses $revisions
     */
    public function revisionId(?int $value = null): static
    {
        $this->revisionId = $value;
        if ($value !== null && $this->revisions === false) {
            $this->revisions = true;
        }
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $revisionOf
     * @uses $revisions
     */
    public function revisionOf($value): static
    {
        if ($value instanceof ElementInterface) {
            $this->revisionOf = $value->getCanonicalId();
        } elseif (is_numeric($value) || $value === null) {
            $this->revisionOf = $value;
        } else {
            throw new InvalidArgumentException('Invalid revisionOf value');
        }
        if ($value !== null && $this->revisions === false) {
            $this->revisions = true;
        }
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $revisionCreator
     * @uses $revisions
     */
    public function revisionCreator($value): static
    {
        if ($value instanceof User) {
            $this->revisionCreator = $value->id;
        } elseif (is_numeric($value) || $value === null) {
            $this->revisionCreator = $value;
        } else {
            throw new InvalidArgumentException('Invalid revisionCreator value');
        }
        if ($value !== null && $this->revisions === false) {
            $this->revisions = true;
        }
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $id
     */
    public function id($value): static
    {
        $this->id = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $uid
     */
    public function uid($value): static
    {
        $this->uid = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $siteSettingsId
     */
    public function siteSettingsId($value): static
    {
        $this->siteSettingsId = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $fixedOrder
     */
    public function fixedOrder(bool $value = true): static
    {
        $this->fixedOrder = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $orderBy
     */
    public function orderBy($columns): static
    {
        parent::orderBy($columns);

        // If $columns normalizes to an empty array, just set it to null
        if ($this->orderBy === []) {
            $this->orderBy = null;
        }

        return $this;
    }

    /**
     * @inheritdoc
     * @uses $orderBy
     */
    public function addOrderBy($columns): static
    {
        // If orderBy is an empty, non-null value (leaving it up to the element query class to decide),
        // then treat this is an orderBy() call.
        if (isset($this->orderBy) && empty($this->orderBy)) {
            $this->orderBy = null;
        }

        parent::addOrderBy($columns);

        // If $this->>orderBy is empty, just set it to null
        if ($this->orderBy === []) {
            $this->orderBy = null;
        }

        return $this;
    }

    /**
     * @inheritdoc
     * @uses $status
     */
    public function status(array|string|null $value): static
    {
        $this->status = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $archived
     */
    public function archived(bool $value = true): static
    {
        $this->archived = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $trashed
     */
    public function trashed(?bool $value = true): static
    {
        $this->trashed = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $dateCreated
     */
    public function dateCreated(mixed $value): static
    {
        $this->dateCreated = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $dateUpdated
     */
    public function dateUpdated(mixed $value): static
    {
        $this->dateUpdated = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @throws InvalidArgumentException if $value is invalid
     * @uses $siteId
     */
    public function site($value): static
    {
        if ($value === null) {
            $this->siteId = null;
        } elseif ($value === '*') {
            $this->siteId = Craft::$app->getSites()->getAllSiteIds();
        } elseif ($value instanceof Site) {
            $this->siteId = $value->id;
        } elseif (is_string($value)) {
            $site = Craft::$app->getSites()->getSiteByHandle($value);
            if (!$site) {
                throw new InvalidArgumentException('Invalid site handle: ' . $value);
            }
            $this->siteId = $site->id;
        } else {
            if ($not = (strtolower(reset($value)) === 'not')) {
                array_shift($value);
            }
            $this->siteId = [];
            foreach (Craft::$app->getSites()->getAllSites() as $site) {
                if (in_array($site->handle, $value, true) === !$not) {
                    $this->siteId[] = $site->id;
                }
            }
            if (empty($this->siteId)) {
                throw new InvalidArgumentException('Invalid site param: [' . ($not ? 'not, ' : '') . implode(', ', $value) . ']');
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     * @uses $siteId
     */
    public function siteId($value): static
    {
        if (is_array($value) && strtolower(reset($value)) === 'not') {
            array_shift($value);
            $this->siteId = [];
            foreach (Craft::$app->getSites()->getAllSites() as $site) {
                if (!in_array($site->id, $value)) {
                    $this->siteId[] = $site->id;
                }
            }
        } else {
            $this->siteId = $value;
        }

        return $this;
    }

    /**
     * @inheritdoc
     * @uses $siteId
     * @return static
     */
    public function language($value): self
    {
        if (is_string($value)) {
            $sites = Craft::$app->getSites()->getSitesByLanguage($value);
            if (empty($sites)) {
                throw new InvalidArgumentException("Invalid language: $value");
            }
            $this->siteId = array_map(fn(Site $site) => $site->id, $sites);
        } else {
            if ($not = (strtolower(reset($value)) === 'not')) {
                array_shift($value);
            }
            $this->siteId = [];
            foreach (Craft::$app->getSites()->getAllSites() as $site) {
                if (in_array($site->language, $value, true) === !$not) {
                    $this->siteId[] = $site->id;
                }
            }
            if (empty($this->siteId)) {
                throw new InvalidArgumentException('Invalid language param: [' . ($not ? 'not, ' : '') . implode(', ', $value) . ']');
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     * @return static
     * @uses $unique
     * @since 3.2.0
     */
    public function unique(bool $value = true): static
    {
        $this->unique = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $preferSites
     * @since 3.2.0
     */
    public function preferSites(?array $value = null): static
    {
        $this->preferSites = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $notRelatedTo
     * @since 5.4.0
     */
    public function notRelatedTo($value): static
    {
        $this->notRelatedTo = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $notRelatedTo
     * @since 5.4.0
     */
    public function andNotRelatedTo($value): static
    {
        $relatedTo = $this->_andRelatedToCriteria($value, $this->notRelatedTo);

        if ($relatedTo === false) {
            return $this;
        }

        return $this->notRelatedTo($relatedTo);
    }

    /**
     * @inheritdoc
     * @uses $relatedTo
     */
    public function relatedTo($value): static
    {
        $this->relatedTo = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException
     * @uses $relatedTo
     */
    public function andRelatedTo($value): static
    {
        $relatedTo = $this->_andRelatedToCriteria($value, $this->relatedTo);

        if ($relatedTo === false) {
            return $this;
        }

        return $this->relatedTo($relatedTo);
    }

    /**
     * @param $value
     * @param $currentValue
     * @return mixed
     * @throws NotSupportedException
     */
    private function _andRelatedToCriteria($value, $currentValue): mixed
    {
        if (!$value) {
            return false;
        }

        if (!$currentValue) {
            return $value;
        }

        // Normalize so element/targetElement/sourceElement values get pushed down to the 2nd level
        $relatedTo = ElementRelationParamParser::normalizeRelatedToParam($currentValue);
        $criteriaCount = count($relatedTo) - 1;

        // Not possible to switch from `or` to `and` if there are multiple criteria
        if ($relatedTo[0] === 'or' && $criteriaCount > 1) {
            throw new NotSupportedException('It’s not possible to combine “or” and “and” relatedTo conditions.');
        }

        $relatedTo[0] = $criteriaCount > 0 ? 'and' : 'or';
        $relatedTo[] = ElementRelationParamParser::normalizeRelatedToCriteria($value);

        return $relatedTo;
    }

    /**
     * @inheritdoc
     * @uses $title
     */
    public function title($value): static
    {
        $this->title = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $slug
     */
    public function slug($value): static
    {
        $this->slug = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $uri
     */
    public function uri($value): static
    {
        $this->uri = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $search
     */
    public function search($value): static
    {
        $this->search = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $inBulkOp
     */
    public function inBulkOp(?string $value): static
    {
        $this->inBulkOp = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $ref
     */
    public function ref($value): static
    {
        $this->ref = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $with
     */
    public function with(array|string|null $value): static
    {
        $this->with = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $with
     */
    public function andWith(array|string|null $value): static
    {
        if (empty($this->with)) {
            $this->with = [$value];
        } else {
            if (is_string($this->with)) {
                $this->with = StringHelper::split($this->with);
            }
            $this->with[] = $value;
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function eagerly(string|bool $value = true): static
    {
        $this->eagerly = $value !== false;
        $this->eagerLoadAlias = is_string($value) ? $value : null;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $withCustomFields
     */
    public function withCustomFields(bool $value = true): static
    {
        $this->withCustomFields = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $withStructure
     */
    public function withStructure(bool $value = true): static
    {
        $this->withStructure = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $structureId
     */
    public function structureId(?int $value = null): static
    {
        $this->structureId = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $level
     */
    public function level($value = null): static
    {
        $this->level = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $hasDescendants
     */
    public function hasDescendants(bool $value = true): static
    {
        $this->hasDescendants = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $leaves
     */
    public function leaves(bool $value = true): static
    {
        $this->leaves = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $ancestorOf
     */
    public function ancestorOf(ElementInterface|int|null $value): static
    {
        $this->ancestorOf = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $ancestorDist
     */
    public function ancestorDist(?int $value = null): static
    {
        $this->ancestorDist = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $descendantOf
     */
    public function descendantOf(ElementInterface|int|null $value): static
    {
        $this->descendantOf = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $descendantDist
     */
    public function descendantDist(?int $value = null): static
    {
        $this->descendantDist = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $siblingOf
     */
    public function siblingOf(ElementInterface|int|null $value): static
    {
        $this->siblingOf = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $prevSiblingOf
     */
    public function prevSiblingOf(ElementInterface|int|null $value): static
    {
        $this->prevSiblingOf = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $nextSiblingOf
     */
    public function nextSiblingOf(ElementInterface|int|null $value): static
    {
        $this->nextSiblingOf = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $positionedBefore
     */
    public function positionedBefore(ElementInterface|int|null $value): static
    {
        $this->positionedBefore = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $positionedAfter
     */
    public function positionedAfter(ElementInterface|int|null $value): static
    {
        $this->positionedAfter = $value;
        return $this;
    }

    /**
     * Sets the [[status()|status]] param to `null`.
     *
     * @return static self reference
     * @since 3.0.17
     * @deprecated in 4.0.0. `status(null)` should be used instead.
     */
    public function anyStatus(): static
    {
        $this->status = null;
        return $this;
    }

    // Query preparation/execution
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function prepForEagerLoading(string $handle, ElementInterface $sourceElement): static
    {
        // Prefix the handle with the provider's handle, if there is one
        $providerHandle = $sourceElement->getFieldLayout()?->provider?->getHandle();
        $this->eagerLoadHandle = $providerHandle ? "$providerHandle:$handle" : $handle;

        $this->eagerLoadSourceElement = $sourceElement;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function wasEagerLoaded(?string $alias = null): bool
    {
        if (!isset($this->eagerLoadHandle, $this->eagerLoadSourceElement)) {
            return false;
        }

        if ($alias !== null) {
            return $this->eagerLoadSourceElement->hasEagerLoadedElements($alias);
        }

        $planHandle = $this->eagerLoadHandle;
        if (str_contains($planHandle, ':')) {
            $planHandle = explode(':', $planHandle, 2)[1];
        }
        return $this->eagerLoadSourceElement->hasEagerLoadedElements($planHandle);
    }

    /**
     * @inheritdoc
     */
    public function wasCountEagerLoaded(?string $alias = null): bool
    {
        if (!isset($this->eagerLoadHandle, $this->eagerLoadSourceElement)) {
            return false;
        }

        if ($alias !== null) {
            return $this->eagerLoadSourceElement->getEagerLoadedElementCount($alias) !== null;
        }

        $planHandle = $this->eagerLoadHandle;
        if (str_contains($planHandle, ':')) {
            $planHandle = explode(':', $planHandle, 2)[1];
        }
        return $this->eagerLoadSourceElement->getEagerLoadedElementCount($planHandle) !== null;
    }

    /**
     * @inheritdoc
     */
    public function cache($duration = true, $dependency = null): \yii\db\Query|ElementQuery
    {
        if ($dependency === null) {
            $dependency = new ElementQueryTagDependency($this);
        }

        return parent::cache($duration, $dependency);
    }

    /**
     * @inheritdoc
     * @return Query
     * @throws QueryAbortedException if it can be determined that there won’t be any results
     */
    public function prepare($builder): Query
    {
        // Log a warning if the app isn't fully initialized yet
        if (!Craft::$app->getIsInitialized()) {
            Craft::warning(
                "Element query executed before Craft is fully initialized.\nStack trace:\n" .
                App::backtrace(),
                __METHOD__
            );
        }

        if (!$builder->db instanceof Connection) {
            throw new QueryAbortedException(sprintf('Element queries must be executed for %s connections.', Connection::class));
        }

        // todo: remove after the next breakpoint
        if (!$builder->db->columnExists(Table::ELEMENTS_SITES, 'content')) {
            throw new QueryAbortedException("The elements_sites.content column doesn't exist yet.");
        }

        // Is the query already doomed?
        if (isset($this->id) && empty($this->id)) {
            throw new QueryAbortedException();
        }

        /** @var class-string<ElementInterface> $class */
        $class = $this->elementType;

        // Make sure the siteId param is set
        try {
            if (!$class::isLocalized()) {
                // The criteria *must* be set to the primary site ID
                $this->siteId = Craft::$app->getSites()->getPrimarySite()->id;
            } else {
                $this->_normalizeSiteId();
            }
        } catch (SiteNotFoundException $e) {
            // Fail silently if Craft isn't installed yet or is in the middle of updating
            if (Craft::$app->getIsInstalled() && !Craft::$app->getUpdates()->getIsCraftUpdatePending()) {
                /** @noinspection PhpUnhandledExceptionInspection */
                throw $e;
            }
            throw new QueryAbortedException($e->getMessage(), 0, $e);
        }

        // Clear out the previous cache tags
        $this->_cacheTags = null;

        // Normalize the orderBy param in case it was set directly
        if (!empty($this->orderBy)) {
            $this->orderBy = $this->normalizeOrderBy($this->orderBy);
        }

        // Normalize `offset` and `limit` for _applySearchParam()
        if (is_numeric($this->offset)) {
            $this->offset = (int)$this->offset;
        }
        if (is_numeric($this->limit)) {
            $this->limit = (int)$this->limit;
        }

        // Build the query
        // ---------------------------------------------------------------------

        $this->query = new Query();
        $this->query->withQueries = $this->withQueries;
        $this->subQuery = new Query();

        $this->query
            ->from(['subquery' => $this->subQuery])
            ->innerJoin(['elements' => Table::ELEMENTS], '[[elements.id]] = [[subquery.elementsId]]')
            ->innerJoin(['elements_sites' => Table::ELEMENTS_SITES], '[[elements_sites.id]] = [[subquery.siteSettingsId]]');

        // Prepare a new column mapping
        // (for use in SELECT and ORDER BY clauses)
        $this->_columnMap = [
            'id' => 'elements.id',
            'enabled' => 'elements.enabled',
            'dateCreated' => 'elements.dateCreated',
            'dateUpdated' => 'elements.dateUpdated',
            'uid' => 'elements.uid',
        ];

        if ($class::hasTitles()) {
            $this->_columnMap['title'] = 'elements_sites.title';
        }

        // Keep track of whether an element table is joined into the query
        $this->_joinedElementTable = false;

        // Give other classes a chance to make changes up front
        if (!$this->beforePrepare()) {
            throw new QueryAbortedException();
        }

        // Gather custom fields and generated field handles
        $this->customFields = [];
        $this->generatedFields = [];

        if ($this->withCustomFields) {
            foreach ($this->fieldLayouts() as $fieldLayout) {
                foreach ($fieldLayout->getCustomFields() as $field) {
                    $this->customFields[] = $field;
                }
                foreach ($fieldLayout->getGeneratedFields() as $field) {
                    $this->generatedFields[] = $field;
                }
            }
        }

        // Map custom field handles to their content values
        $this->_addCustomFieldsToColumnMap($builder->db);

        $this->subQuery
            ->addSelect([
                'elementsId' => 'elements.id',
                'siteSettingsId' => 'elements_sites.id',
            ])
            ->from(['elements' => Table::ELEMENTS])
            ->innerJoin(['elements_sites' => Table::ELEMENTS_SITES], '[[elements_sites.elementId]] = [[elements.id]]')
            ->offset($this->offset)
            ->limit($this->limit)
            ->addParams($this->params);

        $this->_applyWhereParam();

        if (Craft::$app->getIsMultiSite(false, true)) {
            $this->subQuery->andWhere(['elements_sites.siteId' => $this->siteId]);
        }

        $this->_applyCustomFieldParams();

        if ($this->distinct) {
            $this->query->distinct();
        }

        if ($this->groupBy) {
            $this->query->groupBy = $this->groupBy;
        }

        if ($this->id) {
            $this->subQuery->andWhere(Db::parseNumericParam('elements.id', $this->id));
        }

        if ($this->uid) {
            $this->subQuery->andWhere(Db::parseParam('elements.uid', $this->uid));
        }

        if ($this->siteSettingsId) {
            $this->subQuery->andWhere(Db::parseNumericParam('elements_sites.id', $this->siteSettingsId));
        }

        if ($this->archived) {
            $this->subQuery->andWhere(['elements.archived' => true]);
        } else {
            $this->_applyStatusParam($class);

            // only set archived=false if 'archived' doesn't show up in the status param
            // (_applyStatusParam() will normalize $this->status to an array if applicable)
            if (!is_array($this->status) || !in_array(Element::STATUS_ARCHIVED, $this->status)) {
                $this->subQuery->andWhere(['elements.archived' => false]);
            }
        }

        if ($this->trashed === false) {
            $this->subQuery->andWhere(['elements.dateDeleted' => null]);
        } elseif ($this->trashed === true) {
            $this->subQuery->andWhere(['not', ['elements.dateDeleted' => null]]);
        }

        if ($this->dateCreated) {
            $this->subQuery->andWhere(Db::parseDateParam('elements.dateCreated', $this->dateCreated));
        }

        if ($this->dateUpdated) {
            $this->subQuery->andWhere(Db::parseDateParam('elements.dateUpdated', $this->dateUpdated));
        }

        if (isset($this->title) && $this->title !== '' && $class::hasTitles()) {
            if (is_string($this->title)) {
                $this->title = Db::escapeCommas($this->title);
            }
            $this->subQuery->andWhere(Db::parseParam('elements_sites.title', $this->title, '=', true));
        }

        if ($this->slug) {
            $this->subQuery->andWhere(Db::parseParam('elements_sites.slug', $this->slug));
        }

        if ($this->uri) {
            $this->subQuery->andWhere(Db::parseParam('elements_sites.uri', $this->uri, '=', true));
        }

        $this->_applyRelatedToParam();
        $this->_applyNotRelatedToParam();
        $this->_applyStructureParams($class);
        $this->_applyRevisionParams();
        $this->_applySearchParam();
        $this->_applyInBulkOpParam();
        $this->_applyOrderByParams($builder->db);
        $this->_applySelectParam();
        $this->_applyJoinParams();

        // Give other classes a chance to make changes up front
        if (!$this->afterPrepare()) {
            throw new QueryAbortedException();
        }

        // If an element table was never joined in, explicitly filter based on the element type
        if (!$this->_joinedElementTable && $this->elementType) {
            try {
                $ref = new ReflectionClass($this->elementType);
            } catch (ReflectionException) {
                $ref = null;
            }
            /** @phpstan-ignore-next-line */
            if ($ref && !$ref->isAbstract()) {
                $this->subQuery->andWhere(['elements.type' => $this->elementType]);
            }
        }

        $this->_applyUniqueParam($builder->db);

        // Pass along the cache info
        if ($this->queryCacheDuration !== null) {
            $this->query->cache($this->queryCacheDuration, $this->queryCacheDependency);
        }

        // Pass the query back
        return $this->query;
    }

    /**
     * @inheritdoc
     * @return TElement[]|array The resulting elements.
     */
    public function populate($rows): array
    {
        if (empty($rows)) {
            return [];
        }

        // Should we set a search score on the elements?
        if (isset($this->_searchResults)) {
            foreach ($rows as &$row) {
                if (isset($row['id'], $row['siteId'])) {
                    $key = sprintf('%s-%s', $row['id'], $row['siteId']);
                    if (isset($this->_searchResults[$key])) {
                        $row['searchScore'] = (int)round($this->_searchResults[$key]);
                    }
                }
            }
        }

        $elements = $this->_createElements($rows);
        return $this->afterPopulate($elements);
    }

    /**
     * @inheritdoc
     */
    public function afterPopulate(array $elements): array
    {
        if (!$this->asArray) {
            $elementsService = Craft::$app->getElements();

            foreach ($elements as $element) {
                // Set the full query result on the element, in case it's needed for lazy eager loading
                $element->elementQueryResult = $elements;

                // If we're collecting cache info and the element is expirable, register its expiry date
                if (
                    $element instanceof ExpirableElementInterface &&
                    $elementsService->getIsCollectingCacheInfo() &&
                    ($expiryDate = $element->getExpiryDate()) !== null
                ) {
                    $elementsService->setCacheExpiryDate($expiryDate);
                }
            }

            ElementHelper::setNextPrevOnElements($elements);

            // Should we eager-load some elements onto these?
            if ($this->with) {
                $elementsService->eagerLoadElements($this->elementType, $elements, $this->with);
            }
        }

        return $elements;
    }

    /**
     * @inheritdoc
     */
    public function count($q = '*', $db = null): bool|int|string|null
    {
        // Cached?
        if (
            !$this->offset &&
            !$this->limit &&
            ($cachedResult = $this->getCachedResult()) !== null
        ) {
            return count($cachedResult);
        }

        $eagerLoadedCount = $this->eagerLoad(true);
        if ($eagerLoadedCount !== null) {
            return $eagerLoadedCount;
        }

        return parent::count($q, $db) ?: 0;
    }

    /**
     * @inheritdoc
     * @return TElement[]|array
     */
    public function all($db = null): array
    {
        // Cached?
        if (($cachedResult = $this->getCachedResult()) !== null) {
            if ($this->with) {
                Craft::$app->getElements()->eagerLoadElements($this->elementType, $cachedResult, $this->with);
            }
            return $cachedResult;
        }

        return $this->eagerLoad()?->all() ?? parent::all($db);
    }

    /**
     * @param YiiConnection|null $db
     * @return ElementCollection<TKey,TElement>
     */
    public function collect(?YiiConnection $db = null): ElementCollection
    {
        return $this->eagerLoad() ?? ElementCollection::make($this->all($db));
    }

    private function eagerLoad(bool $count = false, array $criteria = []): ElementCollection|int|null
    {
        if (
            !$this->eagerly ||
            !isset($this->eagerLoadSourceElement->elementQueryResult, $this->eagerLoadHandle) ||
            count($this->eagerLoadSourceElement->elementQueryResult) < 2
        ) {
            return null;
        }

        $alias = $this->eagerLoadAlias ?? "eagerly:$this->eagerLoadHandle";

        // see if it was already eager-loaded
        $eagerLoaded = match ($count) {
            true => $this->wasCountEagerLoaded($alias),
            false => $this->wasEagerLoaded($alias),
        };

        if (!$eagerLoaded) {
            Craft::$app->getElements()->eagerLoadElements(
                $this->eagerLoadSourceElement::class,
                $this->eagerLoadSourceElement->elementQueryResult,
                [
                    new EagerLoadPlan([
                        'handle' => $this->eagerLoadHandle,
                        'alias' => $alias,
                        'criteria' => $criteria + $this->getCriteria() + ['with' => $this->with],
                        'all' => !$count,
                        'count' => $count,
                        'lazy' => true,
                    ]),
                ],
            );
        }

        if ($count) {
            return $this->eagerLoadSourceElement->getEagerLoadedElementCount($alias);
        }

        return $this->eagerLoadSourceElement->getEagerLoadedElements($alias);
    }

    /**
     * @inheritdoc
     * @return TElement|array|null
     */
    public function one($db = null): mixed
    {
        // Cached?
        if (($cachedResult = $this->getCachedResult()) !== null) {
            return reset($cachedResult) ?: null;
        }

        // Eagerly?
        $eagerResult = $this->eagerLoad(criteria: ['limit' => 1]);
        if ($eagerResult !== null) {
            return $eagerResult->first();
        }

        if ($row = parent::one($db)) {
            $elements = $this->populate([$row]);
            return reset($elements) ?: null;
        }

        return null;
    }

    /**
     * @inheritdoc
     * @since 3.3.16.2
     */
    public function column($db = null): array
    {
        // Avoid indexing by an ambiguous column
        if (
            !isset($this->from) &&
            is_string($this->indexBy) &&
            in_array($this->indexBy, ['id', 'dateCreated', 'dateUpdated', 'uid'], true)
        ) {
            $this->from = ['elements' => Table::ELEMENTS];
            $result = parent::column($db);
            $this->from = null;
            return $result;
        }

        return parent::column($db);
    }

    /**
     * @inheritdoc
     */
    public function exists($db = null): bool
    {
        $cachedResult = $this->getCachedResult();
        if ($cachedResult !== null) {
            return !empty($cachedResult);
        }

        if (
            !$this->distinct
            && empty($this->groupBy)
            && empty($this->having)
            && empty($this->union)
        ) {
            try {
                $subquery = $this->prepareSubquery();

                // If distinct, et al. were set by prepare(), don't mess with it
                // see https://github.com/craftcms/cms/issues/15001#issuecomment-2174563927
                if (
                    !$subquery->distinct
                    && empty($subquery->groupBy)
                    && empty($subquery->having)
                    && empty($subquery->union)
                ) {
                    return $subquery
                        ->select('elements.id')
                        ->exists($db);
                }
            } catch (QueryAbortedException) {
                return false;
            }
        }

        return parent::exists($db);
    }

    /**
     * @inheritdoc
     * @return TElement|array|null
     */
    public function nth(int $n, ?YiiConnection $db = null): mixed
    {
        // Cached?
        if (($cachedResult = $this->getCachedResult()) !== null) {
            return $cachedResult[$n] ?? null;
        }

        // Eagerly?
        $eagerResult = $this->eagerLoad(criteria: [
            'offset' => ($this->offset ?: 0) + $n,
            'limit' => 1,
        ]);
        if ($eagerResult !== null) {
            return $eagerResult->first();
        }

        return parent::nth($n, $db);
    }

    /**
     * @inheritdoc
     */
    public function ids(?YiiConnection $db = null): array
    {
        $select = $this->select;
        $this->select = ['elements.id' => 'elements.id'];
        $result = $this->column($db);
        $this->select($select);

        return $result;
    }

    /**
     * Executes the query and renders the resulting elements using their partial templates.
     *
     * If no partial template exists for an element, its string representation will be output instead.
     *
     * @param array $variables
     * @return Markup
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @see ElementHelper::renderElements()
     * @since 5.0.0
     */
    public function render(array $variables = []): Markup
    {
        return ElementHelper::renderElements($this->all(), $variables);
    }

    /**
     * Returns the resulting elements set by [[setCachedResult()]], if the criteria params haven’t changed since then.
     *
     * @return TElement[]|null $elements The resulting elements, or null if setCachedResult() was never called or the criteria has changed
     * @see setCachedResult()
     */
    public function getCachedResult(): ?array
    {
        if (!isset($this->_result)) {
            return null;
        }

        // Make sure the criteria hasn't changed
        if ($this->_resultCriteria !== $this->getCriteria()) {
            $this->_result = null;
            return null;
        }

        return $this->_result;
    }

    /**
     * Sets the resulting elements.
     *
     * If this is called, [[all()]] will return these elements rather than initiating a new SQL query,
     * as long as none of the parameters have changed since setCachedResult() was called.
     *
     * @param TElement[] $elements The resulting elements.
     * @see getCachedResult()
     */
    public function setCachedResult(array $elements): void
    {
        $this->_result = $elements;
        $this->_resultCriteria = $this->getCriteria();
    }

    /**
     * Clears the [cached result](https://craftcms.com/docs/5.x/development/element-queries.html#cache).
     *
     * @see getCachedResult()
     * @see setCachedResult()
     * @since 3.4.0
     */
    public function clearCachedResult(): void
    {
        $this->_result = $this->_resultCriteria = null;
    }

    /**
     * Returns an array of the current criteria attribute values.
     *
     * @return array
     */
    public function getCriteria(): array
    {
        return $this->toArray($this->criteriaAttributes(), [], false);
    }

    /**
     * Returns the query's criteria attributes.
     *
     * @return string[]
     */
    public function criteriaAttributes(): array
    {
        $names = [];

        // By default, include all public, non-static properties that were defined by a sub class, and certain ones in this class
        foreach ((new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $dec = $property->getDeclaringClass();
                if (
                    ($dec->getName() === self::class || $dec->isSubclassOf(self::class)) &&
                    !in_array($property->getName(), ['elementType', 'query', 'subQuery', 'customFields', 'asArray', 'with', 'eagerly'], true)
                ) {
                    $names[] = $property->getName();
                }
            }
        }

        // Add custom field properties
        /** @var CustomFieldBehavior $behavior */
        $behavior = $this->getBehavior('customFields');
        foreach ((new ReflectionClass($behavior))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $name = $property->getName();
                if (
                    !in_array($name, ['canSetProperties', 'hasMethods', 'owner']) &&
                    !method_exists($this, "get$name")
                ) {
                    $names[] = $property->getName();
                }
            }
        }

        return $names;
    }

    /**
     * Prepares the element query and returns its subquery (which determines what elements will be returned).
     *
     * @param QueryBuilder|null $builder
     * @return Query
     * @since 4.0.3
     */
    public function prepareSubquery(?QueryBuilder $builder = null): Query
    {
        if ($builder === null) {
            $builder = Craft::$app->getDb()->getQueryBuilder();
        }

        /** @var Query */
        return $this->prepare($builder)->from['subquery'];
    }

    /**
     * @inheritdoc
     */
    protected function queryScalar($selectExpression, $db): bool|string|null
    {
        // Mostly copied from yii\db\Query::queryScalar(),
        // except that createCommand() is called on the prepared subquery rather than this query.
        // (We still temporarily override $select, $orderBy, $limit, and $offset on this query,
        // so those values look right from EVENT_BEFORE_PREPARE/EVENT_AFTER_PREPARE listeners.)

        if ($this->emulateExecution) {
            return null;
        }

        if (
            !$this->distinct
            && empty($this->groupBy)
            && empty($this->having)
            && empty($this->union)
        ) {
            // Set $orderBy, $limit, and $offset on this query just so it more closely resembles the
            // actual query that will be executed for `beforePrepare`/`afterPrepare` listeners
            // (https://github.com/craftcms/cms/issues/15001)

            // DON’T set $select though, in case this query ends up being cloned and executed from
            // an event handler, like BaseRelationField does. (https://github.com/craftcms/cms/issues/15071)

            $order = $this->orderBy;
            $limit = $this->limit;
            $offset = $this->offset;

            $this->orderBy = null;
            $this->limit = null;
            $this->offset = null;

            try {
                $subquery = $this->prepareSubquery();

                // If distinct, et al. were set by prepare(), don't mess with it
                // see https://github.com/craftcms/cms/issues/15001#issuecomment-2174563927
                if (
                    !$subquery->distinct
                    && empty($subquery->groupBy)
                    && empty($subquery->having)
                    && empty($subquery->union)
                ) {
                    $subquery->select = [$selectExpression];
                    $subquery->orderBy = null;
                    $subquery->limit = null;
                    $subquery->offset = null;
                    return $subquery->createCommand($db)->queryScalar();
                }
            } catch (QueryAbortedException) {
                return false;
            } finally {
                $this->orderBy = $order;
                $this->limit = $limit;
                $this->offset = $offset;
            }
        }

        return parent::queryScalar($selectExpression, $db);
    }

    // Arrayable methods
    // -------------------------------------------------------------------------

    /**
     * Returns the list of fields that should be returned by default by [[toArray()]] when no specific fields are specified.
     *
     * A field is a named element in the returned array by [[toArray()]].
     * This method should return an array of field names or field definitions.
     * If the former, the field name will be treated as an object property name whose value will be used
     * as the field value. If the latter, the array key should be the field name while the array value should be
     * the corresponding field definition which can be either an object property name or a PHP callable
     * returning the corresponding field value. The signature of the callable should be:
     *
     * ```php
     * function ($model, $field) {
     *     // return field value
     * }
     * ```
     *
     * For example, the following code declares four fields:
     *
     * - `email`: the field name is the same as the property name `email`;
     * - `firstName` and `lastName`: the field names are `firstName` and `lastName`, and their
     *   values are obtained from the `first_name` and `last_name` properties;
     * - `fullName`: the field name is `fullName`. Its value is obtained by concatenating `first_name`
     *   and `last_name`.
     *
     * ```php
     * return [
     *     'email',
     *     'firstName' => 'first_name',
     *     'lastName' => 'last_name',
     *     'fullName' => function ($model) {
     *         return $model->first_name . ' ' . $model->last_name;
     *     },
     * ];
     * ```
     *
     * @return array The list of field names or field definitions.
     * @see toArray()
     */
    public function fields(): array
    {
        $vars = array_keys(Craft::getObjectVars($this));
        $behavior = $this->getBehavior('customFields');
        $behaviorVars = array_keys(Craft::getObjectVars($behavior));
        // if using an array_merge here, reverse the order so that the $behaviorVars go before $vars;
        // the properties ($var) have to take priority over custom fields ($behaviorVars);
        $fields = array_combine($vars, $vars) +
            array_combine($behaviorVars, array_map(fn(string $var) => fn() => $behavior->$var, $behaviorVars));
        unset($fields['query'], $fields['subQuery'], $fields['owner']);
        return $fields;
    }

    // Internal Methods
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function createElement(array $row): ElementInterface
    {
        // Do we have a placeholder for this element?
        if (
            !$this->ignorePlaceholders &&
            isset($row['id'], $row['siteId']) &&
            ($element = Craft::$app->getElements()->getPlaceholderElement($row['id'], $row['siteId'])) !== null
        ) {
            return $element;
        }

        /** @var class-string<ElementInterface> $class */
        $class = $this->elementType;

        // Instantiate the element
        if ($this->structureId) {
            $row['structureId'] = $this->structureId;
        }

        if ($class::hasTitles()) {
            // Ensure the title is a string
            $row['title'] = (string)($row['title'] ?? '');
        }

        // Set the field values
        $content = ArrayHelper::remove($row, 'content');
        $row['fieldValues'] = [];

        if (!empty($content) && (!empty($this->customFields) || !empty($this->generatedFields))) {
            if (is_string($content)) {
                $content = Json::decode($content);
            }

            foreach ($this->customFields as $field) {
                if ($field::dbType() !== null && isset($content[$field->layoutElement->uid])) {
                    $handle = $field->layoutElement->handle ?? $field->handle;
                    $row['fieldValues'][$handle] = $content[$field->layoutElement->uid];
                }
            }

            foreach ($this->generatedFields as $field) {
                if (isset($content[$field['uid']])) {
                    $row['generatedFieldValues'][$field['uid']] = $content[$field['uid']];
                    if (($field['handle'] ?? '') !== '') {
                        $row['generatedFieldValues'][$field['handle']] = $content[$field['uid']];
                    }
                }
            }
        }

        if (array_key_exists('dateDeleted', $row)) {
            $row['trashed'] = $row['dateDeleted'] !== null;
        }

        $behaviors = [];

        if ($this->drafts !== false) {
            $row['isProvisionalDraft'] = (bool)($row['isProvisionalDraft'] ?? false);

            if (!empty($row['draftId'])) {
                $behaviors['draft'] = new DraftBehavior([
                    'creatorId' => ArrayHelper::remove($row, 'draftCreatorId'),
                    'draftName' => ArrayHelper::remove($row, 'draftName'),
                    'draftNotes' => ArrayHelper::remove($row, 'draftNotes'),
                ]);
            } else {
                unset(
                    $row['draftCreatorId'],
                    $row['draftName'],
                    $row['draftNotes']
                );
            }
        }

        if ($this->revisions !== false) {
            if (!empty($row['revisionId'])) {
                $behaviors['revision'] = new RevisionBehavior([
                    'creatorId' => ArrayHelper::remove($row, 'revisionCreatorId'),
                    'revisionNum' => ArrayHelper::remove($row, 'revisionNum'),
                    'revisionNotes' => ArrayHelper::remove($row, 'revisionNotes'),
                ]);
            } else {
                unset(
                    $row['revisionCreatorId'],
                    $row['revisionNum'],
                    $row['revisionNotes'],
                );
            }
        }

        $element = null;

        // Fire a 'beforePopulateElement' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_POPULATE_ELEMENT)) {
            $event = new PopulateElementEvent([
                'row' => $row,
            ]);
            $this->trigger(self::EVENT_BEFORE_POPULATE_ELEMENT, $event);
            $row = $event->row ?? $row;
            if (isset($event->element)) {
                $element = $event->element;
            }
        }

        $element ??= new $class($row);
        $element->attachBehaviors($behaviors);

        // Fire an 'afterPopulateElement' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_POPULATE_ELEMENT)) {
            $event = new PopulateElementEvent([
                'element' => $element,
                'row' => $row,
            ]);
            $this->trigger(self::EVENT_AFTER_POPULATE_ELEMENT, $event);
            return $event->element;
        }

        return $element;
    }

    /**
     * This method is called at the beginning of preparing an element query for the query builder.
     *
     * The main Query object being prepared for the query builder is available via [[query]].
     * The subselect’s Query object being prepared is available via [[subQuery]].
     * The role of the subselect query is to apply conditions to the query and narrow the result set down to
     * just the elements that should actually be returned.
     * The role of the main query is to join in any tables that should be included in the results, and select
     * all of the columns that should be included in the results.
     *
     * @return bool Whether the query should be prepared and returned to the query builder.
     * If false, the query will be cancelled and no results will be returned.
     * @throws QueryAbortedException
     * @see prepare()
     * @see afterPrepare()
     */
    protected function beforePrepare(): bool
    {
        // Fire a 'beforePrepare' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_PREPARE)) {
            $event = new CancelableEvent();
            $this->trigger(self::EVENT_BEFORE_PREPARE, $event);
            return $event->isValid;
        }

        return true;
    }

    /**
     * This method is called at the end of preparing an element query for the query builder.
     *
     * It is called at the beginning of [[prepare()]], right after [[query]] and [[subQuery]] have been created.
     *
     * @return bool Whether the query should be prepared and returned to the query builder.
     * If false, the query will be cancelled and no results will be returned.
     * @see prepare()
     * @see beforePrepare()
     */
    protected function afterPrepare(): bool
    {
        // Fire an 'afterPrepare' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_PREPARE)) {
            $event = new CancelableEvent();
            $this->trigger(self::EVENT_AFTER_PREPARE, $event);
            if (!$event->isValid) {
                return false;
            }
        }

        $elementsService = Craft::$app->getElements();
        if ($elementsService->getIsCollectingCacheInfo()) {
            $elementsService->collectCacheTags($this->getCacheTags());
        }

        return true;
    }

    /**
     * @return string[]
     */
    public function getCacheTags(): array
    {
        if ($this->_cacheTags === null) {
            $this->_cacheTags = [
                'element',
                "element::$this->elementType",
            ];

            // If (<= 100) specific IDs were requested, then use those
            if (
                is_numeric($this->id) ||
                (is_array($this->id) && count($this->id) <= 100 && ArrayHelper::isNumeric($this->id))
            ) {
                array_push($this->_cacheTags, ...array_map(fn($id) => "element::$id", (array)$this->id));
            } else {
                $queryTags = $this->cacheTags();

                // Fire a 'defineCacheTags' event
                if ($this->hasEventHandlers(self::EVENT_DEFINE_CACHE_TAGS)) {
                    $event = new DefineValueEvent(['value' => $queryTags]);
                    $this->trigger(self::EVENT_DEFINE_CACHE_TAGS, $event);
                    $queryTags = $event->value;
                }

                if (!empty($queryTags)) {
                    if ($this->drafts !== false) {
                        $queryTags[] = 'drafts';
                    }
                    if ($this->revisions !== false) {
                        $queryTags[] = 'revisions';
                    }
                } else {
                    $queryTags[] = '*';
                }

                foreach ($queryTags as $tag) {
                    // tags can be provided fully-formed, or relative to the element type
                    if (!str_starts_with($tag, 'element::')) {
                        $tag = sprintf('element::%s::%s', $this->elementType, $tag);
                    }
                    $this->_cacheTags[] = $tag;
                }
            }
        }

        return $this->_cacheTags;
    }

    /**
     * Returns any cache invalidation tags that caches involving this element query should use as dependencies.
     *
     * Use the most specific tag(s) possible, to reduce the likelihood of pointless cache clearing.
     *
     * When elements are created/updated/deleted, their [[ElementInterface::getCacheTags()]] method will be called,
     * and any caches that have those tags listed as dependencies will be invalidated.
     *
     * @return string[]
     * @since 3.5.0
     */
    protected function cacheTags(): array
    {
        return [];
    }

    /**
     * Returns the fields that should take part in an upcoming elements query.
     *
     * @return FieldInterface[] The fields that should take part in the upcoming elements query
     * @deprecated in 5.8.0.
     */
    protected function customFields(): array
    {
        if (!$this->withCustomFields) {
            return [];
        }
        $fields = [];
        foreach ($this->fieldLayouts() as $fieldLayout) {
            array_push($fields, ...$fieldLayout->getCustomFields());
        }
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayouts(): array
    {
        return $this->fieldLayouts();
    }

    /**
     * Returns the field layouts whose custom fields should be returned by [[customFields()]].
     *
     * @return FieldLayout[]
     * @since 5.0.0
     */
    protected function fieldLayouts(): array
    {
        return Craft::$app->getFields()->getLayoutsByType($this->elementType);
    }

    /**
     * Returns the condition that should be applied to the element query for a given status.
     *
     * For example, if you support a status called “pending”, which maps back to a `pending` database column that will
     * either be 0 or 1, this method could do this:
     *
     * ```php
     * protected function statusCondition($status)
     * {
     *     switch ($status) {
     *         case 'pending':
     *             return ['mytable.pending' => true];
     *         default:
     *             return parent::statusCondition($status);
     *     }
     * ```
     *
     * @param string $status The status
     * @return string|array|ExpressionInterface|false|null The status condition, or false if $status is an unsupported status
     */
    protected function statusCondition(string $status): mixed
    {
        return match ($status) {
            Element::STATUS_ENABLED => [
                'elements.enabled' => true,
                'elements_sites.enabled' => true,
            ],
            Element::STATUS_DISABLED => [
                'or',
                ['elements.enabled' => false],
                ['elements_sites.enabled' => false],
            ],
            Element::STATUS_ARCHIVED => ['elements.archived' => true],
            default => false,
        };
    }

    /**
     * Joins in a table with an `id` column that has a foreign key pointing to `elements.id`.
     *
     * The table will be joined with an alias based on the unprefixed table name. For example,
     * if `{{%entries}}` is passed, the table will be aliased to `entries`.
     *
     * @param string $table The table name, e.g. `entries` or `{{%entries}}`
     */
    protected function joinElementTable(string $table): void
    {
        $alias = Db::rawTableShortName($table);
        $table = "{{%$alias}}";

        $joinTable = [$alias => $table];
        $this->query->innerJoin($joinTable, "[[$alias.id]] = [[subquery.elementsId]]");
        $this->subQuery->innerJoin($joinTable, "[[$alias.id]] = [[elements.id]]");
        $this->_joinedElementTable = true;

        // Add element table cols to the column map
        foreach (Craft::$app->getDb()->getTableSchema($table)->columns as $column) {
            if (!isset($this->_columnMap[$column->name])) {
                $this->_columnMap[$column->name] = "$alias.$column->name";
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function normalizeOrderBy($columns): array
    {
        // Special case for 'score' - that should be shorthand for SORT_DESC, not SORT_ASC
        if (is_string($columns)) {
            $columns = preg_replace('/(?<=^|,)(\s*)score(\s*)(?=$|,)/', '$1score desc$2', $columns);
        }

        return parent::normalizeOrderBy($columns);
    }

    /**
     * Combines the given condition with an alternative condition if there are any relevant placeholder elements.
     *
     * @param mixed $condition
     * @return mixed
     */
    private function _placeholderCondition(mixed $condition): mixed
    {
        if ($this->ignorePlaceholders) {
            return $condition;
        }

        if (!isset($this->_placeholderCondition) || $this->siteId !== $this->_placeholderSiteIds) {
            $placeholderSourceIds = [];
            $placeholderElements = Craft::$app->getElements()->getPlaceholderElements();
            if (!empty($placeholderElements)) {
                $siteIds = array_flip((array)$this->siteId);
                foreach ($placeholderElements as $element) {
                    if ($element instanceof $this->elementType && isset($siteIds[$element->siteId])) {
                        $placeholderSourceIds[] = $element->getCanonicalId();
                    }
                }
            }

            if (!empty($placeholderSourceIds)) {
                $this->_placeholderCondition = ['elements.id' => $placeholderSourceIds];
            } else {
                $this->_placeholderCondition = false;
            }
            $this->_placeholderSiteIds = is_array($this->siteId) ? array_merge($this->siteId) : $this->siteId;
        }

        if ($this->_placeholderCondition === false) {
            return $condition;
        }

        return ['or', $condition, $this->_placeholderCondition];
    }

    /**
     * Include custom fields in the column map
     */
    private function _addCustomFieldsToColumnMap(Connection $db): void
    {
        $isMysql = $db->getIsMysql();

        foreach ($this->customFields as $field) {
            $dbTypes = $field::dbType();

            if ($dbTypes !== null) {
                if (is_string($dbTypes)) {
                    $dbTypes = ['*' => $dbTypes];
                } else {
                    $dbTypes = [
                        '*' => reset($dbTypes),
                        ...$dbTypes,
                    ];
                }

                foreach ($dbTypes as $key => $dbType) {
                    $alias = $field->handle . ($key !== '*' ? ".$key" : '');
                    $resolver = fn() => $field->getValueSql($key !== '*' ? $key : null);

                    $this->_addToColumnMap($alias, $resolver);

                    // for mysql, we have to make sure text column type is cast to char, otherwise it won't be sorted correctly
                    // see https://github.com/craftcms/cms/issues/15609
                    if ($isMysql && Db::parseColumnType($dbType) === Schema::TYPE_TEXT) {
                        $this->_columnsToCast[$alias] = 'CHAR(255)';
                    }
                }
            }
        }

        if (!empty($this->generatedFields)) {
            $qb = $db->getQueryBuilder();
            foreach ($this->generatedFields as $field) {
                if (($field['handle'] ?? '') !== '') {
                    $this->_addToColumnMap($field['handle'], $qb->jsonExtract('elements_sites.content', [$field['uid']]));
                }
            }
        }
    }

    private function _addToColumnMap(string $alias, string|callable $column): void
    {
        if (isset($this->_columnMap[$alias])) {
            if (!is_array($this->_columnMap[$alias])) {
                $this->_columnMap[$alias] = [$this->_columnMap[$alias]];
            }
            $this->_columnMap[$alias][] = $column;
        } else {
            $this->_columnMap[$alias] = $column;
        }
    }

    /**
     * Allow the custom fields to modify the query.
     *
     * @throws QueryAbortedException
     */
    private function _applyCustomFieldParams(): void
    {
        if (empty($this->customFields) && empty($this->generatedFields)) {
            return;
        }

        $generatedFieldsByHandle = [];
        if (!empty($this->generatedFields)) {
            // Group the generated fields by handle and field UUID
            foreach ($this->generatedFields as $field) {
                if (!empty($field['handle'])) {
                    $generatedFieldsByHandle[$field['handle']][$field['uid']] = $field;
                }
            }
        }

        /** @var CustomFieldBehavior $fieldAttributes */
        $fieldAttributes = $this->getBehavior('customFields');
        /** @var FieldInterface[][][] $fieldsByHandle */
        $fieldsByHandle = [];

        if (!empty($this->customFields)) {
            // Group the fields by handle and field UUID
            foreach ($this->customFields as $field) {
                $fieldsByHandle[$field->handle][$field->uid][] = $field;
            }

            foreach (array_keys(CustomFieldBehavior::$fieldHandles) as $handle) {
                // $fieldAttributes->$handle will return true even if it's set to null, so can't use isset() here
                if ($handle === 'owner' || ($fieldAttributes->$handle ?? null) === null) {
                    continue;
                }

                // Make sure the custom field exists in one of the field layouts or there's a generated field with that handle
                if (!isset($fieldsByHandle[$handle]) && !isset($generatedFieldsByHandle[$handle])) {
                    // If it looks like null/:empty: is a valid option, let it slide
                    $value = is_array($fieldAttributes->$handle) && isset($fieldAttributes->$handle['value'])
                        ? $fieldAttributes->$handle['value']
                        : $fieldAttributes->$handle;
                    if (is_array($value) && in_array(null, $value, true)) {
                        $values = [...$value];
                        $operator = QueryParam::extractOperator($values) ?? QueryParam::OR;
                        if ($operator === QueryParam::OR) {
                            continue;
                        }
                    }

                    throw new QueryAbortedException("No custom or generated field with the handle \"$handle\" exists in the field layouts involved with this element query.");
                }

                $conditions = [];
                $params = [];

                if (isset($fieldsByHandle[$handle])) {
                    foreach ($fieldsByHandle[$handle] as $instances) {
                        $firstInstance = $instances[0];
                        $condition = $firstInstance::queryCondition($instances, $fieldAttributes->$handle, $params);

                        // aborting?
                        if ($condition === false) {
                            throw new QueryAbortedException();
                        }

                        if ($condition !== null) {
                            $conditions[] = $condition;

                            // if we have a generated field with the same handle, we need to add it into the condition
                            if (isset($generatedFieldsByHandle[$handle])) {
                                $generatedFieldsConditions = $this->_conditionsForGeneratedFields(
                                    $generatedFieldsByHandle,
                                    $fieldAttributes,
                                    $fieldsByHandle,
                                    false
                                );
                                $conditions = array_merge($conditions, $generatedFieldsConditions);
                            }
                        }
                    }

                    if (!empty($conditions)) {
                        if (count($conditions) === 1) {
                            $this->subQuery->andWhere(reset($conditions), $params);
                        } else {
                            // if we're querying for empty, we need to glue the conditions with 'and'
                            $glue = $fieldAttributes->$handle === ':empty:' ? QueryParam::AND : QueryParam::OR;
                            $this->subQuery->andWhere([$glue, ...$conditions], $params);
                        }
                    }
                }
            }
        }

        if (!empty($this->generatedFields)) {
            $conditions = $this->_conditionsForGeneratedFields($generatedFieldsByHandle, $fieldAttributes, $fieldsByHandle);
            if (!empty($conditions)) {
                if (count($conditions) === 1) {
                    $this->subQuery->andWhere(reset($conditions));
                } else {
                    $this->subQuery->andWhere(['and', ...$conditions]);
                }
            }
        }
    }

    /**
     * Get query conditions for generated fields
     *
     * @param array $generatedFieldsByHandle
     * @param CustomFieldBehavior $fieldAttributes
     * @param array $fieldsByHandle
     * @param bool $checkCustomField whether to check if the custom field with that handle exists
     * @return array
     */
    private function _conditionsForGeneratedFields(
        array $generatedFieldsByHandle,
        CustomFieldBehavior $fieldAttributes,
        array $fieldsByHandle,
        bool $checkCustomField = true,
    ): array {
        $qb = Craft::$app->getDb()->getQueryBuilder();
        $conditions = [];
        $generatedFieldColumns = [];

        foreach ($generatedFieldsByHandle as $handle => $fields) {
            if (isset($fieldAttributes->$handle) && (!$checkCustomField || !isset($fieldsByHandle[$handle]))) {
                foreach ($fields as $field) {
                    $generatedFieldColumns[$handle][] = $qb->jsonExtract('elements_sites.content', [$field['uid']]);
                }
            }
        }

        foreach ($generatedFieldColumns as $handle => $columns) {
            $column = count($columns) === 1
                ? $columns[0]
                : (new CoalesceColumnsExpression($columns))->getSql($this->subQuery->params);
            $conditions[] = Db::parseParam($column, $fieldAttributes->$handle);
        }

        return $conditions;
    }

    /**
     * Applies the 'status' param to the query being prepared.
     *
     * @param class-string<ElementInterface> $class
     * @throws QueryAbortedException
     */
    private function _applyStatusParam(string $class): void
    {
        if (!$this->status || !$class::hasStatuses()) {
            return;
        }

        // Normalize the status param
        if (!is_array($this->status)) {
            $this->status = StringHelper::split($this->status);
        }

        $statuses = array_merge($this->status);

        $firstVal = strtolower(reset($statuses));
        if (in_array($firstVal, ['not', 'or'])) {
            $glue = $firstVal;
            array_shift($statuses);
            if (!$statuses) {
                return;
            }
        } else {
            $glue = 'or';
        }

        if ($negate = ($glue === 'not')) {
            $glue = 'and';
        }

        $condition = [$glue];

        foreach ($statuses as $status) {
            $status = strtolower($status);
            $statusCondition = $this->statusCondition($status);

            if ($statusCondition === false) {
                throw new QueryAbortedException('Unsupported status: ' . $status);
            }

            if ($statusCondition !== null) {
                if ($negate) {
                    $condition[] = ['not', $statusCondition];
                } else {
                    $condition[] = $statusCondition;
                }
            }
        }

        $this->subQuery->andWhere($this->_placeholderCondition($condition));
    }

    /**
     * Applies the 'relatedTo' param to the query being prepared.
     *
     * @throws QueryAbortedException
     */
    private function _applyRelatedToParam(): void
    {
        if (!$this->relatedTo) {
            return;
        }

        $parser = new ElementRelationParamParser([
            'fields' => $this->customFields ? ArrayHelper::index(
                $this->customFields,
                fn(FieldInterface $field) => $field->layoutElement?->getOriginalHandle() ?? $field->handle,
            ) : [],
        ]);
        $condition = $parser->parse($this->relatedTo, $this->siteId !== '*' ? $this->siteId : null);

        if ($condition === false) {
            throw new QueryAbortedException();
        }

        $this->subQuery->andWhere($condition);
    }

    /**
     * Applies the 'notRelatedTo' param to the query being prepared.
     *
     * @throws QueryAbortedException
     */
    private function _applyNotRelatedToParam(): void
    {
        if (!$this->notRelatedTo) {
            return;
        }

        $notRelatedToParam = $this->notRelatedTo;

        $parser = new ElementRelationParamParser([
            'fields' => $this->customFields ? ArrayHelper::index(
                $this->customFields,
                fn(FieldInterface $field) => $field->layoutElement?->getOriginalHandle() ?? $field->handle,
            ) : [],
        ]);
        $condition = $parser->parse($notRelatedToParam, $this->siteId !== '*' ? $this->siteId : null);

        if ($condition === false) {
            // just don't modify the query
            return;
        }

        // Prepend `not` as this is not expect to be provided
        $condition = ['not', $condition];

        $this->subQuery->andWhere($condition);
    }

    /**
     * Returns whether we should join structure data in the query.
     *
     * @return bool
     */
    private function _shouldJoinStructureData(): bool
    {
        return (
            !$this->revisions &&
            ($this->withStructure ?? ($this->structureId && !$this->trashed))
        );
    }

    /**
     * Applies the structure params to the query being prepared.
     *
     * @param class-string<ElementInterface> $class
     * @throws QueryAbortedException
     */
    private function _applyStructureParams(string $class): void
    {
        if (!$this->_shouldJoinStructureData()) {
            $structureParams = [
                'hasDescendants',
                'ancestorOf',
                'descendantOf',
                'siblingOf',
                'prevSiblingOf',
                'nextSiblingOf',
                'positionedBefore',
                'positionedAfter',
                'level',
            ];

            foreach ($structureParams as $param) {
                if ($this->$param !== null) {
                    throw new QueryAbortedException("Unable to apply the '$param' param because 'structureId' isn't set");
                }
            }

            return;
        }

        $this->query
            ->addSelect([
                'structureelements.root',
                'structureelements.lft',
                'structureelements.rgt',
                'structureelements.level',
            ]);

        if ($this->structureId) {
            $this->query->leftJoin(['structureelements' => Table::STRUCTUREELEMENTS], [
                'and',
                '[[structureelements.elementId]] = [[subquery.elementsId]]',
                ['structureelements.structureId' => $this->structureId],
            ]);
            $this->subQuery->leftJoin(['structureelements' => Table::STRUCTUREELEMENTS], [
                'and',
                '[[structureelements.elementId]] = [[elements.id]]',
                ['structureelements.structureId' => $this->structureId],
            ]);
        } else {
            $this->query
                ->addSelect(['structureelements.structureId'])
                ->leftJoin(['structureelements' => Table::STRUCTUREELEMENTS], [
                    'and',
                    '[[structureelements.elementId]] = [[subquery.elementsId]]',
                    '[[structureelements.structureId]] = [[subquery.structureId]]',
                ]);
            $existsQuery = new Query();
            // Use index hints to specify index so Mysql does not select the less
            // performant one (dateDeleted).
            if (Craft::$app->getDb()->getIsMysql()) {
                $existsQuery->from([new Expression(sprintf('%s use index(primary)', Table::STRUCTURES))]);
            } else {
                $existsQuery->from([Table::STRUCTURES]);
            }
            $existsQuery
                ->where('[[id]] = [[structureelements.structureId]]')
                ->andWhere(['dateDeleted' => null]);
            $this->subQuery
                ->addSelect(['structureelements.structureId'])
                ->leftJoin(['structureelements' => Table::STRUCTUREELEMENTS], [
                    'and',
                    '[[structureelements.elementId]] = [[elements.id]]',
                    ['exists', $existsQuery],
                ]);
        }

        if (isset($this->hasDescendants)) {
            if ($this->hasDescendants) {
                $this->subQuery->andWhere('[[structureelements.rgt]] > [[structureelements.lft]] + 1');
            } else {
                $this->subQuery->andWhere('[[structureelements.rgt]] = [[structureelements.lft]] + 1');
            }
        }

        if ($this->ancestorOf) {
            $ancestorOf = $this->_normalizeStructureParamValue('ancestorOf', $class);

            $this->subQuery->andWhere([
                'and',
                ['<', 'structureelements.lft', $ancestorOf->lft],
                ['>', 'structureelements.rgt', $ancestorOf->rgt],
                ['structureelements.root' => $ancestorOf->root],
            ]);

            if ($this->ancestorDist) {
                $this->subQuery->andWhere(['>=', 'structureelements.level', $ancestorOf->level - $this->ancestorDist]);
            }
        }

        if ($this->descendantOf) {
            $descendantOf = $this->_normalizeStructureParamValue('descendantOf', $class);

            $this->subQuery->andWhere([
                'and',
                ['>', 'structureelements.lft', $descendantOf->lft],
                ['<', 'structureelements.rgt', $descendantOf->rgt],
                ['structureelements.root' => $descendantOf->root],
            ]);

            if ($this->descendantDist) {
                $this->subQuery->andWhere(['<=', 'structureelements.level', $descendantOf->level + $this->descendantDist]);
            }
        }

        foreach (['siblingOf', 'prevSiblingOf', 'nextSiblingOf'] as $param) {
            if (!$this->$param) {
                continue;
            }

            $siblingOf = $this->_normalizeStructureParamValue($param, $class);

            $this->subQuery->andWhere([
                'and',
                [
                    'structureelements.level' => $siblingOf->level,
                    'structureelements.root' => $siblingOf->root,
                ],
                ['not', ['structureelements.elementId' => $siblingOf->id]],
            ]);

            if ($siblingOf->level != 1) {
                $parent = $siblingOf->getParent();

                if (!$parent) {
                    throw new QueryAbortedException();
                }

                $this->subQuery->andWhere([
                    'and',
                    ['>', 'structureelements.lft', $parent->lft],
                    ['<', 'structureelements.rgt', $parent->rgt],
                ]);
            }

            switch ($param) {
                case 'prevSiblingOf':
                    $this->query->orderBy(['structureelements.lft' => SORT_DESC]);
                    $this->subQuery
                        ->andWhere(['<', 'structureelements.lft', $siblingOf->lft])
                        ->orderBy(['structureelements.lft' => SORT_DESC])
                        ->limit(1);
                    break;
                case 'nextSiblingOf':
                    $this->query->orderBy(['structureelements.lft' => SORT_ASC]);
                    $this->subQuery
                        ->andWhere(['>', 'structureelements.lft', $siblingOf->lft])
                        ->orderBy(['structureelements.lft' => SORT_ASC])
                        ->limit(1);
                    break;
            }
        }

        if ($this->positionedBefore) {
            $positionedBefore = $this->_normalizeStructureParamValue('positionedBefore', $class);

            $this->subQuery->andWhere([
                'and',
                ['<', 'structureelements.lft', $positionedBefore->lft],
                ['structureelements.root' => $positionedBefore->root],
            ]);
        }

        if ($this->positionedAfter) {
            $positionedAfter = $this->_normalizeStructureParamValue('positionedAfter', $class);

            $this->subQuery->andWhere([
                'and',
                ['>', 'structureelements.lft', $positionedAfter->rgt],
                ['structureelements.root' => $positionedAfter->root],
            ]);
        }

        if ($this->level) {
            $allowNull = is_array($this->level) && in_array(null, $this->level, true);
            if ($allowNull) {
                $levelCondition = [
                    'or',
                    Db::parseNumericParam('structureelements.level', array_filter($this->level, fn($v) => $v !== null)),
                    ['structureelements.level' => null],
                ];
            } else {
                $levelCondition = Db::parseNumericParam('structureelements.level', $this->level);
            }
            $this->subQuery->andWhere($levelCondition);
        }

        if ($this->leaves) {
            $this->subQuery->andWhere('[[structureelements.rgt]] = [[structureelements.lft]] + 1');
        }
    }

    /**
     * Applies draft and revision params to the query being prepared.
     */
    private function _applyRevisionParams(): void
    {
        if ($this->drafts !== false) {
            $joinType = $this->drafts === true ? 'INNER JOIN' : 'LEFT JOIN';
            $this->subQuery->join($joinType, ['drafts' => Table::DRAFTS], '[[drafts.id]] = [[elements.draftId]]');
            $this->query->join($joinType, ['drafts' => Table::DRAFTS], '[[drafts.id]] = [[elements.draftId]]');

            $this->query->addSelect([
                'elements.draftId',
                'drafts.creatorId as draftCreatorId',
                'drafts.provisional as isProvisionalDraft',
                'drafts.name as draftName',
                'drafts.notes as draftNotes',
            ]);

            if ($this->draftId) {
                $this->subQuery->andWhere(['elements.draftId' => $this->draftId]);
            }

            if ($this->draftOf === '*') {
                $this->subQuery->andWhere(['not', ['elements.canonicalId' => null]]);
            } elseif (isset($this->draftOf)) {
                $this->subQuery->andWhere(['elements.canonicalId' => $this->draftOf ?: null]);
            }

            if ($this->draftCreator) {
                $this->subQuery->andWhere(['drafts.creatorId' => $this->draftCreator]);
            }

            if (isset($this->provisionalDrafts)) {
                $this->subQuery->andWhere([
                    'or',
                    ['elements.draftId' => null],
                    ['drafts.provisional' => $this->provisionalDrafts],
                ]);
            }

            if ($this->canonicalsOnly) {
                $this->subQuery->andWhere([
                    'or',
                    ['elements.draftId' => null],
                    [
                        'elements.canonicalId' => null,
                        ...($this->savedDraftsOnly ? ['drafts.saved' => true] : []),
                    ],
                ]);
            } elseif ($this->savedDraftsOnly) {
                $this->subQuery->andWhere([
                    'or',
                    ['elements.draftId' => null],
                    ['not', ['elements.canonicalId' => null]],
                    ['drafts.saved' => true],
                ]);
            }
        } else {
            $this->subQuery->andWhere($this->_placeholderCondition(['elements.draftId' => null]));
        }

        if ($this->revisions !== false) {
            $joinType = $this->revisions === true ? 'INNER JOIN' : 'LEFT JOIN';
            $this->subQuery->join($joinType, ['revisions' => Table::REVISIONS], '[[revisions.id]] = [[elements.revisionId]]');
            $this->query->join($joinType, ['revisions' => Table::REVISIONS], '[[revisions.id]] = [[elements.revisionId]]');

            $this->query->addSelect([
                'elements.revisionId',
                'revisions.creatorId as revisionCreatorId',
                'revisions.num as revisionNum',
                'revisions.notes as revisionNotes',
            ]);

            if ($this->revisionId) {
                $this->subQuery->andWhere(['elements.revisionId' => $this->revisionId]);
            }

            if ($this->revisionOf) {
                $this->subQuery->andWhere(['elements.canonicalId' => $this->revisionOf]);
            }

            if ($this->revisionCreator) {
                $this->subQuery->andWhere(['revisions.creatorId' => $this->revisionCreator]);
            }
        } else {
            $this->subQuery->andWhere($this->_placeholderCondition(['elements.revisionId' => null]));
        }
    }

    /**
     * Normalizes the siteId param value.
     */
    private function _normalizeSiteId(): void
    {
        $sitesService = Craft::$app->getSites();
        if (!$this->siteId) {
            // Default to the current site
            $this->siteId = $sitesService->getCurrentSite()->id;
        } elseif ($this->siteId === '*') {
            $this->siteId = $sitesService->getAllSiteIds();
        } elseif (is_numeric($this->siteId) || ArrayHelper::isNumeric($this->siteId)) {
            // Filter out any invalid site IDs
            $siteIds = Collection::make((array)$this->siteId)
                ->filter(fn($siteId) => $sitesService->getSiteById($siteId, true) !== null)
                ->all();
            if (empty($siteIds)) {
                throw new QueryAbortedException();
            }
            $this->siteId = is_array($this->siteId) ? $siteIds : reset($siteIds);
        }
    }

    /**
     * Normalizes a structure param value to either an Element object or false.
     *
     * @param string $property The parameter’s property name.
     * @param class-string<ElementInterface> $class The element class
     * @return ElementInterface The normalized element
     * @throws QueryAbortedException if the element can't be found
     */
    private function _normalizeStructureParamValue(string $property, string $class): ElementInterface
    {
        $element = $this->$property;

        if ($element === false) {
            throw new QueryAbortedException();
        }

        if ($element instanceof ElementInterface && !$element->lft) {
            $element = $element->getCanonicalId();

            if ($element === null) {
                throw new QueryAbortedException();
            }
        }

        if (!$element instanceof ElementInterface) {
            $element = Craft::$app->getElements()->getElementById($element, $class, $this->siteId, [
                'structureId' => $this->structureId,
            ]);

            if ($element === null) {
                $this->$property = false;
                throw new QueryAbortedException();
            }
        }

        if (!$element->lft) {
            if ($element->getIsDerivative()) {
                $element = $element->getCanonical(true);
            }

            if (!$element->lft) {
                $this->$property = false;
                throw new QueryAbortedException();
            }
        }

        return $this->$property = $element;
    }

    /**
     * Applies the 'search' param to the query being prepared.
     *
     * @throws QueryAbortedException
     */
    private function _applySearchParam(): void
    {
        $this->_searchResults = null;

        if (!$this->search) {
            return;
        }

        $searchService = Craft::$app->getSearch();

        if (isset($this->orderBy['score']) || $searchService->shouldCallSearchElements($this)) {
            // Get the scored results up front
            $searchResults = $searchService->searchElements($this);

            if ($this->orderBy['score'] === SORT_ASC) {
                $searchResults = array_reverse($searchResults, true);
            }

            if (array_key_first($this->orderBy) === 'score') {
                // Only use the portion we're actually querying for
                if (is_int($this->offset) && $this->offset !== 0) {
                    $searchResults = array_slice($searchResults, $this->offset, null, true);
                    $this->subQuery->offset(null);
                }
                if (is_int($this->limit) && $this->limit !== 0) {
                    $searchResults = array_slice($searchResults, 0, $this->limit, true);
                    $this->subQuery->limit(null);
                }
            }

            if (empty($searchResults)) {
                throw new QueryAbortedException();
            }

            $this->_searchResults = $searchResults;

            $elementIdsBySiteId = [];
            foreach (array_keys($searchResults) as $key) {
                [$elementId, $siteId] = explode('-', $key, 2);
                $elementIdsBySiteId[$siteId][] = $elementId;
            }
            $condition = ['or'];
            foreach ($elementIdsBySiteId as $siteId => $elementIds) {
                $condition[] = [
                    'elements_sites.siteId' => $siteId,
                    'elements.id' => $elementIds,
                ];
            }
            $this->subQuery->andWhere($condition);
        } else {
            // Just filter the main query by the search query
            $searchQuery = $searchService->createDbQuery($this->search, $this);

            if ($searchQuery === false) {
                throw new QueryAbortedException();
            }

            $this->subQuery->andWhere([
                'elements.id' => $searchQuery->select(['elementId']),
            ]);
        }
    }

    /**
     * Applies the 'inBulkOp' param to the query being prepared.
     */
    private function _applyInBulkOpParam(): void
    {
        if ($this->inBulkOp) {
            $this->subQuery
                ->innerJoin(['elements_bulkops' => Table::ELEMENTS_BULKOPS], '[[elements_bulkops.elementId]] = [[elements.id]]')
                ->andWhere(['elements_bulkops.key' => $this->inBulkOp]);
        }
    }

    /**
     * Applies the 'fixedOrder' and 'orderBy' params to the query being prepared.
     *
     * @param YiiConnection $db
     * @throws Exception if the DB connection doesn't support fixed ordering
     * @throws QueryAbortedException
     */
    private function _applyOrderByParams(YiiConnection $db): void
    {
        if (!isset($this->orderBy) || !empty($this->query->orderBy)) {
            return;
        }

        $orderBy = array_merge($this->orderBy ?: []);

        // Only set to the default order if `orderBy` is still set to the placeholder
        if (
            count($orderBy) === 1 &&
            ($orderBy[0] ?? null) instanceof OrderByPlaceholderExpression
        ) {
            if ($this->fixedOrder) {
                if (empty($this->id)) {
                    throw new QueryAbortedException();
                }

                $ids = $this->id;
                if (!is_array($ids)) {
                    $ids = is_string($ids) ? StringHelper::split($ids) : [$ids];
                }

                if (!$db instanceof Connection) {
                    throw new Exception('The database connection doesn’t support fixed ordering.');
                }
                $orderBy = [new FixedOrderExpression('elements.id', $ids, $db)];
            } elseif ($this->revisions) {
                $orderBy = ['num' => SORT_DESC];
            } elseif ($this->_shouldJoinStructureData()) {
                $orderBy = ['structureelements.lft' => SORT_ASC] + $this->defaultOrderBy;
            } elseif (!empty($this->defaultOrderBy)) {
                $orderBy = $this->defaultOrderBy;
            } else {
                return;
            }
        } else {
            $orderBy = array_filter($orderBy, fn($value) => !$value instanceof OrderByPlaceholderExpression);
        }

        // Parse orderBy keys for column mappings
        $orderByColumns = array_keys($orderBy);
        foreach ($orderByColumns as $i => $column) {
            $parsed = $this->_parseStrForColumnMappings($column);
            // add CAST()?
            if (
                isset($this->_columnsToCast[$column]) &&
                !str_contains($parsed, 'COALESCE(')
            ) {
                $orderByColumns[$i] = "CAST($parsed AS {$this->_columnsToCast[$column]})";
            } else {
                $orderByColumns[$i] = $parsed;
            }
        }
        $orderBy = array_combine($orderByColumns, $orderBy);

        // swap `score` direction value with a fixed order expression
        if (isset($this->_searchResults)) {
            $scoreSql = 'CASE';
            $scoreParams = [];
            $paramSuffix = StringHelper::randomString(10);
            $keys = array_keys($this->_searchResults);
            if ($this->inReverse) {
                $keys = array_reverse($keys);
            }
            $i = -1;
            foreach ($keys as $i => $key) {
                [$elementId, $siteId] = array_pad(explode('-', $key, 2), 2, null);
                if ($siteId === null) {
                    throw new InvalidValueException("Invalid element search score key: \"$key\". Search scores should be indexed by element ID and site ID (e.g. \"100-1\").");
                }
                $keyParamSuffix = sprintf('%s_%s', $paramSuffix, $i);
                $scoreSql .= sprintf(
                    ' WHEN [[elements.id]] = :elementId_%s AND [[elements_sites.siteId]] = :siteId_%s THEN :value_%s',
                    $keyParamSuffix, $keyParamSuffix, $keyParamSuffix
                );
                $scoreParams[":elementId_$keyParamSuffix"] = $elementId;
                $scoreParams[":siteId_$keyParamSuffix"] = $siteId;
                $scoreParams[":value_$keyParamSuffix"] = $i;
            }
            $defaultParam = sprintf(':value_%s_%s', $paramSuffix, $i + 1);
            $scoreSql .= sprintf(' ELSE %s END', $defaultParam);
            $scoreParams[$defaultParam] = $i + 1;
            $orderBy['score'] = new Expression($scoreSql, $scoreParams);
        } else {
            unset($orderBy['score']);
        }

        if ($this->inReverse) {
            foreach ($orderBy as &$direction) {
                if ($direction instanceof FixedOrderExpression) {
                    $values = array_reverse($direction->values);
                    $direction = new FixedOrderExpression($direction->column, $values, $direction->db, $direction->params);
                } // Can't do anything about custom SQL expressions
                elseif (!$direction instanceof ExpressionInterface) {
                    $direction = $direction === SORT_DESC ? SORT_ASC : SORT_DESC;
                }
            }
            unset($direction);
        }

        $this->query->orderBy($orderBy);
        $this->subQuery->orderBy($orderBy);
    }

    /**
     * Applies the 'select' param to the query being prepared.
     */
    private function _applySelectParam(): void
    {
        // Select all columns defined by [[select]], swapping out any mapped column names
        $select = [];
        $includeDefaults = false;

        foreach ((array)$this->select as $alias => $column) {
            if ($alias === '**') {
                $includeDefaults = true;
            } else {
                // Is this a mapped column name?
                if (is_string($column) && isset($this->_columnMap[$column])) {
                    $column = $this->_resolveColumnMapping($column);

                    // Completely ditch the mapped name if instantiated elements are going to be returned
                    if (!$this->asArray && is_string($column)) {
                        $alias = $column;
                    }
                }

                if (is_array($column)) {
                    $select[$alias] = new CoalesceColumnsExpression($column);
                } else {
                    $select[$alias] = $column;
                }
            }
        }

        // Is there still a ** placeholder param?
        if ($includeDefaults) {
            // Merge in the default columns
            $select = array_merge($select, [
                'elements.id' => 'elements.id',
                'elements.canonicalId' => 'elements.canonicalId',
                'elements.fieldLayoutId' => 'elements.fieldLayoutId',
                'elements.uid' => 'elements.uid',
                'elements.enabled' => 'elements.enabled',
                'elements.archived' => 'elements.archived',
                'elements.dateLastMerged' => 'elements.dateLastMerged',
                'elements.dateCreated' => 'elements.dateCreated',
                'elements.dateUpdated' => 'elements.dateUpdated',
                'siteSettingsId' => 'elements_sites.id',
                'elements_sites.siteId' => 'elements_sites.siteId',
                'elements_sites.title' => 'elements_sites.title',
                'elements_sites.slug' => 'elements_sites.slug',
                'elements_sites.uri' => 'elements_sites.uri',
                'elements_sites.content' => 'elements_sites.content',
                'enabledForSite' => 'elements_sites.enabled',
            ]);

            // If the query includes soft-deleted elements, include the date deleted
            if ($this->trashed !== false) {
                $select['elements.dateDeleted'] = 'elements.dateDeleted';
            }

            // If the query already specifies any columns, merge those in too
            if (!empty($this->query->select)) {
                $select = array_merge($select, $this->query->select);
            }
        }

        $this->query->select = $select;
    }

    /**
     * Applies the 'join' params to the query being prepared.
     */
    private function _applyJoinParams(): void
    {
        if (isset($this->join)) {
            foreach ($this->join as $join) {
                $this->query->join[] = $join;
                $this->subQuery->join[] = $join;
            }
        }
    }

    /**
     * Applies the 'unique' param to the query being prepared
     *
     * @param YiiConnection $db
     */
    private function _applyUniqueParam(YiiConnection $db): void
    {
        if (
            !$this->unique ||
            !Craft::$app->getIsMultiSite(false, true) ||
            (
                $this->siteId &&
                (!is_array($this->siteId) || count($this->siteId) === 1)
            )
        ) {
            return;
        }

        $sitesService = Craft::$app->getSites();

        if (!$this->preferSites) {
            $preferSites = [$sitesService->getCurrentSite()->id];
        } else {
            $preferSites = [];
            foreach ($this->preferSites as $preferSite) {
                if (is_numeric($preferSite)) {
                    $preferSites[] = $preferSite;
                } elseif ($site = $sitesService->getSiteByHandle($preferSite)) {
                    $preferSites[] = $site->id;
                }
            }
        }

        $caseSql = 'case';
        $caseParams = [];
        foreach ($preferSites as $index => $siteId) {
            $param = 'preferSites' . $index;
            $caseSql .= " when [[elements_sites.siteId]] = :$param then $index";
            $caseParams[$param] = $siteId;
        }
        $caseSql .= ' else ' . count($preferSites) . ' end';

        $subSelectSql = (clone $this->subQuery)
            ->select(['elements_sites.id'])
            ->andWhere('[[subElements.id]] = [[tmpElements.id]]')
            ->orderBy([
                new Expression($caseSql, $caseParams),
                'elements_sites.id' => SORT_ASC,
            ])
            ->offset(0)
            ->limit(1)
            ->getRawSql();

        // `elements` => `subElements`
        $qElements = $db->quoteTableName('elements');
        $qSubElements = $db->quoteTableName('subElements');
        $qTmpElements = $db->quoteTableName('tmpElements');
        $q = $qElements[0];
        $subSelectSql = str_replace("$qElements.", "$qSubElements.", $subSelectSql);
        $subSelectSql = str_replace("$q $qElements", "$q $qSubElements", $subSelectSql);
        $subSelectSql = str_replace($qTmpElements, $qElements, $subSelectSql);

        $this->subQuery->andWhere(new Expression("[[elements_sites.id]] = ($subSelectSql)"));
    }

    /**
     * Applies the `where` param to the query being prepraed.
     */
    private function _applyWhereParam(): void
    {
        if (empty($this->where)) {
            return;
        }

        if (is_string($this->where)) {
            $where = $this->_parseStrForColumnMappings($this->where);
        } elseif (is_array($this->where)) {
            $where = $this->_parseArrayCondition($this->where);
        } else {
            $where = $this->where;
        }

        $this->subQuery->andWhere($where);
    }

    private function _parseStrForColumnMappings(string $str): string
    {
        if (!str_contains($str, '[')) {
            return $this->_columnMappingSql($str) ?? $str;
        }

        return preg_replace_callback('/(?<!\.)\[\[(\w+(?:\.\w+)?)]]/', function(array $match) {
            $mapping = $this->_columnMappingSql($match[1]);
            if ($mapping === null) {
                return $match[0];
            }
            if (preg_match('/^\w+(?:\.\w+)?$/', $mapping)) {
                return "[[$mapping]]";
            }
            return $mapping;
        }, $str);
    }

    private function _columnMappingSql(string $str): ?string
    {
        if (!isset($this->_columnMap[$str])) {
            return null;
        }

        $column = $this->_resolveColumnMapping($str);
        return is_array($column)
            ? (new CoalesceColumnsExpression($column))->getSql($this->subQuery->params)
            : $column;
    }

    private function _parseArrayCondition(array $condition): array
    {
        $parsed = [];

        if (isset($condition[0])) {
            // Operator format: [operator, ...operands]
            $operator = $parsed[] = strtoupper(array_shift($condition));
            if (in_array($operator, ['NOT', 'AND', 'OR'])) {
                foreach ($condition as $value) {
                    if (is_string($value)) {
                        $value = $this->_parseStrForColumnMappings($value);
                    } elseif (is_array($value)) {
                        $value = $this->_parseArrayCondition($value);
                    }
                    $parsed[] = $value;
                }
            } else {
                if (isset($condition[0]) && is_string($condition[0])) {
                    $condition[0] = $this->_columnMappingSql($condition[0]) ?? $condition[0];
                }
                array_push($parsed, ...$condition);
            }
        } else {
            // Hash format: [column => value]
            foreach ($condition as $key => $value) {
                $key = $this->_columnMappingSql($key) ?? $key;
                $parsed[$key] = $value;
            }
        }

        return $parsed;
    }

    /**
     * Converts found rows into element instances
     *
     * @param array $rows
     * @return array|ElementInterface[]
     */
    private function _createElements(array $rows): array
    {
        $elements = [];

        if ($this->asArray === true) {
            if (!isset($this->indexBy)) {
                return $rows;
            }

            foreach ($rows as $row) {
                if (is_string($this->indexBy)) {
                    $key = $row[$this->indexBy];
                } else {
                    $key = call_user_func($this->indexBy, $row);
                }

                $elements[$key] = $row;
            }
        } else {
            foreach ($rows as $row) {
                $element = $this->createElement($row);

                // Add it to the elements array
                if (!isset($this->indexBy)) {
                    $elements[] = $element;
                } else {
                    if (is_string($this->indexBy)) {
                        $key = $element->{$this->indexBy};
                    } else {
                        $key = call_user_func($this->indexBy, $element);
                    }

                    $elements[$key] = $element;
                }
            }

            if ($this->withProvisionalDrafts) {
                ElementHelper::swapInProvisionalDrafts($elements);
            }

            // Fire an 'afterPopulateElements' event
            if ($this->hasEventHandlers(self::EVENT_AFTER_POPULATE_ELEMENTS)) {
                $event = new PopulateElementsEvent([
                    'elements' => $elements,
                    'rows' => $rows,
                ]);
                $this->trigger(self::EVENT_AFTER_POPULATE_ELEMENTS, $event);
                $elements = $event->elements;
            }
        }

        return $elements;
    }

    private function _resolveColumnMapping(string $key): string|array
    {
        if (!isset($this->_columnMap[$key])) {
            throw new InvalidArgumentException("Invalid column map key: $key");
        }

        // make sure it's not still a callback
        if (is_callable($this->_columnMap[$key])) {
            $this->_columnMap[$key] = $this->_columnMap[$key]();
        } elseif (is_array($this->_columnMap[$key])) {
            foreach ($this->_columnMap[$key] as $i => $mapping) {
                if (is_callable($mapping)) {
                    $this->_columnMap[$key][$i] = $mapping();
                }
            }
        }

        return $this->_columnMap[$key];
    }
}
