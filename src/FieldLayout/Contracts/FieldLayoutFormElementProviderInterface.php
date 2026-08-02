<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Contracts;

use CraftCms\Cms\Cp\Components\ViewComponent;
use CraftCms\Cms\Cp\Forms\Contracts\FormElement;
use CraftCms\Cms\FieldLayout\FieldLayoutFormElementContext;

interface FieldLayoutFormElementProviderInterface
{
    public function formElement(
        FieldLayoutFormElementContext $context,
    ): (ViewComponent&FormElement)|null;
}
