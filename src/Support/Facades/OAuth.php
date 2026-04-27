<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static bool isAvailable()
 * @method static array getLoginButtons(?bool $isCpRequest = null)
 *
 * @see \CraftCms\Cms\Auth\OAuth\OAuth
 */
class OAuth extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Auth\OAuth\OAuth::class;
    }
}
