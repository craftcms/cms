<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries;

use Closure;
use Craft;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\ElementCollection;
use craft\helpers\ElementHelper;
use CraftCms\Cms\Database\Queries\Concerns\LegacyMethods;
use CraftCms\Cms\Database\Queries\Exceptions\ElementNotFoundException;
use CraftCms\Cms\Database\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Support\Utils;
use CraftCms\DependencyAwareCache\Facades\DependencyCache;
use Exception;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Concerns\BuildsQueries;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Traits\ForwardsCalls;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use Tpetry\QueryExpressions\Function\Conditional\Coalesce;
use Tpetry\QueryExpressions\Language\Alias;
use Twig\Markup;

/**
 * @template TElement of ElementInterface
 *
 * @mixin \Illuminate\Database\Query\Builder
 *
 * @method static select($columns = ['*'])
 * @method static addSelect($column)
 * @method static join($table, $first, $operator = null, $second = null, $type = 'inner', $where = false)
 * @method static leftJoin($table, $first, $operator = null, $second = null)
 * @method static orderBy($column)
 * @method static orderByDesc($column)
 * @method static rightJoin($table, $first, $operator = null, $second = null)
 * @method static where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static whereNot($column, $operator = null, $value = null, $boolean = 'and')
 * @method static whereNotIn($column, $values, $boolean = 'and')
 * @method static whereNotNull($columns, $boolean = 'and')
 * @method static whereNotExists($callback, $boolean = 'and')
 * @method static whereNull($columns, $boolean = 'and', $not = false)
 *
 * @phpstan-ignore class.missingExtends
 */
class ElementQuery implements \Illuminate\Contracts\Database\Query\Builder, ElementQueryInterface
{
    /** @use \Illuminate\Database\Concerns\BuildsQueries<TElement> */
    use BuildsQueries {
        BuildsQueries::sole as baseSole;
        BuildsQueries::first as baseFirst;
    }

    use Concerns\CachesQueries;
    use Concerns\CollectsCacheTags;
    use Concerns\FormatsResults;

    /** @use Concerns\HydratesElements<TElement> */
    use Concerns\HydratesElements;

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

    /** @TODO: Remove after ElementQueryInterface is removed */
    use LegacyMethods;

    /**
     * The base query builder instance.
     */
    protected Builder $query;

    /**
     * The subquery that the main query will select from.
     */
    protected Builder $subQuery;

    /**
     * All of the globally registered builder macros.
     */
    protected static array $macros = [];

    /**
     * All of the locally registered builder macros.
     */
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
        'getcountforpagination',
        'getgrammar',
        'getrawbindings',
        'implode',
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
        'value',
    ];

    /**
     * The callbacks that should be invoked before retrieving data from the database.
     */
    protected array $beforeQueryCallbacks = [];

    /**
     * The callbacks that should be invoked after retrieving data from the database.
     */
    protected array $afterQueryCallbacks = [];

    /**
     * The callbacks that should be invoked on clone.
     */
    protected array $onCloneCallbacks = [];

    // Use ** as a placeholder for "all the default columns"
    protected array $columns = ['**'];

    // For internal use
    // -------------------------------------------------------------------------

    /**
     * @var array<string,string> Column alias => name mapping
     *
     * @see joinElementTable()
     * @see applyOrderByParams()
     * @see applySelectParam()
     */
    private array $columnMap;

    /**
     * @var bool Whether an element table has been joined for the query
     *
     * @see prepare()
     * @see joinElementTable()
     */
    private bool $joinedElementTable = false;

    /**
     * Create a new Element query instance.
     *
     * @param  class-string<TElement>  $elementType
     */
    public function __construct(
        /** @var class-string<TElement> */
        public string $elementType = Element::class,
        protected array $config = [],
    ) {
        Typecast::properties(static::class, $config);

        foreach ($config as $key => $value) {
            $this->{$key} = $value;
        }

        $this->query = DB::query()
            ->join(new Alias(Table::ELEMENTS_SITES, 'elements_sites'), 'elements_sites.id', 'subquery.siteSettingsId')
            ->join(new Alias(Table::ELEMENTS, 'elements'), 'elements.id', 'subquery.elementsId')
            ->select('**');

        $this->subQuery = DB::table(Table::ELEMENTS, 'elements')
            ->select([
                'elements.id as elementsId',
                'elements_sites.id as siteSettingsId',
            ])
            ->join(new Alias(Table::ELEMENTS_SITES, 'elements_sites'), 'elements_sites.elementId', 'elements.id');

        // Prepare a new column mapping
        // (for use in SELECT and ORDER BY clauses)
        $this->columnMap = [
            'id' => 'elements.id',
            'enabled' => 'elements.enabled',
            'dateCreated' => 'elements.dateCreated',
            'dateUpdated' => 'elements.dateUpdated',
            'uid' => 'elements.uid',
        ];

        if ($this->elementType::hasTitles()) {
            $this->columnMap['title'] = 'elements_sites.title';
        }

        $this->initTraits();
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
     * @see ElementHelper::renderElements()
     */
    public function render(array $variables = []): Markup
    {
        return ElementHelper::renderElements($this->all(), $variables);
    }

    /**
     * Find a model by its primary key.
     *
     * @return ($id is (\Illuminate\Contracts\Support\Arrayable<array-key, mixed>|array<mixed>) ? \craft\elements\ElementCollection<int, TElement> : TElement|null)
     */
    public function find(mixed $id, array|string $columns = ['*']): ElementInterface|ElementCollection|null
    {
        if (is_array($id) || $id instanceof Arrayable) {
            return $this->findMany($id, $columns);
        }

        return $this->id($id)->first($columns);
    }

    /**
     * Find multiple elements by their primary keys.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $ids
     * @return \craft\elements\ElementCollection<int, TElement>|array<int, TElement>
     */
    public function findMany(mixed $ids, array|string $columns = ['*']): ElementCollection|array
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
     * @return ($id is (\Illuminate\Contracts\Support\Arrayable<array-key, mixed>|array<mixed>) ? \craft\elements\ElementCollection<int, TElement> : TElement)
     *
     * @throws ElementNotFoundException<TElement>
     */
    public function findOrFail(mixed $id, array|string $columns = ['*']): ElementInterface|ElementCollection
    {
        $result = $this->find($id, $columns);

        $id = $id instanceof Arrayable ? $id->toArray() : $id;

        if (is_array($id)) {
            if (count($result) !== count(array_unique($id))) {
                throw (new ElementNotFoundException)->setElement(
                    $this->elementType, array_diff($id, $result->pluck('id')->all())
                );
            }

            return $result;
        }

        if (is_null($result)) {
            throw (new ElementNotFoundException)->setElement(
                $this->elementType, $id
            );
        }

        return $result;
    }

    /**
     * Find a model by its primary key or call a callback.
     *
     * @template TValue
     *
     * @param  (\Closure(): TValue)|list<string>|string  $columns
     * @param  (\Closure(): TValue)|null  $callback
     * @return (
     *     $id is (\Illuminate\Contracts\Support\Arrayable<array-key, mixed>|array<mixed>)
     *     ? \craft\elements\ElementCollection<int, TElement>
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
     * @return TElement
     *
     * @throws ElementNotFoundException<TElement>
     */
    public function firstOrFail(array|string $columns = ['*']): ElementInterface
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
     * @param  (\Closure(): TValue)|list<string>  $columns
     * @param  (\Closure(): TValue)|null  $callback
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
     * @return TElement
     *
     * @throws ElementNotFoundException<TElement>
     * @throws \Illuminate\Database\MultipleRecordsFoundException
     */
    public function sole(array|string $columns = ['*']): ElementInterface
    {
        try {
            return $this->baseSole($columns);
        } catch (RecordsNotFoundException) {
            throw (new ElementNotFoundException)->setElement($this->elementType);
        }
    }

    /**
     * @return TElement|null
     */
    public function first($columns = ['*']): ?ElementInterface
    {
        // Eagerly?
        $eagerResult = $this->eagerLoad(criteria: ['limit' => 1]);

        if ($eagerResult !== null) {
            return $eagerResult->first();
        }

        return $this->baseFirst($columns);
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  string|\Illuminate\Contracts\Database\Query\Expression|array<string|\Illuminate\Contracts\Database\Query\Expression>  $columns
     * @return \craft\elements\ElementCollection<int, TElement>
     */
    public function get($columns = ['*']): ElementCollection
    {
        $models = $this->getModels($columns);

        return $this->applyAfterQueryCallbacks(new ElementCollection($models))
            ->when($this->indexBy, fn (ElementCollection $collection) => $collection->keyBy($this->indexBy));
    }

    /**
     * Get the hydrated elements
     *
     * @return array<int, TElement>
     */
    public function getModels(array|string $columns = ['*']): array
    {
        if (! is_null($result = $this->getResultOverride())) {
            if ($this->with) {
                Craft::$app->getElements()->eagerLoadElements($this->elementType, $result, $this->with);
            }

            return $result;
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

        return $this->eagerLoad()?->all() ?? $this->hydrate($result);
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array|string  $columns
     * @return array<int, TElement>
     *
     * @phpstan-ignore-next-line
     */
    public function all($columns = ['*']): array
    {
        return collect($this->get($columns))->all();
    }

    /**
     * @return TElement|null
     *
     * @phpstan-ignore parameter.defaultValue, method.childReturnType
     */
    public function one($columns = ['*']): ?ElementInterface
    {
        return $this->first($columns);
    }

    public function pluck($column, $key = null): Collection|array
    {
        $column = $this->columnMap[$column] ?? $column;

        if (! is_null($result = $this->getResultOverride())) {
            return collect($result)->pluck($column, $key);
        }

        try {
            $this->applyBeforeQueryCallbacks();
        } catch (QueryAbortedException) {
            return $this->asArray ? [] : new Collection;
        }

        $column = $this->columnMap[$column] ?? $column;

        if ((int) $this->queryCacheDuration >= 0) {
            $result = DependencyCache::remember(
                key: $this->queryCacheKey($this, 'pluck', [$column, $key]),
                ttl: $this->queryCacheDuration,
                callback: fn () => $this->query->pluck($column, $key),
                dependency: $this->getCacheDependency(),
            );
        } else {
            $result = $this->query->pluck($column, $key);
        }

        return $result->when($this->asArray, fn (Collection $collection) => $collection->all());
    }

    /** @TODO: Remove $_ variable after ElementQueryInterface is removed */
    public function count($columns = '*', $_ = null): int
    {
        if (! $this->getOffset() && ! $this->getLimit() && ! is_null($result = $this->getResultOverride())) {
            return count($result);
        }

        try {
            $this->applyBeforeQueryCallbacks();
        } catch (QueryAbortedException) {
            return 0;
        }

        $eagerLoadedCount = $this->eagerLoad(count: true);

        if ($eagerLoadedCount !== null) {
            return $eagerLoadedCount;
        }

        if ((int) $this->queryCacheDuration >= 0) {
            $result = DependencyCache::remember(
                key: $this->queryCacheKey($this, 'count', $columns),
                ttl: $this->queryCacheDuration,
                callback: fn () => $this->query->count($columns),
                dependency: $this->getCacheDependency(),
            );
        } else {
            $result = $this->query->count($columns);
        }

        return $this->applyAfterQueryCallbacks($result);
    }

    public function nth(int $n, array|string $columns = ['*']): ?ElementInterface
    {
        if (! is_null($result = $this->getResultOverride())) {
            return $result[$n] ?? null;
        }

        // Eagerly?
        $eagerResult = $this->eagerLoad(criteria: [
            'offset' => ($this->offset ?: 0) + $n,
            'limit' => 1,
        ]);

        if ($eagerResult !== null) {
            return $eagerResult->first();
        }

        /** @var ?ElementInterface $element */
        $element = $this->query->skip(($this->offset ?: 0) + $n)->first($columns);

        return $element;
    }

    /**
     * Register a closure to be invoked after the query is executed.
     */
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
            $result = $afterQueryCallback($result) ?: $result;
        }

        return $result;
    }

    /**
     * Get a lazy collection for the given query.
     *
     * @return \Illuminate\Support\LazyCollection<int, TElement>
     */
    public function cursor(): LazyCollection
    {
        try {
            $this->applyBeforeQueryCallbacks();
        } catch (QueryAbortedException) {
            return new LazyCollection;
        }

        return $this->query->cursor()->map(function ($record) {
            $model = $this->createElement((array) $record);

            return $this->applyAfterQueryCallbacks(new ElementCollection([$model]))->first();
        })->reject(fn ($model) => is_null($model));
    }

    /**
     * Get the underlying query builder instance.
     */
    public function getQuery(): Builder
    {
        return $this->query;
    }

    /**
     * Get the underlying subquery builder instance.
     */
    public function getSubQuery(): Builder
    {
        return $this->subQuery;
    }

    public function limit($value): self
    {
        $this->subQuery->limit = $value;

        return $this;
    }

    /**
     * Get the "limit" value from the query or null if it's not set.
     */
    public function getLimit(): mixed
    {
        return $this->subQuery->getLimit();
    }

    public function offset($value): self
    {
        $this->subQuery->offset = $value;

        return $this;
    }

    public function orderBy($column, $direction = 'asc'): self
    {
        /**
         * In case orderBy is called with an array of [$column, $direction]
         */
        if (is_array($column)) {
            Deprecator::log(static::class.'::orderBy', static::class.'::orderBy() with an array argument is deprecated in 6.0.0.');

            foreach ($column as $c => $dir) {
                $this->query->orderBy($c, $dir === SORT_ASC ? 'asc' : 'desc');
                $this->subQuery->orderBy($c, $dir === SORT_ASC ? 'asc' : 'desc');
            }

            return $this;
        }

        $this->forwardCallTo($this->query, 'orderBy', [$column, $direction]);
        $this->forwardCallTo($this->subQuery, 'orderBy', [$column, $direction]);

        return $this;
    }

    /**
     * Get the "offset" value from the query or null if it's not set.
     */
    public function getOffset(): mixed
    {
        return $this->subQuery->getOffset();
    }

    public function getWhereForColumn(string $column): ?array
    {
        return collect($this->subQuery->wheres)
            ->firstWhere('column', $column);
    }

    /**
     * Returns an array of the current criteria attribute values.
     */
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
    public function hasMacro(string $name): bool
    {
        return isset($this->localMacros[$name]);
    }

    /**
     * Get the given global macro by name.
     */
    public static function getGlobalMacro(string $name): Closure
    {
        return Arr::get(static::$macros, $name);
    }

    /**
     * Checks if a global macro is registered.
     *
     * @param  string  $name
     */
    public static function hasGlobalMacro($name): bool
    {
        return isset(static::$macros[$name]);
    }

    /**
     * Dynamically access builder proxies.
     *
     * @param  string  $key
     *
     * @throws \Exception
     */
    public function __get($key): mixed
    {
        if (array_key_exists($key, $this->customFieldValues)) {
            return $this->customFieldValues[$key];
        }

        if (in_array($key, $this->propertyPassthru)) {
            return $this->getQuery()->{$key};
        }

        throw new Exception("Property [{$key}] does not exist on the Element query instance.");
    }

    public function __set(string $name, $value): void
    {
        if (array_key_exists($name, $this->customFieldValues)) {
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

    /**
     * Dynamically handle calls into the query instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     */
    public function __call($method, $parameters): mixed
    {
        if ($method === 'macro') {
            $this->localMacros[$parameters[0]] = $parameters[1];

            return null;
        }

        if (array_key_exists($method, $this->customFieldValues)) {
            $this->customFieldValues[$method] = $parameters[0];

            return $this;
        }

        if ($this->hasMacro($method)) {
            array_unshift($parameters, $this);

            return $this->localMacros[$method](...$parameters);
        }

        if (static::hasGlobalMacro($method)) {
            $callable = static::$macros[$method];

            if ($callable instanceof Closure) {
                $callable = $callable->bindTo($this, static::class);
            }

            return $callable(...$parameters);
        }

        if (in_array(strtolower($method), $this->passthru)) {
            try {
                $this->applyBeforeQueryCallbacks();
            } catch (QueryAbortedException) {
                return null;
            }

            if ((int) $this->queryCacheDuration >= 0) {
                return DependencyCache::remember(
                    key: $this->queryCacheKey($this, strtolower($method), $parameters),
                    ttl: $this->queryCacheDuration,
                    callback: fn () => $this->getQuery()->{$method}(...$parameters),
                    dependency: $this->getCacheDependency(),
                );
            }

            return $this->getQuery()->{$method}(...$parameters);
        }

        /**
         * Joins and orders should be done on both queries
         */
        if (in_array(strtolower($method), ['join', 'orderby', 'orderbydesc'])) {
            $this->forwardCallTo($this->query, $method, $parameters);
            $this->forwardCallTo($this->subQuery, $method, $parameters);

            return $this;
        }

        if (in_array(strtolower($method), ['select', 'reorder', 'addselect'])) {
            $this->forwardCallTo($this->query, $method, $parameters);

            return $this;
        }

        $this->forwardCallTo($this->subQuery, $method, $parameters);

        return $this;
    }

    /**
     * Dynamically handle calls into the query instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     *
     * @throws \BadMethodCallException
     */
    public static function __callStatic($method, $parameters): mixed
    {
        if ($method === 'macro') {
            static::$macros[$parameters[0]] = $parameters[1];

            return null;
        }

        if ($method === 'mixin') {
            static::registerMixin($parameters[0], $parameters[1] ?? true);

            return null;
        }

        if (! static::hasGlobalMacro($method)) {
            static::throwBadMethodCallException($method);
        }

        $callable = static::$macros[$method];

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
            ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
        );

        foreach ($methods as $method) {
            if ($replace || ! static::hasGlobalMacro($method->name)) {
                static::macro($method->name, $method->invoke($mixin));
            }
        }
    }

    public function clone(): static
    {
        return clone $this;
    }

    /**
     * Register a closure to be invoked on a clone.
     */
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
        $this->query = clone $this->query;
        $this->subQuery = clone $this->subQuery;

        foreach ($this->onCloneCallbacks as $onCloneCallback) {
            $onCloneCallback($this);
        }
    }

    /**
     * Register a closure to be invoked before the query is executed.
     */
    public function beforeQuery(Closure $callback): self
    {
        $this->beforeQueryCallbacks[] = $callback;

        return $this;
    }

    public function applyBeforeQueryCallbacks(): void
    {
        foreach ($this->beforeQueryCallbacks as $i => $callback) {
            $callback($this);

            unset($this->beforeQueryCallbacks[$i]);
        }

        $this->beforeQueryCallbacks = [];

        $this->elementQueryBeforeQuery();
    }

    protected function elementQueryBeforeQuery(): void
    {
        $this->applySelectParams();

        // If an element table was never joined in, explicitly filter based on the element type
        if (! $this->joinedElementTable && $this->elementType !== Element::class) {
            try {
                $ref = new ReflectionClass($this->elementType);
            } catch (ReflectionException) {
                $ref = null;
            }

            if ($ref && ! $ref->isAbstract()) {
                $this->subQuery->where('elements.type', $this->elementType);
            }
        }

        $this->applyOrderByParams($this);
        $this->applyUniqueParams($this);

        $this->query->fromSub($this->subQuery, 'subquery');
    }

    /**
     * Joins in a table with an `id` column that has a foreign key pointing to `elements.id`.
     *
     * The table will be joined with an alias based on the unprefixed table name. For example,
     * if `{{%entries}}` is passed, the table will be aliased to `entries`.
     *
     * @param  string  $table  The table name, e.g. `entries` or `{{%entries}}`
     */
    public function joinElementTable(string $table, ?string $alias = null): void
    {
        $alias ??= $table;

        $this->query->join(new Alias($table, $alias), "$alias.id", 'subquery.elementsId');
        $this->subQuery->join(new Alias($table, $alias), "$alias.id", 'elements.id');
        $this->joinedElementTable = true;

        // Add element table cols to the column map
        foreach (Schema::getColumnListing($table) as $column) {
            if (! isset($this->columnMap[$column])) {
                $this->columnMap[$column] = "$alias.$column";
            }
        }
    }

    /**
     * Applies the 'select' param to the query being executed.
     */
    private function applySelectParams(): void
    {
        // Select all columns defined by [[select]], swapping out any mapped column names
        $select = [];
        $includeDefaults = false;

        foreach ($this->query->columns as $column) {
            if ($column instanceof Expression) {
                $column = $column->getValue($this->query->getGrammar());
            }

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

    private function resolveColumnMapping(string $key): string|array
    {
        if (! isset($this->columnMap[$key])) {
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
     * @return void
     *
     * @throws \RuntimeException
     */
    protected function enforceOrderBy()
    {
        if (empty($this->query->orders) && empty($this->query->unionOrders)) {
            throw new RuntimeException('You must specify an orderBy clause when using this function.');
        }
    }
}
