<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Conditions;

use CraftCms\Cms\Condition\BaseLightswitchConditionRule;
use CraftCms\Cms\Field\Conditions\Contracts\FieldConditionRuleInterface;
use CraftCms\Cms\Field\Lightswitch;
use RuntimeException;

class LightswitchFieldConditionRule extends BaseLightswitchConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    #[\Override]
    protected function inputHtml(): string
    {
        if (! $this->field() instanceof Lightswitch) {
            throw new RuntimeException;
        }

        return parent::inputHtml();
    }

    protected function elementQueryParam(): ?bool
    {
        if (! $this->field() instanceof Lightswitch) {
            return null;
        }

        return $this->value;
    }

    /** @param bool $value */
    protected function matchFieldValue(mixed $value): bool
    {
        if (! $this->field() instanceof Lightswitch) {
            return true;
        }

        return $this->matchValue($value);
    }
}
