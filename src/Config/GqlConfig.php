<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Typecast;
use InvalidArgumentException;

/**
 * Settings for the GraphQL API.
 *
 * @since 6.0.0
 */
class GqlConfig extends BaseConfig
{
    /** Whether the GraphQL API should be enabled. The GraphQL API is only available for Craft Pro. */
    public bool $enabled = true;

    /** Whether GraphQL introspection queries are allowed. They’re always allowed in the control panel. */
    public bool $enableIntrospection = true;

    /**
     * Whether Craft should cache the results of unique GraphQL queries per access token.
     *
     * The cache is invalidated any time an element is saved, the site structure is updated, or a GraphQL schema is
     * saved. It has no effect when a [[\CraftCms\Cms\Gql\Events\GqlQueryExecuting]] handler sets its own `result`.
     */
    public bool $enableCaching = true;

    /** Whether dates returned by the GraphQL API should be set to the system time zone by default, rather than UTC. */
    public bool $setDatesToSystemTimeZone = false;

    /** Prefix to use for all type names returned by GraphQL. */
    public string $typePrefix = '';

    /** Whether `typePrefix` should apply to the `query`, `mutation`, and `subscription` types. */
    public bool $prefixRootTypes = true;

    /** Whether GraphQL types should be generated lazily. */
    public bool $lazyTypes = false;

    /** The maximum number of queries that can be executed in a single batched request, or `0` for no limit. */
    public int $maxBatchSize = 0;

    /** The maximum complexity a query is allowed to have, or `0` for no limit. */
    public int $maxComplexity = 0;

    /** The maximum depth a query is allowed to reach, or `0` for no limit. */
    public int $maxDepth = 0;

    /** The maximum number of results a single query can return, or `0` for no limit. */
    public int $maxResults = 0;

    /** @param array<string, mixed>|string $value */
    public static function fromConfig(array|string $value): self
    {
        $value = is_array($value) ? $value : Json::decode($value);

        if (! is_array($value)) {
            throw new InvalidArgumentException('The GraphQL configuration must be an array or a JSON object.');
        }

        Typecast::properties(self::class, $value);

        $config = self::create();

        foreach ($value as $property => $propertyValue) {
            if (property_exists($config, $property) && method_exists($config, $property)) {
                $config->{$property}($propertyValue);
            }
        }

        return $config;
    }

    public function enabled(bool $value = true): self
    {
        $this->enabled = $value;

        return $this;
    }

    public function enableIntrospection(bool $value = true): self
    {
        $this->enableIntrospection = $value;

        return $this;
    }

    public function enableCaching(bool $value = true): self
    {
        $this->enableCaching = $value;

        return $this;
    }

    public function setDatesToSystemTimeZone(bool $value = true): self
    {
        $this->setDatesToSystemTimeZone = $value;

        return $this;
    }

    public function typePrefix(string $value): self
    {
        $this->typePrefix = $value;

        return $this;
    }

    public function prefixRootTypes(bool $value = true): self
    {
        $this->prefixRootTypes = $value;

        return $this;
    }

    public function lazyTypes(bool $value = true): self
    {
        $this->lazyTypes = $value;

        return $this;
    }

    public function maxBatchSize(int $value): self
    {
        $this->maxBatchSize = $value;

        return $this;
    }

    public function maxComplexity(int $value): self
    {
        $this->maxComplexity = $value;

        return $this;
    }

    public function maxDepth(int $value): self
    {
        $this->maxDepth = $value;

        return $this;
    }

    public function maxResults(int $value): self
    {
        $this->maxResults = $value;

        return $this;
    }
}
