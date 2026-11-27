<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns;

use Closure;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Support\Json;
use CraftCms\DependencyAwareCache\Dependency\Dependency;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use CraftCms\DependencyAwareCache\Facades\DependencyCache;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Expression;

/**
 * @internal
 */
trait CachesQueries
{
    /**
     * @var int the default number of seconds that query results can remain valid in cache.
     *          Use `null` to indicate that the cached data will never expire. And use a negative number to indicate
     *          query cache should not be used.
     *
     * @see cache()
     */
    public ?int $queryCacheDuration = -1;

    /**
     * @var Dependency the dependency to be associated with the cached query result for this command
     *
     * @see cache()
     */
    public ?Dependency $queryCacheDependency = null;

    /**
     * @param ElementQuery<*> $elementQuery
     * @param  array<array-key, mixed>|string  $parameters
     */
    protected function queryCacheKey(ElementQuery $elementQuery, string $method, array|string $parameters = []): string
    {
        $sql = $elementQuery->query->toRawSql();
        /** @var Connection $connection */
        $connection = $elementQuery->query->getConnection();
        $config = Json::encode($connection->getConfig());

        // Expressions don't encode to JSON, so they're keyed by their SQL
        $grammar = $elementQuery->query->getGrammar();
        $normalize = function (mixed $value) use (&$normalize, $grammar): mixed {
            return match (true) {
                $value instanceof Expression => ['expression' => (string) $value->getValue($grammar)],
                is_array($value) => array_map($normalize, $value),
                default => $value,
            };
        };

        return md5($sql.$method.$config.Json::encode($normalize($parameters)));
    }

    public function cache(int $duration = 3600, ?Dependency $dependency = null): static
    {
        $this->queryCacheDuration = $duration;
        $this->queryCacheDependency = $dependency;

        return $this;
    }

    public function noCache(): static
    {
        $this->queryCacheDuration = -1;

        return $this;
    }

    protected function getCacheDependency(): Dependency
    {
        return $this->queryCacheDependency ?? new TagDependency($this->getCacheTags());
    }

    /**
     * Returns the cached result for the query, or runs the callback and caches its result.
     *
     * The cache is bypassed while any of the query’s cache tags are waiting to be invalidated, so code that runs
     * during an element operation, such as an `ElementSaved` listener, reads the element’s new state. Queries cached
     * with a dependency other than a `TagDependency` are bypassed while any tags are pending.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    protected function rememberQueryResult(string $key, Closure $callback): mixed
    {
        $dependency = $this->getCacheDependency();
        $elementCaches = app(ElementCaches::class);

        // Other dependencies, such as an AnyDependency, don't expose their tags, so they're bypassed while any are pending
        $bypass = $dependency instanceof TagDependency
            ? $elementCaches->hasPendingInvalidations($dependency->tags)
            : $elementCaches->hasAnyPendingInvalidations();

        if ($bypass) {
            return $callback();
        }

        return DependencyCache::remember($key, $this->queryCacheDuration, $callback, $dependency);
    }
}
