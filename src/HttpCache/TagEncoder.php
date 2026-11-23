<?php

declare(strict_types=1);

namespace CraftCms\Cms\HttpCache;

/**
 * Turns tags into the strings a cache stores.
 *
 * Response tag headers go through the bound encoder. Callers always purge the tags they added, unencoded, such as
 * `HttpCache::invalidator()->invalidateTags(['nav'])`; a client whose cache stores encoded tags encodes them with the
 * same encoder, so purges match what was sent. A driver that hashes or prefixes tags binds its own:
 *
 * ```php
 * $this->app->singleton(TagEncoder::class, MyTagEncoder::class);
 * ```
 *
 * @since 6.0.0
 */
interface TagEncoder
{
    /**
     * @param  list<string>  $tags
     * @return list<string>
     */
    public function encode(array $tags): array;
}
