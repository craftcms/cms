<?php

namespace craft\conditions\elements\fields;

use craft\conditions\BaseNumberConditionRule;

/**
 * Text field condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class NumberFieldConditionRule extends BaseNumberConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    /**
     * @inheritdoc
     */
    protected function elementQueryParam(): ?string
    {
        return $this->paramValue();
    }
}
