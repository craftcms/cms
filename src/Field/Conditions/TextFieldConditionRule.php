<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Conditions;

use CraftCms\Cms\Condition\BaseTextConditionRule;
use CraftCms\Cms\Field\Conditions\Contracts\FieldConditionRuleInterface;
use Stringable;

class TextFieldConditionRule extends BaseTextConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    /** @return array{value: list<string>|string, caseInsensitive: true}|null */
    protected function elementQueryParam(): ?array
    {
        $value = $this->paramValue();
        if ($value === null) {
            return null;
        }

        return [
            'value' => $this->paramValue(),
            'caseInsensitive' => true,
        ];
    }

    /** @param Stringable|string|null $value */
    protected function matchFieldValue(mixed $value): bool
    {
        return $this->matchValue($value);
    }
}
