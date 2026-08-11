<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Conditions;

use CraftCms\Cms\Condition\BaseNumberConditionRule;
use CraftCms\Cms\Field\Conditions\Contracts\FieldConditionRuleInterface;
use Money\Money;

class NumberFieldConditionRule extends BaseNumberConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    /** @return list<string>|string|null */
    protected function elementQueryParam(): string|array|null
    {
        return $this->paramValue();
    }

    /** @param Money|float|int|null $value */
    protected function matchFieldValue(mixed $value): bool
    {
        if ($value instanceof Money) {
            $value = (float) $value->getAmount();
        }

        return $this->matchValue($value);
    }
}
