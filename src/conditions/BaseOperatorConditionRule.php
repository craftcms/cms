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
    /**
     * @var bool
     */
    protected bool $showOperator = false;

    /**
     * @var string
     */
    public string $operator = '=';

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
            '=' => Craft::t('app', 'equals'),
            '!=' => Craft::t('app', 'does not equal'),
            '<' => Craft::t('app', 'is less than'),
            '<=' => Craft::t('app', 'is less than or equals'),
            '>' => Craft::t('app', 'is greater than'),
            '>=' => Craft::t('app', 'is greater than or equals'),
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
