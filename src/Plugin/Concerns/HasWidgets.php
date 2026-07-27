<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\WidgetTypes;
use CraftCms\Cms\Plugin\Plugin;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasWidgets
{
    /**
     * Array of widget classes to register.
     *
     * @var class-string<WidgetInterface>[]
     */
    protected array $widgets = [];

    public function bootHasWidgets(): void
    {
        $this->app->make(WidgetTypes::class)->register(...$this->widgets);
    }
}
