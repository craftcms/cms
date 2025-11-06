<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns;

use craft\helpers\Db as DbHelper;
use CraftCms\Cms\Database\Expressions\FixedOrderExpression;
use CraftCms\Cms\Database\Expressions\OrderByPlaceholderExpression;
use CraftCms\Cms\Database\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Support\Arr;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use ReflectionException;
use Tpetry\QueryExpressions\Function\Conditional\Coalesce;
use Tpetry\QueryExpressions\Language\Alias;

/**
 * Temporary placeholder trait for all other functionality
 */
trait ElementQueryTrait
{
    // Result formatting attributes
    // -------------------------------------------------------------------------

    /**
     * @var bool Whether the results should be queried in reverse.
     * @used-by inReverse()
     */
    protected bool $inReverse = false;

    /**
     * @var bool Whether to return each element as an array. If false (default), an object
     * of [[elementType]] will be created to represent each element.
     * @used-by asArray()
     */
    public bool $asArray = false;

    /**
     * @var \craft\base\FieldInterface[]|null The fields that may be involved in this query.
     */
    public ?array $customFields = null;

    /**
     * @var array|null The generated field handles that may be involved in this query.
     */
    public ?array $generatedFields = null;

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
     */
    public mixed $siteSettingsId = null;

    /**
     * @var bool|null Whether to return trashed (soft-deleted) elements.
     * If this is set to `null`, then both trashed and non-trashed elements will be returned.
     * @used-by trashed()
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

    /**
     * @var bool Whether results should be returned in the order specified by [[id]].
     * @used-by fixedOrder()
     */
    public bool $fixedOrder = false;

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
     * @var string[]|null
     * @see getCacheTags()
     */
    private array|null $cacheTags = null;

    /**
     * @var array<string,array<string|\Illuminate\Contracts\Database\Query\Expression>> Column alias => name mapping
     * @see prepare()
     * @see joinElementTable()
     * @see applyOrderByParams()
     * @see applySelectParam()
     */
    private array $columnMap;

    /**
     * @var bool Whether an element table has been joined for the query
     * @see prepare()
     * @see joinElementTable()
     */
    private bool $joinedElementTable = false;

    /**
     * @var array<string,int>|null
     * @see applySearchParam()
     * @see applyOrderByParams()
     * @see populate()
     */
    private ?array $searchResults = null;

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
     * @uses $fixedOrder
     */
    public function fixedOrder(bool $value = true): static
    {
        $this->fixedOrder = $value;

        return $this;
    }

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


    // Internal Methods
    // -------------------------------------------------------------------------

    protected function elementQueryBeforeQuery(Builder $query): void
    {
        // Is the query already doomed?
        if (isset($this->id) && empty($this->id)) {
            throw new QueryAbortedException();
        }

        // Clear out the previous cache tags
        $this->cacheTags = null;

        // Give other classes a chance to make changes up front
        /*if (!$this->beforePrepare()) {
            throw new QueryAbortedException();
        }*/

        $this->subQuery
            ->addSelect([
                'elements.id as elementsId',
                'elements_sites.id as siteSettingsId',
            ])
            ->join(new Alias(Table::ELEMENTS_SITES, 'elements_sites'), 'elements_sites.elementId', 'elements.id');
        // @TODO: Params?
        // ->addParams($this->params);

        if ($this->id) {
            foreach (DbHelper::parseNumericParam('elements.id', $this->id) as $column => $values) {
                $this->subQuery->whereIn($column, Arr::wrap($values));
            }
        }

        if ($this->uid) {
            foreach (DbHelper::parseParam('elements.uid', $this->uid) as $column => $values) {
                $this->subQuery->whereIn($column, Arr::wrap($values));
            }
        }

        if ($this->siteSettingsId) {
            foreach (DbHelper::parseNumericParam('elements_sites.id', $this->siteSettingsId) as $column => $values) {
                $this->subQuery->whereIn($column, Arr::wrap($values));
            }
        }

        match($this->trashed) {
            true => $this->subQuery->whereNotNull('elements.dateDeleted'),
            false => $this->subQuery->whereNull('elements.dateDeleted'),
            default => null,
        };

        if ($this->dateCreated) {
            $parsed = DbHelper::parseDateParam('elements.dateCreated', $this->dateCreated);

            $operator = $parsed[0];
            $column = $parsed[1];
            $value = $parsed[2] ?? null;

            if (is_null($value)) {
                $value = $column;
                $column = $operator;
                $operator = '=';
            }

            $this->subQuery->where($column, $operator, $value);
        }

        if ($this->dateUpdated) {
            $this->subQuery->where(DbHelper::parseDateParam('elements.dateUpdated', $this->dateUpdated));
        }

        if (isset($this->title) && $this->title !== '' && $this->elementType::hasTitles()) {
            if (is_string($this->title)) {
                $this->title = DbHelper::escapeCommas($this->title);
            }

            $this->subQuery->where(DbHelper::parseParam('elements_sites.title', $this->title, '=', true));
        }

        if ($this->slug) {
            $this->subQuery->where(DbHelper::parseParam('elements_sites.slug', $this->slug));
        }

        if ($this->uri) {
            $this->subQuery->where(DbHelper::parseParam('elements_sites.uri', $this->uri, '=', true));
        }

        if ($this->inBulkOp) {
            $this->subQuery
                ->join(new Alias(Table::ELEMENTS_BULKOPS, 'elements_bulkops'), 'elements_bulkops.elementId', 'elements.id')
                ->where('elements_bulkops.key', $this->inBulkOp);
        }

        $this->applySearchParam($query);
        $this->applyOrderByParams($query);
        $this->applySelectParams($query);

        // If an element table was never joined in, explicitly filter based on the element type
        if (! $this->joinedElementTable && $this->elementType !== Element::class) {
            try {
                $ref = new ReflectionClass($this->elementType);
            } catch (ReflectionException) {
                $ref = null;
            }

            if ($ref && !$ref->isAbstract()) {
                $this->subQuery->where('elements.type', $this->elementType);
            }
        }

        /** @var \Illuminate\Database\Query\JoinClause $join */
        foreach ($this->query->joins ?? [] as $join) {
            $this->subQuery->joins[] = $join;
        }

        $query
            ->fromSub($this->subQuery, 'subquery')
            ->join(new Alias(Table::ELEMENTS_SITES, 'elements_sites'), 'elements_sites.id', 'subquery.siteSettingsId')
            ->join(new Alias(Table::ELEMENTS, 'elements'), 'elements.id', 'subquery.elementsId');
    }

    protected function elementQueryAfterQuery(Collection $collection): void
    {
        $elementsService = \Craft::$app->getElements();

        if ($elementsService->getIsCollectingCacheInfo()) {
            $elementsService->collectCacheTags($this->getCacheTags());
        }
    }

    /**
     * @return string[]
     */
    public function getCacheTags(): array
    {
        if (! is_null($this->cacheTags)) {
            return $this->cacheTags;
        }

        $modelClass = $this->elementType;

        $this->cacheTags = [
            'element',
            "element::{$modelClass}",
        ];

        // If (<= 100) specific IDs were requested, then use those
        if (is_numeric($this->id) ||
            (is_array($this->id) && count($this->id) <= 100 && Arr::isNumeric($this->id))
        ) {
            array_push($this->cacheTags, ...array_map(fn($id) => "element::$id", Arr::wrap($this->id)));

            return $this->cacheTags;
        }

        $queryTags = $this->cacheTags();

        // Fire a 'defineCacheTags' event
        /*if ($this->hasEventHandlers(self::EVENT_DEFINE_CACHE_TAGS)) {
            $event = new DefineValueEvent(['value' => $queryTags]);
            $this->trigger(self::EVENT_DEFINE_CACHE_TAGS, $event);
            $queryTags = $event->value;
        }*/

        if (! empty($queryTags)) {
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
            if (! str_starts_with($tag, 'element::')) {
                $tag = sprintf('element::%s::%s', $this->elementType, $tag);
            }

            $this->cacheTags[] = $tag;
        }

        return $this->cacheTags;
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
     */
    protected function cacheTags(): array
    {
        return [];
    }

    /**
     * Joins in a table with an `id` column that has a foreign key pointing to `elements.id`.
     *
     * The table will be joined with an alias based on the unprefixed table name. For example,
     * if `{{%entries}}` is passed, the table will be aliased to `entries`.
     *
     * @param string $table The table name, e.g. `entries` or `{{%entries}}`
     */
    protected function joinElementTable(string $table, ?string $alias = null): void
    {
        $this->query->join(new Alias($table, $alias ?? $table), "$alias.id", 'subquery.elementsId');
        $this->subQuery->join(new Alias($table, $alias ?? $table), "$alias.id", 'elements.id');
        $this->joinedElementTable = true;

        // Add element table cols to the column map
        foreach (Schema::getColumnListing($table) as $column) {
            $name = $column['name'];

            if (! isset($this->columnMap[$name])) {
                $this->columnMap[$name] = "$alias.$name";
            }
        }
    }

    /**
     * Applies the 'search' param to the query being prepared.
     *
     * @throws QueryAbortedException
     */
    private function applySearchParam(Builder $query): void
    {
        $this->searchResults = null;

        if (! $this->search) {
            return;
        }

        $searchService = \Craft::$app->getSearch();

        $scoreOrder = Arr::first($query->orders, fn($order) => $order['column'] === 'score');

        if ($scoreOrder || $searchService->shouldCallSearchElements($this)) {
            // Get the scored results up front
            $searchResults = $searchService->searchElements($this);

            if ($scoreOrder['direction'] === 'asc') {
                $searchResults = array_reverse($searchResults, true);
            }

            if (($this->orders[0]['column'] ?? null) === 'score') {
                // Only use the portion we're actually querying for
                if (is_int($this->offset) && $this->offset !== 0) {
                    $searchResults = array_slice($searchResults, $this->offset, null, true);
                    $this->subQuery->offset = null;
                }
                if (is_int($this->limit) && $this->limit !== 0) {
                    $searchResults = array_slice($searchResults, 0, $this->limit, true);
                    $this->subQuery->limit = null;
                }
            }

            if (empty($searchResults)) {
                throw new QueryAbortedException();
            }

            $this->searchResults = $searchResults;

            $elementIdsBySiteId = [];
            foreach (array_keys($searchResults) as $key) {
                [$elementId, $siteId] = explode('-', $key, 2);
                $elementIdsBySiteId[$siteId][] = $elementId;
            }

            $this->subQuery->where(function (Builder $query) use ($elementIdsBySiteId) {
                foreach ($elementIdsBySiteId as $siteId => $elementIds) {
                    $query->orWhere(function (Builder $query) use ($siteId, $elementIds) {
                        $query->where('elements_sites.siteId', $siteId)
                            ->whereIn('elements.id', $elementIds);
                    });
                }
            });

            return;
        }

        // Just filter the main query by the search query
        $searchQuery = $searchService->createDbQuery($this->search, $this);

        if ($searchQuery === false) {
            throw new QueryAbortedException();
        }

        $this->subQuery->whereIn('elements.id', $searchQuery->select('elementId'));
    }

    private function applyOrderByParams(Builder $query): void
    {
        $orders = $query->orders;

        // Only set to the default order if `orderBy` is null
        if (is_null($orders)) {
            if ($this->fixedOrder) {
                if (empty($this->id)) {
                    throw new QueryAbortedException();
                }

                $ids = $this->id;
                if (!is_array($ids)) {
                    $ids = is_string($ids) ? str($ids)->explode(',')->all() : [$ids];
                }

                $query->orderBy(new FixedOrderExpression('elements.id', $ids));
            } elseif ($this->revisions) {
                $query->orderByDesc('num');
            } elseif ($this->shouldJoinStructureData()) {
                $query->orderBy('structureelements.lft');

                foreach ($this->defaultOrderBy as $column => $direction) {
                    $query->orderBy($column, $direction === SORT_ASC ? 'asc' : 'desc');
                }
            } elseif (!empty($this->defaultOrderBy)) {
                foreach ($this->defaultOrderBy as $column => $direction) {
                    $query->orderBy($column, $direction === SORT_ASC ? 'asc' : 'desc');
                }
            } else {
                return;
            }
        } else {
            $orders = array_filter($orders, fn($order) => $order['column'] !== '');
            foreach ($orders as $order) {
                $query->orderBy($order['column'], $order['direction']);
            }
        }


        if ($this->inReverse) {
            $orders = $query->orders;

            $query->reorder();

            foreach (array_reverse($orders) as $order) {
                $query->orderBy($order['column'], $order['direction'] === 'asc' ? 'desc' : 'asc');
            }
        }
    }

    /**
     * Applies the 'select' param to the query being executed.
     */
    private function applySelectParams(Builder $query): void
    {
        // Select all columns defined by [[select]], swapping out any mapped column names
        $select = [];
        $includeDefaults = false;

        foreach ((array)$this->columns as $column) {
            [$column, $alias] = explode(' as ', $column, 2) + [1 => null];

            $alias ??= $column;

            if ($column === '**') {
                $includeDefaults = true;
            } else {
                // Is this a mapped column name?
                if (is_string($column) && isset($this->columnMap[$column])) {
                    $column = $this->resolveColumnMapping($column);

                    // Completely ditch the mapped name if instantiated elements are going to be returned
                    if (! $this->asArray && is_string($column)) {
                        $alias = $column;
                    }
                }

                if (is_array($column)) {
                    $select[] = new Alias(new Coalesce($column), $alias);
                } else {
                    $select[] = new Alias($column, $alias);
                }
            }
        }

        // Is there still a ** placeholder param?
        if (! $includeDefaults) {
            $query->columns = $select;

            return;
        }

        // Merge in the default columns
        $select = array_merge($select, [
            'elements.id',
            'elements.canonicalId',
            'elements.fieldLayoutId',
            'elements.uid',
            'elements.enabled',
            'elements.archived',
            'elements.dateLastMerged',
            'elements.dateCreated',
            'elements.dateUpdated',
            new Alias('elements_sites.id', 'siteSettingsId'),
            'elements_sites.siteId',
            'elements_sites.title',
            'elements_sites.slug',
            'elements_sites.uri',
            'elements_sites.content',
            new Alias('elements_sites.enabled', 'enabledForSite'),
        ]);

        // If the query includes soft-deleted elements, include the date deleted
        if ($this->trashed !== false) {
            $select[] = 'elements.dateDeleted';
        }

        $query->columns = $select;
    }

    private function resolveColumnMapping(string $key): string|array
    {
        if (!isset($this->columnMap[$key])) {
            throw new InvalidArgumentException("Invalid column map key: $key");
        }

        // make sure it's not still a callback
        if (is_callable($this->columnMap[$key])) {
            $this->columnMap[$key] = $this->columnMap[$key]();
        } elseif (is_array($this->columnMap[$key])) {
            foreach ($this->columnMap[$key] as $i => $mapping) {
                if (is_callable($mapping)) {
                    $this->columnMap[$key][$i] = $mapping();
                }
            }
        }

        return $this->columnMap[$key];
    }
}
