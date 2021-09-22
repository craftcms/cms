<?php

namespace craft\conditions\elements;

use Craft;
use craft\conditions\BaseQueryCondition;
use craft\conditions\ConditionRuleInterface;

/**
 * Base class for conditions designed for queries.
 *
 * @property-read string $addRuleLabel
 * @property-read string $builderHtml
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ElementQueryCondition extends BaseQueryCondition
{
    /**
     * @inheritdoc
     */
    public function getAddRuleLabel(): string
    {
        return Craft::t('app', 'Add a filter');
    }

    /**
     * @inheritdoc
     */
    protected function conditionRuleTypes(): array
    {
        return [
            RelatedToConditionRule::class,
            SlugConditionRule::class,
            StatusConditionRule::class,
            TrashedConditionRule::class,
        ];
    }
}
