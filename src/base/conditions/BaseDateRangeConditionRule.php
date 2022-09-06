<?php

namespace craft\base\conditions;

use Craft;
use craft\enums\PeriodType;
use craft\fields\Date;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\DateRangeHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use DateTime;
use Exception;

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
     * @var string
     * @since 4.3.0
     */
    public string $dateRange = DateRangeHelper::RANGE;

    /**
     * @var string
     * @since 4.3.0
     */
    public string $periodType = PeriodType::Hours;

    /**
     * @var float|null
     * @since 4.3.0
     */
    public ?float $periodTypeValue = null;

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
            'dateRange' => $this->dateRange,
            'periodType' => $this->periodType,
            'periodTypeValue' => $this->periodTypeValue,
            'startDate' => $this->getStartDate(),
            'endDate' => $this->getEndDate(),
        ]);
    }

    /**
     * @inheritdoc
     * @noinspection PhpNamedArgumentsWithChangedOrderInspection
     */
    protected function inputHtml(): string
    {
        $dateRangeOptionsHtml = Html::beginTag('ul', ['class' => 'padded']);
        foreach (DateRangeHelper::getDateRangeOptions() as $value => $label) {
            if ($value === DateRangeHelper::RANGE) {
                $dateRangeOptionsHtml .= Html::tag('hr', options: ['class' => 'padded']);
            }

            $dateRangeOptionsHtml .= Html::tag('li',
                Html::a($label, options: [
                    'class' => $value === $this->dateRange ? 'sel' : false,
                    'data' => ['value' => $value],
                ])
            );
        }
        $dateRangeOptionsHtml .= Html::endTag('ul');

        $buttonId = 'date-range-btn';
        $inputId = 'date-range-input';
        $menuId = 'date-range-menu';

        $view = Craft::$app->getView();
        $view->registerJsWithVars(
            fn($buttonId, $inputId) => <<<JS
Garnish.requestAnimationFrame(() => {
  const \$button = $('#' + $buttonId);
  \$button.menubtn().data('menubtn').on('optionSelect', event => {
    const \$option = $(event.option);
    \$button.text(\$option.text()).removeClass('add');
    // Don't use data('value') here because it could result in an object if data-value is JSON
    const \$input = $('#' + $inputId).val(\$option.attr('data-value'));
    htmx.trigger(\$input[0], 'change');
  });
});
JS,
            [
                $view->namespaceInputId($buttonId),
                $view->namespaceInputId($inputId),
            ]
        );

        $html = Html::button(DateRangeHelper::getDateRangeOptions()[$this->dateRange], [
            'id' => $buttonId,
            'class' => ['btn', 'menubtn'],
            'autofocus' => false,
        ]) .
        Html::tag('div', $dateRangeOptionsHtml, [
            'id' => $menuId,
            'class' => 'menu',
        ]) .
        Html::hiddenInput('dateRange', $this->dateRange, [
            'id' => $inputId,
            'hx' => [
                'post' => UrlHelper::actionUrl('conditions/render'),
            ],
        ]);

        if ($this->dateRange === DateRangeHelper::RANGE) {
            $html .= Html::tag(
                    'div',
                    options: ['class' => ['flex', 'flex-nowrap']],
                    content:
                    Html::label(Craft::t('app', 'From'), 'start-date-date') .
                    Html::tag('div',
                        Cp::dateHtml([
                            'id' => 'start-date',
                            'name' => 'startDate',
                            'value' => $this->getStartDate(),
                        ])
                    )
                ) .
                Html::tag(
                    'div',
                    options: ['class' => ['flex', 'flex-nowrap']],
                    content:
                    Html::label(Craft::t('app', 'To'), 'end-date-date') .
                    Html::tag('div',
                        Cp::dateHtml([
                            'id' => 'end-date',
                            'name' => 'endDate',
                            'value' => $this->getEndDate(),
                        ])
                    )
                );
        } elseif ($this->dateRange === DateRangeHelper::BEFORE || $this->dateRange === DateRangeHelper::AFTER) {
            $html .= Html::tag(
                'div',
                options: ['class' => ['flex', 'flex-nowrap']],
                content:
                Cp::textHtml([
                    'name' => 'periodTypeValue',
                    'value' => $this->periodTypeValue,
                    'placeholder' => '4',
                ]) .
                Cp::selectHtml([
                    'name' => 'periodType',
                    'value' => $this->periodType,
                    'options' => DateRangeHelper::getPeriodTypeOptions(),
                ])
            );
        }

        return Html::tag('div', $html, ['class' => ['flex']]);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['startDate', 'endDate', 'dateRange', 'timeFrameUnits', 'timeFrameValue'], 'safe'],
            [['dateRange'], 'in', 'range' => array_keys(DateRangeHelper::getDateRangeOptions())],
            [['periodType'], 'in', 'range' => array_keys(DateRangeHelper::getPeriodTypeOptions())],
            [['periodTypeValue'], 'number', 'skipOnEmpty' => true],
        ]);
    }

    /**
     * Returns the rule’s value, prepped for [[\craft\helpers\Db::parseDateParam()]].
     *
     * @return array|string|null
     */
    protected function queryParamValue(): array|string|null
    {
        if ($this->dateRange === DateRangeHelper::RANGE && ($this->_startDate || $this->_endDate)) {
            return array_filter([
                'and',
                $this->_startDate ? ">= $this->_startDate" : null,
                $this->_endDate ? "< $this->_endDate" : null,
            ]);
        }

        if (($this->dateRange === DateRangeHelper::BEFORE || $this->dateRange === DateRangeHelper::AFTER) && $this->periodTypeValue && $this->periodType) {
            $dateInterval = DateRangeHelper::getDateIntervalByTimePeriod($this->periodTypeValue, $this->periodType);

            return ($this->dateRange === DateRangeHelper::AFTER ? '>=' : '<=') . ' ' . DateTimeHelper::toIso8601(DateTimeHelper::now()->sub($dateInterval));
        }

        $dateRangeOptions = DateRangeHelper::getDateRangeOptions();
        ArrayHelper::remove($dateRangeOptions, DateRangeHelper::BEFORE);
        ArrayHelper::remove($dateRangeOptions, DateRangeHelper::AFTER);
        ArrayHelper::remove($dateRangeOptions, DateRangeHelper::RANGE);
        if (array_key_exists($this->dateRange, $dateRangeOptions)) {
            $dateRange = DateRangeHelper::getDatesByDateRange($this->dateRange);
            $startDate = DateTimeHelper::toIso8601($dateRange['startDate']);
            $endDate = DateTimeHelper::toIso8601($dateRange['endDate']);

            return ['and', ">= $startDate", "<= $endDate"];
        }

        return null;
    }

    /**
     * Returns whether the condition rule matches the given value.
     *
     * @param DateTime|null $value
     * @return bool
     * @throws Exception
     */
    protected function matchValue(?DateTime $value): bool
    {
        if ($this->dateRange === DateRangeHelper::RANGE) {
            return (
                (!$this->_startDate || ($value && $value >= DateTimeHelper::toDateTime($this->_startDate))) &&
                (!$this->_endDate || ($value && $value < DateTimeHelper::toDateTime($this->_endDate)))
            );
        }

        if (($this->dateRange === DateRangeHelper::BEFORE || $this->dateRange === DateRangeHelper::AFTER) && $this->periodTypeValue && $this->periodType) {
            $date = DateTimeHelper::now()->sub(DateRangeHelper::getDateIntervalByTimePeriod($this->periodTypeValue, $this->periodType));

            if ($this->dateRange === DateRangeHelper::AFTER) {
                return $value && $value >= $date;
            }

            return $value && $value <= $date;
        }

        $dateRangeOptions = DateRangeHelper::getDateRangeOptions();
        ArrayHelper::remove($dateRangeOptions, DateRangeHelper::BEFORE);
        ArrayHelper::remove($dateRangeOptions, DateRangeHelper::AFTER);
        ArrayHelper::remove($dateRangeOptions, DateRangeHelper::RANGE);
        if (array_key_exists($this->dateRange, $dateRangeOptions)) {
            $dateRange = DateRangeHelper::getDatesByDateRange($this->dateRange);
            return $value && $value >= $dateRange['startDate'] && $value <= $dateRange['endDate'];
        }

        return false;
    }
}
