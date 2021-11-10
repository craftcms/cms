<?php

namespace craft\conditions;

use Craft;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\UrlHelper;

/**
 * BaseTextConditionRule provides a base implementation for condition rules that are composed of an operator menu and text input.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class BaseTextConditionRule extends BaseConditionRule
{
    public const OPERATOR_EQ = '=';
    public const OPERATOR_NE = '!=';
    public const OPERATOR_LT = '<';
    public const OPERATOR_LTE = '<=';
    public const OPERATOR_GT = '>';
    public const OPERATOR_GTE = '>=';
    public const OPERATOR_BW = 'bw';
    public const OPERATOR_EW = 'ew';
    public const OPERATOR_CONTAINS = '**';

    /**
     * @var string The selected operator.
     */
    public string $operator = self::OPERATOR_EQ;

    /**
     * @var string The input value.
     */
    public string $value = '';

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'operator' => $this->operator,
            'value' => $this->value,
        ]);
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
            self::OPERATOR_BW,
            self::OPERATOR_EW,
            self::OPERATOR_CONTAINS,
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
     * Returns the option label for a given operator.
     *
     * @param string $operator
     * @return string
     */
    protected function operatorLabel(string $operator): string
    {
        switch ($operator) {
            case self::OPERATOR_EQ:
                return Craft::t('app', 'equals');
            case self::OPERATOR_NE:
                return Craft::t('app', 'does not equal');
            case self::OPERATOR_LT:
                return Craft::t('app', 'is less than');
            case self::OPERATOR_LTE:
                return Craft::t('app', 'is less than or equals');
            case self::OPERATOR_GT:
                return Craft::t('app', 'is greater than');
            case self::OPERATOR_GTE:
                return Craft::t('app', 'is greater than or equals');
            case self::OPERATOR_BW:
                return Craft::t('app', 'begins with');
            case self::OPERATOR_EW:
                return Craft::t('app', 'ends with');
            case self::OPERATOR_CONTAINS:
                return Craft::t('app', 'contains');
            default:
                return $operator;
        }
    }

    /**
     * @inheritdoc
     */
    public function getHtml(array $options = []): string
    {
        return
            Html::beginTag('div', [
                'class' => ['flex', 'flex-nowrap'],
            ]) .
            Html::tag('div',
                Cp::selectHtml([
                    'name' => 'operator',
                    'value' => $this->operator,
                    'options' => array_map(function($operator) {
                        return ['value' => $operator, 'label' => $this->operatorLabel($operator)];
                    }, $this->operators()),
                ])
            ) .
            Html::tag('div',
                Cp::textHtml([
                    'type' => $this->inputType(),
                    'name' => 'value',
                    'value' => $this->value,
                    'autocomplete' => false,
                ])
            ) .
            Html::endTag('div');
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['operator'], 'in', 'range' => $this->operators()],
            [['value'], 'safe'],
        ]);
    }

    /**
     * Returns the rule’s value, prepped for [[Db::parseParam()]] based on the selected operator.
     *
     * @return string|null
     */
    protected function paramValue(): ?string
    {
        if ($this->value === '') {
            return null;
        }

        $value = Db::escapeParam($this->value);

        switch ($this->operator) {
            case self::OPERATOR_BW:
                return "$value*";
            case self::OPERATOR_EW:
                return "*$value";
            case self::OPERATOR_CONTAINS:
                return "*$value*";
            default:
                return "$this->operator $value";
        }
    }
}
