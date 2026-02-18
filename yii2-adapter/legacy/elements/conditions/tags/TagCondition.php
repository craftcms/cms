<?php

namespace craft\elements\conditions\tags;

use CraftCms\Cms\Element\Conditions\ElementCondition;

/**
 * Tag query condition.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated in 6.0.0
 */
class TagCondition extends ElementCondition
{
    /**
     * @inheritdoc
     */
    protected function selectableConditionRules(): array
    {
        return array_merge(parent::selectableConditionRules(), [
            GroupConditionRule::class,
        ]);
    }
}
