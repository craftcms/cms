<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Contracts;

use CraftCms\Cms\Cp\FormDefinitions\Data\FormElementData;

interface FormElement
{
    public static function formElementType(): string;

    public static function isFormElementContainer(): bool;

    public function toFormElementData(): FormElementData;
}
