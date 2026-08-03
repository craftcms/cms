<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Cp\Components\ViewComponent;
use CraftCms\Cms\Cp\Forms\Contracts\FormElement;
use CraftCms\Cms\Cp\Forms\FormElementTypes;
use CraftCms\Cms\Plugin\Plugin;

/**
 * @mixin Plugin
 */
trait HasFormElementTypes
{
    /** @param class-string<ViewComponent&FormElement> ...$classes */
    public function registerFormElementTypes(string ...$classes): void
    {
        $this->app->make(FormElementTypes::class)->register(...$classes);
    }
}
