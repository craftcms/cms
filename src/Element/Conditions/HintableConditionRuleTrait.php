<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use Illuminate\Support\Facades\Auth;

trait HintableConditionRuleTrait
{
    public function showLabelHint(): bool
    {
        return Auth::user()?->getPreference('showFieldHandles') ?? false;
    }
}
