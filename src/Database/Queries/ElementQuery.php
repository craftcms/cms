<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries;

use Closure;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\helpers\ElementHelper;
use CraftCms\Cms\Database\Queries\Exceptions\ElementNotFoundException;
use CraftCms\Cms\Database\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Support\Utils;
use Exception;
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
use Tpetry\QueryExpressions\Function\Conditional\Coalesce;
use Tpetry\QueryExpressions\Language\Alias;
use Twig\Markup;

/**
 * @template TElement of ElementInterface
 *
 * @mixin \Illuminate\Database\Query\Builder
 */
class ElementQuery implements ElementQueryInterface
{
    /** @use \Illuminate\Database\Concerns\BuildsQueries<TElement> */
    use BuildsQueries {
        BuildsQueries::sole as baseSole;
        BuildsQueries::first as baseFirst;
    }

    use Concerns\CollectsCacheTags;
    use Concerns\FormatsResults;
    use Concerns\HydratesElements;
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
        'count',
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
     * @var array<string,array<string|\Illuminate\Contracts\Database\Query\Expression>> Column alias => name mapping
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
        protected string $elementType = Element::class,
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
     * @return ($id is (\Illuminate\Contracts\Support\Arrayable<array-key, mixed>|array<mixed>) ? \Illuminate\Database\Eloquent\Collection<int, TElement> : TElement|null)
     */
    public function find(mixed $id, array|string $columns = ['*']): ElementInterface|Collection|null
    {
        if (is_array($id) || $id instanceof Arrayable) {
            return $this->findMany($id, $columns);
        }

        return $this->where('elements.id', $id)->first($columns);
    }

    /**
     * Find multiple elements by their primary keys.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $ids
     * @return \Illuminate\Database\Eloquent\Collection<int, TElement>|array<int, TElement>
     */
    public function findMany(mixed $ids, array|string $columns = ['*']): Collection|array
    {
        $ids = $ids instanceof Arrayable ? $ids->toArray() : $ids;

        if (empty($ids)) {
            return new Collection;
        }

        return $this->whereIn('elements.id', $ids)->get($columns);
    }

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @return ($id is (\Illuminate\Contracts\Support\Arrayable<array-key, mixed>|array<mixed>) ? \Illuminate\Database\Eloquent\Collection<int, TElement> : TElement)
     *
     * @throws ElementNotFoundException<TElement>
     */
    public function findOrFail(mixed $id, array|string $columns = ['*']): ElementInterface|Collection
    {
        $result = $this->find($id, $columns);

        $id = $id instanceof Arrayable ? $id->toArray() : $id;

        if (is_array($id)) {
            if (count($result) !== count(array_unique($id))) {
                throw (new ElementNotFoundException)->setElement(
                    $this->elementType, array_diff($id, $result->modelKeys())
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
     *     ? \Illuminate\Database\Eloquent\Collection<int, TElement>
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
     * @return \Illuminate\Database\Eloquent\Collection<int, TElement>|array<int, TElement>
     */
    public function get(array|string $columns = ['*']): Collection|array
    {
        $models = $this->getModels($columns);

        return $this->applyAfterQueryCallbacks(new Collection($models))
            ->when($this->asArray, fn (Collection $collection) => $collection->all());
    }

    /**
     * Get the hydrated elements
     *
     * @return array<int, TElement>
     */
    public function getModels(array|string $columns = ['*']): array
    {
        $this->applyBeforeQueryCallbacks();

        return $this->eagerLoad()?->all() ?? $this->hydrate(
            $this->query->get($columns)->all()
        )->all();
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, TElement>|array<int, TElement>
     */
    public function all(array|string $columns = ['*']): Collection|array
    {
        return $this->get($columns);
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, TElement>
     */
    public function collect(array|string $columns = ['*']): Collection
    {
        $this->asArray = false;

        return $this->get($columns);
    }

    public function one(array|string $columns = ['*']): ?ElementInterface
    {
        return $this->first($columns);
    }

    public function pluck($column, $key = null): Collection|array
    {
        $this->applyBeforeQueryCallbacks();

        $column = $this->columnMap[$column] ?? $column;

        return $this->query->pluck($column, $key)
            ->when($this->asArray, fn (Collection $collection) => $collection->all());
    }

    public function count($columns = '*'): int
    {
        $eagerLoadedCount = $this->eagerLoad(count: true);

        if ($eagerLoadedCount !== null) {
            return $eagerLoadedCount;
        }

        return $this->query->count($columns);
    }

    public function nth(int $n, array|string $columns = ['*']): ?ElementInterface
    {
        // Eagerly?
        $eagerResult = $this->eagerLoad(criteria: [
            'offset' => ($this->offset ?: 0) + $n,
            'limit' => 1,
        ]);

        if ($eagerResult !== null) {
            return $eagerResult->first();
        }

        return $this->query->skip(($this->offset ?: 0) + $n)->first($columns);
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
        $this->applyBeforeQueryCallbacks();

        return $this->applyScopes()->query->cursor()->map(function ($record) {
            $model = $this->createElement((array) $record);

            return $this->applyAfterQueryCallbacks($this->newModelInstance()->newCollection([$model]))->first();
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

    public function limit(?int $value): self
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

    public function offset(?int $value): self
    {
        $this->subQuery->offset = $value;

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
        foreach (Utils::getPublicProperties($this, fn (\ReflectionProperty $property) => ! in_array($property->getName(), ['elementType', 'query', 'subQuery', 'customFields', 'asArray', 'with', 'eagerly'], true)) as $name => $value) {
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
            $this->applyBeforeQueryCallbacks();

            return $this->getQuery()->{$method}(...$parameters);
        }

        if (in_array(strtolower($method), ['orderby', 'orderbydesc', 'select', 'reorder'])) {
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
            return static::registerMixin($parameters[0], $parameters[1] ?? true);
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
    protected static function registerMixin(string $mixin, bool $replace = true): void
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

    public function clone(): self
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
        // Is the query already doomed?
        throw_if(isset($this->id) && empty($this->id), QueryAbortedException::class);

        // Give other classes a chance to make changes up front
        /*if (!$this->beforePrepare()) {
            throw new QueryAbortedException();
        }*/

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
    protected function joinElementTable(string $table, ?string $alias = null): void
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
            if ($column instanceof Alias) {
                $column = $column->getValue($this->getGrammar());
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
}
