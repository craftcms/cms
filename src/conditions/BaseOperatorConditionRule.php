<?php

namespace craft\conditions;

use Craft;
use craft\helpers\Cp;
use craft\helpers\Html;

/**
 * The BaseValueConditionRule has a single `value` attribute that stores the value entered.
 *
 * @property-read array $config
 * @property-read array $operators
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class BaseOperatorConditionRule extends BaseConditionRule
{
    public const OPERATOR_EQUALS = '=';

    /**
     * @var bool
     */
    protected bool $showOperator = false;

    /**
     * @var string
     */
    public string $operator = self::OPERATOR_EQUALS;

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
     * @return array
     */
    protected function operators(): array
    {
        return [
            self::OPERATOR_EQUALS => Craft::t('app', 'equals')
        ];
    }

    /**
     * @inheritdoc
     */
    public function getHtml(array $options = []): string
    {
        if (!$this->showOperator) {
            return '';
        }

        return Html::tag('div',
            Cp::selectHtml([
                'name' => 'operator',
                'value' => $this->operator,
                'options' => $this->operators()
            ])
        );
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['operator'], 'safe'],
        ]);
    }
}
