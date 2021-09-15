<?php

namespace craft\conditions;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\helpers\Html;
use craft\helpers\UrlHelper;

/**
 * The BaseDateRangeConditionRule class provides a condition rule with two dates inputs for after and before dates.
 *
 * @property-read array $inputAttributes
 * @property-read string $inputHtml
 * @property-read string $settingsHtml
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class BaseDateRangeConditionRule extends BaseConditionRule
{
    /**
     * @var bool
     */
    private $_startDate;

    /**
     * @var bool
     */
    private $_endDate;

    /**
     * @param $value
     */
    public function setStartDate($value)
    {
        $this->_startDate = ($value ? DateTimeHelper::toIso8601($value) : null);
    }

    /**
     * @return string|null
     */
    public function getStartDate()
    {
        return $this->_startDate;
    }

    /**
     * @param $value
     */
    public function setEndDate($value)
    {
        $this->_endDate = ($value ? DateTimeHelper::toIso8601($value) : null);
    }

    /**
     * @return string|null
     */
    public function getEndDate()
    {
        return $this->_endDate;
    }

    /**
     * Returns the input container attributes.
     *
     * @return array
     */
    protected function getContainerAttributes(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getHtml(array $options = []): string
    {
        $html = Html::beginTag('div', ['class' => ['flex', 'flex-nowrap']]);
        $html .= Html::tag('div', Html::tag('p', Craft::t('app', 'After')));
        $html .= Html::tag('div',
            Craft::$app->getView()->renderTemplate('_includes/forms/date', [
                'name' => 'startDate',
                'id' => 'startDate',
                'value' => $this->getStartDate()
            ])
        );
        $html .= Html::tag('div', Html::tag('p', Craft::t('app', 'Before')));
        $html .= Html::tag('div',
            Craft::$app->getView()->renderTemplate('_includes/forms/date', [
                'name' => 'endDate',
                'id' => 'endDate',
                'value' => $this->getEndDate()
            ])
        );
        $html .= Html::endTag('div');

        return $html;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['startDate', 'endDate'], 'safe'],
        ]);
    }
}
