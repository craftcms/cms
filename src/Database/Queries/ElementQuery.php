<?php

namespace CraftCms\Cms\Database\Queries;

use Closure;
use CraftCms\Cms\Database\Expressions\OrderByPlaceholderExpression;
use CraftCms\Cms\Database\Queries\Exceptions\ElementNotFoundException;
use CraftCms\Cms\Database\Table;
use craft\base\ElementInterface;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Str;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Concerns\BuildsQueries;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Traits\ForwardsCalls;
use ReflectionClass;
use ReflectionMethod;

/**
 * @template TElement of ElementInterface
 *
 * @mixin \Illuminate\Database\Query\Builder
 */
class ElementQuery implements ElementQueryInterface
{
    /** @use \Illuminate\Database\Concerns\BuildsQueries<TElement> */
    use BuildsQueries;
    use ForwardsCalls;

    use Concerns\QueriesCustomFields;
    use Concerns\QueriesDraftsAndRevisions;
    use Concerns\QueriesEagerly;
    use Concerns\QueriesPlaceholderElements;
    use Concerns\QueriesRelatedElements;
    use Concerns\QueriesSites;
    use Concerns\QueriesStatuses;
    use Concerns\QueriesStructures;
    use Concerns\QueriesUniqueElements;
    use Concerns\ElementQueryTrait;

    /**
     * The base query builder instance.
     */
    protected Builder $query;

    /**
     * The subquery that the main query will select from.
     */
    protected Builder $subQuery;

    /**
     * The element being queried.
     *
     * @var class-string<TElement>
     */
    protected string $elementType;

    /**
     * The table to be joined to elements.
     */
    protected string $table = Table::ELEMENTS;

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
     * @see \Illuminate\Database\Eloquent\Builder::$propertyPassthru for inspiration.
     */
    protected array $propertyPassthru = [
        'from',
    ];

    /**
     * The methods that should be returned from query builder.
     *
     * @var string[]
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
     * The callbacks that should be invoked after retrieving data from the database.
     */
    protected array $afterQueryCallbacks = [];

    /**
     * The callbacks that should be invoked on clone.
     */
    protected array $onCloneCallbacks = [];

    // Use ** as a placeholder for "all the default columns"
    protected array $columns = ['**'];

    /**
     * Create a new Element query instance.
     *
     * @param class-string<TElement> $elementType
     */
    public function __construct(string $elementType)
    {
        $this->elementType = $elementType;

        $this->query = DB::query();

        // Build the query
        // ---------------------------------------------------------------------
        $this->subQuery = DB::table(Table::ELEMENTS, 'elements');

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
            $this->columnMap[] = 'elements_sites.title as title';
        }

        $this->query->beforeQuery(fn (Builder $builder) => $this->elementQueryBeforeQuery($builder));

        $this->initializeTraits();

        $this->query->afterQuery(fn (Collection $collection) => $this->elementQueryAfterQuery($collection));
    }

    protected function initializeTraits(): void
    {
        $class = static::class;

        $uses = class_uses_recursive($class);

        $conventionalInitMethods = array_map(static fn ($trait) => 'initialize'.class_basename($trait), $uses);

        foreach (new ReflectionClass($class)->getMethods() as $method) {
            if (in_array($method->getName(), $conventionalInitMethods)) {
                $this->{$method->getName()}();
            }
        }
    }

    /**
     * Create a collection of elements from plain arrays.
     *
     * @param  array  $items
     * @return \Illuminate\Database\Eloquent\Collection<int, TElement>
     */
    public function hydrate(array $items): Collection
    {
        return new Collection(array_map(function ($item) {
            // @TODO: Actually populate
            return new $this->elementType;
        }, $items));
    }

    /**
     * Create a collection of models from a raw query.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return Collection<int, TElement>
     */
    public function fromQuery($query, $bindings = []): Collection
    {
        return $this->hydrate(
            $this->query->getConnection()->select($query, $bindings)
        );
    }

    /**
     * Find a model by its primary key.
     *
     * @param  mixed  $id
     * @param  array|string  $columns
     * @return ($id is (\Illuminate\Contracts\Support\Arrayable<array-key, mixed>|array<mixed>) ? \Illuminate\Database\Eloquent\Collection<int, TElement> : TElement|null)
     */
    public function find($id, $columns = ['*']): ElementInterface|Collection|null
    {
        if (is_array($id) || $id instanceof Arrayable) {
            return $this->findMany($id, $columns);
        }

        return $this->where('id', $id)->first($columns);
    }

    /**
     * Find multiple elements by their primary keys.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $ids
     * @param  array|string  $columns
     * @return \Illuminate\Database\Eloquent\Collection<int, TElement>
     */
    public function findMany($ids, $columns = ['*']): Collection
    {
        $ids = $ids instanceof Arrayable ? $ids->toArray() : $ids;

        if (empty($ids)) {
            return new Collection;
        }

        return $this->whereIn('id', $ids)->get($columns);
    }

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param  mixed  $id
     * @param  array|string  $columns
     * @return ($id is (\Illuminate\Contracts\Support\Arrayable<array-key, mixed>|array<mixed>) ? \Illuminate\Database\Eloquent\Collection<int, TElement> : TElement)
     *
     * @throws ElementNotFoundException<TElement>
     */
    public function findOrFail($id, $columns = ['*']): ElementInterface|Collection
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
     * @param  mixed  $id
     * @param  (\Closure(): TValue)|list<string>|string  $columns
     * @param  (\Closure(): TValue)|null  $callback
     * @return (
     *     $id is (\Illuminate\Contracts\Support\Arrayable<array-key, mixed>|array<mixed>)
     *     ? \Illuminate\Database\Eloquent\Collection<int, TElement>
     *     : TElement|TValue
     * )
     */
    public function findOr($id, $columns = ['*'], ?Closure $callback = null): mixed
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
     * @param  array|string  $columns
     * @return TElement
     *
     * @throws ElementNotFoundException<TElement>
     */
    public function firstOrFail($columns = ['*'])
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
    public function firstOr($columns = ['*'], ?Closure $callback = null): mixed
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
     * @param  array|string  $columns
     * @return TElement
     *
     * @throws ElementNotFoundException<TElement>
     * @throws \Illuminate\Database\MultipleRecordsFoundException
     */
    public function sole($columns = ['*']): mixed
    {
        try {
            return $this->baseSole($columns);
        } catch (RecordsNotFoundException) {
            throw (new ElementNotFoundException)->setElement($this->elementType);
        }
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array|string  $columns
     * @return \Illuminate\Database\Eloquent\Collection<int, TElement>
     */
    public function get($columns = ['*']): Collection
    {
        $models = $this->getModels($columns);

        return $this->applyAfterQueryCallbacks(new Collection($models));
    }

    /**
     * Get the hydrated elements
     *
     * @param  array|string  $columns
     * @return array<int, TElement>
     */
    public function getModels($columns = ['*']): array
    {
        return $this->hydrate(
            $this->query->get($columns)->all()
        )->all();
    }

    public function all(array|string $columns = ['*']): Collection
    {
        return $this->get($columns);
    }

    public function one(array|string $columns = ['*']): ?ElementInterface
    {
        return $this->first($columns);
    }

    public function pluck($column, $key = null)
    {
        $column = $this->columnMap[$column] ?? $column;

        return $this->query->pluck($column, $key);
    }

    /**
     * Register a closure to be invoked after the query is executed.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function afterQuery(Closure $callback): self
    {
        $this->afterQueryCallbacks[] = $callback;

        return $this;
    }

    /**
     * Invoke the "after query" modification callbacks.
     *
     * @param  mixed  $result
     * @return mixed
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
        return $this->applyScopes()->query->cursor()->map(function ($record) {
            // @TODO: Actually populate
            $model = new $this->elementType;

            return $this->applyAfterQueryCallbacks($this->newModelInstance()->newCollection([$model]))->first();
        })->reject(fn ($model) => is_null($model));
    }

    /**
     * Get the underlying query builder instance.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function getQuery(): Builder
    {
        return $this->query;
    }

    /**
     * Get the underlying subquery builder instance.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function getSubQuery(): Builder
    {
        return $this->subQuery;
    }

    /**
     * Get the "limit" value from the query or null if it's not set.
     *
     * @return mixed
     */
    public function getLimit(): mixed
    {
        return $this->subQuery->getLimit();
    }

    /**
     * Get the "offset" value from the query or null if it's not set.
     *
     * @return mixed
     */
    public function getOffset(): mixed
    {
        return $this->subQuery->getOffset();
    }

    /**
     * Get the given macro by name.
     *
     * @param  string  $name
     * @return \Closure
     */
    public function getMacro($name): Closure
    {
        return Arr::get($this->localMacros, $name);
    }

    /**
     * Checks if a macro is registered.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasMacro($name): bool
    {
        return isset($this->localMacros[$name]);
    }

    /**
     * Get the given global macro by name.
     *
     * @param  string  $name
     * @return \Closure
     */
    public static function getGlobalMacro($name): Closure
    {
        return Arr::get(static::$macros, $name);
    }

    /**
     * Checks if a global macro is registered.
     *
     * @param  string  $name
     * @return bool
     */
    public static function hasGlobalMacro($name): bool
    {
        return isset(static::$macros[$name]);
    }

    /**
     * Dynamically access builder proxies.
     *
     * @param  string  $key
     * @return mixed
     *
     * @throws \Exception
     */
    public function __get($key): mixed
    {
        if (in_array($key, $this->propertyPassthru)) {
            return $this->getQuery()->{$key};
        }

        throw new Exception("Property [{$key}] does not exist on the Element query instance.");
    }

    /**
     * Dynamically handle calls into the query instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters): mixed
    {
        if ($method === 'macro') {
            $this->localMacros[$parameters[0]] = $parameters[1];

            return null;
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
            return $this->getQuery()->{$method}(...$parameters);
        }

        $this->forwardCallTo($this->subQuery, $method, $parameters);

        return $this;
    }

    /**
     * Dynamically handle calls into the query instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
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
     *
     * @param  string  $mixin
     * @param  bool  $replace
     * @return void
     */
    protected static function registerMixin(string $mixin, bool $replace = true)
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
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function onClone(Closure $callback): self
    {
        $this->onCloneCallbacks[] = $callback;

        return $this;
    }

    /**
     * Force a clone of the underlying query builder when cloning.
     *
     * @return void
     */
    public function __clone()
    {
        $this->query = clone $this->query;

        foreach ($this->onCloneCallbacks as $onCloneCallback) {
            $onCloneCallback($this);
        }
    }
}
