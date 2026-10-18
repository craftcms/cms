<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static bool available(string $handle)
 * @method static \CraftCms\Cms\Ai\Data\ResolvedProvider resolve(string $handle)
 * @method static \CraftCms\Cms\Ai\TaskClient task(string $handle)
 *
 * @see \CraftCms\Cms\Ai\Ai
 */
class Ai extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Ai\Ai::class;
    }
}
