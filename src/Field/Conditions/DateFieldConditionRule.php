<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Conditions;

use CraftCms\Cms\Condition\BaseDateRangeConditionRule;
use CraftCms\Cms\Field\Conditions\Contracts\FieldConditionRuleInterface;
use CraftCms\Cms\Field\Date;
use DateTimeInterface;
use RuntimeException;

class DateFieldConditionRule extends BaseDateRangeConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    #[\Override]
    protected function inputHtml(): string
    {
        if (! $this->field() instanceof Date) {
            throw new RuntimeException;
        }

        return parent::inputHtml();
    }

    /** @return array<int, string>|string|null */
    protected function elementQueryParam(): array|string|null
    {
        if (! $this->field() instanceof Date) {
            return null;
        }

        return $this->queryParamValue();
    }

    /** @param DateTimeInterface|null $value */
    protected function matchFieldValue(mixed $value): bool
    {
        if (! $this->field() instanceof Date) {
            return true;
        }

        return $this->matchValue($value);
    }
}
