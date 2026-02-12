<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use craft\helpers\Cp;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Query;
use Override;
use yii\base\InvalidConfigException;

/**
 * BaseTextConditionRule provides a base implementation for condition rules that are composed of an operator menu and text input.
 */
abstract class BaseTextConditionRule extends BaseConditionRule
{
    /**
     * {@inheritdoc}
     */
    public string $operator = self::OPERATOR_EQ;

    /**
     * @var string The input value.
     */
    public string $value = '';

    /**
     * {@inheritdoc}
     */
    protected bool $reloadOnOperatorChange = true;

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'value' => $this->value,
        ]);
    }

    /**
     * Returns the operators that should be allowed for this rule.
     */
    #[Override]
    protected function operators(): array
    {
        return [
            self::OPERATOR_EQ,
            self::OPERATOR_BEGINS_WITH,
            self::OPERATOR_ENDS_WITH,
            self::OPERATOR_CONTAINS,
            self::OPERATOR_NOT_EMPTY,
            self::OPERATOR_EMPTY,
        ];
    }

    /**
     * Returns the input type that should be used.
     */
    protected function inputType(): string
    {
        return 'text';
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function inputHtml(): string
    {
        // don't show the value input if the condition checks for empty/notempty
        if ($this->operator === self::OPERATOR_EMPTY || $this->operator === self::OPERATOR_NOT_EMPTY) {
            return '';
        }

        return
            Html::hiddenLabel(Html::encode($this->getLabel()), 'value').
            Cp::textHtml($this->inputOptions());
    }

    /**
     * Returns the input options that should be used.
     */
    protected function inputOptions(): array
    {
        return [
            'type' => $this->inputType(),
            'id' => 'value',
            'name' => 'value',
            'value' => $this->value,
            'autocomplete' => false,
            'class' => 'flex-grow flex-shrink',
        ];
    }

    #[\Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'value' => ['nullable', 'string'],
        ]);
    }

    /**
     * Returns the rule’s value, prepped for {@see \CraftCms\Cms\Database\QueryParam::parse()} based on the selected operator.
     */
    protected function paramValue(): ?string
    {
        switch ($this->operator) {
            case self::OPERATOR_EMPTY:
                return ':empty:';
            case self::OPERATOR_NOT_EMPTY:
                return 'not :empty:';
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
            default => throw new InvalidConfigException("Invalid operator: $this->operator"),
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
