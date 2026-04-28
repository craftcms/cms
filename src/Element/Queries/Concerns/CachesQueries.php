<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns;

use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Support\Json;
use CraftCms\DependencyAwareCache\Dependency\Dependency;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use Illuminate\Database\Connection;

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

    protected function queryCacheKey(ElementQuery $elementQuery, string $method, array|string $parameters = []): string
    {
        $sql = $elementQuery->query->toRawSql();
        /** @var Connection $connection */
        $connection = $elementQuery->query->getConnection();
        $config = Json::encode($connection->getConfig());

        return md5($sql.$method.$config.Json::encode($parameters));
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
}
