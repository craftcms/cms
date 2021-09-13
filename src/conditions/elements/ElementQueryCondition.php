<?php

namespace craft\conditions\elements;

use Craft;
use craft\conditions\BaseQueryCondition;

/**
 * Base class for conditions designed for queries.
 *
 * @property-read string $addRuleLabel
 * @property-read string $builderHtml
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ElementQueryCondition extends BaseQueryCondition implements ElementQueryConditionInterface
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

    /**
     * @inheritdoc
     */
    protected function validateConditionRule($rule): bool
    {
        return parent::validateConditionRule($rule) && ($rule instanceof ElementQueryConditionRuleInterface);
    }
}
