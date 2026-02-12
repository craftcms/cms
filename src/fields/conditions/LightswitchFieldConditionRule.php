<?php

namespace craft\fields\conditions;

use craft\base\conditions\BaseLightswitchConditionRule;
use craft\fields\Lightswitch;
use yii\base\InvalidConfigException;

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
    protected function inputHtml(): string
    {
        if (!$this->field() instanceof Lightswitch) {
            throw new InvalidConfigException();
        }

        return parent::inputHtml();
    }

    /**
     * @inheritdoc
     */
    protected function elementQueryParam(): ?bool
    {
        if (!$this->field() instanceof Lightswitch) {
            return null;
        }

        return $this->value;
    }

    /**
     * @inheritdoc
     */
    protected function matchFieldValue($value): bool
    {
        if (!$this->field() instanceof Lightswitch) {
            return true;
        }

        /** @var bool $value */
        return $this->matchValue($value);
    }
}
