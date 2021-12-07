<?php

namespace craft\conditions\elements\tags;

use craft\conditions\elements\ElementQueryCondition;

/**
 * Tag query condition.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class TagQueryCondition extends ElementQueryCondition
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
