<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Conditions;

use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Conditions\HasDescendantsRule;
use CraftCms\Cms\Element\Conditions\LevelConditionRule;
use Override;

class EntryCondition extends ElementCondition
{
    #[Override]
    protected function selectableConditionRules(): array
    {
        return array_merge(parent::selectableConditionRules(), [
            AuthorConditionRule::class,
            AuthorGroupConditionRule::class,
            ExpiryDateConditionRule::class,
            HasDescendantsRule::class,
            LevelConditionRule::class,
            PostDateConditionRule::class,
            SavableConditionRule::class,
            SectionConditionRule::class,
            FieldConditionRule::class,
            TypeConditionRule::class,
            ViewableConditionRule::class,
        ]);
    }
}
