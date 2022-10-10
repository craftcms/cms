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
use craft\db\FixedOrderExpression;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\db\Table;
use craft\elements\User;
use craft\errors\SiteNotFoundException;
use craft\events\CancelableEvent;
use craft\events\DefineValueEvent;
use craft\events\PopulateElementEvent;
use craft\events\PopulateElementsEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\StringHelper;
use craft\models\Site;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use yii\base\ArrayableTrait;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\NotSupportedException;
use yii\db\Connection;
use yii\db\Expression;
use yii\db\ExpressionInterface;
use yii\db\QueryBuilder;

/**
 * ElementQuery represents a SELECT SQL statement for elements in a way that is independent of DBMS.
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
     * @event Event An event that is triggered at the beginning of preparing an element query for the query builder.
     */
    public const EVENT_BEFORE_PREPARE = 'beforePrepare';

    /**
     * @event Event An event that is triggered at the end of preparing an element query for the query builder.
     */
    public const EVENT_AFTER_PREPARE = 'afterPrepare';

    /**
     * @event DefineValueEvent An event that is triggered when defining the cache tags that should be associated with the query.
     * @see getCacheTags()
     * @since 4.1.0
     */
    public const EVENT_DEFINE_CACHE_TAGS = 'defineCacheTags';

    /**
     * @event PopulateElementEvent The event that is triggered after an element is populated.
     *
     * If [[PopulateElementEvent::$element]] is replaced by an event handler, the replacement will be returned by [[createElement()]] instead.
     */
    public const EVENT_AFTER_POPULATE_ELEMENT = 'afterPopulateElement';

    /**
     * @event PopulateElementEvent The event that is triggered after an element is populated.
     *
     * If [[PopulateElementEvent::$element]] is replaced by an event handler, the replacement will be returned by [[createElement()]] instead.
     */
    public const EVENT_AFTER_POPULATE_ELEMENTS = 'afterPopulateElements';

    /**
     * @var string The name of the [[ElementInterface]] class.
     * @phpstan-var class-string<ElementInterface>
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
     * @var string|null The content table that will be joined by this query.
     */
    public ?string $contentTable = Table::CONTENT;

    /**
     * @var FieldInterface[]|null The fields that may be involved in this query.
     */
    public ?array $customFields = null;

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
     * See [Relations](https://craftcms.com/docs/4.x/relations.html) for supported syntax options.
     *
     * @used-by relatedTo()
     */
    public mixed $relatedTo = null;

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
     * See [Searching](https://craftcms.com/docs/4.x/searching.html) for supported syntax options.
     *
     * @used-by ElementQuery::search()
     */
    public mixed $search = null;

    /**
     * @var mixed The reference code(s) used to identify the element(s).
     *
     * This property is set when accessing elements via their reference tags, e.g. `{entry:section/slug}`.
     *
     * @used-by ElementQuery::ref()
     */
    public mixed $ref = null;

    /**
     * @var string|array|null The eager-loading declaration.
     *
     * See [Eager-Loading Elements](https://craftcms.com/docs/4.x/dev/eager-loading-elements.html) for supported syntax options.
     *
     * @used-by with()
     * @used-by andWith()
     */
    public array|string|null $with = null;

    /**
     * @inheritdoc
     * @used-by orderBy()
     * @used-by addOrderBy()
     */
    public $orderBy = '';

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
    protected array $defaultOrderBy = ['elements.dateCreated' => SORT_DESC];

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
     * @var int[]|null
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
     * @var bool Whether an element table has been joined for the query
     * @see prepare()
     * @see joinElementTable()
     */
    private bool $_joinedElementTable = false;

    /**
     * Constructor
     *
     * @param string $elementType The element type class associated with this query
     * @phpstan-param class-string<ElementInterface> $elementType
     * @param array $config Configurations to be applied to the newly created query object
     */
    public function __construct(string $elementType, array $config = [])
    {
        $this->elementType = $elementType;

        // Use ** as a placeholder for "all the default columns"
        $config['select'] = $config['select'] ?? ['**' => '**'];

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
    public function offsetExists(mixed $offset): bool
    {
        // Cached?
        if (is_numeric($offset)) {
            $cachedResult = $this->getCachedResult();
            if ($cachedResult !== null) {
                return $offset < count($cachedResult);
            }
        }

        return parent::offsetExists($offset); // TODO: Change the autogenerated stub
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
    public function inReverse(bool $value = true): self
    {
        $this->inReverse = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $asArray
     */
    public function asArray(bool $value = true): self
    {
        $this->asArray = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $asArray
     */
    public function ignorePlaceholders(bool $value = true): self
    {
        $this->ignorePlaceholders = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $drafts
     */
    public function drafts(?bool $value = true): self
    {
        $this->drafts = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $draftId
     * @uses $drafts
     */
    public function draftId(?int $value = null): self
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
    public function draftOf($value): self
    {
        if ($value instanceof ElementInterface) {
            $this->draftOf = $value->getCanonicalId();
        } elseif (is_numeric($value) || $value === '*' || $value === false || $value === null) {
            $this->draftOf = $value;
        } else {
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
    public function draftCreator($value): self
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
    public function provisionalDrafts(?bool $value = true): self
    {
        $this->provisionalDrafts = $value;
        if ($value === true && $this->drafts === false) {
            $this->drafts = true;
        }
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $savedDraftsOnly
     */
    public function savedDraftsOnly(bool $value = true): self
    {
        $this->savedDraftsOnly = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $revisions
     */
    public function revisions(?bool $value = true): self
    {
        $this->revisions = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $revisionId
     * @uses $revisions
     */
    public function revisionId(?int $value = null): self
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
    public function revisionOf($value): self
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
    public function revisionCreator($value): self
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
    public function id($value): self
    {
        $this->id = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $uid
     */
    public function uid($value): self
    {
        $this->uid = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $siteSettingsId
     */
    public function siteSettingsId($value): self
    {
        $this->siteSettingsId = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $fixedOrder
     */
    public function fixedOrder(bool $value = true): self
    {
        $this->fixedOrder = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $orderBy
     */
    public function orderBy($columns): self
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
    public function addOrderBy($columns): self
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
    public function status(array|string|null $value): self
    {
        $this->status = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $archived
     */
    public function archived(bool $value = true): self
    {
        $this->archived = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $trashed
     */
    public function trashed(?bool $value = true): self
    {
        $this->trashed = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $dateCreated
     */
    public function dateCreated(mixed $value): self
    {
        $this->dateCreated = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $dateUpdated
     */
    public function dateUpdated(mixed $value): self
    {
        $this->dateUpdated = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @throws InvalidArgumentException if $value is invalid
     * @uses $siteId
     */
    public function site($value): self
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
    public function siteId($value): self
    {
        if (is_array($value) && strtolower(reset($value)) === 'not') {
            array_shift($value);
            $this->siteId = [];
            foreach (Craft::$app->getSites()->getAllSites() as $site) {
                if (!in_array($site->id, $value, false)) {
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
     * @uses $unique
     * @since 3.2.0
     */
    public function unique(bool $value = true): self
    {
        $this->unique = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $preferSites
     * @since 3.2.0
     */
    public function preferSites(?array $value = null): self
    {
        $this->preferSites = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $relatedTo
     */
    public function relatedTo($value): self
    {
        $this->relatedTo = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException
     * @uses $relatedTo
     */
    public function andRelatedTo($value): self
    {
        if (!$value) {
            return $this;
        }

        if (!$this->relatedTo) {
            return $this->relatedTo($value);
        }

        // Normalize so element/targetElement/sourceElement values get pushed down to the 2nd level
        $relatedTo = ElementRelationParamParser::normalizeRelatedToParam($this->relatedTo);
        $criteriaCount = count($relatedTo) - 1;

        // Not possible to switch from `or` to `and` if there are multiple criteria
        if ($relatedTo[0] === 'or' && $criteriaCount > 1) {
            throw new NotSupportedException('It’s not possible to combine “or” and “and” relatedTo conditions.');
        }

        $relatedTo[0] = $criteriaCount > 0 ? 'and' : 'or';
        $relatedTo[] = ElementRelationParamParser::normalizeRelatedToCriteria($value);
        return $this->relatedTo($relatedTo);
    }

    /**
     * @inheritdoc
     * @uses $title
     */
    public function title($value): self
    {
        $this->title = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $slug
     */
    public function slug($value): self
    {
        $this->slug = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $uri
     */
    public function uri($value): self
    {
        $this->uri = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $search
     */
    public function search($value): self
    {
        $this->search = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $ref
     */
    public function ref($value): self
    {
        $this->ref = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $with
     */
    public function with(array|string|null $value): self
    {
        $this->with = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $with
     */
    public function andWith(array|string|null $value): self
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
     * @uses $withStructure
     */
    public function withStructure(bool $value = true): self
    {
        $this->withStructure = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $structureId
     */
    public function structureId(?int $value = null): self
    {
        $this->structureId = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $level
     */
    public function level($value = null): self
    {
        $this->level = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $hasDescendants
     */
    public function hasDescendants(bool $value = true): self
    {
        $this->hasDescendants = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $leaves
     */
    public function leaves(bool $value = true): self
    {
        $this->leaves = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $ancestorOf
     */
    public function ancestorOf(ElementInterface|int|null $value): self
    {
        $this->ancestorOf = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $ancestorDist
     */
    public function ancestorDist(?int $value = null): self
    {
        $this->ancestorDist = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $descendantOf
     */
    public function descendantOf(ElementInterface|int|null $value): self
    {
        $this->descendantOf = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $descendantDist
     */
    public function descendantDist(?int $value = null): self
    {
        $this->descendantDist = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $siblingOf
     */
    public function siblingOf(ElementInterface|int|null $value): self
    {
        $this->siblingOf = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $prevSiblingOf
     */
    public function prevSiblingOf(ElementInterface|int|null $value): self
    {
        $this->prevSiblingOf = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $nextSiblingOf
     */
    public function nextSiblingOf(ElementInterface|int|null $value): self
    {
        $this->nextSiblingOf = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $positionedBefore
     */
    public function positionedBefore(ElementInterface|int|null $value): self
    {
        $this->positionedBefore = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $positionedAfter
     */
    public function positionedAfter(ElementInterface|int|null $value): self
    {
        $this->positionedAfter = $value;
        return $this;
    }

    /**
     * Sets the [[status()|status]] param to `null`.
     *
     * @return self self reference
     * @since 3.0.17
     * @deprecated in 4.0.0. `status(null)` should be used instead.
     */
    public function anyStatus(): self
    {
        $this->status = null;
        return $this;
    }

    // Query preparation/execution
    // -------------------------------------------------------------------------

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
     * @throws QueryAbortedException if it can be determined that there won’t be any results
     */
    public function prepare($builder): Query
    {
        // Log a warning if the app isn't fully initialized yet
        if (!Craft::$app->getIsInitialized()) {
            Craft::warning('Element query executed before Craft is fully initialized.', __METHOD__);
        }

        // Is the query already doomed?
        if (isset($this->id) && empty($this->id)) {
            throw new QueryAbortedException();
        }
        /** @var string|ElementInterface $class */
        /** @phpstan-var class-string<ElementInterface>|ElementInterface $class */
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

        // Build the query
        // ---------------------------------------------------------------------

        $this->query = new Query();
        $this->query->withQueries = $this->withQueries;
        $this->subQuery = new Query();

        $this->query
            ->from(['subquery' => $this->subQuery])
            ->innerJoin(['elements' => Table::ELEMENTS], '[[elements.id]] = [[subquery.elementsId]]')
            ->innerJoin(['elements_sites' => Table::ELEMENTS_SITES], '[[elements_sites.id]] = [[subquery.elementsSitesId]]');

        // Keep track of whether an element table is joined into the query
        $this->_joinedElementTable = false;

        // Give other classes a chance to make changes up front
        if (!$this->beforePrepare()) {
            throw new QueryAbortedException();
        }

        $this->subQuery
            ->addSelect([
                'elementsId' => 'elements.id',
                'elementsSitesId' => 'elements_sites.id',
            ])
            ->from(['elements' => Table::ELEMENTS])
            ->innerJoin(['elements_sites' => Table::ELEMENTS_SITES], '[[elements_sites.elementId]] = [[elements.id]]')
            ->andWhere($this->where)
            ->offset($this->offset)
            ->limit($this->limit)
            ->addParams($this->params);

        if (Craft::$app->getIsMultiSite(false, true)) {
            $this->subQuery->andWhere(['elements_sites.siteId' => $this->siteId]);
        }

        if ($class::hasContent() && isset($this->contentTable)) {
            $this->customFields = $this->customFields();
            $this->_joinContentTable($class);
        } else {
            $this->customFields = null;
        }

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
            $this->subQuery->andWhere(['elements.archived' => false]);
            $this->_applyStatusParam($class);
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
            $this->subQuery->andWhere(Db::parseParam('content.title', $this->title, '=', true));
        }

        if ($this->slug) {
            $this->subQuery->andWhere(Db::parseParam('elements_sites.slug', $this->slug));
        }

        if ($this->uri) {
            $this->subQuery->andWhere(Db::parseParam('elements_sites.uri', $this->uri, '=', true));
        }

        // Map ambiguous column names to the `elements` table
        // (for use in SELECT and ORDER BY clauses)
        $columnMap = [
            'id' => 'elements.id',
            'enabled' => 'elements.enabled',
            'dateCreated' => 'elements.dateCreated',
            'dateUpdated' => 'elements.dateUpdated',
            'uid' => 'elements.uid',
        ];

        if (is_array($this->customFields)) {
            // Map custom field handles to their content columns
            foreach ($this->customFields as $field) {
                if (($column = $this->_fieldColumn($field)) !== null) {
                    $firstCol = is_string($column) ? $column : reset($column);
                    $columnMap[$field->handle] = "content.$firstCol";
                }
            }
        }

        $this->_applyRelatedToParam();
        $this->_applyStructureParams($class);
        $this->_applyRevisionParams();
        $this->_applySearchParam($builder->db);
        $this->_applyOrderByParams($builder->db, $columnMap);
        $this->_applySelectParam($columnMap);
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
            /** @var ReflectionClass|null $ref */
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
     * @return ElementInterface[]|array The resulting elements.
     */
    public function populate($rows): array
    {
        if (empty($rows)) {
            return [];
        }

        // Should we set a search score on the elements?
        if (isset($this->_searchResults)) {
            foreach ($rows as &$row) {
                if (isset($row['id'], $this->_searchResults[$row['id']])) {
                    $row['searchScore'] = (int)round($this->_searchResults[$row['id']]);
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
        return $elements;
    }

    /**
     * @inheritdoc
     */
    public function count($q = '*', $db = null): bool|int|string|null
    {
        // Cached?
        if (($cachedResult = $this->getCachedResult()) !== null) {
            return count($cachedResult);
        }

        return parent::count($q, $db) ?: 0;
    }

    /**
     * @inheritdoc
     * @return ElementInterface[]|array
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

        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return ElementInterface|array|null
     */
    public function one($db = null): mixed
    {
        // Cached?
        if (($cachedResult = $this->getCachedResult()) !== null) {
            return reset($cachedResult) ?: null;
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
        return $this->getCachedResult() !== null || parent::exists($db);
    }

    /**
     * @inheritdoc
     * @return ElementInterface|array|null
     */
    public function nth(int $n, ?Connection $db = null): mixed
    {
        // Cached?
        if (($cachedResult = $this->getCachedResult()) !== null) {
            return $cachedResult[$n] ?? null;
        }

        return parent::nth($n, $db);
    }

    /**
     * @inheritdoc
     */
    public function ids(?Connection $db = null): array
    {
        $select = $this->select;
        $this->select = ['elements.id' => 'elements.id'];
        $result = $this->column($db);
        $this->select($select);

        return $result;
    }

    /**
     * Returns the resulting elements set by [[setCachedResult()]], if the criteria params haven’t changed since then.
     *
     * @return ElementInterface[]|null $elements The resulting elements, or null if setCachedResult() was never called or the criteria has changed
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
     * @param ElementInterface[] $elements The resulting elements.
     * @see getCachedResult()
     */
    public function setCachedResult(array $elements): void
    {
        $this->_result = $elements;
        $this->_resultCriteria = $this->getCriteria();
    }

    /**
     * Clears the [cached result](https://craftcms.com/docs/4.x/element-queries.html#cache).
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
        $attributes = $this->criteriaAttributes();

        // Ignore the 'with' param
        ArrayHelper::removeValue($attributes, 'with');

        return $this->toArray($attributes, [], false);
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
                    !in_array($property->getName(), ['elementType', 'query', 'subQuery', 'contentTable', 'customFields', 'asArray'], true)
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
        $fields = array_unique(array_merge(
            array_keys(Craft::getObjectVars($this)),
            array_keys(Craft::getObjectVars($this->getBehavior('customFields')))
        ));
        $fields = array_combine($fields, $fields);
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

        /** @var string|ElementInterface $class */
        /** @phpstan-var class-string<ElementInterface>|ElementInterface $class */
        $class = $this->elementType;

        // Instantiate the element
        if ($this->structureId) {
            $row['structureId'] = $this->structureId;
        }

        if ($class::hasContent() && isset($this->contentTable)) {
            if ($class::hasTitles()) {
                // Ensure the title is a string
                $row['title'] = (string)($row['title'] ?? '');
            }

            // Separate the content values from the main element attributes
            $fieldValues = [];

            if (!empty($this->customFields)) {
                foreach ($this->customFields as $field) {
                    if (($column = $this->_fieldColumn($field)) !== null) {
                        // Account for results where multiple fields have the same handle, but from
                        // different columns e.g. two Matrix block types that each have a field with the
                        // same handle
                        $firstCol = is_string($column) ? $column : reset($column);
                        $setValue = !isset($fieldValues[$field->handle]) || (empty($fieldValues[$field->handle]) && !empty($row[$firstCol]));

                        if (is_string($column)) {
                            if ($setValue) {
                                $fieldValues[$field->handle] = $row[$column] ?? null;
                            }
                            unset($row[$column]);
                        } else {
                            if ($setValue) {
                                $columnValues = [];
                                $hasColumnValues = false;

                                foreach ($column as $key => $col) {
                                    $columnValues[$key] = $row[$col] ?? null;
                                    $hasColumnValues = $hasColumnValues || $columnValues[$key] !== null;
                                }

                                // Only actually set it on $fieldValues if any of the columns weren't null.
                                // Otherwise, leave it alone in case another field has the same handle.
                                if ($hasColumnValues) {
                                    $fieldValues[$field->handle] = $columnValues;
                                }
                            }

                            foreach ($column as $col) {
                                unset($row[$col]);
                            }
                        }
                    }
                }
            }
        }

        if (array_key_exists('dateDeleted', $row)) {
            $row['trashed'] = $row['dateDeleted'] !== null;
        }

        // Set the custom field values
        if (isset($fieldValues)) {
            $row['fieldValues'] = $fieldValues;
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

        $element = new $class($row);
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
     * @see prepare()
     * @see afterPrepare()
     */
    protected function beforePrepare(): bool
    {
        $event = new CancelableEvent();
        $this->trigger(self::EVENT_BEFORE_PREPARE, $event);

        return $event->isValid;
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
        $event = new CancelableEvent();
        $this->trigger(self::EVENT_AFTER_PREPARE, $event);

        if (!$event->isValid) {
            return false;
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

            // If specific IDs were requested, then use those
            if (is_numeric($this->id) || (is_array($this->id) && ArrayHelper::isNumeric($this->id))) {
                $queryTags = (array)$this->id;
            } else {
                $queryTags = $this->cacheTags();

                if ($this->hasEventHandlers(self::EVENT_DEFINE_CACHE_TAGS)) {
                    $event = new DefineValueEvent([
                        'value' => $queryTags,
                    ]);
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
            }

            foreach ($queryTags as $tag) {
                $this->_cacheTags[] = "element::$this->elementType::$tag";
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
     */
    protected function customFields(): array
    {
        $contentService = Craft::$app->getContent();
        $originalFieldContext = $contentService->fieldContext;
        $contentService->fieldContext = 'global';
        $fields = Craft::$app->getFields()->getAllFields();
        $contentService->fieldContext = $originalFieldContext;

        return $fields;
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
     * Joins in a table with an `id` column that has a foreign key pointing to `craft_elements`.`id`.
     *
     * @param string $table The unprefixed table name. This will also be used as the table’s alias within the query.
     */
    protected function joinElementTable(string $table): void
    {
        $joinTable = [$table => "{{%$table}}"];
        $this->query->innerJoin($joinTable, "[[$table.id]] = [[subquery.elementsId]]");
        $this->subQuery->innerJoin($joinTable, "[[$table.id]] = [[elements.id]]");
        $this->_joinedElementTable = true;
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
     * Joins the content table into the query being prepared.
     *
     * @param string $class
     * @phpstan-param class-string<ElementInterface> $class
     * @throws QueryAbortedException
     */
    private function _joinContentTable(string $class): void
    {
        /** @var ElementInterface|string $class */
        // Join in the content table on both queries
        $joinCondition = [
            'and',
            '[[content.elementId]] = [[elements.id]]',
        ];
        if (Craft::$app->getIsMultiSite(false, true)) {
            $joinCondition[] = '[[content.siteId]] = [[elements_sites.siteId]]';
        }
        $this->subQuery
            ->innerJoin($this->contentTable . ' content', $joinCondition)
            ->addSelect(['contentId' => 'content.id']);

        $this->query->innerJoin($this->contentTable . ' content', '[[content.id]] = [[subquery.contentId]]');

        // Select the content table columns on the main query
        $this->query->addSelect(['contentId' => 'content.id']);

        if ($class::hasTitles()) {
            $this->query->addSelect(['content.title']);
        }

        if (is_array($this->customFields)) {
            $contentService = Craft::$app->getContent();
            $originalFieldColumnPrefix = $contentService->fieldColumnPrefix;
            $fieldAttributes = $this->getBehavior('customFields');

            foreach ($this->customFields as $field) {
                if (($column = $this->_fieldColumn($field)) !== null) {
                    if (is_string($column)) {
                        $this->query->addSelect("content.$column");
                    } else {
                        foreach ($column as $c) {
                            $this->query->addSelect("content.$c");
                        }
                    }
                }

                $handle = $field->handle;

                // In theory all field handles will be accounted for on the CustomFieldBehavior, but just to be safe...
                if ($handle !== 'owner' && isset($fieldAttributes->$handle)) {
                    $fieldAttributeValue = $fieldAttributes->$handle;
                } else {
                    $fieldAttributeValue = null;
                }

                // Set the field's column prefix on the Content service.
                if ($field->columnPrefix !== null) {
                    $contentService->fieldColumnPrefix = $field->columnPrefix;
                }

                $exception = null;
                try {
                    $field->modifyElementsQuery($this, $fieldAttributeValue);
                } catch (QueryAbortedException $exception) {
                }

                // Set it back
                $contentService->fieldColumnPrefix = $originalFieldColumnPrefix;

                // Need to bail early?
                if ($exception !== null) {
                    throw $exception;
                }
            }
        }
    }

    /**
     * Applies the 'status' param to the query being prepared.
     *
     * @param string $class
     * @phpstan-param class-string<ElementInterface> $class
     * @throws QueryAbortedException
     */
    private function _applyStatusParam(string $class): void
    {
        /** @var string|ElementInterface $class */
        /** @phpstan-var class-string<ElementInterface>|ElementInterface $class */
        if (!$this->status || !$class::hasStatuses()) {
            return;
        }

        /** @var string[]|string|null $statuses */
        $statuses = $this->status;
        if (!is_array($statuses)) {
            $statuses = $statuses ? StringHelper::split($statuses) : [];
        }

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
            'fields' => $this->customFields ? ArrayHelper::index($this->customFields, 'handle') : [],
        ]);
        $condition = $parser->parse($this->relatedTo, $this->siteId !== '*' ? $this->siteId : null);

        if ($condition === false) {
            throw new QueryAbortedException();
        }

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
            !$this->trashed &&
            !$this->revisions &&
            ($this->withStructure ?? (bool)$this->structureId)
        );
    }

    /**
     * Applies the structure params to the query being prepared.
     *
     * @param string $class
     * @phpstan-param class-string<ElementInterface> $class
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
            $this->subQuery->andWhere(Db::parseNumericParam('structureelements.level', $this->level));
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

            if ($this->savedDraftsOnly) {
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
        if (!$this->siteId) {
            // Default to the current site
            $this->siteId = Craft::$app->getSites()->getCurrentSite()->id;
        } elseif ($this->siteId === '*') {
            $this->siteId = Craft::$app->getSites()->getAllSiteIds();
        }
    }

    /**
     * Normalizes a structure param value to either an Element object or false.
     *
     * @param string $property The parameter’s property name.
     * @param string $class The element class
     * @phpstan-param class-string<ElementInterface> $class
     * @return ElementInterface The normalized element
     * @throws QueryAbortedException if the element can't be found
     */
    private function _normalizeStructureParamValue(string $property, string $class): ElementInterface
    {
        $element = $this->$property;

        if ($element === false) {
            throw new QueryAbortedException();
        }

        /** @var string|ElementInterface $class */
        /** @phpstan-var class-string<ElementInterface>|ElementInterface $class */
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
     * @param Connection $db
     * @throws Exception if the DB connection doesn't support fixed ordering
     * @throws QueryAbortedException
     */
    private function _applySearchParam(Connection $db): void
    {
        $this->_searchResults = null;

        if ($this->search) {
            $searchResults = Craft::$app->getSearch()->searchElements($this);

            // No results?
            if (empty($searchResults)) {
                throw new QueryAbortedException();
            }

            $this->_searchResults = $searchResults;

            $this->subQuery->andWhere(['elements.id' => array_keys($searchResults)]);
        }
    }

    /**
     * Applies the 'fixedOrder' and 'orderBy' params to the query being prepared.
     *
     * @param Connection $db
     * @param string[] $columnMap
     * @phpstan-param array<string,string> $columnMap
     * @throws Exception if the DB connection doesn't support fixed ordering
     * @throws QueryAbortedException
     */
    private function _applyOrderByParams(Connection $db, array $columnMap): void
    {
        if (!isset($this->orderBy) || !empty($this->query->orderBy)) {
            return;
        }

        // Any other empty value means we should set it
        if (empty($this->orderBy)) {
            if ($this->fixedOrder) {
                if (empty($this->id)) {
                    throw new QueryAbortedException();
                }

                $ids = $this->id;
                if (!is_array($ids)) {
                    $ids = is_string($ids) ? StringHelper::split($ids) : [$ids];
                }

                if (!$db instanceof \craft\db\Connection) {
                    throw new Exception('The database connection doesn’t support fixed ordering.');
                }
                $this->orderBy = [new FixedOrderExpression('elements.id', $ids, $db)];
            } elseif ($this->revisions) {
                $this->orderBy = ['num' => SORT_DESC];
            } elseif ($this->_shouldJoinStructureData()) {
                $this->orderBy = ['structureelements.lft' => SORT_ASC] + $this->defaultOrderBy;
            } elseif (!empty($this->defaultOrderBy)) {
                $this->orderBy = $this->defaultOrderBy;
            } else {
                return;
            }
        }

        // Rename orderBy keys based on the real column name mapping
        // (yes this is awkward but we need to preserve the order of the keys!)
        /** @var array $orderBy */
        $orderBy = array_merge($this->orderBy);
        $orderByColumns = array_keys($orderBy);

        foreach ($columnMap as $orderValue => $columnName) {
            // Are we ordering by this column name?
            $pos = array_search($orderValue, $orderByColumns, true);

            if ($pos !== false) {
                // Swap it with the mapped column name
                $orderByColumns[$pos] = $columnName;
                $orderBy = array_combine($orderByColumns, $orderBy);
            }
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

        // swap `score` direction value with a case expression
        if (
            !empty($this->_searchResults) &&
            isset($orderBy['score']) &&
            in_array($orderBy['score'], [SORT_ASC, SORT_DESC], true)
        ) {
            $elementIdsByScore = [];
            foreach ($this->_searchResults as $elementId => $score) {
                if ($score !== 0) {
                    $elementIdsByScore[$score][] = $elementId;
                }
            }
            if (!empty($elementIdsByScore)) {
                $caseSql = 'CASE';
                foreach ($elementIdsByScore as $score => $elementIds) {
                    $caseSql .= ' WHEN (';
                    if (count($elementIds) === 1) {
                        $caseSql .= "[[elements.id]] = $elementIds[0]";
                    } else {
                        $caseSql .= '[[elements.id]] IN (' . implode(',', $elementIds) . ')';
                    }
                    $caseSql .= ") THEN $score";
                }
                $caseSql .= ' ELSE 0 END';
                if ($orderBy['score'] === SORT_DESC) {
                    $caseSql .= ' DESC';
                }
                $orderBy['score'] = new Expression($caseSql);
            } else {
                unset($orderBy['score']);
            }
        } else {
            unset($orderBy['score']);
        }

        $this->query->orderBy($orderBy);
        $this->subQuery->orderBy($orderBy);
    }

    /**
     * Applies the 'select' param to the query being prepared.
     *
     * @param string[] $columnMap
     * @phpstan-param array<string,string> $columnMap
     */
    private function _applySelectParam(array $columnMap): void
    {
        // Select all columns defined by [[select]], swapping out any mapped column names
        $select = [];
        $includeDefaults = false;

        foreach ((array)$this->select as $alias => $column) {
            if ($alias === '**') {
                $includeDefaults = true;
            } else {
                // Is this a mapped column name (without a custom alias)?
                if ($alias === $column && isset($columnMap[$alias])) {
                    $column = $columnMap[$alias];

                    // Completely ditch the mapped name if instantiated elements are going to be returned
                    if (!$this->asArray) {
                        $alias = $columnMap[$alias];
                    }
                }

                $select[$alias] = $column;
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
                'elements_sites.slug' => 'elements_sites.slug',
                'elements_sites.siteId' => 'elements_sites.siteId',
                'elements_sites.uri' => 'elements_sites.uri',
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
     * @param Connection $db
     */
    private function _applyUniqueParam(Connection $db): void
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
     * Returns a field’s content column name(s).
     *
     * @param FieldInterface $field
     * @return string|string[]|null
     */
    private function _fieldColumn(FieldInterface $field): array|string|null
    {
        if (!$field::hasContentColumn()) {
            return null;
        }

        $type = $field->getContentColumnType();

        if (is_array($type)) {
            $columns = [];
            foreach (array_keys($type) as $i => $key) {
                $columns[$key] = ElementHelper::fieldColumnFromField($field, $i !== 0 ? $key : null);
            }
            return $columns;
        }

        return ElementHelper::fieldColumnFromField($field);
    }

    /**
     * Converts found rows into element instances
     *
     * @param array $rows
     * @return array|ElementInterface[]
     */
    private function _createElements(array $rows): array
    {
        $elementsService = Craft::$app->getElements();
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
                Craft::$app->getElements()->eagerLoadElements($this->elementType, $elements, $this->with);
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
}
