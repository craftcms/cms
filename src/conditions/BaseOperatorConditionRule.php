<?php

namespace craft\conditions;

use Craft;
use craft\helpers\Html;
use craft\helpers\UrlHelper;

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
    protected function getOperators(): array
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
        $html = '';
        if ($this->showOperator) {
            $html .= Html::tag('div', Craft::$app->getView()->renderTemplate('_includes/forms/select', [
                'name' => 'operator',
                'value' => $this->operator,
                'options' => $this->getOperators(),
                'inputAttributes' => [
                    'hx-post' => UrlHelper::actionUrl('conditions/render'),
                ]
            ]));
        }

        return $html;
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
