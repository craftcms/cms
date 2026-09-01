<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Conditions;

use CraftCms\Cms\Condition\BaseConditionRule;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Conditions\Contracts\FieldConditionRuleInterface;
use CraftCms\Cms\Field\Exceptions\InvalidFieldException;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use Override;
use RuntimeException;

class EmptyFieldConditionRule extends BaseConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    #[Override]
    public string $operator = self::OPERATOR_NOT_EMPTY;

    /** @return list<string> */
    #[Override]
    protected function operators(): array
    {
        return [
            self::OPERATOR_NOT_EMPTY,
            self::OPERATOR_EMPTY,
        ];
    }

    public function matchElement(ElementInterface $element): bool
    {
        try {
            $field = $this->field();
        } catch (RuntimeException) {
            // The field doesn't exist
            return true;
        }

        try {
            $value = $element->getFieldValue($field->handle);
        } catch (InvalidFieldException) {
            // The field doesn't belong to the element's field layout
            return false;
        }

        $isEmpty = $field->isValueEmpty($value, $element);

        if ($this->operator === self::OPERATOR_EMPTY) {
            return $isEmpty;
        }

        return ! $isEmpty;
    }

    protected function elementQueryParam(): int|string|null
    {
        return match ($this->operator) {
            self::OPERATOR_EMPTY => ':empty:',
            self::OPERATOR_NOT_EMPTY => 'not :empty:',
            default => throw new RuntimeException("Invalid operator: $this->operator"),
        };
    }

    protected function matchFieldValue(mixed $value): bool
    {
        throw new NotSupportedException;
    }
}
