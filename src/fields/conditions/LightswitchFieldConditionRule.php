<?php

namespace craft\fields\conditions;

use craft\base\conditions\BaseLightswitchConditionRule;

/**
 * Lightswitch field condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class LightswitchFieldConditionRule extends BaseLightswitchConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    /**
     * @inheritdoc
     */
    protected function elementQueryParam(): bool
    {
        return $this->value;
    }

    /**
     * @inheritdoc
     */
    protected function matchFieldValue($value): bool
    {
        /** @var bool $value */
        return $this->matchValue($value);
    }
}
