<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Conditions;

use CraftCms\Cms\Condition\BaseNumberConditionRule;
use CraftCms\Cms\Field\Conditions\Contracts\FieldConditionRuleInterface;
use Money\Money;

class NumberFieldConditionRule extends BaseNumberConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    /**
     * {@inheritdoc}
     */
    protected function elementQueryParam(): ?string
    {
        return $this->paramValue();
    }

    /**
     * {@inheritdoc}
     */
    protected function matchFieldValue($value): bool
    {
        if ($value instanceof Money) {
            $value = (float) $value->getAmount();
        }

        /** @var int|float|null $value */
        return $this->matchValue($value);
    }
}
