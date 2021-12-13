<?php

namespace craft\base\conditions;

use Craft;
use craft\helpers\Cp;
use craft\helpers\Html;

/**
 * BaseOperatorConditionRule provides a base implementation for condition rules that are composed of an operator menu and input.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class BaseOperatorConditionRule extends BaseConditionRule
{
    protected const OPERATOR_EQ = '=';
    protected const OPERATOR_NE = '!=';
    protected const OPERATOR_LT = '<';
    protected const OPERATOR_LTE = '<=';
    protected const OPERATOR_GT = '>';
    protected const OPERATOR_GTE = '>=';
    protected const OPERATOR_BEGINS_WITH = 'bw';
    protected const OPERATOR_ENDS_WITH = 'ew';
    protected const OPERATOR_CONTAINS = '**';
    protected const OPERATOR_IN = 'in';
    protected const OPERATOR_NOT_IN = 'ni';

    /**
     * @var string The selected operator.
     */
    public string $operator;

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'operator' => $this->operator,
        ]);
    }

    /**
     * Returns the operators that should be allowed for this rule.
     *
     * @return array
     */
    abstract protected function operators(): array;

    /**
     * Returns the input HTML.
     *
     * @return string
     */
    abstract protected function inputHtml(): string;

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
            case self::OPERATOR_BEGINS_WITH:
                return Craft::t('app', 'begins with');
            case self::OPERATOR_ENDS_WITH:
                return Craft::t('app', 'ends with');
            case self::OPERATOR_CONTAINS:
                return Craft::t('app', 'contains');
            case self::OPERATOR_IN:
                return Craft::t('app', 'is one of');
            case self::OPERATOR_NOT_IN:
                return Craft::t('app', 'is not one of');
            default:
                return $operator;
        }
    }

    /**
     * @inheritdoc
     */
    public function getHtml(array $options = []): string
    {
        $operators = $this->operators();

        return
            Html::beginTag('div', [
                'class' => ['flex', 'flex-nowrap'],
            ]) .
            (count($operators) > 1
                ? (
                    Html::hiddenLabel(Craft::t('app', 'Operator'), 'operator') .
                    Cp::selectHtml([
                        'id' => 'operator',
                        'name' => 'operator',
                        'value' => $this->operator,
                        'options' => array_map(function($operator) {
                            return ['value' => $operator, 'label' => $this->operatorLabel($operator)];
                        }, $operators),
                    ])
                )
                : Html::hiddenInput('operator', reset($operators))
            ) .
            $this->inputHtml() .
            Html::endTag('div');
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['operator'], 'in', 'range' => $this->operators()],
        ]);
    }
}
