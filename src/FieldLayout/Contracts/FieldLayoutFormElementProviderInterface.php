<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Contracts;

use CraftCms\Cms\Cp\Components\ViewComponent;
use CraftCms\Cms\Cp\FormDefinitions\Contracts\ProjectableFormElement;
use CraftCms\Cms\FieldLayout\FieldLayoutFormElementContext;

interface FieldLayoutFormElementProviderInterface
{
    public function formElement(
        FieldLayoutFormElementContext $context,
    ): (ViewComponent&ProjectableFormElement)|null;
}
