<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use function CraftCms\Cms\currentUser;

trait HintableConditionRuleTrait
{
    public function showLabelHint(): bool
    {
        return currentUser()?->getPreference('showFieldHandles') ?? false;
    }
}
