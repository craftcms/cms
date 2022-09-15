<?php

namespace craft\base\conditions;

use Craft;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\Html;

/**
 * BaseNumberConditionRule provides a base implementation for condition rules that are composed of a number input.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class BaseNumberConditionRule extends BaseTextConditionRule
{
    protected const OPERATOR_BETWEEN = 'between';

    /**
     * @inheritdoc
     */
    protected bool $reloadOnOperatorChange = true;

    /**
     * @inheritdoc
     */
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
        ];
    }

    /**
     * @inerhitdoc
     */
    protected function operatorLabel(string $operator): string
    {
        if ($operator === self::OPERATOR_BETWEEN) {
            return Craft::t('app', 'Between…');
        }

        return parent::operatorLabel($operator);
    }

    /**
     * @inheritdoc
     */
    protected function inputType(): string
    {
        return 'number';
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(): string
    {
        if ($this->operator === self::OPERATOR_BETWEEN) {
            $this->value = empty($this->value) ? ['min' => 0, 'max' => 0] : $this->value;
            return Html::tag('div',
                Html::hiddenLabel(Craft::t('app', 'Min Value'), 'min') .
                Cp::textHtml([
                    'type' => $this->inputType(),
                    'id' => 'min',
                    'name' => 'value[min]',
                    'value' => $this->value['min'],
                    'autocomplete' => false,
                    'class' => 'flex-grow flex-shrink',
                ]) .
                Html::tag('span', Craft::t('app', 'and')) .
                Html::hiddenLabel(Craft::t('app', 'Max Value'), 'max') .
                Cp::textHtml([
                    'type' => $this->inputType(),
                    'id' => 'max',
                    'name' => 'value[max]',
                    'value' => $this->value['max'],
                    'autocomplete' => false,
                    'class' => 'flex-grow flex-shrink',
                ]) .
                Html::tag('span', Craft::t('app', 'Values entered are matched inclusively.'), ['class' => 'info']),
                ['class' => 'flex flex-center']
            );
        }

        return parent::inputHtml();
    }

    /**
     * @inheritdoc
     */
    protected function paramValue(): string|array|null
    {
        if ($this->operator === self::OPERATOR_BETWEEN && is_array($this->value)) {
            if (empty($this->value)) {
                return null;
            }

            array_walk($this->value, static fn($val) => Db::escapeParam($val));

            return ['and', '>= ' . $this->value['min'], '<= ' . $this->value['max']];
        }

        return parent::paramValue();
    }

    /**
     * @inheritdoc
     */
    protected function matchValue(mixed $value): bool
    {
        if ($this->operator === self::OPERATOR_BETWEEN && is_array($this->value)) {
            if (empty($this->value)) {
                return true;
            }

            return $value >= $this->value['min'] && $value <= $this->value['max'];
        }

        return parent::matchValue($value);
    }
}
