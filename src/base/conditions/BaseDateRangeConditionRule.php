<?php

namespace craft\base\conditions;

use Craft;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\Html;
use DateTime;

/**
 * BaseDateRangeConditionRule provides a base implementation for condition rules that are composed of date range inputs.
 *
 * @property string|null $startDate
 * @property string|null $endDate
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class BaseDateRangeConditionRule extends BaseConditionRule
{
    /**
     * @var string|null
     */
    private ?string $_startDate = null;

    /**
     * @var string|null
     */
    private ?string $_endDate = null;

    /**
     * @return string|null
     */
    public function getStartDate(): ?string
    {
        return $this->_startDate;
    }

    /**
     * @param mixed $value
     */
    public function setStartDate(mixed $value): void
    {
        $this->_startDate = ($value ? DateTimeHelper::toIso8601($value) : null);
    }

    /**
     * @return string|null
     */
    public function getEndDate(): ?string
    {
        return $this->_endDate;
    }

    /**
     * @param mixed $value
     */
    public function setEndDate(mixed $value): void
    {
        $this->_endDate = ($value ? DateTimeHelper::toIso8601($value) : null);
    }

    /**
     * Returns the input container attributes.
     *
     * @return array
     */
    protected function containerAttributes(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'startDate' => $this->getStartDate(),
            'endDate' => $this->getEndDate(),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(): string
    {
        return
            Html::beginTag('div', ['class' => ['flex', 'flex-nowrap']]) .
            Html::label(Craft::t('app', 'From'), 'start-date-date') .
            Html::tag('div',
                Cp::dateHtml([
                    'id' => 'start-date',
                    'name' => 'startDate',
                    'value' => $this->getStartDate(),
                ])
            ) .
            Html::label(Craft::t('app', 'To'), 'end-date-date') .
            Html::tag('div',
                Cp::dateHtml([
                    'id' => 'end-date',
                    'name' => 'endDate',
                    'value' => $this->getEndDate(),
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
            [['startDate', 'endDate'], 'safe'],
        ]);
    }

    /**
     * Returns the rule’s value, prepped for [[\craft\helpers\Db::parseDateParam()]].
     *
     * @return array|null
     */
    protected function queryParamValue(): ?array
    {
        if (!$this->_startDate && !$this->_endDate) {
            return null;
        }

        return array_filter([
            'and',
            $this->_startDate ? ">= $this->_startDate" : null,
            $this->_endDate ? "< $this->_endDate" : null,
        ]);
    }

    /**
     * Returns whether the condition rule matches the given value.
     *
     * @param DateTime|null $value
     * @return bool
     */
    protected function matchValue(?DateTime $value): bool
    {
        return (
            (!$this->_startDate || ($value && $value >= DateTimeHelper::toDateTime($this->_startDate))) &&
            (!$this->_endDate || ($value && $value < DateTimeHelper::toDateTime($this->_endDate)))
        );
    }
}
