<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Database\QueryParam;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Query;
use Override;
use RuntimeException;

/**
 * BaseTextConditionRule provides a base implementation for condition rules that are composed of an operator menu and text input.
 */
abstract class BaseTextConditionRule extends BaseConditionRule
{
    #[Override]
    public string $operator = self::OPERATOR_EQ;

    /**
     * @var string The input value.
     */
    public string $value = '';

    #[Override]
    protected bool $reloadOnOperatorChange = true;

    /** @return array<string, mixed> */
    #[Override]
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'value' => $this->value,
        ]);
    }

    #[Override]
    public function __set(string $name, mixed $value): void
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
     * @return string[]
     */
    #[Override]
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
     */
    protected function inputType(): string
    {
        return 'text';
    }

    #[Override]
    protected function inputHtml(): string
    {
        // don't show the value input if the condition checks for empty/notempty
        if ($this->operator === self::OPERATOR_EMPTY || $this->operator === self::OPERATOR_NOT_EMPTY) {
            return '';
        }

        if (in_array($this->operator, [self::OPERATOR_IN, self::OPERATOR_NOT_IN])) {
            return FormFields::selectizeHtml($this->inputOptions());
        }

        return
            Html::hiddenLabel(Html::encode($this->getLabel()), 'value').
            FormFields::textHtml($this->inputOptions());
    }

    /**
     * Returns the input options that should be used.
     *
     * @return array<string, mixed>
     */
    protected function inputOptions(): array
    {
        $defaults = [
            'id' => 'value'.mt_rand(),
            'name' => 'value',
            'class' => 'cp:flex-grow cp:flex-shrink',
        ];

        if (in_array($this->operator, [self::OPERATOR_IN, self::OPERATOR_NOT_IN])) {
            $values = Json::decodeIfJson($this->value);
            $values = is_array($values) ? array_values($values) : [];

            return [...$defaults, ...[
                'values' => $values,
                'options' => array_map(fn ($v) => ['value' => $v, 'label' => $v], $values),
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

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'value' => ['nullable', 'string'],
        ]);
    }

    /**
     * Returns the rule’s value, prepped for {@see QueryParam::parse()} based on the selected operator.
     *
     * @return string|array<mixed>|null
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
                array_unshift($value, 'not');

                return $value;
        }

        if ($this->value === '') {
            return null;
        }

        $value = Query::escapeParam($this->value);

        return match ($this->operator) {
            self::OPERATOR_BEGINS_WITH => "$value*",
            self::OPERATOR_ENDS_WITH => "*$value",
            self::OPERATOR_CONTAINS => "*$value*",
            default => "$this->operator $value",
        };
    }

    /**
     * Returns whether the condition rule matches the given value.
     */
    protected function matchValue(mixed $value): bool
    {
        switch ($this->operator) {
            case self::OPERATOR_EMPTY:
                return $this->isEmpty($value);
            case self::OPERATOR_NOT_EMPTY:
                return ! $this->isEmpty($value);
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
            self::OPERATOR_BEGINS_WITH => is_string($value) && str_starts_with(mb_strtolower($value), mb_strtolower($this->value)),
            self::OPERATOR_ENDS_WITH => is_string($value) && str_ends_with(mb_strtolower($value), mb_strtolower($this->value)),
            self::OPERATOR_CONTAINS => is_string($value) && str_contains(mb_strtolower($value), mb_strtolower($this->value)),
            self::OPERATOR_IN => in_array($value, Json::decodeIfJson($this->value)),
            self::OPERATOR_NOT_IN => ! in_array($value, Json::decodeIfJson($this->value)),
            default => throw new RuntimeException("Invalid operator: $this->operator"),
        };
    }

    /**
     * Returns whether the given value should be considered empty.
     */
    protected function isEmpty(mixed $value): bool
    {
        return ! $value;
    }
}
