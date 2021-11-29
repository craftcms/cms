<?php

namespace craft\conditions\elements\categories;

use craft\conditions\elements\ElementQueryCondition;

/**
 * Category query condition.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class CategoryQueryCondition extends ElementQueryCondition
{
    /**
     * @inheritdoc
     */
    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            GroupConditionRule::class,
        ]);
    }
}
