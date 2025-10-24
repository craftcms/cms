<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\Events\RegisterWidgetTypes;
use CraftCms\Cms\Plugin\Plugin;
use Illuminate\Support\Facades\Event;

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
        if (! $this->widgets) {
            return;
        }

        Event::listen(RegisterWidgetTypes::class, function (RegisterWidgetTypes $event) {
            $event->types->push(...$this->widgets);
        });
    }
}
