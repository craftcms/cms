<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Conditions;

use craft\elements\conditions\HasDescendantsRule;
use craft\elements\conditions\LevelConditionRule;
use CraftCms\Cms\Element\Conditions\ElementCondition;

/**
 * Entry query condition.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.0.0
 */
class EntryCondition extends ElementCondition
{
    #[\Override]
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
