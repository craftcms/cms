<?php

declare(strict_types=1);

namespace CraftCms\Cms\HttpCache;

/**
 * Turns tags into the strings a cache stores.
 *
 * Response tag headers and tag purges both go through the bound encoder, so purges match what was sent.
 * A driver that hashes or prefixes tags binds its own:
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
