<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Cp\Components\ViewComponent;
use CraftCms\Cms\Cp\FormDefinitions\Contracts\ProjectableFormElement;
use CraftCms\Cms\Cp\FormDefinitions\FormElementTypes;
use CraftCms\Cms\Plugin\Plugin;

/**
 * @mixin Plugin
 */
trait HasFormElementTypes
{
    /** @param class-string<ViewComponent&ProjectableFormElement> ...$classes */
    public function registerFormElementTypes(string ...$classes): void
    {
        $this->app->make(FormElementTypes::class)->registerForPlugin($this, ...$classes);
    }
}
