<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Contracts;

use CraftCms\Cms\Cp\FormDefinitions\Elements\FormElement;
use CraftCms\Cms\FieldLayout\FieldLayoutFormElementContext;

interface FieldLayoutFormElementProviderInterface
{
    public function formElement(FieldLayoutFormElementContext $context): ?FormElement;
}
