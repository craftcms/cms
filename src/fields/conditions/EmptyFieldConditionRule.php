<?php

namespace craft\fields\conditions;

use craft\base\conditions\BaseConditionRule;
use craft\base\ElementInterface;
use craft\errors\InvalidFieldException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;

/**
 * Empty/not-empty field condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.2.0
 */
class EmptyFieldConditionRule extends BaseConditionRule implements FieldConditionRuleInterface
{
    use FieldConditionRuleTrait;

    /**
     * @inheritdoc
     */
    public string $operator = self::OPERATOR_NOT_EMPTY;

    /**
     * @inheritdoc
     */
    protected function operators(): array
    {
        return [
            self::OPERATOR_NOT_EMPTY,
            self::OPERATOR_EMPTY,
        ];
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        try {
            $field = $this->field();
        } catch (InvalidConfigException) {
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

        return !$isEmpty;
    }

    /**
     * @inheritdoc
     */
    protected function elementQueryParam(): int|string|null
    {
        return match ($this->operator) {
            self::OPERATOR_EMPTY => ':empty:',
            self::OPERATOR_NOT_EMPTY => 'not :empty:',
            default => throw new InvalidConfigException("Invalid operator: $this->operator"),
        };
    }

    /**
     * @inheritdoc
     */
    protected function matchFieldValue($value): bool
    {
        throw new NotSupportedException();
    }
}
