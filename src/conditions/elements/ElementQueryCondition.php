<?php

namespace craft\conditions\elements;

use Craft;
use craft\conditions\BaseCondition;

/**
 * Base class for conditions designed for queries.
 *
 * @property-read string $addRuleLabel
 * @property-read string $builderHtml
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class ElementQueryCondition extends BaseCondition implements ElementQueryConditionInterface
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
    protected function validateConditionRule($rule): bool
    {
        return parent::validateConditionRule($rule) && ($rule instanceof ElementQueryConditionRuleInterface);
    }
}
