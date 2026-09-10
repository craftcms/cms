<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Nodes\Field;
use Override;

/**
 * BaseLightswitchConditionRule provides a base implementation for condition rules that are composed of a lightswitch input.
 */
abstract class BaseLightswitchConditionRule extends BaseConditionRule
{
    public bool $value = true;

    /** @return array<string, mixed> */
    #[Override]
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'value' => $this->value,
        ]);
    }

    /** @return list<Node> */
    #[Override]
    protected function inputNodes(): array
    {
        return [Field::make($this->getLabel(), Lightswitch::make('value')->value($this->value))];
    }

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'value' => ['boolean'],
        ]);
    }

    /**
     * Returns whether the condition rule matches the given value.
     */
    protected function matchValue(bool $value): bool
    {
        return $this->value === $value;
    }
}
