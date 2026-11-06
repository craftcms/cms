<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries;

use Closure;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Enums\ElementQueryParamType;
use CraftCms\Cms\Element\Queries\Attributes\ElementQueryParam;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\Exceptions\ElementNotFoundException;
use CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Template;
use CraftCms\Cms\Support\Utils;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use CraftCms\DependencyAwareCache\Facades\DependencyCache;
use DateTimeInterface;
use Exception;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Concerns\BuildsQueries;
use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Reflector;
use Illuminate\Support\Traits\ForwardsCalls;
use InvalidArgumentException;
use Override;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use Tpetry\QueryExpressions\Function\Conditional\Coalesce;
use Tpetry\QueryExpressions\Language\Alias;

/**
 * @template TElement of ElementInterface
 *
 * @implements ElementQueryInterface<TElement>
 *
 * @mixin Builder
 *
 * @method static select($columns = ['*'])
 * @method static addSelect($column)
 * @method static join($table, $first, $operator = null, $second = null, $type = 'inner', $where = false)
 * @method static leftJoin($table, $first, $operator = null, $second = null)
 * @method static orderBy($column, $direction = 'asc')
 * @method static orderByDesc($column)
 * @method static reorder($column = null, $direction = 'asc')
 * @method static rightJoin($table, $first, $operator = null, $second = null)
 * @method static whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static whereNotIn($column, $values, $boolean = 'and')
 * @method static whereNotNull($columns, $boolean = 'and')
 * @method static whereNested(Closure $callback, string $boolean = 'and')
 * @method static whereNotExists($callback, $boolean = 'and')
 * @method static whereNull($columns, $boolean = 'and', $not = false)
 *
 * @since 6.0.0
 */
class ElementQuery extends Component implements \Illuminate\Contracts\Database\Query\Builder, ElementQueryInterface
{
    /** @use BuildsQueries<TElement> */
    use BuildsQueries {
        BuildsQueries::sole as baseSole;
        BuildsQueries::first as baseFirst;
    }

    use Concerns\CachesQueries;
    use Concerns\CollectsCacheTags;
    use Concerns\FormatsResults;

    /** @use Concerns\HydratesElements<TElement> */
    use Concerns\HydratesElements;

    /** @use Concerns\OverridesResults<TElement> */
    use Concerns\OverridesResults;

    use Concerns\QueriesCustomFields;
    use Concerns\QueriesDraftsAndRevisions;
    use Concerns\QueriesEagerly;
    use Concerns\QueriesFields;
    use Concerns\QueriesPlaceholderElements;
    use Concerns\QueriesRelatedElements;
    use Concerns\QueriesSites;
    use Concerns\QueriesStatuses;
    use Concerns\QueriesStructures;
    use Concerns\QueriesUniqueElements;
    use Concerns\SearchesElements;
    use ForwardsCalls;

    protected string $table = Table::ELEMENTS;

    /**
     * The base query builder instance.
     */
    protected Builder $query;

    /**
     * All of the locally registered builder macros.
     */
    /** @var array<string, Closure> */
    protected array $localMacros = [];

    /**
     * The properties that should be returned from query builder.
     *
     * @var string[]
     *
     * @see \Illuminate\Database\Eloquent\Builder::$propertyPassthru for inspiration.
     */
    protected array $propertyPassthru = [
        'from',
        'orders',
        'limit',
        'offset',
    ];

    /**
     * The methods that should be returned from query builder.
     *
     * @var string[]
     *
     * @see \Illuminate\Database\Eloquent\Builder::$passthru for inspiration.
     */
    protected array $passthru = [
        'aggregate',
        'average',
        'avg',
        'dd',
        'ddrawsql',
        'doesntexist',
        'doesntexistor',
        'dump',
        'dumprawsql',
        'exists',
        'existsor',
        'explain',
        'getbindings',
        'getconnection',
        'getgrammar',
        'getrawbindings',
        'insert',
        'insertgetid',
        'insertorignore',
        'insertusing',
        'insertorignoreusing',
        'max',
        'min',
        'numericaggregate',
        'pluck',
        'raw',
        'rawvalue',
        'sum',
        'tosql',
        'torawsql',
    ];

    /**
     * The callbacks that should be invoked before retrieving data from the database.
     */
    /** @var array<int, callable(self<TElement>): void> */
    protected array $beforeQueryCallbacks = [];

    /**
     * The callbacks that should be invoked after retrieving data from the database.
     */
    /** @var array<int, callable(mixed, self<TElement>): mixed> */
    protected array $afterQueryCallbacks = [];

    /**
     * The callbacks that should be invoked on clone.
     */
    /** @var array<int, callable(self<TElement>): void> */
    protected array $onCloneCallbacks = [];

    // Use ** as a placeholder for "all the default columns"
    /** @var array<int, string> */
    protected array $columns = ['**'];

    // For internal use
    // -------------------------------------------------------------------------

    /**
     * @var array<string,string|callable|array<array-key, mixed>> Column alias => name mapping
     *
     * @see applyOrderByParams()
     * @see applySelectParam()
     */
    private array $columnMap;

    /**
     * @var bool Whether the query is sourcing rows from a concrete element table
     *
     * @see configureSourceTable()
     */
    private bool $hasElementSourceTable = false;

    /**
     * @var bool Whether the element query before query callback has been called
     *
     * @see elementQueryBeforeQuery()
     */
    private bool $elementQueryBeforeQueryCalled = false;

    private ?Builder $queryBeforePrepare = null;

    /**
     * @var bool Whether this is the copy of a query that one of its execution methods prepares and runs,
     *           so the query itself stays unprepared and params set on it later still apply
     *
     * @see executeOnCopy()
     */
    private bool $isExecutionCopy = false;

    /**
     * @var bool Whether this query only provides the conditions for another query’s nested where clause,
     *           so it leaves out the default site and archived conditions
     *
     * @see whereNestedElementQuery()
     */
    private bool $isNestedCondition = false;

    /**
     * @var bool Whether columns were added to the default select with addSelect() or its variants, so the
     *           columns passed to get() don't apply, as in Laravel
     */
    private bool $addsSelect = false;

    /**
     * @var bool|null Whether the prepared query selects the default columns, rather than only the ones passed to select(),
     *                or null until the select is resolved
     *
     * @see applySelectParams()
     */
    private ?bool $selectsDefaultColumns = null;

    /**
     * @var array<int, Expression> The expressions the prepared query selects, such as subqueries, which
     *                             may carry bindings and can be plucked by their alias
     *
     * @see applySelectParams()
     * @see pluck()
     */
    private array $selectedExpressions = [];

    /** @var array<string,string|callable|array<array-key, mixed>>|null */
    private ?array $columnMapBeforePrepare = null;

    /** @var array<int, callable(self<TElement>): void>|null */
    private ?array $beforeQueryCallbacksBeforePrepare = null;

    /**
     * @var array<string,array<int,string>>
     */
    private static array $columnListings = [];

    /**
     * The current element query instance being prepared, for reference by fields’ `queryCondition()` methods.
     */
    /** @var self<*>|null */
    public static ?self $activeQuery = null;

    /**
     * Create a new Element query instance.
     *
     * @param  class-string<TElement>  $elementType
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        /** @var class-string<TElement> */
        public string $elementType = Element::class,
        protected array $config = [],
    ) {
        parent::__construct();

        $this->query = DB::table($this->table)->select('**');

        if ($this->table !== Table::ELEMENTS) {
            $this->when(
                DB::isMysql(),
                fn (self $query) => $this->straightJoin(Table::ELEMENTS.' as elements', 'elements.id', '=', "{$this->table}.id"),
                fn (self $query) => $this->join(Table::ELEMENTS.' as elements', 'elements.id', '=', "{$this->table}.id"),
            );

            $this->hasElementSourceTable = true;
        }

        $this->query->join(Table::ELEMENTS_SITES.' as elements_sites', 'elements_sites.elementId', '=', 'elements.id');

        // Prepare a new column mapping
        // (for use in SELECT and ORDER BY clauses)
        $this->columnMap = [
            'dateCreated' => 'elements.dateCreated',
            'dateUpdated' => 'elements.dateUpdated',
            'enabled' => 'elements.enabled',
            'id' => 'elements.id',
            'slug' => 'elements_sites.slug',
            'uid' => 'elements.uid',
            'siteId' => 'elements_sites.siteId',
        ];

        if ($this->elementType::hasTitles()) {
            $this->columnMap['title'] = 'elements_sites.title';
        }

        if ($this->hasElementSourceTable) {
            $columnListing = self::$columnListings[$this->table] ??= DB::getSchemaBuilder()->getColumnListing($this->table);

            foreach ($columnListing as $column) {
                $this->columnMap[$column] ??= "$this->table.$column";
            }
        }

        // The other columns the query selects by default, which several joined tables can share
        $this->columnMap += [
            'archived' => 'elements.archived',
            'canonicalId' => 'elements.canonicalId',
            'dateDeleted' => 'elements.dateDeleted',
            'dateLastMerged' => 'elements.dateLastMerged',
            'draftId' => 'elements.draftId',
            'enabledForSite' => 'elements_sites.enabled',
            'fieldLayoutId' => 'elements.fieldLayoutId',
            'revisionId' => 'elements.revisionId',
            'siteSettingsId' => 'elements_sites.id',
            'uri' => 'elements_sites.uri',
        ];

        $this->initTraits();

        self::configure($this, $config);
    }

    protected function initTraits(): void
    {
        $class = static::class;

        $uses = class_uses_recursive($class);

        $conventionalInitMethods = array_map(static fn ($trait) => 'init'.class_basename($trait), $uses);

        foreach (new ReflectionClass($class)->getMethods() as $method) {
            if (in_array($method->getName(), $conventionalInitMethods)) {
                $this->{$method->getName()}();
            }
        }
    }

    /**
     * Executes the query and renders the resulting elements using their partial templates.
     *
     * If no partial template exists for an element, its string representation will be output instead.
     *
     * @see Template::render()
     */
    /** @param array<string, mixed> $variables */
    #[AllowedInSandbox]
    public function render(array $variables = []): HtmlString
    {
        return Template::render($this->all(), $variables);
    }

    /**
     * Find a model by its primary key.
     *
     * @param  int|string|Arrayable<array-key, mixed>|array<mixed>  $id
     * @param  string|Expression|array<int, string|Expression>  $columns
     * @return ($id is (Arrayable<array-key, mixed>|array<mixed>) ? ElementCollection<int, TElement>|Collection<int, array<string, mixed>> : TElement|array<string, mixed>|null)
     */
    public function find(mixed $id, $columns = ['*']): ElementInterface|ElementCollection|Collection|array|null
    {
        if (is_array($id) || $id instanceof Arrayable) {
            return $this->findMany($id, $columns);
        }

        return $this->id($id)->first($columns);
    }

    /**
     * Find multiple elements by their primary keys.
     *
     * @param  Arrayable<array-key, mixed>|array<array-key, mixed>  $ids
     * @param  array<int, string>  $columns
     * @return ElementCollection<int, TElement>|Collection<int, array<string, mixed>>
     */
    public function findMany(mixed $ids, array|string $columns = ['*']): ElementCollection|Collection
    {
        $ids = $ids instanceof Arrayable ? $ids->toArray() : $ids;

        if (empty($ids)) {
            return new ElementCollection;
        }

        return $this->id($ids)->get($columns);
    }

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param  array<int, string>  $columns
     * @return ($id is (Arrayable<array-key, mixed>|array<mixed>) ? ElementCollection<int, TElement>|Collection<int, array<string, mixed>> : TElement|array<string, mixed>)
     *
     * @throws ElementNotFoundException<TElement>
     */
    public function findOrFail(mixed $id, array|string $columns = ['*']): ElementInterface|ElementCollection|Collection|array
    {
        $result = $this->find($id, $columns);

        $id = $id instanceof Arrayable ? $id->toArray() : $id;

        if (is_array($id)) {
            if (count($result) !== count(array_unique($id))) {
                throw (new ElementNotFoundException)->setElement(
                    $this->elementType, array_diff($id, $result->pluck('id')->all()),
                );
            }

            return $result;
        }

        if (is_null($result)) {
            throw (new ElementNotFoundException)->setElement(
                $this->elementType, $id,
            );
        }

        return $result;
    }

    /**
     * Find a model by its primary key or call a callback.
     *
     * @template TValue
     *
     * @param  (Closure(): TValue)|list<string>|string  $columns
     * @param  (Closure(): TValue)|null  $callback
     * @return (
     *     $id is (\Illuminate\Contracts\Support\Arrayable<array-key, mixed>|array<mixed>)
     *     ? \CraftCms\Cms\Element\ElementCollection<int, TElement>
     *     : TElement|TValue
     * )
     */
    public function findOr(mixed $id, array|string|Closure $columns = ['*'], ?Closure $callback = null): mixed
    {
        if ($columns instanceof Closure) {
            $callback = $columns;

            $columns = ['*'];
        }

        if (! is_null($model = $this->find($id, $columns))) {
            return $model;
        }

        return $callback();
    }

    /**
     * Execute the query and get the first result or throw an exception.
     *
     * @param  array<int, string>  $columns
     * @return TElement|array<string, mixed>
     *
     * @throws ElementNotFoundException<TElement>
     */
    public function firstOrFail(array|string $columns = ['*']): ElementInterface|array
    {
        if (! is_null($model = $this->first($columns))) {
            return $model;
        }

        throw (new ElementNotFoundException)->setElement($this->elementType);
    }

    /**
     * Execute the query and get the first result or call a callback.
     *
     * @template TValue
     *
     * @param  (Closure(): TValue)|list<string>  $columns
     * @param  (Closure(): TValue)|null  $callback
     * @return TElement|TValue
     */
    public function firstOr(array|string|Closure $columns = ['*'], ?Closure $callback = null): mixed
    {
        if ($columns instanceof Closure) {
            $callback = $columns;

            $columns = ['*'];
        }

        if (! is_null($model = $this->first($columns))) {
            return $model;
        }

        return $callback();
    }

    /**
     * Execute the query and get the first result if it's the sole matching record.
     *
     * @param  array<int, string>  $columns
     * @return TElement|array<string, mixed>
     *
     * @throws ElementNotFoundException<TElement>
     * @throws MultipleRecordsFoundException
     */
    public function sole(array|string $columns = ['*']): ElementInterface|array
    {
        try {
            return $this->baseSole($columns);
        } catch (RecordsNotFoundException) {
            throw (new ElementNotFoundException)->setElement($this->elementType);
        }
    }

    /**
     * Get a single attribute’s value from the first result of the query.
     *
     * Like Eloquent, the value is read from the hydrated element, so custom fields return their normalized values.
     * Use `pluck()` for raw column values.
     */
    public function value(string|Expression $column): mixed
    {
        $element = $this->first();

        return $element === null ? null : $this->elementValue($element, $column);
    }

    /**
     * Get a single attribute’s value from the first result of the query if it’s the sole matching element.
     *
     * @throws ElementNotFoundException<TElement>
     * @throws MultipleRecordsFoundException
     */
    public function soleValue(string|Expression $column): mixed
    {
        return $this->elementValue($this->sole(), $column);
    }

    /**
     * Get a single attribute’s value from the first result of the query or throw an exception.
     *
     * @throws ElementNotFoundException<TElement>
     */
    public function valueOrFail(string|Expression $column): mixed
    {
        return $this->elementValue($this->firstOrFail(), $column);
    }

    /**
     * Reads an attribute from a result.
     *
     * @param  TElement|array<string, mixed>  $element
     */
    private function elementValue(ElementInterface|array $element, string|Expression $column): mixed
    {
        return data_get($element, $this->attributeName($column));
    }

    /**
     * Returns the element attribute a column name refers to, dropping a table prefix such as `elements.`.
     */
    private function attributeName(string|Expression $column): string
    {
        $column = $column instanceof Expression ? (string) $column->getValue($this->query->getGrammar()) : $column;

        return $this->isCustomFieldHandle(explode('.', $column, 2)[0]) ? $column : Str::afterLast($column, '.');
    }

    /**
     * @param  array<int, string>  $columns
     * @return TElement|array<string, mixed>|null
     */
    public function first($columns = ['*']): ElementInterface|array|null
    {
        if (! $this->isExecutionCopy) {
            return $this->executeOnCopy(fn (self $query) => $query->first($columns));
        }

        // Eagerly?
        $eagerResult = $this->eagerLoad(
            criteria: ['limit' => 1],
            columns: $columns,
        );

        if ($eagerResult !== null) {
            return $this->applyAfterQueryCallbacks($eagerResult)->first();
        }

        return $this->baseFirst($columns);
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  string|Expression|array<int, string|Expression>  $columns
     * @return ElementCollection<array-key, TElement>|Collection<array-key, array<string, mixed>>
     */
    public function get($columns = ['*']): ElementCollection|Collection
    {
        if (! $this->isExecutionCopy) {
            return $this->executeOnCopy(fn (self $query) => $query->get($columns));
        }

        $models = $this->getModels($columns);

        $results = $this->asArray
            ? new Collection($models)
            : new ElementCollection($models);

        return $this->applyAfterQueryCallbacks($results)
            ->when($this->indexBy, fn (ElementCollection|Collection $collection) => $collection->keyBy($this->indexBy));
    }

    /**
     * Execute the query as a "select" statement and return a collection.
     *
     * @return ElementCollection<array-key, TElement>|Collection<array-key, array<string, mixed>>
     */
    public function collect(): ElementCollection|Collection
    {
        return $this->get();
    }

    /**
     * Get the hydrated elements
     *
     * @param  array<int, string>  $columns
     * @return array<int, TElement|array<string, mixed>>
     */
    public function getModels(array|string $columns = ['*']): array
    {
        if (! $this->isExecutionCopy) {
            return $this->executeOnCopy(fn (self $query) => $query->getModels($columns));
        }

        if (! is_null($result = $this->getResultOverride())) {
            if ($this->with) {
                Elements::eagerLoadElements($this->elementType, $result, $this->with);
            }

            return $result;
        }

        if (($eagerResult = $this->eagerLoad(columns: $columns)) !== null) {
            return $eagerResult->all();
        }

        // Select the given columns unless the query has its own select, as Eloquent does
        if ($columns !== ['*'] && $columns !== '*' && ! $this->addsSelect && in_array('**', $this->query->columns ?? [], true)) {
            $this->query->select($columns);
        }

        try {
            $this->applyBeforeQueryCallbacks();
        } catch (QueryAbortedException) {
            return [];
        }

        if ((int) $this->queryCacheDuration >= 0) {
            $result = DependencyCache::remember(
                key: $this->queryCacheKey($this, 'all', $columns),
                ttl: $this->queryCacheDuration,
                callback: fn () => $this->query->get($columns)->all(),
                dependency: $this->getCacheDependency(),
            );
        } else {
            $result = $this->query->get($columns)->all();
        }

        return $this->hydrate($result);
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array<int, string>|string  $columns
     * @return array<int, TElement|array<string, mixed>>
     */
    public function all($columns = ['*']): array
    {
        return collect($this->get($columns))->all();
    }

    /**
     * @param  array<int, string>  $columns
     * @return TElement|array<string, mixed>|null
     */
    public function one($columns = ['*']): ElementInterface|array|null
    {
        return $this->first($columns);
    }

    /**
     * Get a collection of a column’s raw values, optionally keyed by another column’s.
     *
     * Columns can be custom field handles and other mapped names, such as `id` or `dateCreated`.
     *
     * @return Collection<array-key, mixed>
     */
    public function pluck(string|Expression $column, string|Expression|null $key = null): Collection
    {
        if (! $this->isExecutionCopy) {
            return $this->executeOnCopy(fn (self $query) => $query->pluck($column, $key));
        }

        if (! is_null($result = $this->getResultOverride())) {
            return collect($result)->pluck($this->attributeName($column), $key === null ? null : $this->attributeName($key));
        }

        try {
            $this->applyBeforeQueryCallbacks();
        } catch (QueryAbortedException) {
            return new Collection;
        }

        // Select only the plucked columns and any selected expressions, unless the query selects its own columns, which they may refer to by alias
        $query = clone $this->query;
        $query->columns = $this->selectsDefaultColumns ? $this->selectedExpressions : $query->columns;
        $valueColumn = $this->pluckColumn($query, $column, 'pluckValue');
        $keyColumn = $key === null ? null : $this->pluckColumn($query, $key, 'pluckKey');

        $pluck = fn () => $query->pluck($valueColumn, $keyColumn);

        if ((int) $this->queryCacheDuration >= 0) {
            return DependencyCache::remember(
                key: $this->queryCacheKey($this, 'pluck', [$column, $key]),
                ttl: $this->queryCacheDuration,
                callback: $pluck,
                dependency: $this->getCacheDependency(),
            );
        }

        return $pluck();
    }

    /**
     * Concatenate a column’s raw values as a string.
     */
    public function implode(string|Expression $column, string $glue = ''): string
    {
        return $this->pluck($column)->implode($glue);
    }

    /**
     * Adds a column to a pluck query’s select under the given alias and returns the name to read it by.
     *
     * A column the query’s own select already includes is read by its name, as in Laravel.
     */
    private function pluckColumn(Builder $query, string|Expression $column, string $alias): string
    {
        if (is_string($column) && (
            (! $this->selectsDefaultColumns && ! $this->hasColumnMapping($column)) ||
            $this->selectsAlias($query, $column)
        )) {
            return $column;
        }

        $query->columns[] = new Alias($this->resolveColumn($column), $alias);

        return $alias;
    }

    /**
     * Returns whether a query selects a column under the given alias, such as one added with `selectSub()`.
     */
    private function selectsAlias(Builder $query, string $alias): bool
    {
        $pattern = sprintf('/\\sas\\s+(%s|%s)$/i', preg_quote($query->getGrammar()->wrap($alias), '/'), preg_quote($alias, '/'));

        return array_any($query->columns ?? [], fn (mixed $column) => preg_match(
            $pattern,
            trim($column instanceof Expression ? (string) $column->getValue($query->getGrammar()) : (string) $column),
        ) === 1);
    }

    /**
     * Resolves a column name through the column map, so custom field handles and names like `id` refer to their columns.
     *
     * A custom field used in several field layouts resolves to the first of its instances’ values that isn’t null.
     */
    private function resolveColumn(string|Expression $column): string|Expression
    {
        if (! is_string($column) || ! $this->hasColumnMapping($column)) {
            return $column;
        }

        $column = $this->resolveColumnMapping($column);

        if (! is_array($column)) {
            return $column;
        }

        return count($column) === 1 ? reset($column) : new Coalesce($column);
    }

    /**
     * Paginate the given query.
     *
     * @param  int|null|Closure  $perPage
     * @param  array<int, string>|string  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @param  Closure|int|null  $total
     * @return LengthAwarePaginator<array-key, TElement|array<string, mixed>>
     *
     * @throws InvalidArgumentException
     */
    public function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null, $total = null): LengthAwarePaginator
    {
        if (! $this->isExecutionCopy) {
            return $this->executeOnCopy(fn (self $query) => $query->paginate($perPage, $columns, $pageName, $page, $total));
        }

        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $total = value($total) ?? $this->getCountForPagination();

        $perPage = value($perPage, $total);

        $results = $total
            ? $this->forPage($page, $perPage)->get($columns)
            : new ElementCollection;

        return $this->paginator($results, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Paginate the given query into a simple paginator.
     *
     * @param  int|null  $perPage
     * @param  array<int, string>|string  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Contracts\Pagination\Paginator<array-key, TElement|array<string, mixed>>
     */
    public function simplePaginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null)
    {
        if (! $this->isExecutionCopy) {
            return $this->executeOnCopy(fn (self $query) => $query->simplePaginate($perPage, $columns, $pageName, $page));
        }

        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        // Next we will set the limit and offset for this query so that when we get the
        // results we get the proper section of results. Then, we'll create the full
        // paginator instances for these results with the given page and per page.
        $this->offset(($page - 1) * $perPage)->limit($perPage + 1);

        return $this->simplePaginator($this->get($columns), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Paginate the given query into a cursor paginator.
     *
     * The query must be ordered by columns, not by expressions such as a custom field or `fixedOrder`.
     *
     * @param  int  $perPage
     * @param  array<int, string>|string  $columns
     * @param  string  $cursorName
     * @param  Cursor|string|null  $cursor
     * @return CursorPaginator<int, TElement|array<string, mixed>>
     */
    public function cursorPaginate($perPage = 15, $columns = ['*'], $cursorName = 'cursor', $cursor = null): CursorPaginator
    {
        if (! $this->isExecutionCopy) {
            return $this->executeOnCopy(fn (self $query) => $query->cursorPaginate($perPage, $columns, $cursorName, $cursor));
        }

        // Prepare the query first, so the orders cursor pagination reads are the resolved ones
        try {
            $this->applyBeforeQueryCallbacks();
        } catch (QueryAbortedException) {
            return $this->cursorPaginator(new Collection, $perPage, null, [
                'path' => Paginator::resolveCurrentPath(),
                'cursorName' => $cursorName,
            ]);
        }

        return $this->paginateUsingCursor($perPage, $columns, $cursorName, $cursor);
    }

    /**
     * @param  bool  $shouldReverse
     * @return Collection<int, array{column: string, direction: string}>
     */
    protected function ensureOrderForCursorPagination($shouldReverse = false): Collection
    {
        if ($shouldReverse) {
            $this->query->orders = array_map(fn (array $order) => isset($order['direction'])
                ? [...$order, 'direction' => $order['direction'] === 'asc' ? 'desc' : 'asc']
                : $order, $this->query->orders ?? []);
        }

        return collect($this->query->orders)
            ->filter(fn (array $order) => isset($order['direction']))
            ->map(function (array $order) {
                if (! is_string($order['column'])) {
                    throw new InvalidArgumentException(sprintf(
                        'Cursor pagination needs the query to be ordered by columns, but it’s ordered by `%s`.',
                        $order['column']->getValue($this->query->getGrammar()),
                    ));
                }

                return ['column' => $order['column'], 'direction' => (string) $order['direction']];
            })
            ->values();
    }

    /**
     * The orders are already resolved to the columns they select, so no alias needs to be looked up.
     *
     * @param  mixed  $builder
     */
    protected function getOriginalColumnNameForCursorPagination($builder, string $parameter): string
    {
        return $parameter;
    }

    /**
     * Get the queries this query is unioned with, as Eloquent’s `getUnionBuilders()` does.
     *
     * @return Collection<int, mixed>
     */
    protected function getUnionBuilders(): Collection
    {
        return collect($this->query->unions ?? [])->pluck('query');
    }

    /**
     * @param  Collection<int, TElement|array<string, mixed>>  $items
     * @param  int  $perPage
     * @param  Cursor|null  $cursor
     * @param  array<string, mixed>  $options
     * @return CursorPaginator<int, TElement|array<string, mixed>>
     */
    protected function cursorPaginator($items, $perPage, $cursor, $options): CursorPaginator
    {
        return new ElementCursorPaginator($items, $perPage, $cursor, $options);
    }

    /**
     * Get a base query builder with this query’s params applied, as Eloquent’s `toBase()` does.
     *
     * This query isn’t changed. If a param means the query can’t match anything, the builder matches nothing.
     */
    public function toBase(): Builder
    {
        $query = clone $this;

        try {
            $query->applyBeforeQueryCallbacks();
        } catch (QueryAbortedException) {
            if (in_array('**', $query->query->columns ?? [], true)) {
                $query->applySelectParams();
            }

            $query->query->reorder()->whereRaw('1 = 0');
        }

        return $query->query;
    }

    /**
     * Replaces element queries passed to the base query builder with their base query, so they can be used
     * as subqueries, as Eloquent queries can.
     *
     * @param  array<array-key, mixed>  $parameters
     * @return array<array-key, mixed>
     */
    private function toBaseSubqueries(array $parameters): array
    {
        return array_map(fn (mixed $parameter) => match (true) {
            $parameter instanceof self => $parameter->toBase(),
            is_array($parameter) => array_map(fn (mixed $value) => $value instanceof self ? $value->toBase() : $value, $parameter),
            default => $parameter,
        }, $parameters);
    }

    /** @param array<int, string>|string $columns */
    public function count($columns = '*'): int
    {
        if (! $this->isExecutionCopy) {
            return $this->executeOnCopy(fn (self $query) => $query->count($columns));
        }

        if (! $this->getOffset() && ! $this->getLimit() && ! is_null($result = $this->getResultOverride())) {
            return count($result);
        }

        try {
            $this->applyBeforeQueryCallbacks();
        } catch (QueryAbortedException) {
            return 0;
        }

        $eagerLoadedCount = $this->eagerLoad(count: true, columns: $columns);
        $cacheKeyColumns = $columns;
        $columns = is_array($columns) ? array_map($this->resolveColumn(...), $columns) : $this->resolveColumn($columns);

        if ($eagerLoadedCount !== null) {
            return $this->applyAfterQueryCallbacks($eagerLoadedCount);
        }

        if ((int) $this->queryCacheDuration >= 0) {
            $result = DependencyCache::remember(
                key: $this->queryCacheKey($this, 'count', $cacheKeyColumns),
                ttl: $this->queryCacheDuration,
                callback: fn () => $this->query->count($columns),
                dependency: $this->getCacheDependency(),
            );
        } else {
            $result = $this->query->count($columns);
        }

        return $this->applyAfterQueryCallbacks($result);
    }

    /** @param array<int, string> $columns */
    public function getCountForPagination(array $columns = ['*']): int
    {
        $query = clone $this;
        $query->query = $query->query->cloneWithout([
            'limit',
            'offset',
            'unionLimit',
            'unionOffset',
        ]);

        try {
            $query->applyBeforeQueryCallbacks();
        } catch (QueryAbortedException) {
            return 0;
        }

        if ((int) $query->queryCacheDuration >= 0) {
            return DependencyCache::remember(
                key: $query->queryCacheKey($query, 'getcountforpagination', $columns),
                ttl: $query->queryCacheDuration,
                callback: fn () => $query->query->getCountForPagination($columns),
                dependency: $query->getCacheDependency(),
            );
        }

        return $query->query->getCountForPagination($columns);
    }

    public function exists(): bool
    {
        if (! $this->isExecutionCopy) {
            return $this->executeOnCopy(fn (self $query) => $query->exists());
        }

        try {
            $this->applyBeforeQueryCallbacks();
        } catch (QueryAbortedException) {
            return false;
        }

        if ((int) $this->queryCacheDuration >= 0) {
            return DependencyCache::remember(
                key: $this->queryCacheKey($this, 'exists'),
                ttl: $this->queryCacheDuration,
                callback: fn () => $this->getQuery()->exists(),
                dependency: $this->getCacheDependency(),
            );
        }

        return $this->getQuery()->exists();
    }

    /**
     * @param  array<int, string>  $columns
     * @return TElement|array<string, mixed>|null
     */
    public function nth(int $n, $columns = ['*']): ElementInterface|array|null
    {
        if (! is_null($result = $this->getResultOverride())) {
            return $result[$n] ?? null;
        }

        // Eagerly?
        $eagerResult = $this->eagerLoad(
            criteria: [
                'offset' => ($this->offset ?: 0) + $n,
                'limit' => 1,
            ],
            columns: $columns,
        );

        if ($eagerResult !== null) {
            return $this->applyAfterQueryCallbacks($eagerResult)->first();
        }

        return (clone $this)
            ->offset(($this->offset ?: 0) + $n)
            ->first($columns);
    }

    /**
     * Register a closure to be invoked with the result and executing query after execution.
     */
    /** @return self<TElement> */
    public function afterQuery(Closure $callback): self
    {
        $this->afterQueryCallbacks[] = $callback;

        return $this;
    }

    /**
     * Invoke the "after query" modification callbacks.
     */
    public function applyAfterQueryCallbacks(mixed $result): mixed
    {
        foreach ($this->afterQueryCallbacks as $afterQueryCallback) {
            $result = $afterQueryCallback($result, $this) ?: $result;
        }

        return $result;
    }

    /**
     * Get a lazy collection for the given query.
     *
     * @return LazyCollection<int, TElement|array<string, mixed>>
     */
    public function cursor(): LazyCollection
    {
        if (! $this->isExecutionCopy) {
            return $this->executeOnCopy(fn (self $query) => $query->cursor());
        }

        try {
            $this->applyBeforeQueryCallbacks();
        } catch (QueryAbortedException) {
            return new LazyCollection;
        }

        return $this->query->cursor()->map(function ($record) {
            $model = $this->createElement((array) $record);

            if ($this->with) {
                Elements::eagerLoadElements($this->elementType, [$model], $this->with);
            }

            return $this->applyAfterQueryCallbacks(new ElementCollection([$model]))->first();
        })->reject(fn ($model) => is_null($model));
    }

    /**
     * Get the default key name for `chunkById()` and `lazyById()`, as Eloquent uses the model’s key name.
     *
     * It resolves to `elements.id` in the query and is read back from each element or row as `id`.
     */
    protected function defaultKeyName(): string
    {
        return 'id';
    }

    /**
     * Get the underlying query builder instance.
     */
    public function getQuery(): Builder
    {
        return $this->query;
    }

    /** @return self<TElement> */
    #[ElementQueryParam(ElementQueryParamType::Int, 'Sets the limit for paginated results.')]
    public function limit(?int $value): self
    {
        $this->getQuery()->limit = $value;

        return $this;
    }

    /**
     * Get the "limit" value from the query or null if it's not set.
     */
    public function getLimit(): mixed
    {
        return $this->getQuery()->getLimit();
    }

    /** @return self<TElement> */
    #[ElementQueryParam(ElementQueryParamType::Int, 'Sets the offset for paginated results.')]
    public function offset(?int $value): self
    {
        $this->getQuery()->offset = $value;

        return $this;
    }

    /**
     * @param  string|Expression|array<int|string, int|string|Expression>  $column
     * @param  int|string  $direction
     * @return self<TElement>
     */
    #[ElementQueryParam(ElementQueryParamType::String, 'Sets the field the returned {elements} should be ordered by.')]
    public function orderBy($column, $direction = 'asc'): self
    {
        /**
         * In case orderBy is called with an array of [$column, $direction]
         */
        if (is_array($column)) {
            Deprecator::log(static::class.'::orderBy', static::class.'::orderBy() with an array argument is deprecated in 6.0.0.');

            foreach ($column as $c => $dir) {
                $this->query->orderBy($this->sqlColumn($c), $dir === SORT_ASC ? 'asc' : 'desc');
            }

            return $this;
        }

        if (is_string($column)) {
            if ($this->isDeprecatedOrderByString($column)) {
                Deprecator::log(
                    static::class.'::orderBy(string)',
                    sprintf('Passing `%s` to %s::orderBy() has been deprecated. Pass the direction as the second argument, or call orderBy() once for each column.', $column, static::class),
                );
            }

            foreach ($this->normalizeOrderByString($column, $direction) as [$column, $direction]) {
                $this->forwardCallTo($this->query, 'orderBy', [$this->sqlColumn($column), $direction]);
            }

            return $this;
        }

        $this->forwardCallTo($this->query, 'orderBy', [$column, $direction]);

        return $this;
    }

    /**
     * Order the query by a date column, newest first, as Eloquent orders by the model’s creation date by default.
     *
     * @return self<TElement>
     */
    public function latest(string|Expression|null $column = null): self
    {
        return $this->orderBy($column ?? 'dateCreated', 'desc');
    }

    /**
     * Order the query by a date column, oldest first, as Eloquent orders by the model’s creation date by default.
     *
     * @return self<TElement>
     */
    public function oldest(string|Expression|null $column = null): self
    {
        return $this->orderBy($column ?? 'dateCreated');
    }

    private function isDeprecatedOrderByString(string $columns): bool
    {
        return count($this->splitOrderByString($columns)) > 1 || preg_match('/\s+(asc|desc)(?:\s*(?:,|$))/i', $columns) === 1;
    }

    /**
     * Splits an order string on commas that aren't inside parentheses, so `FIELD(id, 3, 1)` stays one column.
     *
     * @return list<string>
     */
    private function splitOrderByString(string $columns): array
    {
        return preg_split('/(\((?:[^()]++|(?1))*\))(*SKIP)(*FAIL)|,/', $columns) ?: [$columns];
    }

    /**
     * Craft 5 left column names containing `(` unquoted, so templates could order by `RAND()`.
     * Strings are quoted as column names now, so they can't inject SQL. `rand()` and `random()`
     * are mapped to the driver's random function; other expressions must be passed as expressions.
     */
    private function sqlColumn(mixed $column): mixed
    {
        if (! is_string($column) || ! str_contains($column, '(')) {
            return $column;
        }

        if (in_array(strtolower(trim($column)), ['rand()', 'random()'], true)) {
            return DB::raw(DB::isMysql() ? 'RAND()' : 'RANDOM()');
        }

        throw new InvalidArgumentException(sprintf(
            'Element queries quote `%s` as a column name. Pass SQL expressions with `expression()` in Twig or `DB::raw()` in PHP.',
            $column,
        ));
    }

    /** @return array<int, array{string, 'asc'|'desc'}> */
    private function normalizeOrderByString(string $columns, mixed $defaultDirection): array
    {
        $defaultDirection = match ($defaultDirection) {
            SORT_DESC, 'desc' => 'desc',
            default => 'asc',
        };

        return collect($this->splitOrderByString($columns))
            ->map(fn (string $column) => trim($column))
            ->filter()
            ->map(function (string $column) use ($defaultDirection) {
                $direction = $defaultDirection;

                if (preg_match('/^(.+?)\s+(asc|desc)$/i', $column, $matches)) {
                    $column = $matches[1];
                    $direction = strtolower($matches[2]);
                }

                return [trim($column), $direction];
            })
            ->values()
            ->all();
    }

    /**
     * Get the "offset" value from the query or null if it's not set.
     */
    public function getOffset(): mixed
    {
        return $this->getQuery()->getOffset();
    }

    /** @return array<string, mixed>|null */
    public function getWhereForColumn(string $column): ?array
    {
        return collect($this->query->wheres)->firstWhere('column', $column);
    }

    /**
     * Returns an array of the current criteria attribute values.
     */
    /** @return array<string, mixed> */
    public function getCriteria(): array
    {
        return collect($this->criteriaAttributes())
            ->mapWithKeys(fn (string $name) => [$name => $this->{$name}])
            ->all();
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
        foreach (Utils::getPublicProperties($this, fn (ReflectionProperty $property) => ! in_array($property->getName(), ['elementType', 'query', 'subQuery', 'customFields', 'asArray', 'with', 'eagerly'], true)) as $name => $value) {
            $names[] = $name;
        }

        foreach ($this->customFieldValues as $name => $value) {
            $names[] = $name;
        }

        return $names;
    }

    /**
     * Get the given macro by name.
     */
    public function getMacro(string $name): Closure
    {
        return Arr::get($this->localMacros, $name);
    }

    /**
     * Checks if a macro is registered.
     */
    public function hasLocalMacro(string $name): bool
    {
        return isset($this->localMacros[$name]);
    }

    /**
     * Get the given global macro by name, as registered on this class or the closest parent class.
     */
    public static function getGlobalMacro(string $name): object|callable|null
    {
        return static::findMacro($name);
    }

    /**
     * Checks if a global macro is registered on this class or a parent class.
     *
     * @param  string  $name
     */
    public static function hasGlobalMacro($name): bool
    {
        return static::hasMacro($name);
    }

    /**
     * Dynamically access builder proxies.
     *
     * @param  string  $key
     *
     * @throws Exception
     */
    #[Override]
    public function __get($key): mixed
    {
        if ($this->isCustomFieldHandle($key)) {
            return $this->customFieldValues[$key] ?? null;
        }

        if (in_array($key, $this->propertyPassthru)) {
            return $this->getQuery()->{$key};
        }

        throw new Exception("Property [{$key}] does not exist on the Element query instance.");
    }

    #[Override]
    public function __set(string $name, mixed $value): void
    {
        if ($this->isCustomFieldHandle($name)) {
            $this->customFieldValues[$name] = $value;

            return;
        }

        if (method_exists($this, $name)) {
            $this->{$name}($value);

            return;
        }

        if (in_array($name, $this->propertyPassthru)) {
            $this->getQuery()->{$name} = $value;

            return;
        }

        throw new Exception("Property [{$name}] does not exist on the Element query instance.");
    }

    #[Override]
    public function canSetProperty(string $name): bool
    {
        return parent::canSetProperty($name)
            || $this->isCustomFieldHandle($name)
            || method_exists($this, $name)
            || in_array($name, $this->propertyPassthru);
    }

    /**
     * Resolves the columns passed to an aggregate method, such as `sum('price')`, through the column map.
     *
     * @param  array<int, mixed>  $parameters
     * @return array<int, mixed>
     */
    private function resolveAggregateColumns(string $method, array $parameters): array
    {
        $index = match ($method) {
            'average', 'avg', 'max', 'min', 'sum' => 0,
            'aggregate', 'numericaggregate' => 1,
            default => null,
        };

        if ($index === null || ! isset($parameters[$index])) {
            return $parameters;
        }

        $columns = $parameters[$index];
        $parameters[$index] = is_array($columns)
            ? array_map(fn ($column) => is_string($column) || $column instanceof Expression ? $this->resolveColumn($column) : $column, $columns)
            : (is_string($columns) || $columns instanceof Expression ? $this->resolveColumn($columns) : $columns);

        return $parameters;
    }

    /**
     * Dynamically handle calls into the query instance.
     *
     * @param  string  $method
     * @param  array<int, mixed>  $parameters
     */
    #[Override]
    public function __call($method, $parameters): mixed
    {
        if ($method === 'macro') {
            $this->localMacros[$parameters[0]] = $parameters[1];

            return null;
        }

        if ($this->isCustomFieldHandle($method)) {
            $this->customFieldValues[$method] = $parameters[0];

            return $this;
        }

        if ($this->hasLocalMacro($method)) {
            array_unshift($parameters, $this);

            return $this->localMacros[$method](...$parameters);
        }

        if (($callable = static::findMacro($method)) !== null) {

            if ($callable instanceof Closure) {
                $callable = $callable->bindTo($this, static::class);
            }

            return $callable(...$parameters);
        }

        if (in_array(strtolower($method), $this->passthru)) {
            if (! $this->isExecutionCopy) {
                return $this->executeOnCopy(fn (self $query) => $query->__call($method, $parameters));
            }

            try {
                $this->applyBeforeQueryCallbacks();
            } catch (QueryAbortedException) {
                return null;
            }

            $cacheKeyParameters = $parameters;
            $parameters = $this->resolveAggregateColumns(strtolower($method), $this->toBaseSubqueries($parameters));

            if ((int) $this->queryCacheDuration >= 0) {
                return DependencyCache::remember(
                    key: $this->queryCacheKey($this, strtolower($method), $cacheKeyParameters),
                    ttl: $this->queryCacheDuration,
                    callback: fn () => $this->getQuery()->{$method}(...$parameters),
                    dependency: $this->getCacheDependency(),
                );
            }

            return $this->getQuery()->{$method}(...$parameters);
        }

        $this->forwardCallTo($this->query, $method, $this->toBaseSubqueries($parameters));

        if (in_array(strtolower($method), ['addselect', 'selectraw', 'selectsub', 'selectexpression'], true)) {
            $this->addsSelect = true;
        }

        return $this;
    }

    /**
     * Dynamically handle calls into the query instance.
     *
     * @param  string  $method
     * @param  array<int, mixed>  $parameters
     *
     * @throws \BadMethodCallException
     */
    #[Override]
    public static function __callStatic($method, $parameters): mixed
    {
        if ($method === 'mixin') {
            static::registerMixin($parameters[0], $parameters[1] ?? true);

            return null;
        }

        $callable = static::findMacro($method);

        if ($callable === null) {
            static::throwBadMethodCallException($method);
        }

        if ($callable instanceof Closure) {
            $callable = $callable->bindTo(null, static::class);
        }

        return $callable(...$parameters);
    }

    /**
     * Register the given mixin with the builder.
     */
    protected static function registerMixin(object $mixin, bool $replace = true): void
    {
        $methods = new ReflectionClass($mixin)->getMethods(
            ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED,
        );

        foreach ($methods as $method) {
            if ($replace || ! static::hasGlobalMacro($method->name)) {
                static::macro($method->name, $method->invoke($mixin));
            }
        }
    }

    /**
     * Add a basic where clause to the query.
     *
     * Given a closure, the closure receives a new element query of the same type, so its params can be used
     * in a nested condition, as Eloquent passes a model query. A closure whose first parameter is typed as
     * the base query builder receives one instead.
     *
     * @param  Closure|string|array<array-key, mixed>|Expression  $column
     */
    public function where($column, mixed $operator = null, mixed $value = null, string $boolean = 'and'): static
    {
        if ($column instanceof Closure && $operator === null) {
            return $this->whereNestedElementQuery($column, $boolean);
        }

        $this->query->where(...$this->toBaseSubqueries(func_get_args()));

        return $this;
    }

    /**
     * Add an "or where" clause to the query.
     *
     * @param  Closure|string|array<array-key, mixed>|Expression  $column
     */
    public function orWhere($column, mixed $operator = null, mixed $value = null): static
    {
        if ($column instanceof Closure && $operator === null) {
            return $this->whereNestedElementQuery($column, 'or');
        }

        $this->query->orWhere(...$this->toBaseSubqueries(func_get_args()));

        return $this;
    }

    /**
     * Add a "where not" clause to the query.
     *
     * @param  Closure|string|array<array-key, mixed>|Expression  $column
     */
    public function whereNot($column, mixed $operator = null, mixed $value = null, string $boolean = 'and'): static
    {
        if ($column instanceof Closure && $operator === null) {
            return $this->whereNestedElementQuery($column, $boolean.' not');
        }

        $this->query->whereNot(...$this->toBaseSubqueries(func_get_args()));

        return $this;
    }

    /**
     * Add an "or where not" clause to the query.
     *
     * @param  Closure|string|array<array-key, mixed>|Expression  $column
     */
    public function orWhereNot($column, mixed $operator = null, mixed $value = null): static
    {
        if ($column instanceof Closure && $operator === null) {
            return $this->whereNestedElementQuery($column, 'or not');
        }

        $this->query->orWhereNot(...$this->toBaseSubqueries(func_get_args()));

        return $this;
    }

    /**
     * Adds the conditions a closure sets on a new element query of the same type as a nested where clause.
     *
     * Like Eloquent’s nested queries, which leave out global scopes, the new query starts without the
     * default site, status, draft, revision and trashed conditions, which this query applies once.
     *
     * A closure whose first parameter can’t accept an element query, such as one typed as the base
     * query builder, gets a nested base query builder instead.
     *
     * @param  Closure(static): mixed|Closure(Builder): mixed  $callback
     */
    private function whereNestedElementQuery(Closure $callback, string $boolean): static
    {
        $parameter = new ReflectionFunction($callback)->getParameters()[0] ?? null;
        $types = $parameter ? Reflector::getParameterClassNames($parameter) : [];

        if ($types !== [] && ! array_any($types, fn (string $type) => $this instanceof $type)) {
            $this->query->whereNested($callback, $boolean);

            return $this;
        }

        $baseline = $this->newNestedElementQuery();
        $nested = $this->newNestedElementQuery();
        $callback($nested);

        try {
            $nested->applyBeforeQueryCallbacks();
        } catch (QueryAbortedException) {
            $this->query->whereRaw('1 = 0', boolean: $boolean);

            return $this;
        }

        $baseline->applyBeforeQueryCallbacks();
        $baselineJoins = array_map(fn ($join) => $join->table, $baseline->query->joins ?? []);

        foreach ($nested->query->joins ?? [] as $join) {
            if (! in_array($join->table, $baselineJoins)) {
                $table = $join->table instanceof Expression ? $join->table->getValue($this->query->getGrammar()) : $join->table;

                throw new InvalidArgumentException(sprintf(
                    'A param set in a nested where() closure joins `%s`, which a nested condition can’t add. Set that param on the outer query instead.',
                    $table,
                ));
            }
        }

        if ($nested->query->wheres !== []) {
            $this->query->addNestedWhereQuery($nested->query, $boolean);
        }

        return $this;
    }

    private function newNestedElementQuery(): static
    {
        /** @var static $query */
        $query = $this->elementType::find();
        $query->isNestedCondition = true;

        return $query
            ->site(null)
            ->status(null)
            ->drafts(null)
            ->provisionalDrafts(null)
            ->revisions(null)
            ->trashed(null);
    }

    /**
     * Runs an execution method on a copy of this query, which the method prepares, so this query stays unprepared
     * and params set on it afterwards still apply, as Eloquent applies its scopes to a copy of its query.
     *
     * @template TResult
     *
     * @param  Closure(static): TResult  $callback
     * @return TResult
     */
    private function executeOnCopy(Closure $callback): mixed
    {
        $copy = clone $this;
        $copy->isExecutionCopy = true;

        return $callback($copy);
    }

    public function clone(): static
    {
        return clone $this;
    }

    /**
     * Register a closure to be invoked on a clone.
     */
    /** @return self<TElement> */
    public function onClone(Closure $callback): self
    {
        $this->onCloneCallbacks[] = $callback;

        return $this;
    }

    /**
     * Force a clone of the underlying query builders when cloning.
     */
    public function __clone(): void
    {
        if ($this->queryBeforePrepare !== null) {
            $beforeQueryCallbacks = array_diff_key(
                $this->beforeQueryCallbacks,
                $this->beforeQueryCallbacksBeforePrepare ?? [],
            );

            $this->query = clone $this->queryBeforePrepare;
            $this->columnMap = $this->columnMapBeforePrepare ?? $this->columnMap;
            $this->beforeQueryCallbacks = [
                ...($this->beforeQueryCallbacksBeforePrepare ?? []),
                ...$beforeQueryCallbacks,
            ];
            $this->elementQueryBeforeQueryCalled = false;
            $this->selectsDefaultColumns = null;
            $this->queryBeforePrepare = null;
            $this->columnMapBeforePrepare = null;
            $this->beforeQueryCallbacksBeforePrepare = null;
        } else {
            $this->query = clone $this->query;
        }

        $this->isExecutionCopy = false;

        foreach ($this->onCloneCallbacks as $onCloneCallback) {
            $onCloneCallback($this);
        }
    }

    /**
     * Register a closure to be invoked before the query is executed.
     */
    /** @return self<TElement> */
    public function beforeQuery(Closure $callback): self
    {
        $this->beforeQueryCallbacks[] = $callback;

        return $this;
    }

    public function applyBeforeQueryCallbacks(): void
    {
        if (! $this->elementQueryBeforeQueryCalled && $this->queryBeforePrepare === null) {
            $this->queryBeforePrepare = clone $this->query;
            $this->columnMapBeforePrepare = $this->columnMap;
            $this->beforeQueryCallbacksBeforePrepare = $this->beforeQueryCallbacks;
        }

        $originalWhereCount = count($this->query->wheres);

        foreach ($this->beforeQueryCallbacks as $i => $callback) {
            $callback($this);

            unset($this->beforeQueryCallbacks[$i]);
        }

        $this->beforeQueryCallbacks = [];

        $this->elementQueryBeforeQuery();

        $this->query->wheres = [
            ...$this->resolveWhereColumns(array_slice($this->query->wheres, 0, $originalWhereCount)),
            ...array_slice($this->query->wheres, $originalWhereCount),
        ];

        if (count($this->query->wheres) > $originalWhereCount) {
            $this->addNewWheresWithinGroup($originalWhereCount);
        }

        $this->convertDateBindingsToUtc();
    }

    /**
     * Resolves the columns in where clauses through the column map, so they can name custom fields
     * and columns that several joined tables share, as orderBy() and select() can.
     *
     * Subqueries select from their own tables, and Laravel’s JSON where clauses need real columns,
     * so neither is resolved.
     *
     * @param  array<int, array<string, mixed>>  $wheres
     * @return array<int, array<string, mixed>>
     */
    private function resolveWhereColumns(array $wheres): array
    {
        return array_map(function (array $where) {
            $type = $where['type'] ?? '';

            if ($type === 'Nested') {
                $where['query'] = clone $where['query'];
                $where['query']->wheres = $this->resolveWhereColumns($where['query']->wheres);

                return $where;
            }

            if (str_starts_with($type, 'Json') || $type === 'Fulltext') {
                return $where;
            }

            foreach (['column', 'first', 'second'] as $key) {
                if (isset($where[$key]) && (is_string($where[$key]) || $where[$key] instanceof Expression)) {
                    $where[$key] = $this->resolveColumn($where[$key]);
                }
            }

            // Laravel’s MySQL grammar mistakes a JSON extraction in a null check for a JSON path, so compile it as raw SQL
            if (in_array($type, ['Null', 'NotNull'], true) && $where['column'] instanceof Expression) {
                return [
                    'type' => 'raw',
                    'sql' => $where['column']->getValue($this->query->getGrammar()).($type === 'Null' ? ' is null' : ' is not null'),
                    'boolean' => $where['boolean'],
                ];
            }

            if ($type === 'RowValues') {
                $where['columns'] = array_map($this->resolveColumn(...), $where['columns']);
            }

            return $where;
        }, $wheres);
    }

    /**
     * Converts dates bound to the query to UTC, which is how Craft stores them.
     *
     * Laravel binds a date in its own time zone, which is the system time zone,
     * so `where('postDate', '<=', now())` would otherwise compare against the wrong time.
     */
    private function convertDateBindingsToUtc(): void
    {
        foreach ($this->query->bindings as $type => $bindings) {
            foreach ($bindings as $i => $binding) {
                if ($binding instanceof DateTimeInterface) {
                    $this->query->bindings[$type][$i] = Query::prepareDateForDb($binding);
                }
            }
        }
    }

    /**
     * Groups the conditions added before the query was prepared apart from the ones its params added,
     * so an `orWhere()` can't bypass the element query's own conditions.
     *
     * @see \Illuminate\Database\Eloquent\Builder::addNewWheresWithinGroup()
     */
    private function addNewWheresWithinGroup(int $originalWhereCount): void
    {
        $allWheres = $this->query->wheres;

        $this->query->wheres = [];

        $this->groupWhereSlice(array_slice($allWheres, 0, $originalWhereCount));
        $this->groupWhereSlice(array_slice($allWheres, $originalWhereCount));
    }

    /** @param array<int, array<string, mixed>> $whereSlice */
    private function groupWhereSlice(array $whereSlice): void
    {
        $booleans = array_column($whereSlice, 'boolean');

        if (! array_any($booleans, fn (string $boolean) => str_contains($boolean, 'or'))) {
            $this->query->wheres = [...$this->query->wheres, ...$whereSlice];

            return;
        }

        $group = $this->query->forNestedWhere();
        $group->wheres = $whereSlice;

        $this->query->wheres[] = ['type' => 'Nested', 'query' => $group, 'boolean' => str_replace(' not', '', $booleans[0])];
    }

    protected function elementQueryBeforeQuery(): void
    {
        if ($this->elementQueryBeforeQueryCalled) {
            return;
        }

        $this->warnAboutLegacyBeforePrepare();

        $this->applySelectParams();

        if ($this->query->groups) {
            $this->query->groups = array_map(fn ($column) => $this->resolveColumn($this->sqlColumn($column)), $this->query->groups);
        }

        // If the query isn't sourced from a concrete element table, explicitly filter by element type.
        if (! $this->hasElementSourceTable && $this->elementType !== Element::class) {
            try {
                $ref = new ReflectionClass($this->elementType);
            } catch (ReflectionException) {
                $ref = null;
            }

            if ($ref && ! $ref->isAbstract()) {
                $this->query->where('elements.type', $this->elementType);
            }
        }

        $this->applyStructureParams($this);
        $this->applyOrderByParams($this);
        $this->applyUniqueParams($this);

        if ($this->getOffset() !== null && $this->getLimit() === null && (DB::isMysql() || DB::isSqlite())) {
            // Limit is not optional in MySQL or SQLite
            $this->query->limit(PHP_INT_MAX);
        }

        $this->elementQueryBeforeQueryCalled = true;
    }

    /**
     * Warns when a subclass still defines Craft 5's `beforePrepare()` hook.
     *
     * Nothing calls that hook any more — this method is where the query gets
     * assembled — so a query which staged its joins and conditions there loses
     * them silently. Worse, the loss surfaces nowhere near the cause: what fails
     * is whatever still references the tables `beforePrepare()` was supposed to
     * have joined, typically a `$defaultOrderBy` pointing at one of them, and it
     * fails as a bare SQL error (`Unknown column …`) from deep inside whatever
     * ran the query. Saying so here names the problem at prepare time instead.
     *
     * Deliberately only a warning: the hook can't be called on the query's
     * behalf, because its body is written against the Craft 5 two-query
     * architecture (`$subQuery`) that no longer exists.
     */
    private function warnAboutLegacyBeforePrepare(): void
    {
        if (! method_exists($this, 'beforePrepare')) {
            return;
        }

        Deprecator::log(
            static::class.'::beforePrepare',
            sprintf(
                '%1$s::beforePrepare() is no longer called as of 6.0.0, so anything it sets up is being ignored. Override %1$s::elementQueryBeforeQuery() instead, declare the element’s own table with the `$table` property rather than joinElementTable(), and apply joins and conditions to `$query` — `$subQuery` no longer exists.',
                static::class,
            ),
        );
    }

    /**
     * Applies the 'select' param to the query being executed.
     */
    private function applySelectParams(): void
    {
        // Select all columns defined by [[select]], swapping out any mapped column names
        $select = [];
        $selectedExpressions = [];
        $includeDefaults = false;

        foreach ($this->query->columns as $column) {
            if ($column instanceof Expression) {
                $select[] = $column;
                $selectedExpressions[] = $column;

                continue;
            }

            [$column, $alias] = explode(' as ', $column, 2) + [1 => null];

            $column = $this->sqlColumn($column);

            if ($column instanceof Expression) {
                $select[] = $alias === null ? $column : new Alias($column, $alias);

                continue;
            }

            if ($column === '**') {
                $includeDefaults = true;
            } elseif ($alias === null && str_ends_with($column, '*')) {
                $select[] = $this->resolveColumn($column);
            } else {
                // Name the column as SQL would, without its table
                $select[] = new Alias($this->resolveColumn($column), $alias ?? Str::afterLast($column, '.'));
            }
        }

        $this->selectsDefaultColumns = $includeDefaults;
        $this->selectedExpressions = $selectedExpressions;

        // Is there still a ** placeholder param?
        if (! $includeDefaults) {
            $this->query->columns = $select;

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
            'elements_sites.id as siteSettingsId',
            'elements_sites.siteId',
            'elements_sites.title',
            'elements_sites.slug',
            'elements_sites.uri',
            'elements_sites.content',
            'elements_sites.enabled as enabledForSite',
        ]);

        // If the query includes soft-deleted elements, include the date deleted
        if ($this->trashed !== false) {
            $select[] = 'elements.dateDeleted';
        }

        $this->query->columns = $select;
    }

    /** @return string|array<array-key, mixed> */
    private function resolveColumnMapping(string $key): string|array
    {
        if (! $this->hasColumnMapping($key)) {
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

    /**
     * Throw an exception if the query doesn't have an orderBy clause.
     *
     *
     * @throws RuntimeException
     */
    protected function enforceOrderBy(): void
    {
        if (empty($this->query->orders) && empty($this->query->unionOrders)) {
            throw new RuntimeException('You must specify an orderBy clause when using this function.');
        }
    }
}
