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
use craft\base\Field;
use craft\base\FieldInterface;
use craft\behaviors\ElementQueryBehavior;
use craft\db\FixedOrderExpression;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\errors\SiteNotFoundException;
use craft\events\CancelableEvent;
use craft\events\PopulateElementEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\StringHelper;
use craft\models\Site;
use craft\search\SearchQuery;
use yii\base\ArrayableTrait;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\db\Connection;
use yii\db\ExpressionInterface;

/**
 * ElementQuery represents a SELECT SQL statement for elements in a way that is independent of DBMS.
 *
 * @property string|Site $site The site or site handle that the elements should be returned in
 * @mixin ElementQueryBehavior
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ElementQuery extends Query implements ElementQueryInterface
{
    // Traits
    // =========================================================================

    use ArrayableTrait;

    // Constants
    // =========================================================================

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
     */
    const EVENT_AFTER_POPULATE_ELEMENT = 'afterPopulateElement';

    // Properties
    // =========================================================================

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
    public $contentTable = '{{%content}}';

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
    public $status = ['enabled'];

    /**
     * @var bool Whether to return only archived elements.
     * @used-by archived()
     */
    public $archived = false;

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
     * @var int|null The site ID that the elements should be returned in.
     * @used-by site()
     * @used-by siteId()
     */
    public $siteId;

    /**
     * @var bool Whether the elements must be enabled for the chosen site.
     * @used-by enabledForSite()
     */
    public $enabledForSite = true;

    /**
     * @var bool Whether the elements must be “leaves” in the structure.
     * @used-by leaves()
     */
    public $leaves = false;

    /**
     * @var int|array|ElementInterface|null The element relation criteria.
     *
     * See [Relations](https://docs.craftcms.com/v3/relations.html) for supported syntax options.
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
     * See [Searching](https://docs.craftcms.com/v3/searching.html) for supported syntax options.
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
     * See [Eager-Loading Elements](https://docs.craftcms.com/v3/eager-loading-elements.html) for supported syntax options.
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
     * @var ElementInterface[]|null The cached element query result
     * @see setCachedResult()
     */
    private $_result;

    /**
     * @var Element[]|null The criteria params that were set when the cached element query result was set
     * @see setCachedResult()
     */
    private $_resultCriteria;

    /**
     * @var array|null
     */
    private $_searchScores;

    // Public Methods
    // =========================================================================

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
        $config['select'] = $config['select'] ?? ['**'];

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function __isset($name)
    {
        if ($name === 'order') {
            Craft::$app->getDeprecator()->log('ElementQuery::order()', 'The “order” element query param has been deprecated. Use “orderBy” instead.');

            return $this->orderBy !== null;
        }

        return parent::__isset($name);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        switch ($name) {
            case 'locale':
                Craft::$app->getDeprecator()->log('ElementQuery::locale()', 'The “locale” element query param has been deprecated. Use “site” or “siteId” instead.');
                if ($this->siteId && ($site = Craft::$app->getSites()->getSiteById($this->siteId))) {
                    return $site->handle;
                }

                return null;

            case 'order':
                Craft::$app->getDeprecator()->log('ElementQuery::order()', 'The “order” element query param has been deprecated. Use “orderBy” instead.');

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
                Craft::$app->getDeprecator()->log('ElementQuery::localeEnabled()', 'The “localeEnabled” element query param has been deprecated. Use “enabledForSite” instead.');
                $this->enabledForSite($value);
                break;
            case 'locale':
                Craft::$app->getDeprecator()->log('ElementQuery::locale()', 'The “locale” element query param has been deprecated. Use “site” or “siteId” instead.');
                $this->site($value);
                break;
            case 'order':
                Craft::$app->getDeprecator()->log('ElementQuery::order()', 'The “order” element query param has been deprecated. Use “orderBy” instead.');
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
            Craft::$app->getDeprecator()->log('ElementQuery::order()', 'The “order” element query param has been deprecated. Use “orderBy” instead.');

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
        Craft::$app->getDeprecator()->log('ElementQuery::getIterator()', 'Looping through element queries directly has been deprecated. Use the all() function to fetch the query results before looping over them.');
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
        $behaviors['customFields'] = ElementQueryBehavior::class;
        return $behaviors;
    }

    // Element criteria parameter setters
    // -------------------------------------------------------------------------

    /**
     * Sets the [[$inReverse]] property.
     *
     * @param bool $value The property value
     * @return static self reference
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
     * @throws Exception if $value is an invalid site handle
     * @uses $siteId
     */
    public function site($value)
    {
        if ($value instanceof Site) {
            $this->siteId = $value->id;
        } else {
            $site = Craft::$app->getSites()->getSiteByHandle($value);

            if (!$site) {
                throw new Exception('Invalid site handle: ' . $value);
            }

            $this->siteId = $site->id;
        }

        return $this;
    }

    /**
     * @inheritdoc
     * @uses $siteId
     */
    public function siteId(int $value = null)
    {
        $this->siteId = $value;
        return $this;
    }

    /**
     * Sets the [[$site]] property.
     *
     * @param string $value The property value
     * @return static self reference
     * @deprecated in 3.0. Use [[site]] or [[siteId]] instead.
     */
    public function locale(string $value)
    {
        Craft::$app->getDeprecator()->log('ElementQuery::locale()', 'The “locale” element query param has been deprecated. Use “site” or “siteId” instead.');
        $this->site($value);
        return $this;
    }

    /**
     * @inheritdoc
     * @uses $enabledForSite
     */
    public function enabledForSite(bool $value = true)
    {
        $this->enabledForSite = $value;
        return $this;
    }

    /**
     * Sets the [[$enabledForSite]] property.
     *
     * @param mixed $value The property value (defaults to true)
     * @return static self reference
     * @deprecated in 3.0. Use [[enabledForSite]] instead.
     */
    public function localeEnabled($value = true)
    {
        Craft::$app->getDeprecator()->log('ElementQuery::localeEnabled()', 'The “localeEnabled” element query param has been deprecated. Use “enabledForSite” instead.');
        $this->enabledForSite = $value;
        return $this;
    }

    /**
     * Sets the [[$leaves]] property.
     *
     * @param bool $value The property value.
     * @return static self reference
     * @uses $leaves
     */
    public function leaves(bool $value = true)
    {
        $this->leaves = $value;
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

        /** @var Element $class */
        $class = $this->elementType;

        // Make sure the siteId param is set
        try {
            if (!$class::isLocalized()) {
                // The criteria *must* be set to the primary site ID
                $this->siteId = Craft::$app->getSites()->getPrimarySite()->id;
            } else if (!$this->siteId) {
                // Default to the current site
                $this->siteId = Craft::$app->getSites()->getCurrentSite()->id;
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
            ->innerJoin('{{%elements}} elements', '[[elements.id]] = [[subquery.elementsId]]')
            ->innerJoin('{{%elements_sites}} elements_sites', '[[elements_sites.id]] = [[subquery.elementsSitesId]]');

        $this->subQuery
            ->addSelect([
                'elementsId' => 'elements.id',
                'elementsSitesId' => 'elements_sites.id',
            ])
            ->from(['elements' => '{{%elements}}'])
            ->innerJoin('{{%elements_sites}} elements_sites', '[[elements_sites.elementId]] = [[elements.id]]')
            ->andWhere(['elements_sites.siteId' => $this->siteId])
            ->andWhere($this->where)
            ->offset($this->offset)
            ->limit($this->limit)
            ->addParams($this->params);

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

        if ($this->dateCreated) {
            $this->subQuery->andWhere(Db::parseDateParam('elements.dateCreated', $this->dateCreated));
        }

        if ($this->dateUpdated) {
            $this->subQuery->andWhere(Db::parseDateParam('elements.dateUpdated', $this->dateUpdated));
        }

        if ($this->title && $class::hasTitles()) {
            $this->subQuery->andWhere(Db::parseParam('content.title', $this->title));
        }

        if ($this->slug) {
            $this->subQuery->andWhere(Db::parseParam('elements_sites.slug', $this->slug));
        }

        if ($this->uri) {
            $this->subQuery->andWhere(Db::parseParam('elements_sites.uri', $this->uri));
        }

        if ($this->enabledForSite) {
            $this->subQuery->andWhere(['elements_sites.enabled' => true]);
        }

        $this->_applyRelatedToParam();
        $this->_applyStructureParams($class);
        $this->_applySearchParam($builder->db);
        $this->_applyOrderByParams($builder->db);
        $this->_applySelectParam();
        $this->_applyJoinParams();

        // Give other classes a chance to make changes up front
        if (!$this->afterPrepare()) {
            throw new QueryAbortedException();
        }

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
                if (isset($this->_searchScores[$row['id']])) {
                    $row['searchScore'] = $this->_searchScores[$row['id']];
                }
            }
        }

        return $this->_createElements($rows);
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
            Craft::$app->getDeprecator()->log('ElementQuery::ids($criteria)', 'Passing new criteria params to the ids() element query function is now deprecated. Set the parameters before calling ids().');
            $db = null;
        }

        $select = $this->select;
        $this->select = ['elements.id'];
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
        // By default, include all public, non-static properties that were defined by a sub class, and certain ones in this class
        $class = new \ReflectionClass($this);
        $names = [];

        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
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
        Craft::$app->getDeprecator()->log('ElementQuery::order()', 'The “order” element query param has been deprecated. Use “orderBy” instead.');

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
        Craft::$app->getDeprecator()->log('ElementQuery::find()', 'The find() function used to query for elements is now deprecated. Use all() instead.');
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
        Craft::$app->getDeprecator()->log('ElementQuery::first()', 'The first() function used to query for elements is now deprecated. Use one() instead.');
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
        Craft::$app->getDeprecator()->log('ElementQuery::last()', 'The last() function used to query for elements is now deprecated. Use inReverse().one() instead.');
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
        Craft::$app->getDeprecator()->log('ElementQuery::total()', 'The total() function used to query for elements is now deprecated. Use count() instead.');
        $this->_setAttributes($attributes);

        return $this->count();
    }

    // Protected Methods
    // =========================================================================

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

        return $event->isValid;
    }

    /**
     * Returns the fields that should take part in an upcoming elements query.
     *
     * These fields will get their own criteria parameters in the [[ElementQueryInterface]] that gets passed in,
     * their field types will each have an opportunity to help build the element query, and their columns in the content
     * table will be selected by the query (for those that have one).
     * If a field has its own column in the content table, but the column name is prefixed with something besides
     * “field_”, make sure you set the `columnPrefix` attribute on the [[\craft\base\Field]], so
     * [[\craft\services\Elements::buildElementsQuery()]] knows which column to select.
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
                return ['elements.enabled' => true];
            case Element::STATUS_DISABLED:
                return ['elements.enabled' => false];
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
        $joinTable = "{{%{$table}}} {$table}";
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

    // Private Methods
    // =========================================================================

    /**
     * Joins the content table into the query being prepared.
     *
     * @param string $class
     * @throws QueryAbortedException
     */
    private function _joinContentTable(string $class)
    {
        // Join in the content table on both queries
        $this->subQuery->innerJoin($this->contentTable . ' content', '[[content.elementId]] = [[elements.id]]');
        $this->subQuery->addSelect(['contentId' => 'content.id']);
        $this->subQuery->andWhere(['content.siteId' => $this->siteId]);

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
                /** @var Field $field */
                if ($field->hasContentColumn()) {
                    $this->query->addSelect(['content.' . $this->_getFieldContentColumnName($field)]);
                }

                $handle = $field->handle;

                // In theory all field handles will be accounted for on the ElementQueryBehavior, but just to be safe...
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
            $status = StringHelper::toLowerCase($status);
            $statusCondition = $this->statusCondition($status);

            if ($statusCondition === false) {
                throw new QueryAbortedException('Unsupported status: ' . $status);
            }

            if ($statusCondition !== null) {
                $condition[] = $statusCondition;
            }
        }

        $this->subQuery->andWhere($condition);
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
        return $this->withStructure || ($this->withStructure !== false && $this->structureId);
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
            $this->query
                ->innerJoin('{{%structureelements}} structureelements', '[[structureelements.elementId]] = [[subquery.elementsId]]');
            $this->subQuery
                ->innerJoin('{{%structureelements}} structureelements', '[[structureelements.elementId]] = [[elements.id]]')
                ->andWhere(['structureelements.structureId' => $this->structureId]);
        } else {
            $this->query
                ->addSelect(['structureelements.structureId'])
                ->leftJoin('{{%structureelements}} structureelements', '[[structureelements.elementId]] = [[subquery.elementsId]]');
            $this->subQuery
                ->leftJoin('{{%structureelements}} structureelements', '[[structureelements.elementId]] = [[elements.id]]');
        }

        if ($this->hasDescendants !== null) {
            if ($this->hasDescendants) {
                $this->subQuery->andWhere('[[structureelements.rgt]] > [[structureelements.lft]] + 1');
            } else {
                $this->subQuery->andWhere('[[structureelements.rgt]] = [[structureelements.lft]] + 1');
            }
        }

        if ($this->ancestorOf) {
            /** @var Element $ancestorOf */
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
            /** @var Element $descendantOf */
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

        if ($this->siblingOf) {
            /** @var Element $siblingOf */
            $siblingOf = $this->_normalizeStructureParamValue('siblingOf', $class);

            $this->subQuery->andWhere([
                'and',
                [
                    'structureelements.level' => $siblingOf->level,
                    'structureelements.root' => $siblingOf->root,
                ],
                ['not', ['structureelements.elementId' => $siblingOf->id]]
            ]);

            if ($siblingOf->level != 1) {
                /** @var Element $parent */
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
        }

        if ($this->prevSiblingOf) {
            /** @var Element $prevSiblingOf */
            $prevSiblingOf = $this->_normalizeStructureParamValue('prevSiblingOf', $class);

            $this->subQuery->andWhere([
                'structureelements.level' => $prevSiblingOf->level,
                'structureelements.rgt' => $prevSiblingOf->lft - 1,
                'structureelements.root' => $prevSiblingOf->root
            ]);
        }

        if ($this->nextSiblingOf) {
            /** @var Element $nextSiblingOf */
            $nextSiblingOf = $this->_normalizeStructureParamValue('nextSiblingOf', $class);

            $this->subQuery->andWhere([
                'structureelements.level' => $nextSiblingOf->level,
                'structureelements.lft' => $nextSiblingOf->rgt + 1,
                'structureelements.root' => $nextSiblingOf->root
            ]);
        }

        if ($this->positionedBefore) {
            /** @var Element $positionedBefore */
            $positionedBefore = $this->_normalizeStructureParamValue('positionedBefore', $class);

            $this->subQuery->andWhere([
                'and',
                ['<', 'structureelements.rgt', $positionedBefore->lft],
                ['structureelements.root' => $positionedBefore->root]
            ]);
        }

        if ($this->positionedAfter) {
            /** @var Element $positionedAfter */
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
     * Normalizes a structure param value to either an Element object or false.
     *
     * @param string $property The parameter’s property name.
     * @param string $class The element class
     * @return ElementInterface The normalized element
     * @throws QueryAbortedException if the element can't be found
     */
    private function _normalizeStructureParamValue(string $property, string $class): ElementInterface
    {
        /** @var Element $class */
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
            $limit = $this->query->limit;
            $offset = $this->query->offset;
            $subLimit = $this->subQuery->limit;
            $subOffset = $this->subQuery->offset;

            $this->query->limit = null;
            $this->query->offset = null;
            $this->subQuery->limit = null;
            $this->subQuery->offset = null;

            $select = $this->query->select;
            $this->query->select = ['elements.id'];
            $elementIds = $this->query->column();
            $this->query->select = $select;
            $searchResults = Craft::$app->getSearch()->filterElementIdsByQuery($elementIds, $this->search, true, $this->siteId, true);

            $this->query->limit = $limit;
            $this->query->offset = $offset;
            $this->subQuery->limit = $subLimit;
            $this->subQuery->offset = $subOffset;

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
        if ($this->orderBy === null) {
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
            } else if ($this->_shouldJoinStructureData()) {
                $this->orderBy = ['structureelements.lft' => SORT_ASC] + $this->defaultOrderBy;
            } else {
                $this->orderBy = $this->defaultOrderBy;
            }
        }

        if (
            empty($this->orderBy) ||
            $this->orderBy === ['score' => SORT_ASC] ||
            $this->orderBy === ['score' => SORT_DESC] ||
            !empty($this->query->orderBy)
        ) {
            return;
        }

        $orderBy = array_merge($this->orderBy);
        $orderByColumns = array_keys($orderBy);
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

        foreach ($orderColumnMap as $orderValue => $columnName) {
            // Are we ordering by this column name?
            $pos = array_search($orderValue, $orderByColumns, true);

            if ($pos !== false) {
                // Swap it with the mapped column name
                $orderByColumns[$pos] = $columnName;
                $orderBy = array_combine($orderByColumns, $orderBy);
            }
        }

        if (empty($orderBy)) {
            return;
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
        if (($placeholderPos = array_search('**', $select, true)) !== false) {
            array_splice($select, $placeholderPos, 1);

            // Merge in the default columns
            $select = array_merge($select, [
                'elements.id',
                'elements.fieldLayoutId',
                'elements.uid',
                'elements.enabled',
                'elements.archived',
                'elements.dateCreated',
                'elements.dateUpdated',
                'elements_sites.slug',
                'elements_sites.uri',
                'enabledForSite' => 'elements_sites.enabled',
            ]);

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
     * Returns a field’s corresponding content column name.
     *
     * @param FieldInterface $field
     * @return string
     */
    private function _getFieldContentColumnName(FieldInterface $field): string
    {
        /** @var Field $field */
        return ($field->columnPrefix ?: 'field_') . $field->handle;
    }

    /**
     * Converts found rows into element instances
     *
     * @param array $rows
     * @return array|Element[]
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
                $element = $this->_createElement($row);

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
     * Converts a found row into an element instance.
     *
     * @param array $row
     * @return ElementInterface
     */
    private function _createElement(array $row)
    {
        // Do we have a placeholder for this element?
        if (($element = Craft::$app->getElements()->getPlaceholderElement($row['id'], $this->siteId)) !== null) {
            return $element;
        }

        /** @var Element $class */
        $class = $this->elementType;

        // Instantiate the element
        $row['siteId'] = $this->siteId;

        if ($this->structureId) {
            $row['structureId'] = $this->structureId;
        }

        if ($class::hasContent() && $this->contentTable !== null) {
            // Separate the content values from the main element attributes
            $fieldValues = [];

            if (!empty($this->customFields)) {
                foreach ($this->customFields as $field) {
                    /** @var Field $field */
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

        /** @var Element $element */
        $element = new $class($row);

        // Set the custom field values
        /** @noinspection UnSafeIsSetOverArrayInspection - FP */
        if (isset($fieldValues)) {
            $element->setFieldValues($fieldValues);
        }

        // Fire an 'afterPopulateElement' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_POPULATE_ELEMENT)) {
            $this->trigger(self::EVENT_AFTER_POPULATE_ELEMENT, new PopulateElementEvent([
                'element' => $element,
                'row' => $row
            ]));
        }

        return $element;
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
