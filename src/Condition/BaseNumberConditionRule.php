<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use craft\helpers\Cp;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Query;
use Override;

use function CraftCms\Cms\t;

/**
 * BaseNumberConditionRule provides a base implementation for condition rules that are composed of a number input.
 */
abstract class BaseNumberConditionRule extends BaseTextConditionRule
{
    protected const OPERATOR_BETWEEN = 'between';

    public string $maxValue = '';

    /**
     * @var int|float|null The `step` value the input should have.
     */
    public int|float|null $step = 1;

    #[Override]
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'maxValue' => $this->maxValue,
            'step' => $this->step,
        ]);
    }

    #[Override]
    protected function operators(): array
    {
        return [
            self::OPERATOR_EQ,
            self::OPERATOR_NE,
            self::OPERATOR_LT,
            self::OPERATOR_LTE,
            self::OPERATOR_GT,
            self::OPERATOR_GTE,
            self::OPERATOR_BETWEEN,
            self::OPERATOR_NOT_EMPTY,
            self::OPERATOR_EMPTY,
        ];
    }

    #[Override]
    protected function operatorLabel(string $operator): string
    {
        if ($operator === self::OPERATOR_BETWEEN) {
            return t('is between…');
        }

        return parent::operatorLabel($operator);
    }

    #[Override]
    protected function inputType(): string
    {
        return 'number';
    }

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'maxValue' => ['nullable', 'numeric'],
            'step' => ['nullable', 'numeric'],
        ]);
    }

    #[Override]
    protected function inputHtml(): string
    {
        if ($this->operator !== self::OPERATOR_BETWEEN) {
            return parent::inputHtml();
        }

        return Html::tag('div',
            Html::hiddenLabel(t('Min Value'), 'min').
            Cp::textHtml([
                'type' => $this->inputType(),
                'id' => 'min',
                'name' => 'value',
                'value' => $this->value,
                'autocomplete' => false,
                'class' => 'flex-grow flex-shrink',
            ]).
            Html::tag('span', t('and')).
            Html::hiddenLabel(t('Max Value'), 'max').
            Cp::textHtml([
                'type' => $this->inputType(),
                'id' => 'max',
                'name' => 'maxValue',
                'value' => $this->maxValue,
                'autocomplete' => false,
                'class' => 'flex-grow flex-shrink',
            ]).
            Html::tag('span', t('The values are matched inclusively.'), ['class' => 'info']),
            ['class' => 'flex flex-center']
        );
    }

    #[Override]
    protected function paramValue(): ?string
    {
        if ($this->operator !== self::OPERATOR_BETWEEN) {
            return parent::paramValue();
        }

        if (empty($this->value) && empty($this->maxValue)) {
            return null;
        }

        if (empty($this->maxValue)) {
            return '>= '.Query::escapeParam($this->value);
        }

        if (empty($this->value)) {
            return '<= '.Query::escapeParam($this->maxValue);
        }

        return sprintf('and, >= %s, <= %s', Query::escapeParam($this->value), Query::escapeParam($this->maxValue));
    }

    #[Override]
    protected function matchValue(mixed $value): bool
    {
        if ($this->operator !== self::OPERATOR_BETWEEN) {
            return parent::matchValue($value);
        }

        if (empty($this->value) && empty($this->maxValue)) {
            return true;
        }

        if (! empty($this->value) && $value < $this->value) {
            return false;
        }

        if (! empty($this->maxValue) && $value > $this->maxValue) {
            return false;
        }

        return true;
    }

    #[Override]
    protected function inputOptions(): array
    {
        return array_merge(parent::inputOptions(), [
            'step' => $this->step ?? 'any',
        ]);
    }
}
