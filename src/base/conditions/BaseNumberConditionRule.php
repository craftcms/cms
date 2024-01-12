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
    /**
     * @since 4.3.0
     */
    protected const OPERATOR_BETWEEN = 'between';

    /**
     * @var string
     * @since 4.3.0
     */
    public string $maxValue = '';

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'maxValue' => $this->maxValue,
        ]);
    }

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
            self::OPERATOR_NOT_EMPTY,
            self::OPERATOR_EMPTY,
        ];
    }

    /**
     * @inerhitdoc
     */
    protected function operatorLabel(string $operator): string
    {
        if ($operator === self::OPERATOR_BETWEEN) {
            return Craft::t('app', 'is between…');
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
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['maxValue'], 'safe'],
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(): string
    {
        if ($this->operator === self::OPERATOR_BETWEEN) {
            return Html::tag('div',
                Html::hiddenLabel(Craft::t('app', 'Min Value'), 'min') .
                Cp::textHtml([
                    'type' => $this->inputType(),
                    'id' => 'min',
                    'name' => 'value',
                    'value' => $this->value,
                    'autocomplete' => false,
                    'class' => 'flex-grow flex-shrink',
                ]) .
                Html::tag('span', Craft::t('app', 'and')) .
                Html::hiddenLabel(Craft::t('app', 'Max Value'), 'max') .
                Cp::textHtml([
                    'type' => $this->inputType(),
                    'id' => 'max',
                    'name' => 'maxValue',
                    'value' => $this->maxValue,
                    'autocomplete' => false,
                    'class' => 'flex-grow flex-shrink',
                ]) .
                Html::tag('span', Craft::t('app', 'The values are matched inclusively.'), ['class' => 'info']),
                ['class' => 'flex flex-center']
            );
        }

        return parent::inputHtml();
    }

    /**
     * @inheritdoc
     */
    protected function paramValue(): ?string
    {
        if ($this->operator === self::OPERATOR_BETWEEN) {
            if (empty($this->value) && empty($this->maxValue)) {
                return null;
            }

            if (empty($this->maxValue)) {
                return '>= ' . Db::escapeParam($this->value);
            }

            if (empty($this->value)) {
                return '<= ' . Db::escapeParam($this->maxValue);
            }

            return sprintf('and, >= %s, <= %s', Db::escapeParam($this->value), Db::escapeParam($this->maxValue));
        }

        return parent::paramValue();
    }

    /**
     * @inheritdoc
     */
    protected function matchValue(mixed $value): bool
    {
        if ($this->operator === self::OPERATOR_BETWEEN) {
            if (empty($this->value) && empty($this->maxValue)) {
                return true;
            }

            if (!empty($this->value) && $value < $this->value) {
                return false;
            }

            if (!empty($this->maxValue) && $value > $this->maxValue) {
                return false;
            }

            return true;
        }

        return parent::matchValue($value);
    }

    /**
     * @inheritdoc
     */
    protected function inputOptions(): array
    {
        return array_merge(parent::inputOptions(), [
            'step' => '1',
        ]);
    }
}
