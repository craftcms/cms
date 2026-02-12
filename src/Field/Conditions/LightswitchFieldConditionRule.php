<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Conditions;

use CraftCms\Cms\Condition\BaseLightswitchConditionRule;
use CraftCms\Cms\Field\Conditions\Contracts\FieldConditionRuleInterface;
use CraftCms\Cms\Field\Lightswitch;
use yii\base\InvalidConfigException;

class LightswitchFieldConditionRule extends BaseLightswitchConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function inputHtml(): string
    {
        if (! $this->field() instanceof Lightswitch) {
            throw new InvalidConfigException;
        }

        return parent::inputHtml();
    }

    /**
     * {@inheritdoc}
     */
    protected function elementQueryParam(): ?bool
    {
        if (! $this->field() instanceof Lightswitch) {
            return null;
        }

        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    protected function matchFieldValue($value): bool
    {
        if (! $this->field() instanceof Lightswitch) {
            return true;
        }

        /** @var bool $value */
        return $this->matchValue($value);
    }
}
