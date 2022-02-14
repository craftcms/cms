<?php

namespace craft\base\conditions;

use Craft;
use craft\base\Component;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;

/**
 * BaseConditionRule provides a base implementation for condition rules.
 *
 * @property bool $isNew Whether the rule is new
 * @property ConditionInterface $condition
 * @property-read array $config The rule’s portable config
 * @property-read string $html The rule’s HTML for a condition builder
 * @property-read string $uiLabel The rule’s option label
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class BaseConditionRule extends Component implements ConditionRuleInterface
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
    protected const OPERATOR_EMPTY = 'empty';
    protected const OPERATOR_NOT_EMPTY = 'notempty';

    /**
     * @inheritdoc
     */
    public static function supportsProjectConfig(): bool
    {
        return true;
    }

    /**
     * @var string UUID
     */
    public string $uid;

    /**
     * @var string The selected operator.
     */
    public string $operator;

    /**
     * @var bool Whether to reload the condition builder when the operator changes
     */
    protected bool $reloadOnOperatorChange = false;

    /**
     * @var ConditionInterface
     */
    private ConditionInterface $_condition;

    /**
     * @var bool
     * @see getAutofocus()
     * @see setAutofocus()
     */
    private bool $_autofocus = false;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->uid)) {
            $this->uid = StringHelper::UUID();
        }
    }

    /**
     * @inheritdoc
     */
    public function getCondition(): ConditionInterface
    {
        return $this->_condition;
    }

    /**
     * @inheritdoc
     */
    public function setCondition(ConditionInterface $condition): void
    {
        $this->_condition = $condition;
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        $config = [
            'class' => get_class($this),
            'uid' => $this->uid,
        ];

        if (!empty($this->operators())) {
            $config['operator'] = $this->operator;
        }

        return $config;
    }

    /**
     * Returns the operators that should be allowed for this rule.
     *
     * @return array
     */
    protected function operators(): array
    {
        return [];
    }

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
            case self::OPERATOR_EMPTY:
                return Craft::t('app', 'is empty');
            case self::OPERATOR_NOT_EMPTY:
                return Craft::t('app', 'has a value');
            default:
                return $operator;
        }
    }

    /**
     * @inheritdoc
     */
    public function getHtml(): string
    {
        $operators = $this->operators();

        return
            Html::beginTag('div', [
                'class' => ['flex', 'flex-nowrap', 'flex-start'],
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
                        'inputAttributes' => [
                            'hx' => [
                                'post' => $this->reloadOnOperatorChange ? UrlHelper::actionUrl('conditions/render') : false,
                            ],
                        ],
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
        return [
            [['uid'], 'safe'],
            [
                ['operator'],
                function() {
                    return in_array($this->operator, $this->operators(), true);
                },
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAutofocus(): bool
    {
        return $this->_autofocus;
    }

    /**
     * @inheritdoc
     */
    public function setAutofocus(bool $autofocus = true): void
    {
        $this->_autofocus = $autofocus;
    }
}
