<?php

declare(strict_types=1);

namespace CraftCms\Cms\HttpCache;

/**
 * Sends tags unchanged.
 *
 * @since 6.0.0
 */
class DefaultTagEncoder implements TagEncoder
{
    public function encode(array $tags): array
    {
        return $tags;
    }
}
