<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use Closure;
use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
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

    /**
     * @return array<string, array{label:string, action:callable|string, info?:string, params?:array<string, mixed>}|Closure>
     */
    protected function getCacheOptions(): array
    {
        return [];
    }

    /**
     * @return array<string, string|Closure>
     */
    protected function getCacheTags(): array
    {
        return [];
    }

    public function bootHasUtilities(): void
    {
        $this->app->make(UtilityTypes::class)->register(...$this->utilities);

        foreach ($this->getCacheOptions() as $key => $option) {
            ClearCaches::add($key, $option);
        }

        foreach ($this->getCacheTags() as $tag => $label) {
            ClearCaches::addTag($tag, $label);
        }
    }
}
