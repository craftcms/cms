<?php

namespace craft\elements\conditions\categories;

use craft\elements\Category;
use craft\elements\conditions\ElementCondition;
use craft\elements\conditions\LevelConditionRule;

/**
 * Category query condition.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class CategoryCondition extends ElementCondition
{
    /**
     * @inheritdoc
     */
    public ?string $elementType = Category::class;

    /**
     * @inheritdoc
     */
    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            GroupConditionRule::class,
            LevelConditionRule::class,
        ]);
    }
}
