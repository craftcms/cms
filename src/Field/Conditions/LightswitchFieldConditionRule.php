<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Conditions;

use CraftCms\Cms\Condition\BaseLightswitchConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Field\Conditions\Contracts\FieldConditionRuleInterface;
use CraftCms\Cms\Field\Lightswitch;
use CraftCms\Cms\Form\Contracts\Node;
use RuntimeException;

class LightswitchFieldConditionRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface, FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    /** @return list<Node> */
    #[\Override]
    protected function inputNodes(): array
    {
        if (! $this->field() instanceof Lightswitch) {
            throw new RuntimeException;
        }

        return parent::inputNodes();
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
