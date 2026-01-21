<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CraftCms\Cms\Support\Security
 */
final class Security extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Support\Security::class;
    }
}
