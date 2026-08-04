<?php

namespace craft\base\conditions;

use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use yii\base\InvalidConfigException;

/**
 * BaseTextConditionRule provides a base implementation for condition rules that are composed of an operator menu and text input.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class BaseTextConditionRule extends BaseConditionRule
{
    /**
     * @inheritdoc
     */
    public string $operator = self::OPERATOR_EQ;

    /**
     * @var string The input value.
     */
    public string $value = '';

    /**
     * @inheritdoc
     */
    protected bool $reloadOnOperatorChange = true;

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'value' => $this->value,
        ]);
    }

    public function __set($name, $value)
    {
        if (
            $name === 'attributes' &&
            isset($value['operator'], $value['value']) &&
            in_array($value['operator'], [self::OPERATOR_IN, self::OPERATOR_NOT_IN]) &&
            is_array($value['value'])
        ) {
            $value['value'] = Json::encode($value['value']);
        }

        parent::__set($name, $value);
    }

    /**
     * Returns the operators that should be allowed for this rule.
     *
     * @return array
     */
    protected function operators(): array
    {
        return [
            self::OPERATOR_EQ,
            self::OPERATOR_NE,
            self::OPERATOR_BEGINS_WITH,
            self::OPERATOR_ENDS_WITH,
            self::OPERATOR_CONTAINS,
            self::OPERATOR_NOT_EMPTY,
            self::OPERATOR_EMPTY,
            self::OPERATOR_IN,
            self::OPERATOR_NOT_IN,
        ];
    }

    /**
     * Returns the input type that should be used.
     *
     * @return string
     */
    protected function inputType(): string
    {
        return 'text';
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(): string
    {
        // don't show the value input if the condition checks for empty/notempty
        if ($this->operator === self::OPERATOR_EMPTY || $this->operator === self::OPERATOR_NOT_EMPTY) {
            return '';
        }

        if (in_array($this->operator, [self::OPERATOR_IN, self::OPERATOR_NOT_IN])) {
            return Cp::selectizeHtml($this->inputOptions());
        }

        return
            Html::hiddenLabel(Html::encode($this->getLabel()), 'value') .
            Cp::textHtml($this->inputOptions());
    }

    /**
     * Returns the input options that should be used.
     *
     * @return array
     * @since 4.3.0
     */
    protected function inputOptions(): array
    {
        $defaults = [
            'id' => 'value' . mt_rand(),
            'name' => 'value',
            'class' => 'flex-grow flex-shrink',
        ];

        if (in_array($this->operator, [self::OPERATOR_IN, self::OPERATOR_NOT_IN])) {
            $values = Json::decodeIfJson($this->value);
            $values = is_array($values) ? array_values($values) : [];

            return [...$defaults, ...[
                'values' => $values,
                'options' => array_map(fn($v) => ['value' => $v, 'label' => $v], $values),
                'multi' => true,
                'allowEmptyOption' => true,
                'selectizeOptions' => [
                    'create' => true,
                    'persist' => false,
                    'createOnBlur' => true,
                ],
            ]];
        }

        return [
            ...$defaults,
            ...[
                'type' => $this->inputType(),
                'value' => $this->value,
                'autocomplete' => false,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['value'], 'safe'],
        ]);
    }

    /**
     * Returns the rule’s value, prepped for [[Db::parseParam()]] based on the selected operator.
     *
     * @return array|string|null
     */
    protected function paramValue(): string|array|null
    {
        switch ($this->operator) {
            case self::OPERATOR_EMPTY:
                return ':empty:';
            case self::OPERATOR_NOT_EMPTY:
                return 'not :empty:';
            case self::OPERATOR_IN:
                return Json::decodeIfJson($this->value);
            case self::OPERATOR_NOT_IN:
                $value = Json::decodeIfJson($this->value);
                $value = is_array($value) ? $value : [];
                ArrayHelper::prependOrAppend($value, 'not', true);

                return $value;
        }

        if ($this->value === '') {
            return null;
        }

        $value = Db::escapeParam($this->value);

        return match ($this->operator) {
            self::OPERATOR_BEGINS_WITH => "$value*",
            self::OPERATOR_ENDS_WITH => "*$value",
            self::OPERATOR_CONTAINS => "*$value*",
            default => "$this->operator $value",
        };
    }

    /**
     * Returns whether the condition rule matches the given value.
     *
     * @param mixed $value
     * @return bool
     */
    protected function matchValue(mixed $value): bool
    {
        switch ($this->operator) {
            case self::OPERATOR_EMPTY:
                return $this->isEmpty($value);
            case self::OPERATOR_NOT_EMPTY:
                return !$this->isEmpty($value);
        }

        if ($this->value === '') {
            return true;
        }

        return match ($this->operator) {
            self::OPERATOR_EQ => $value == $this->value,
            self::OPERATOR_NE => $value != $this->value,
            self::OPERATOR_LT => $value < $this->value,
            self::OPERATOR_LTE => $value <= $this->value,
            self::OPERATOR_GT => $value > $this->value,
            self::OPERATOR_GTE => $value >= $this->value,
            self::OPERATOR_BEGINS_WITH => is_string($value) && StringHelper::startsWith($value, $this->value, false),
            self::OPERATOR_ENDS_WITH => is_string($value) && StringHelper::endsWith($value, $this->value, false),
            self::OPERATOR_CONTAINS => is_string($value) && StringHelper::contains($value, $this->value, false),
            self::OPERATOR_IN => in_array($value, Json::decodeIfJson($this->value)),
            self::OPERATOR_NOT_IN => !in_array($value, Json::decodeIfJson($this->value)),
            default => throw new InvalidConfigException("Invalid operator: $this->operator"),
        };
    }

    /**
     * Returns whether the given value should be considered empty.
     *
     * @param mixed $value
     * @return bool
     * @since 5.6.11
     */
    protected function isEmpty(mixed $value): bool
    {
        return !$value;
    }
}
