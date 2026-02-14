<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CraftCms\Cms\View\InputNamespace
 */
final class InputNamespace extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\View\InputNamespace::class;
    }
}
