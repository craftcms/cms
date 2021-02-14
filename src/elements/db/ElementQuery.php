<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

use ArrayIterator;
use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\behaviors\CustomFieldBehavior;
use craft\behaviors\DraftBehavior;
use craft\behaviors\RevisionBehavior;
use craft\db\FixedOrderExpression;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\db\Table;
use craft\elements\User;
use craft\errors\SiteNotFoundException;
use craft\events\CancelableEvent;
use craft\events\PopulateElementEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\StringHelper;
use craft\models\Site;
use craft\search\SearchQuery;
use ReflectionProperty;
use yii\base\ArrayableTrait;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\NotSupportedException;
use yii\db\Connection;
use yii\db\Expression;
use yii\db\ExpressionInterface;

/**
 * ElementQuery represents a SELECT SQL statement for elements in a way that is independent of DBMS.
 *
 * @property string|Site $site The site or site handle that the elements should be returned in
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
    const EVENT_BEFORE_PREPARE = 'beforePrepare';

    /**
     * @event Event An event that is triggered at the end of preparing an element query for the query builder.
     */
    const EVENT_AFTER_PREPARE = 'afterPrepare';

    /**
     * @event PopulateElementEvent The event that is triggered after an element is populated.
     *
     * If [[PopulateElementEvent::$element]] is replaced by an event handler, the replacement will be returned by [[createElement()]] instead.
     */
    const EVENT_AFTER_POPULATE_ELEMENT = 'afterPopulateElement';

    /**
     * Returns whether querying for drafts/revisions is supported yet.
     *
     * @return bool
     * @todo remove schema version condition after next beakpoint
     */
    private static function _supportsRevisionParams(): bool
    {
        return Craft::$app->getDb()->columnExists(Table::ELEMENTS, 'draftId');
    }

    /**
     * @var string|null The name of the [[ElementInterface]] class.
     */
    public $elementType;

    /**
     * @var Query|null The query object created by [[prepare()]]
     * @see prepare()
     */
    public $query;

    /**
     * @var Query|null The subselect’s query object created by [[prepare()]]
     * @see prepare()
     */
    public $subQuery;

    /**
     * @var string|null The content table that will be joined by this query.
     */
    public $contentTable = Table::CONTENT;

    /**
     * @var FieldInterface[]|null The fields that may be involved in this query.
     */
    public $customFields;

    // Result formatting attributes
    // -------------------------------------------------------------------------

    /**
     * @var bool Whether the results should be queried in reverse.
     * @used-by inReverse()
     */
    public $inReverse = false;

    /**
     * @var bool Whether to return each element as an array. If false (default), an object
     * of [[elementType]] will be created to represent each element.
     * @used-by asArray()
     */
    public $asArray = false;

    /**
     * @var bool Whether to ignore placeholder elements when populating the results.
     * @used-by ignorePlaceholders()
     * @since 3.2.9
     */
    public $ignorePlaceholders = false;

    // Drafts and revisions
    // -------------------------------------------------------------------------

    /**
     * @var bool|null Whether draft elements should be returned.
     * @since 3.2.0
     */
    public $drafts = false;

    /**
     * @var int|null The ID of the draft to return (from the `drafts` table)
     * @since 3.2.0
     */
    public $draftId;

    /**
     * @var int|string|false|null The source element ID that drafts should be returned for.
     *
     * This can be set to one of the following:
     *
     * - A source element ID – matches drafts of that element
     * - `'*'` – matches drafts of any source element
     * - `false` – matches unpublished drafts that have no source element
     *
     * @since 3.2.0
     */
    public $draftOf;

    /**
     * @var int|null The drafts’ creator ID
     * @since 3.2.0
     */
    public $draftCreator;

    /**
     * @var bool Whether only unpublished drafts which have been saved after initial creation should be included in the results.
     * @since 3.6.6
     */
    public $savedDraftsOnly = false;

    /**
     * @var bool Whether revision elements should be returned.
     * @since 3.2.0
     */
    public $revisions = false;

    /**
     * @var int|null The ID of the revision to return (from the `revisions` table)
     * @since 3.2.0
     */
    public $revisionId;

    /**
     * @var int|null The source element ID that revisions should be returned for
     * @since 3.2.0
     */
    public $revisionOf;

    /**
     * @var int|null The revisions’ creator ID
     * @since 3.2.0
     */
    public $revisionCreator;

    // General parameters
    // -------------------------------------------------------------------------

    /**
     * @var int|int[]|false|null The element ID(s). Prefix IDs with `'not '` to exclude them.
     * @used-by id()
     */
    public $id;

    /**
     * @var string|string[]|null The element UID(s). Prefix UIDs with `'not '` to exclude them.
     * @used-by uid()
     */
    public $uid;

    /**
     * @var bool Whether results should be returned in the order specified by [[id]].
     * @used-by fixedOrder()
     */
    public $fixedOrder = false;

    /**
     * @var string|string[]|null The status(es) that the resulting elements must have.
     * @used-by status()
     */
    public $status = [
        Element::STATUS_ENABLED,
    ];

    /**
     * @var bool Whether to return only archived elements.
     * @used-by archived()
     */
    public $archived = false;

    /**
     * @var bool|null Whether to return trashed (soft-deleted) elements.
     * If this is set to `null`, then both trashed and non-trashed elements will be returned.
     * @used-by trashed()
     * @since 3.1.0
     */
    public $trashed = false;

    /**
     * @var mixed When the resulting elements must have been created.
     * @used-by dateCreated()
     */
    public $dateCreated;

    /**
     * @var mixed When the resulting elements must have been last updated.
     * @used-by dateUpdated()
     */
    public $dateUpdated;

    /**
     * @var int|int[]|string|null The site ID(s) that the elements should be returned in, or `'*'` if elements
     * should be returned in all supported sites.
     * @used-by site()
     * @used-by siteId()
     */
    public $siteId;

    /**
     * @var bool Whether only elements with unique IDs should be returned by the query.
     * @used-by unique()
     * @since 3.2.0
     */
    public $unique = false;

    /**
     * @var array|null Determines which site should be selected when querying multi-site elements.
     * @used-by preferSites()
     * @since 3.2.0
     */
    public $preferSites = false;

    /**
     * @var bool Whether the elements must be enabled for the chosen site.
     * @used-by enabledForSite()
     * @deprecated in 3.5.0
     */
    public $enabledForSite = false;

    /**
     * @var bool Whether the elements must be “leaves” in the structure.
     * @used-by leaves()
     */
    public $leaves = false;

    /**
     * @var int|array|ElementInterface|null The element relation criteria.
     *
     * See [Relations](https://craftcms.com/docs/3.x/relations.html) for supported syntax options.
     *
     * @used-by relatedTo()
     */
    public $relatedTo;

    /**
     * @var string|string[]|null The title that resulting elements must have.
     * @used-by title()
     */
    public $title;

    /**
     * @var string|string[]|null The slug that resulting elements must have.
     * @used-by slug()
     */
    public $slug;

    /**
     * @var string|string[]|null The URI that the resulting element must have.
     * @used-by uri()
     */
    public $uri;

    /**
     * @var string|array|SearchQuery|null The search term to filter the resulting elements by.
     *
     * See [Searching](https://craftcms.com/docs/3.x/searching.html) for supported syntax options.
     *
     * @used-by ElementQuery::search()
     */
    public $search;

    /**
     * @var string|string[]|null The reference code(s) used to identify the element(s).
     *
     * This property is set when accessing elements via their reference tags, e.g. `{entry:section/slug}`.
     *
     * @used-by ElementQuery::ref()
     */
    public $ref;

    /**
     * @var string|array|null The eager-loading declaration.
     *
     * See [Eager-Loading Elements](https://craftcms.com/docs/3.x/dev/eager-loading-elements.html) for supported syntax options.
     *
     * @used-by with()
     * @used-by andWith()
     */
    public $with;

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
    public $withStructure;

    /**
     * @var int|false|null The structure ID that should be used to join in the structureelements table.
     * @used-by structureId()
     */
    public $structureId;

    /**
     * @var mixed The element’s level within the structure
     * @used-by level()
     */
    public $level;

    /**
     * @var bool|null Whether the resulting elements must have descendants.
     * @used-by hasDescendants()
     * @since 3.0.4
     */
    public $hasDescendants;

    /**
     * @var int|ElementInterface|null The element (or its ID) that results must be an ancestor of.
     * @used-by ancestorOf()
     */
    public $ancestorOf;

    /**
     * @var int|null The maximum number of levels that results may be separated from [[ancestorOf]].
     * @used-by ancestorDist()
     */
    public $ancestorDist;

    /**
     * @var int|ElementInterface|null The element (or its ID) that results must be a descendant of.
     * @used-by descendantOf()
     */
    public $descendantOf;

    /**
     * @var int|null The maximum number of levels that results may be separated from [[descendantOf]].
     * @used-by descendantDist()
     */
    public $descendantDist;

    /**
     * @var int|ElementInterface|null The element (or its ID) that the results must be a sibling of.
     * @used-by siblingOf()
     */
    public $siblingOf;

    /**
     * @var int|ElementInterface|null The element (or its ID) that the result must be the previous sibling of.
     * @used-by prevSiblingOf()
     */
    public $prevSiblingOf;

    /**
     * @var int|ElementInterface|null The element (or its ID) that the result must be the next sibling of.
     * @used-by nextSiblingOf()
     */
    public $nextSiblingOf;

    /**
     * @var int|ElementInterface|null The element (or its ID) that the results must be positioned before.
     * @used-by positionedBefore()
     */
    public $positionedBefore;

    /**
     * @var int|ElementInterface|null The element (or its ID) that the results must be positioned after.
     * @used-by positionedAfter()
     */
    public $positionedAfter;

    /**
     * @var array The default [[orderBy]] value to use if [[orderBy]] is empty but not null.
     */
    protected $defaultOrderBy = ['elements.dateCreated' => SORT_DESC];

    // For internal use
    // -------------------------------------------------------------------------

    /**
     * @var mixed The placeholder condition for this query.
     * @see _placeholderCondition()
     */
    private $_placeholderCondition;

    /**
     * @var mixed The [[siteId]] param used at the time the placeholder condition was generated.
     * @see _placeholderCondition()
     */
    private $_placeholderSiteIds;

    /**
     * @var ElementInterface[]|null The cached element query result
     * @see setCachedResult()
     */
    private $_result;

    /**
     * @var array|null The criteria params that were set when the cached element query result was set
     * @see setCachedResult()
     */
    private $_resultCriteria;

    /**
     * @var array|null
     */
    private $_searchScores;

    /**
     * Constructor
     *
     * @param string $elementType The element type class associated with this query
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
    public function __isset($name)
    {
        if ($name === 'order') {
            Craft::$app->getDeprecator()->log('ElementQuery::order()', 'The `order` element query param has been deprecated. Use `orderBy` instead.');

            return $this->orderBy !== null;
        }

        return parent::__isset($name);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        // We must ensure $name is a string; if it is 0 then each of these cases could match.
        // (https://stackoverflow.com/a/8146455)
        switch ((string)$name) {
            case 'locale':
                Craft::$app->getDeprecator()->log('ElementQuery::locale()', 'The `locale` element query param has been deprecated. Use `site` or `siteId` instead.');
                if ($this->siteId && is_numeric($this->siteId) && ($site = Craft::$app->getSites()->getSiteById($this->siteId))) {
                    return $site->handle;
                }

                return null;

            case 'order':
                Craft::$app->getDeprecator()->log('ElementQuery::order()', 'The `order` element query param has been deprecated. Use `orderBy` instead.');

                return $this->orderBy;

            default:
                return parent::__get($name);
        }
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
            case 'localeEnabled':
                Craft::$app->getDeprecator()->log('ElementQuery::localeEnabled()', 'The `localeEnabled` element query param has been deprecated. `status()` should be used instead.');
                $this->enabledForSite = $value;
                break;
            case 'locale':
                Craft::$app->getDeprecator()->log('ElementQuery::locale()', 'The `locale` element query param has been deprecated. Use `site` or `siteId` instead.');
                $this->site($value);
                break;
            case 'order':
                Craft::$app->getDeprecator()->log('ElementQuery::order()', 'The `order` element query param has been deprecated. Use `orderBy` instead.');
                $this->orderBy = $value;
                break;
            default:
                parent::__set($name, $value);
        }
    }

    /**
     * @inheritdoc
     */
    public function __call($name, $params)
    {
        if ($name === 'order') {
            Craft::$app->getDeprecator()->log('ElementQuery::order()', 'The `order` element query param has been deprecated. Use `orderBy` instead.');

            if (count($params) == 1) {
                $this->orderBy = $params[0];
            } else {
                $this->orderBy = $params;
            }

            return $this;
        }

        return parent::__call($name, $params);
    }

    /**
     * Required by the IteratorAggregate interface.
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        Craft::$app->getDeprecator()->log('ElementQuery::getIterator()', 'Looping through element queries directly has been deprecated. Use the `all()` function to fetch the query results before looping over them.');
        return new ArrayIterator($this->all());
    }

    /**
     * Required by the ArrayAccess interface.
     *
     * @param int|string $name The offset to check
     * @return bool
     */
    public function offsetExists($name): bool
    {
        if (is_numeric($name)) {
            // Cached?
            if (($cachedResult = $this->getCachedResult()) !== null) {
                return $name < count($cachedResult);
            }

            $offset = $this->offset;
            $limit = $this->limit;

            $this->offset = $name;
            $this->limit = 1;

            $exists = $this->exists();

            $this->offset = $offset;
            $this->limit = $limit;

            return $exists;
        }

        /** @noinspection ImplicitMagicMethodCallInspection */
        return $this->__isset($name);
    }

    /**
     * Required by the ArrayAccess interface.
     *
     * @param int|string $name The offset to get
     * @return mixed The element at the given offset
     */
    public function offsetGet($name)
    {
        if (is_numeric($name) && ($element = $this->nth($name)) !== null) {
            return $element;
        }

        /** @noinspection ImplicitMagicMethodCallInspection */
        return $this->__get($name);
    }

    /**
     * Required by the ArrayAccess interface.
     *
     * @param string $name The offset to set
     * @param mixed $value The value
     * @throws NotSupportedException if $name is numeric
     */
    public function offsetSet($name, $value)
    {
        if (is_numeric($name)) {
            throw new NotSupportedException('ElementQuery does not support setting an element using array syntax.');
        }

        /** @noinspection ImplicitMagicMethodCallInspection */
        $this->__set($name, $value);
    }

    /**
     * Required by the ArrayAccess interface.
     *
     * @param string $name The offset to unset
     * @throws NotSupportedException if $name is numeric
     */
    public function offsetUnset($name)
    {
        if (is_numeric($name)) {
            throw new NotSupportedException('ElementQuery does not support unsetting an element using array syntax.');
        }

        /** @noinspection ImplicitMagicMethodCallInspection */
        return $this->__unset($name);
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        /** @noinspection PhpUndefinedClassInspection */
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
    public function inReverse(bool $value = true)
    {
        $this->inReverse = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $asArray
     */
    public function asArray(bool $value = true)
    {
        $this->asArray = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $asArray
     */
    public function ignorePlaceholders(bool $value = true)
    {
        $this->ignorePlaceholders = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $drafts
     */
    public function drafts(?bool $value = true)
    {
        $this->drafts = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $draftId
     * @uses $drafts
     */
    public function draftId(int $value = null)
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
    public function draftOf($value)
    {
        if ($value instanceof ElementInterface) {
            $this->draftOf = $value->getSourceId();
        } else if (is_numeric($value) || $value === '*' || $value === false || $value === null) {
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
    public function draftCreator($value)
    {
        if ($value instanceof User) {
            $this->draftCreator = $value->id;
        } else if (is_numeric($value) || $value === null) {
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
     * @uses $savedDraftsOnly
     */
    public function savedDraftsOnly(bool $value = true)
    {
        $this->savedDraftsOnly = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $revisions
     */
    public function revisions(bool $value = true)
    {
        $this->revisions = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $revisionId
     * @uses $revisions
     */
    public function revisionId(int $value = null)
    {
        $this->revisionId = $value;
        $this->revisions = $value !== null;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $revisionOf
     * @uses $revisions
     */
    public function revisionOf($value)
    {
        if ($value instanceof ElementInterface) {
            $this->revisionOf = $value->getSourceId();
        } else if (is_numeric($value) || $value === null) {
            $this->revisionOf = $value;
        } else {
            throw new InvalidArgumentException('Invalid revisionOf value');
        }
        $this->revisions = $value !== null;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $revisionCreator
     * @uses $revisions
     */
    public function revisionCreator($value)
    {
        if ($value instanceof User) {
            $this->revisionCreator = $value->id;
        } else if (is_numeric($value) || $value === null) {
            $this->revisionCreator = $value;
        } else {
            throw new InvalidArgumentException('Invalid revisionCreator value');
        }
        $this->revisions = $value !== null;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $id
     */
    public function id($value)
    {
        $this->id = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $uid
     */
    public function uid($value)
    {
        $this->uid = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $fixedOrder
     */
    public function fixedOrder(bool $value = true)
    {
        $this->fixedOrder = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $orderBy
     */
    public function orderBy($columns)
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
    public function addOrderBy($columns)
    {
        // If orderBy is an empty, non-null value (leaving it up to the element query class to decide),
        // then treat this is an orderBy() call.
        if ($this->orderBy !== null && empty($this->orderBy)) {
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
    public function status($value)
    {
        $this->status = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $archived
     */
    public function archived(bool $value = true)
    {
        $this->archived = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $trashed
     */
    public function trashed($value = true)
    {
        $this->trashed = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $dateCreated
     */
    public function dateCreated($value)
    {
        $this->dateCreated = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $dateUpdated
     */
    public function dateUpdated($value)
    {
        $this->dateUpdated = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @throws InvalidArgumentException if $value is invalid
     * @uses $siteId
     */
    public function site($value)
    {
        if ($value === null) {
            $this->siteId = null;
        } else if ($value === '*') {
            $this->siteId = Craft::$app->getSites()->getAllSiteIds();
        } else if ($value instanceof Site) {
            $this->siteId = $value->id;
        } else if (is_string($value)) {
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
    public function siteId($value)
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
     * Sets the [[$site]] property.
     *
     * @param string $value The property value
     * @return static self reference
     * @deprecated in 3.0.0. Use [[site]] or [[siteId]] instead.
     */
    public function locale(string $value)
    {
        Craft::$app->getDeprecator()->log('ElementQuery::locale()', 'The `locale` element query param has been deprecated. Use `site` or `siteId` instead.');
        $this->site($value);
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $unique
     * @since 3.2.0
     */
    public function unique(bool $value = true)
    {
        $this->unique = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $preferSites
     * @since 3.2.0
     */
    public function preferSites(array $value = null)
    {
        $this->preferSites = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $enabledForSite
     */
    public function enabledForSite(bool $value = true)
    {
        Craft::$app->getDeprecator()->log('ElementQuery::enabledForSite()', 'The `enabledForSite` element query param has been deprecated. `status()` should be used instead.');
        $this->enabledForSite = $value;
        return $this;
    }

    /**
     * Sets the [[$enabledForSite]] property.
     *
     * @param mixed $value The property value (defaults to true)
     * @return static self reference
     * @deprecated in 3.0.0. [[status()]] should be used instead.
     */
    public function localeEnabled($value = true)
    {
        Craft::$app->getDeprecator()->log('ElementQuery::localeEnabled()', 'The `localeEnabled` element query param has been deprecated. `status()` should be used instead.');
        $this->enabledForSite = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $relatedTo
     */
    public function relatedTo($value)
    {
        $this->relatedTo = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $title
     */
    public function title($value)
    {
        $this->title = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $slug
     */
    public function slug($value)
    {
        $this->slug = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $uri
     */
    public function uri($value)
    {
        $this->uri = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $search
     */
    public function search($value)
    {
        $this->search = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $ref
     */
    public function ref($value)
    {
        $this->ref = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $with
     */
    public function with($value)
    {
        $this->with = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $with
     */
    public function andWith($value)
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
    public function withStructure(bool $value = true)
    {
        $this->withStructure = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $structureId
     */
    public function structureId(int $value = null)
    {
        $this->structureId = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $level
     */
    public function level($value = null)
    {
        $this->level = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $hasDescendants
     */
    public function hasDescendants(bool $value = true)
    {
        $this->hasDescendants = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $leaves
     */
    public function leaves(bool $value = true)
    {
        $this->leaves = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $ancestorOf
     */
    public function ancestorOf($value)
    {
        $this->ancestorOf = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $ancestorDist
     */
    public function ancestorDist(int $value = null)
    {
        $this->ancestorDist = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $descendantOf
     */
    public function descendantOf($value)
    {
        $this->descendantOf = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $descendantDist
     */
    public function descendantDist(int $value = null)
    {
        $this->descendantDist = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $siblingOf
     */
    public function siblingOf($value)
    {
        $this->siblingOf = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $prevSiblingOf
     */
    public function prevSiblingOf($value)
    {
        $this->prevSiblingOf = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $nextSiblingOf
     */
    public function nextSiblingOf($value)
    {
        $this->nextSiblingOf = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $positionedBefore
     */
    public function positionedBefore($value)
    {
        $this->positionedBefore = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $positionedAfter
     */
    public function positionedAfter($value)
    {
        $this->positionedAfter = $value;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function anyStatus()
    {
        $this->status = null;
        $this->enabledForSite = false;
        return $this;
    }

    // Query preparation/execution
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     * @throws QueryAbortedException if it can be determined that there won’t be any results
     */
    public function prepare($builder)
    {
        // Is the query already doomed?
        if ($this->id !== null && empty($this->id)) {
            throw new QueryAbortedException();
        }
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
            if (Craft::$app->getIsInstalled() && !Craft::$app->getUpdates()->getIsCraftDbMigrationNeeded()) {
                /** @noinspection PhpUnhandledExceptionInspection */
                throw $e;
            }
            throw new QueryAbortedException($e->getMessage(), 0, $e);
        }

        // Normalize the orderBy param in case it was set directly
        if (!empty($this->orderBy)) {
            $this->orderBy = $this->normalizeOrderBy($this->orderBy);
        }

        // Build the query
        // ---------------------------------------------------------------------

        $this->query = new Query();
        $this->subQuery = new Query();

        // Give other classes a chance to make changes up front
        if (!$this->beforePrepare()) {
            throw new QueryAbortedException();
        }

        $this->query
            ->from(['subquery' => $this->subQuery])
            ->innerJoin(['elements' => Table::ELEMENTS], '[[elements.id]] = [[subquery.elementsId]]')
            ->innerJoin(['elements_sites' => Table::ELEMENTS_SITES], '[[elements_sites.id]] = [[subquery.elementsSitesId]]');

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

        if ($class::hasContent() && $this->contentTable !== null) {
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
            $this->subQuery->andWhere(Db::parseParam('elements.id', $this->id));
        }

        if ($this->uid) {
            $this->subQuery->andWhere(Db::parseParam('elements.uid', $this->uid));
        }

        if ($this->archived) {
            $this->subQuery->andWhere(['elements.archived' => true]);
        } else {
            $this->subQuery->andWhere(['elements.archived' => false]);
            $this->_applyStatusParam($class);
        }

        // todo: remove schema version condition after next beakpoint
        $schemaVersion = Craft::$app->getInstalledSchemaVersion();
        if (version_compare($schemaVersion, '3.1.0', '>=')) {
            if ($this->trashed === false) {
                $this->subQuery->andWhere(['elements.dateDeleted' => null]);
            } else if ($this->trashed === true) {
                $this->subQuery->andWhere(['not', ['elements.dateDeleted' => null]]);
            }
        }

        if ($this->dateCreated) {
            $this->subQuery->andWhere(Db::parseDateParam('elements.dateCreated', $this->dateCreated));
        }

        if ($this->dateUpdated) {
            $this->subQuery->andWhere(Db::parseDateParam('elements.dateUpdated', $this->dateUpdated));
        }

        if ($this->title !== null && $this->title !== '' && $class::hasTitles()) {
            $this->subQuery->andWhere(Db::parseParam('content.title', $this->title, '=', true));
        }

        if ($this->slug) {
            $this->subQuery->andWhere(Db::parseParam('elements_sites.slug', $this->slug));
        }

        if ($this->uri) {
            $this->subQuery->andWhere(Db::parseParam('elements_sites.uri', $this->uri, '=', true));
        }

        if ($this->enabledForSite) {
            $this->subQuery->andWhere(['elements_sites.enabled' => true]);
        }

        $this->_applyRelatedToParam();
        $this->_applyStructureParams($class);
        $this->_applyRevisionParams();
        $this->_applySearchParam($builder->db);
        $this->_applyOrderByParams($builder->db);
        $this->_applySelectParam();
        $this->_applyJoinParams();

        // Give other classes a chance to make changes up front
        if (!$this->afterPrepare()) {
            throw new QueryAbortedException();
        }

        $this->_applyUniqueParam($builder->db);

        // Pass the query back
        return $this->query;
    }

    /**
     * @inheritdoc
     * @return ElementInterface[]|array The resulting elements.
     */
    public function populate($rows)
    {
        if (empty($rows)) {
            return [];
        }

        // Should we set a search score on the elements?
        if ($this->_searchScores !== null) {
            foreach ($rows as &$row) {
                if (isset($row['id'], $this->_searchScores[$row['id']])) {
                    $row['searchScore'] = $this->_searchScores[$row['id']];
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
    public function count($q = '*', $db = null)
    {
        // Cached?
        if (($cachedResult = $this->getCachedResult()) !== null) {
            return count($cachedResult);
        }

        return parent::count($q, $db) ?: 0;
    }

    /**
     * @inheritdoc
     */
    public function all($db = null)
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
     * @return ElementInterface|array|null the first element. Null is returned if the query
     * results in nothing.
     */
    public function one($db = null)
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
    public function column($db = null)
    {
        // Avoid indexing by an ambiguous column
        if (
            $this->from === null &&
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
    public function exists($db = null)
    {
        return ($this->getCachedResult() !== null) ?: parent::exists($db);
    }

    /**
     * @inheritdoc
     * @return ElementInterface|array|null The element. Null is returned if the query
     * results in nothing.
     */
    public function nth(int $n, Connection $db = null)
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
    public function ids($db = null): array
    {
        // TODO: Remove this in Craft 4
        // Make sure $db is not a list of attributes
        if ($this->_setAttributes($db)) {
            Craft::$app->getDeprecator()->log('ElementQuery::ids($criteria)', 'Passing new criteria params to the `ids()` element query function is now deprecated. Set the parameters before calling `ids()`.');
            $db = null;
        }

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
    public function getCachedResult()
    {
        if ($this->_result === null) {
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
    public function setCachedResult(array $elements)
    {
        $this->_result = $elements;
        $this->_resultCriteria = $this->getCriteria();
    }

    /**
     * Clears the cached result.
     *
     * @see getCachedResult()
     * @see setCachedResult()
     * @since 3.4.0
     */
    public function clearCachedResult()
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
        foreach ((new \ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
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
        foreach ((new \ReflectionClass($behavior))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (
                !$property->isStatic() &&
                !in_array($property->getName(), [
                    'hasMethods',
                    'owner',
                    // avoid conflicts with ElementQuery getters
                    'iterator',
                    'cachedResult',
                    'criteria',
                    'behaviors',
                    'behavior',
                    'rawSql',
                ], true)
            ) {
                $names[] = $property->getName();
            }
        }

        return $names;
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
    public function fields()
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

        $class = $this->elementType;

        // Instantiate the element
        if ($this->structureId) {
            $row['structureId'] = $this->structureId;
        }

        if ($class::hasContent() && $this->contentTable !== null) {
            if ($class::hasTitles()) {
                // Ensure the title is a string
                $row['title'] = (string)($row['title'] ?? '');
            }

            // Separate the content values from the main element attributes
            $fieldValues = [];

            if (!empty($this->customFields)) {
                foreach ($this->customFields as $field) {
                    if ($field->hasContentColumn()) {
                        // Account for results where multiple fields have the same handle, but from
                        // different columns e.g. two Matrix block types that each have a field with the
                        // same handle
                        $colName = $this->_getFieldContentColumnName($field);

                        if (!isset($fieldValues[$field->handle]) || (empty($fieldValues[$field->handle]) && !empty($row[$colName]))) {
                            $fieldValues[$field->handle] = $row[$colName] ?? null;
                        }

                        unset($row[$colName]);
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
            if (!empty($row['draftId'])) {
                $behaviors['draft'] = new DraftBehavior([
                    'sourceId' => ArrayHelper::remove($row, 'draftSourceId'),
                    'creatorId' => ArrayHelper::remove($row, 'draftCreatorId'),
                    'draftName' => ArrayHelper::remove($row, 'draftName'),
                    'draftNotes' => ArrayHelper::remove($row, 'draftNotes'),
                    'trackChanges' => (bool)ArrayHelper::remove($row, 'draftTrackChanges'),
                    'dateLastMerged' => ArrayHelper::remove($row, 'draftDateLastMerged'),
                ]);
            } else {
                unset(
                    $row['draftSourceId'],
                    $row['draftCreatorId'],
                    $row['draftName'],
                    $row['draftNotes'],
                    $row['draftTrackChanges'],
                    $row['draftDateLastMerged']
                );
            }
        }

        if ($this->revisions) {
            $behaviors['revision'] = new RevisionBehavior([
                'sourceId' => ArrayHelper::remove($row, 'revisionSourceId'),
                'creatorId' => ArrayHelper::remove($row, 'revisionCreatorId'),
                'revisionNum' => ArrayHelper::remove($row, 'revisionNum'),
                'revisionNotes' => ArrayHelper::remove($row, 'revisionNotes'),
            ]);
        }

        $element = new $class($row);
        $element->attachBehaviors($behaviors);

        // Fire an 'afterPopulateElement' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_POPULATE_ELEMENT)) {
            $event = new PopulateElementEvent([
                'element' => $element,
                'row' => $row
            ]);
            $this->trigger(self::EVENT_AFTER_POPULATE_ELEMENT, $event);
            return $event->element;
        }

        return $element;
    }

    // Deprecated Methods
    // -------------------------------------------------------------------------

    /**
     * Sets the [[$orderBy]] property.
     *
     * @param string $value The property value
     * @return static self reference
     * @deprecated in Craft 3.0. Use [[orderBy()]] instead.
     */
    public function order(string $value)
    {
        Craft::$app->getDeprecator()->log('ElementQuery::order()', 'The `order` element query param has been deprecated. Use `orderBy` instead.');

        return $this->orderBy($value);
    }

    /**
     * Returns all elements that match the criteria.
     *
     * @param array|null $attributes Any last-minute parameters that should be added.
     * @return ElementInterface[] The matched elements.
     * @deprecated in Craft 3.0. Use all() instead.
     */
    public function find(array $attributes = null): array
    {
        Craft::$app->getDeprecator()->log('ElementQuery::find()', 'The `find()` function used to query for elements is now deprecated. Use `all()` instead.');
        $this->_setAttributes($attributes);

        return $this->all();
    }

    /**
     * Returns the first element that matches the criteria.
     *
     * @param array|null $attributes
     * @return ElementInterface|null
     * @deprecated in Craft 3.0. Use one() instead.
     */
    public function first(array $attributes = null)
    {
        Craft::$app->getDeprecator()->log('ElementQuery::first()', 'The `first()` function used to query for elements is now deprecated. Use `one()` instead.');
        $this->_setAttributes($attributes);

        return $this->one();
    }

    /**
     * Returns the last element that matches the criteria.
     *
     * @param array|null $attributes
     * @return ElementInterface|null
     * @deprecated in Craft 3.0. Use nth() instead.
     */
    public function last(array $attributes = null)
    {
        Craft::$app->getDeprecator()->log('ElementQuery::last()', 'The `last()` function used to query for elements is now deprecated. Use `inReverse().one()` instead.');
        $this->_setAttributes($attributes);
        $count = $this->count();
        $offset = $this->offset;
        $this->offset = 0;
        $result = $this->nth($count - 1);
        $this->offset = $offset;

        return $result;
    }

    /**
     * Returns the total elements that match the criteria.
     *
     * @param array|null $attributes
     * @return int
     * @deprecated in Craft 3.0. Use count() instead.
     */
    public function total(array $attributes = null): int
    {
        Craft::$app->getDeprecator()->log('ElementQuery::total()', 'The `total()` function used to query for elements is now deprecated. Use `count()` instead.');
        $this->_setAttributes($attributes);

        return $this->count();
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
        if ($elementsService->getIsCollectingCacheTags()) {
            $cacheTags = [
                'element',
                "element::$this->elementType",
            ];

            // If specific IDs were requested, then use those
            if (is_numeric($this->id) || (is_array($this->id) && ArrayHelper::isNumeric($this->id))) {
                $queryTags = (array)$this->id;
            } else if ($this->drafts) {
                $queryTags = ['drafts'];
            } else if ($this->revisions) {
                $queryTags = ['revisions'];
            } else {
                $queryTags = $this->cacheTags() ?: ['*'];
            }

            foreach ($queryTags as $tag) {
                $cacheTags[] = "element::$this->elementType::$tag";
            }

            $elementsService->collectCacheTags($cacheTags);
        }

        return true;
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
        // todo: remove this after the next breakpoint
        if (Craft::$app->getUpdates()->getIsCraftDbMigrationNeeded()) {
            return [];
        }

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
     *             return ['mytable.pending' => 1];
     *         default:
     *             return parent::statusCondition($status);
     *     }
     * ```
     *
     * @param string $status The status
     * @return string|array|ExpressionInterface|false|null The status condition, or false if $status is an unsupported status
     */
    protected function statusCondition(string $status)
    {
        switch ($status) {
            case Element::STATUS_ENABLED:
                return [
                    'elements.enabled' => true,
                    'elements_sites.enabled' => true,
                ];
            case Element::STATUS_DISABLED:
                return [
                    'or',
                    ['elements.enabled' => false],
                    ['elements_sites.enabled' => false],
                ];
            case Element::STATUS_ARCHIVED:
                return ['elements.archived' => true];
            default:
                return false;
        }
    }

    /**
     * Joins in a table with an `id` column that has a foreign key pointing to `craft_elements`.`id`.
     *
     * @param string $table The unprefixed table name. This will also be used as the table’s alias within the query.
     */
    protected function joinElementTable(string $table)
    {
        $joinTable = [$table => "{{%$table}}"];
        $this->query->innerJoin($joinTable, "[[{$table}.id]] = [[subquery.elementsId]]");
        $this->subQuery->innerJoin($joinTable, "[[{$table}.id]] = [[elements.id]]");
    }

    /**
     * @inheritdoc
     */
    protected function normalizeOrderBy($columns)
    {
        // Special case for 'score' - that should be shorthand for SORT_DESC, not SORT_ASC
        if ($columns === 'score') {
            return ['score' => SORT_DESC];
        }

        return parent::normalizeOrderBy($columns);
    }

    /**
     * Combines the given condition with an alternative condition if there are any relevant placeholder elements.
     *
     * @param mixed $condition
     * @return mixed
     */
    private function _placeholderCondition($condition)
    {
        if ($this->ignorePlaceholders) {
            return $condition;
        }

        if ($this->_placeholderCondition === null || $this->siteId !== $this->_placeholderSiteIds) {
            $placeholderSourceIds = [];
            $placeholderElements = Craft::$app->getElements()->getPlaceholderElements();
            if (!empty($placeholderElements)) {
                $siteIds = array_flip((array)$this->siteId);
                foreach ($placeholderElements as $element) {
                    if ($element instanceof $this->elementType && isset($siteIds[$element->siteId])) {
                        $placeholderSourceIds[] = $element->getSourceId();
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
     * @throws QueryAbortedException
     */
    private function _joinContentTable(string $class)
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
                if ($field->hasContentColumn()) {
                    $this->query->addSelect(['content.' . $this->_getFieldContentColumnName($field)]);
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

                $fieldResponse = $field->modifyElementsQuery($this, $fieldAttributeValue);

                // Set it back
                $contentService->fieldColumnPrefix = $originalFieldColumnPrefix;

                // Need to bail early?
                if ($fieldResponse === false) {
                    throw new QueryAbortedException();
                }
            }
        }
    }

    /**
     * Applies the 'status' param to the query being prepared.
     *
     * @param string $class
     * @throws QueryAbortedException
     */
    private function _applyStatusParam(string $class)
    {
        /** @var string|ElementInterface $class */
        if (!$this->status || !$class::hasStatuses()) {
            return;
        }

        $statuses = $this->status;
        if (!is_array($statuses)) {
            $statuses = is_string($statuses) ? StringHelper::split($statuses) : [$statuses];
        }

        $condition = ['or'];

        foreach ($statuses as $status) {
            $status = strtolower($status);
            $statusCondition = $this->statusCondition($status);

            if ($statusCondition === false) {
                throw new QueryAbortedException('Unsupported status: ' . $status);
            }

            if ($statusCondition !== null) {
                $condition[] = $statusCondition;
            }
        }

        $this->subQuery->andWhere($this->_placeholderCondition($condition));
    }

    /**
     * Applies the 'relatedTo' param to the query being prepared.
     *
     * @throws QueryAbortedException
     */
    private function _applyRelatedToParam()
    {
        if (!$this->relatedTo) {
            return;
        }

        $parser = new ElementRelationParamParser([
            'fields' => $this->customFields ? ArrayHelper::index($this->customFields, 'handle') : []
        ]);
        $condition = $parser->parse($this->relatedTo);

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
            ($this->withStructure || ($this->withStructure !== false && $this->structureId))
        );
    }

    /**
     * Applies the structure params to the query being prepared.
     *
     * @param string $class
     * @throws QueryAbortedException
     */
    private function _applyStructureParams(string $class)
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
                    throw new QueryAbortedException("Unable to apply the '{$param}' param because 'structureId' isn't set");
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
            $this->query->innerJoin(['structureelements' => Table::STRUCTUREELEMENTS], [
                'and',
                '[[structureelements.elementId]] = [[subquery.elementsId]]',
                ['structureelements.structureId' => $this->structureId]
            ]);
            $this->subQuery->innerJoin(['structureelements' => Table::STRUCTUREELEMENTS], [
                'and',
                '[[structureelements.elementId]] = [[elements.id]]',
                ['structureelements.structureId' => $this->structureId]
            ]);
        } else {
            $this->query
                ->addSelect(['structureelements.structureId'])
                ->leftJoin(['structureelements' => Table::STRUCTUREELEMENTS], [
                    'and',
                    '[[structureelements.elementId]] = [[subquery.elementsId]]',
                    '[[structureelements.structureId]] = [[subquery.structureId]]',
                ]);
            $existsQuery = (new Query())
                ->from([Table::STRUCTURES])
                ->where('[[id]] = [[structureelements.structureId]]');
            // todo: remove schema version condition after next beakpoint
            $schemaVersion = Craft::$app->getInstalledSchemaVersion();
            if (version_compare($schemaVersion, '3.1.0', '>=')) {
                $existsQuery->andWhere(['dateDeleted' => null]);
            }
            $this->subQuery
                ->addSelect(['structureelements.structureId'])
                ->leftJoin(['structureelements' => Table::STRUCTUREELEMENTS], [
                    'and',
                    '[[structureelements.elementId]] = [[elements.id]]',
                    ['exists', $existsQuery],
                ]);
        }

        if ($this->hasDescendants !== null) {
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
                ['structureelements.root' => $ancestorOf->root]
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
                ['structureelements.root' => $descendantOf->root]
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
                ['not', ['structureelements.elementId' => $siblingOf->id]]
            ]);

            if ($siblingOf->level != 1) {
                $parent = $siblingOf->getParent();

                if (!$parent) {
                    throw new QueryAbortedException();
                }

                $this->subQuery->andWhere([
                    'and',
                    ['>', 'structureelements.lft', $parent->lft],
                    ['<', 'structureelements.rgt', $parent->rgt]
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
                ['structureelements.root' => $positionedBefore->root]
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
            $this->subQuery->andWhere(Db::parseParam('structureelements.level', $this->level));
        }

        if ($this->leaves) {
            $this->subQuery->andWhere('[[structureelements.rgt]] = [[structureelements.lft]] + 1');
        }
    }

    /**
     * Applies draft and revision params to the query being prepared.
     *
     * @throws QueryAbortedException
     */
    private function _applyRevisionParams()
    {
        if (!self::_supportsRevisionParams()) {
            if ($this->drafts !== false || $this->revisions) {
                throw new QueryAbortedException();
            }
            return;
        }

        if ($this->drafts !== false) {
            if ($this->drafts === true) {
                $this->subQuery->innerJoin(['drafts' => Table::DRAFTS], '[[drafts.id]] = [[elements.draftId]]');
                $this->query->innerJoin(['drafts' => Table::DRAFTS], '[[drafts.id]] = [[elements.draftId]]');
            } else {
                $this->subQuery->leftJoin(['drafts' => Table::DRAFTS], '[[drafts.id]] = [[elements.draftId]]');
                $this->query->leftJoin(['drafts' => Table::DRAFTS], '[[drafts.id]] = [[elements.draftId]]');
            }

            $this->query->addSelect([
                'elements.draftId',
                'drafts.sourceId as draftSourceId',
                'drafts.creatorId as draftCreatorId',
                'drafts.name as draftName',
                'drafts.notes as draftNotes',
            ]);

            $schemaVersion = Craft::$app->getInstalledSchemaVersion();
            if (version_compare($schemaVersion, '3.4.3', '>=')) {
                $this->query->addSelect(['drafts.trackChanges as draftTrackChanges']);
                $this->query->addSelect(['drafts.dateLastMerged as draftDateLastMerged']);
            }

            if ($this->draftId) {
                $this->subQuery->andWhere(['elements.draftId' => $this->draftId]);
            }

            if ($this->draftOf === '*') {
                $this->subQuery->andWhere(['not', ['drafts.sourceId' => null]]);
            } else if ($this->draftOf !== null) {
                $this->subQuery->andWhere(['drafts.sourceId' => $this->draftOf ?: null]);
            }

            if ($this->draftCreator) {
                $this->subQuery->andWhere(['drafts.creatorId' => $this->draftCreator]);
            }

            if ($this->savedDraftsOnly) {
                $this->subQuery->andWhere([
                    'or',
                    ['elements.draftId' => null],
                    ['not', ['drafts.sourceId' => null]],
                    ['drafts.saved' => true]
                ]);
            }
        } else {
            $this->subQuery->andWhere($this->_placeholderCondition(['elements.draftId' => null]));
        }

        if ($this->revisions) {
            $this->subQuery->innerJoin(['revisions' => Table::REVISIONS], '[[revisions.id]] = [[elements.revisionId]]');
            $this->query
                ->innerJoin(['revisions' => Table::REVISIONS], '[[revisions.id]] = [[elements.revisionId]]')
                ->addSelect([
                    'elements.revisionId',
                    'revisions.sourceId as revisionSourceId',
                    'revisions.creatorId as revisionCreatorId',
                    'revisions.num as revisionNum',
                    'revisions.notes as revisionNotes',
                ]);

            if ($this->revisionId) {
                $this->subQuery->andWhere(['elements.revisionId' => $this->revisionId]);
            }

            if ($this->revisionOf) {
                $this->subQuery->andWhere(['revisions.sourceId' => $this->revisionOf]);
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
    private function _normalizeSiteId()
    {
        if (!$this->siteId) {
            // Default to the current site
            $this->siteId = Craft::$app->getSites()->getCurrentSite()->id;
        } else if ($this->siteId === '*') {
            $this->siteId = Craft::$app->getSites()->getAllSiteIds();
        }
    }

    /**
     * Normalizes a structure param value to either an Element object or false.
     *
     * @param string $property The parameter’s property name.
     * @param string $class The element class
     * @return ElementInterface The normalized element
     * @throws QueryAbortedException if the element can't be found
     */
    private function _normalizeStructureParamValue(string $property, string $class): ElementInterface
    {
        if ($this->$property !== false && !$this->$property instanceof ElementInterface) {
            $this->$property = $class::find()
                ->id($this->$property)
                ->siteId($this->siteId)
                ->structureId($this->structureId)
                ->anyStatus()
                ->one();

            if ($this->$property === null) {
                $this->$property = false;
            }
        }

        if ($this->$property === false) {
            throw new QueryAbortedException();
        }

        return $this->$property;
    }

    /**
     * Applies the 'search' param to the query being prepared.
     *
     * @param Connection $db
     * @throws Exception if the DB connection doesn't support fixed ordering
     * @throws QueryAbortedException
     */
    private function _applySearchParam(Connection $db)
    {
        $this->_searchScores = null;

        if ($this->search) {
            // Get the element IDs
            $elementIdsQuery = clone $this;
            $elementIds = $elementIdsQuery
                ->search(null)
                ->offset(null)
                ->limit(null)
                ->ids();

            $searchResults = Craft::$app->getSearch()->filterElementIdsByQuery($elementIds, $this->search, true, $this->siteId, true);

            // No results?
            if (empty($searchResults)) {
                throw new QueryAbortedException();
            }

            $filteredElementIds = array_keys($searchResults);

            if ($this->orderBy === ['score' => SORT_ASC] || $this->orderBy === ['score' => SORT_DESC]) {
                // Order the elements in the exact order that the Search service returned them in
                if (!$db instanceof \craft\db\Connection) {
                    throw new Exception('The database connection doesn’t support fixed ordering.');
                }
                if (
                    ($this->orderBy === ['score' => SORT_ASC] && !$this->inReverse) ||
                    ($this->orderBy === ['score' => SORT_DESC] && $this->inReverse)
                ) {
                    $orderBy = [new FixedOrderExpression('elements.id', array_reverse($filteredElementIds), $db)];
                } else {
                    $orderBy = [new FixedOrderExpression('elements.id', $filteredElementIds, $db)];
                }

                $this->query->orderBy($orderBy);
                $this->subQuery->orderBy($orderBy);
            }

            $this->subQuery->andWhere(['elements.id' => $filteredElementIds]);

            $this->_searchScores = $searchResults;
        }
    }

    /**
     * Applies the 'fixedOrder' and 'orderBy' params to the query being prepared.
     *
     * @param Connection $db
     * @throws Exception if the DB connection doesn't support fixed ordering
     * @throws QueryAbortedException
     */
    private function _applyOrderByParams(Connection $db)
    {
        if (
            $this->orderBy === null ||
            $this->orderBy === ['score' => SORT_ASC] ||
            $this->orderBy === ['score' => SORT_DESC] ||
            !empty($this->query->orderBy)
        ) {
            return;
        }

        // Any other empty value means we should set it
        if (empty($this->orderBy)) {
            if ($this->fixedOrder) {
                if (empty($this->id)) {
                    throw new QueryAbortedException;
                }

                $ids = $this->id;
                if (!is_array($ids)) {
                    $ids = is_string($ids) ? StringHelper::split($ids) : [$ids];
                }

                if (!$db instanceof \craft\db\Connection) {
                    throw new Exception('The database connection doesn’t support fixed ordering.');
                }
                $this->orderBy = [new FixedOrderExpression('elements.id', $ids, $db)];
            } else if (self::_supportsRevisionParams() && $this->revisions) {
                $this->orderBy = ['num' => SORT_DESC];
            } else if ($this->_shouldJoinStructureData()) {
                $this->orderBy = ['structureelements.lft' => SORT_ASC] + $this->defaultOrderBy;
            } else if (!empty($this->defaultOrderBy)) {
                $this->orderBy = $this->defaultOrderBy;
            } else {
                return;
            }
        }

        // Define the real column name mapping (e.g. `fieldHandle` => `field_fieldHandle`)
        $orderColumnMap = [];

        if (is_array($this->customFields)) {
            // Add the field column prefixes
            foreach ($this->customFields as $field) {
                if ($field::hasContentColumn()) {
                    $orderColumnMap[$field->handle] = 'content.' . $this->_getFieldContentColumnName($field);
                }
            }
        }

        // Prevent “1052 Column 'id' in order clause is ambiguous” MySQL error
        $orderColumnMap['id'] = 'elements.id';
        $orderColumnMap['dateCreated'] = 'elements.dateCreated';
        $orderColumnMap['dateUpdated'] = 'elements.dateUpdated';

        // Rename orderBy keys based on the real column name mapping
        // (yes this is awkward but we need to preserve the order of the keys!)
        $orderBy = array_merge($this->orderBy);
        $orderByColumns = array_keys($orderBy);

        foreach ($orderColumnMap as $orderValue => $columnName) {
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
                else if (!$direction instanceof ExpressionInterface) {
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
    private function _applySelectParam()
    {
        // Select all columns defined by [[select]]
        $select = array_merge((array)$this->select);

        // Is there still a ** placeholder param?
        if (isset($select['**'])) {
            unset($select['**']);

            // Merge in the default columns
            $select = array_merge($select, [
                'elements.id' => 'elements.id',
                'elements.fieldLayoutId' => 'elements.fieldLayoutId',
                'elements.uid' => 'elements.uid',
                'elements.enabled' => 'elements.enabled',
                'elements.archived' => 'elements.archived',
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
    private function _applyJoinParams()
    {
        if ($this->join !== null) {
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
    private function _applyUniqueParam(Connection $db)
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
                } else if ($site = $sitesService->getSiteByHandle($preferSite)) {
                    $preferSites[] = $site->id;
                }
            }
        }

        $caseSql = 'case';
        $caseParams = [];
        foreach ($preferSites as $index => $siteId) {
            $param = 'preferSites' . $index;
            $caseSql .= " when [[elements_sites.siteId]] = :{$param} then {$index}";
            $caseParams[$param] = $siteId;
        }
        $caseSql .= ' else ' . count($preferSites) . ' end';

        $subSelectSqlQuery = clone $this->subQuery;
        $subSelectSql = $subSelectSqlQuery
            ->select(['elements_sites.id'])
            ->andWhere('[[subElements.id]] = [[tmpElements.id]]')
            ->orderBy([
                new Expression($caseSql, $caseParams),
                'elements_sites.id' => SORT_ASC
            ])
            ->offset(0)
            ->limit(1)
            ->getRawSql();

        // `elements` => `subElements`
        $qElements = $db->quoteTableName('elements');
        $qSubElements = $db->quoteTableName('subElements');
        $qTmpElements = $db->quoteTableName('tmpElements');
        $q = $qElements[0];
        $subSelectSql = str_replace("{$qElements}.", "{$qSubElements}.", $subSelectSql);
        $subSelectSql = str_replace("{$q} {$qElements}", "{$q} {$qSubElements}", $subSelectSql);
        $subSelectSql = str_replace($qTmpElements, $qElements, $subSelectSql);

        $this->subQuery->andWhere(new Expression("[[elements_sites.id]] = ({$subSelectSql})"));
    }

    /**
     * Returns a field’s corresponding content column name.
     *
     * @param FieldInterface $field
     * @return string
     */
    private function _getFieldContentColumnName(FieldInterface $field): string
    {
        return ($field->columnPrefix ?: 'field_') . $field->handle;
    }

    /**
     * Converts found rows into element instances
     *
     * @param array $rows
     * @return array|ElementInterface[]
     */
    private function _createElements(array $rows)
    {
        $elements = [];

        if ($this->asArray === true) {
            if ($this->indexBy === null) {
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
                if ($this->indexBy === null) {
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

            ElementHelper::setNextPrevOnElements($elements);

            // Should we eager-load some elements onto these?
            if ($this->with) {
                Craft::$app->getElements()->eagerLoadElements($this->elementType, $elements, $this->with);
            }
        }

        return $elements;
    }

    /**
     * Batch-sets attributes. Used by [[find()]], [[first()]], [[last()]], [[ids()]], and [[total()]].
     *
     * @param mixed $attributes
     * @return bool Whether $attributes was an array
     * @todo Remove this in Craft 4, along with the methods that call it.
     */
    private function _setAttributes($attributes): bool
    {
        if (is_array($attributes) || $attributes instanceof \IteratorAggregate) {
            foreach ($attributes as $name => $value) {
                if ($this->canSetProperty($name)) {
                    $this->$name = $value;
                }
            }

            return true;
        }

        return false;
    }
}
