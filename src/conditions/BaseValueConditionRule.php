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
 *
 * @since 4.0
 */
abstract class BaseValueConditionRule extends BaseConditionRule
{
    /**
     * @var bool
     */
    protected bool $showOperator = false;

    /**
     * @var string
     */
    public string $value = '';

    /**
     * @var string
     */
    public string $operator = '=';

    /**
     * @inheritDoc
     */
    public function getConfig(): array
    {
        $config = parent::getConfig();
        $config['value'] = $this->value;
        $config['operator'] = $this->operator;

        return $config;
    }

    /**
     * @return array
     * @since 4.0
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
     * @inheritDoc
     */
    public function getHtml(): string
    {
        $html = Html::beginTag('div', ['class' => ['flex', 'flex-nowrap']]);

        if ($this->showOperator) {
            $html .= Html::tag('div', Craft::$app->getView()->renderTemplate('_includes/forms/select', [
                'name' => 'operator',
                'value' => $this->operator,
                'options' => $this->getOperators(),
                'inputAttributes' => [
                    'hx-post' => UrlHelper::actionUrl('conditions/render')
                ]
            ]));
        }

        $html .= Html::tag('div', $this->getInputHtml());
        $html .= Html::endTag('div');

        return $html;
    }

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['value', 'operator'], 'safe'];

        return $rules;
    }
}