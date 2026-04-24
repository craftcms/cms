<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Conditions;

use CraftCms\Cms\Condition\BaseDateRangeConditionRule;
use CraftCms\Cms\Field\Conditions\Contracts\FieldConditionRuleInterface;
use CraftCms\Cms\Field\Date;
use DateTime;
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

    protected function elementQueryParam(): array|string|null
    {
        if (! $this->field() instanceof Date) {
            return null;
        }

        return $this->queryParamValue();
    }

    protected function matchFieldValue($value): bool
    {
        if (! $this->field() instanceof Date) {
            return true;
        }

        /** @var DateTime|null $value */
        return $this->matchValue($value);
    }
}
