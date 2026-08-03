<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Forms\Contracts;

use CraftCms\Cms\Cp\Forms\FormContext;

interface FormDefinition
{
    /** @return list<FormElement> */
    public function formElements(FormContext $context): array;
}
