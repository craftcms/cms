<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use craft\i18n\Formatter;
use DateTime;
use yii\base\InvalidConfigException;
use yii\base\InvalidValueException;
use yii\validators\Validator;

/**
 * Class DateCompareValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.21
 */
class DateCompareValidator extends Validator
{
    /**
     * @var string|null the name of the attribute to be compared with. When both this property
     * and [[compareValue]] are set, the latter takes precedence.
     * @see compareValue
     */
    public ?string $compareAttribute = null;

    /**
     * @var DateTime|callable|null the constant value to be compared with. When both this property
     * and [[compareAttribute]] are set, this property takes precedence.
     * @see compareAttribute
     */
    public $compareValue;

    /**
     * @var string the operator for comparison. The following operators are supported:
     *
     * - `==`: check if two values are equal. The comparison is done is non-strict mode.
     * - `!=`: check if two values are NOT equal. The comparison is done is non-strict mode.
     * - `>`: check if value being validated is greater than the value being compared with.
     * - `>=`: check if value being validated is greater than or equal to the value being compared with.
     * - `<`: check if value being validated is less than the value being compared with.
     * - `<=`: check if value being validated is less than or equal to the value being compared with.
     */
    public string $operator = '==';

    /**
     * @var string|null the user-defined error message. It may contain the following placeholders which
     * will be replaced accordingly by the validator:
     *
     * - `{attribute}`: the label of the attribute being validated
     * - `{value}`: the value of the attribute being validated
     * - `{compareValue}`: the value or the attribute label to be compared with
     * - `{compareAttribute}`: the label of the attribute to be compared with
     * - `{compareValueOrAttribute}`: the value or the attribute label to be compared with
     */
    public $message;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->message)) {
            $this->message = match ($this->operator) {
                '==' => Craft::t('yii', '{attribute} must be equal to "{compareValueOrAttribute}".'),
                '!=' => Craft::t('yii', '{attribute} must not be equal to "{compareValueOrAttribute}".'),
                '>' => Craft::t('yii', '{attribute} must be greater than "{compareValueOrAttribute}".'),
                '>=' => Craft::t('yii', '{attribute} must be greater than or equal to "{compareValueOrAttribute}".'),
                '<' => Craft::t('yii', '{attribute} must be less than "{compareValueOrAttribute}".'),
                '<=' => Craft::t('yii', '{attribute} must be less than or equal to "{compareValueOrAttribute}".'),
                default => throw new InvalidConfigException("Unknown operator: $this->operator"),
            };
        }
    }

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute): void
    {
        if (!isset($this->compareValue) && !isset($this->compareAttribute)) {
            throw new InvalidConfigException('DateCompareValidator::compareValue or compareAttribute must be set.');
        }

        $value = $model->$attribute;

        if (isset($this->compareValue)) {
            if (is_callable($this->compareValue)) {
                $this->compareValue = call_user_func($this->compareValue);
            }
            $compareValue = $this->compareValue;
        } else {
            $compareValue = $model->{$this->compareAttribute};
            $compareLabel = $compareValueOrAttribute = $model->getAttributeLabel($this->compareAttribute);
        }

        if (!$value instanceof DateTime || !$compareValue instanceof DateTime) {
            throw new InvalidValueException('DateCompareValidator expects both values to be DateTime objects.');
        }

        if (!$this->compareValues($this->operator, $value, $compareValue)) {
            $formattedCompareValue = Craft::$app->getFormatter()->asDatetime($compareValue, Formatter::FORMAT_WIDTH_SHORT);
            $this->addError($model, $attribute, $this->message, [
                'compareAttribute' => $compareLabel ?? $formattedCompareValue,
                'compareValue' => $formattedCompareValue,
                'compareValueOrAttribute' => $compareValueOrAttribute ?? $formattedCompareValue,
            ]);
        }
    }

    /**
     * @inheritdoc
     */
    protected function validateValue($value): ?array
    {
        if (!isset($this->compareValue)) {
            throw new InvalidConfigException('CompareValidator::compareValue must be set.');
        }

        if (is_callable($this->compareValue)) {
            $this->compareValue = call_user_func($this->compareValue);
        }

        if (!$value instanceof DateTime || !$this->compareValue instanceof DateTime) {
            throw new InvalidValueException('DateCompareValidator expects both values to be DateTime objects.');
        }

        if (!$this->compareValues($this->operator, $value, $this->compareValue)) {
            $formattedCompareValue = Craft::$app->getFormatter()->asDatetime($this->compareValue, Formatter::FORMAT_WIDTH_SHORT);
            return [
                $this->message, [
                    'compareAttribute' => $formattedCompareValue,
                    'compareValue' => $formattedCompareValue,
                    'compareValueOrAttribute' => $formattedCompareValue,
                ],
            ];
        }

        return null;
    }

    /**
     * Compares two values with the specified operator.
     *
     * @param string $operator the comparison operator
     * @param DateTime $value the value being compared
     * @param DateTime $compareValue another value being compared
     * @return bool whether the comparison using the specified operator is true.
     */
    protected function compareValues(string $operator, DateTime $value, DateTime $compareValue): bool
    {
        return match ($operator) {
            '==' => $value == $compareValue,
            '!=' => $value != $compareValue,
            '>' => $value > $compareValue,
            '>=' => $value >= $compareValue,
            '<' => $value < $compareValue,
            '<=' => $value <= $compareValue,
            default => false,
        };
    }
}
