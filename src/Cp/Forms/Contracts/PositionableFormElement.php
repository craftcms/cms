<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Forms\Contracts;

use Closure;
use CraftCms\Cms\Cp\Forms\Condition;

interface PositionableFormElement extends FormElement
{
    public function key(string|Closure|null $key): static;

    public function columnWidth(int|Closure|null $width): static;

    public function visibleWhen(Condition|Closure|null $condition): static;
}
