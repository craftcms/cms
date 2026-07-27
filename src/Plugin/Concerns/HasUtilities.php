<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Utility\Utility;
use CraftCms\Cms\Utility\UtilityTypes;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasUtilities
{
    /**
     * Array of utility classes to register.
     *
     * @var class-string<Utility>[]
     */
    protected array $utilities = [];

    public function bootHasUtilities(): void
    {
        $this->app->make(UtilityTypes::class)->register(...$this->utilities);
    }
}
