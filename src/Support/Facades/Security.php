<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isSensitive(string $key)
 * @method static mixed redactIfSensitive(string $key, mixed $value)
 * @method static bool isSystemDir(string $path)
 *
 * @see \CraftCms\Cms\Support\Security
 */
class Security extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Support\Security::class;
    }
}
