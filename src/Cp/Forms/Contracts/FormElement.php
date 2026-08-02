<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Forms\Contracts;

use CraftCms\Cms\Cp\Forms\Data\FormElementData;

interface FormElement
{
    public static function formElementType(): string;

    public static function isFormElementContainer(): bool;

    public function toFormElementData(): FormElementData;
}
