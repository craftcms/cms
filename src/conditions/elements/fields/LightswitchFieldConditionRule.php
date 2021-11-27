<?php

namespace craft\conditions\elements\fields;

use craft\conditions\BaseLightswitchConditionRule;

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
}
