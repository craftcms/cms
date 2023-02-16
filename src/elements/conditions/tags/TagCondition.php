<?php

namespace craft\elements\conditions\tags;

use craft\elements\conditions\ElementCondition;
use craft\elements\Tag;

/**
 * Tag query condition.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class TagCondition extends ElementCondition
{
    /**
     * @inheritdoc
     */
    public ?string $elementType = Tag::class;

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
