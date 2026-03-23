<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Database\QueryParam;
use CraftCms\Cms\Shared\Enums\DateRangePeriod;
use CraftCms\Cms\Shared\Enums\DateRangeType;
use CraftCms\Cms\Shared\Enums\TimePeriod;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\URL;
use DateTime;
use Exception;
use Illuminate\Validation\Rule;
use Override;

use function CraftCms\Cms\t;

/**
 * BaseDateRangeConditionRule provides a base implementation for condition rules that are composed of date range inputs.
 */
abstract class BaseDateRangeConditionRule extends BaseConditionRule
{
    /**
     * @var value-of<DateRangeType>
     */
    public string $rangeType = DateRangeType::Today->value;

    /**
     * @var value-of<DateRangePeriod>
     */
    public string $periodType = DateRangePeriod::DaysAgo->value;

    public ?float $periodValue = null;

    private ?string $_startDate = null;

    public ?string $startDate {
        get => $this->getStartDate();
        set {
            $this->setStartDate($value);
        }
    }

    private ?string $_endDate = null;

    public ?string $endDate {
        get => $this->getEndDate();
        set {
            $this->setEndDate($value);
        }
    }

    public function __construct($config = [])
    {
        if (
            ! isset($config['attributes']['rangeType']) &&
            (! empty($config['attributes']['startDate']) || ! empty($config['attributes']['endDate']))
        ) {
            $config['attributes']['rangeType'] = DateRangeType::Range->value;
        }

        if (isset($config['attributes']['periodType'])) {
            // Maintain BC with older periodType values
            $config['attributes']['periodType'] = match ($config['attributes']['periodType']) {
                TimePeriod::Minutes->value => DateRangePeriod::MinutesAgo->value,
                TimePeriod::Hours->value => DateRangePeriod::HoursAgo->value,
                TimePeriod::Days->value => DateRangePeriod::DaysAgo->value,
                default => $config['attributes']['periodType'],
            };
        }

        parent::__construct($config);
    }

    public function getStartDate(): ?string
    {
        return $this->_startDate;
    }

    public function setStartDate(mixed $value): void
    {
        $this->_startDate = ($value ? DateTimeHelper::toIso8601($value) : null);
    }

    public function getEndDate(): ?string
    {
        return $this->_endDate;
    }

    public function setEndDate(mixed $value): void
    {
        $this->_endDate = ($value ? DateTimeHelper::toIso8601($value) : null);
    }

    #[Override]
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'rangeType' => $this->rangeType,
            'periodType' => $this->periodType,
            'periodValue' => $this->periodValue,
            'startDate' => $this->getStartDate(),
            'endDate' => $this->getEndDate(),
        ]);
    }

    /**
     * @noinspection PhpNamedArgumentsWithChangedOrderInspection
     */
    #[Override]
    protected function inputHtml(): string
    {
        $groupedOptions = [];

        foreach ($this->rangeTypeOptions() as $value => $label) {
            if (in_array($value, [
                DateRangeType::Before->value,
                DateRangeType::After->value,
                DateRangeType::Range->value,
            ])) {
                $index = 1;
            } elseif (in_array($value, [self::OPERATOR_NOT_EMPTY, self::OPERATOR_EMPTY])) {
                $index = 2;
            } else {
                $index = 0;
            }

            $groupedOptions[$index][] = Html::beginTag('li').
                Html::a($label, options: [
                    'class' => $value === $this->rangeType ? 'sel' : false,
                    'data' => ['value' => $value],
                ]).
                Html::endTag('li');
        }

        $optionLists = [];

        foreach ($groupedOptions as $options) {
            $optionLists[] = Html::tag('ul', implode('', $options), ['class' => 'padded']);
        }

        $rangeTypeOptionsHtml = implode(Html::tag('hr', attributes: ['class' => 'padded']), $optionLists);

        $buttonId = 'date-range-btn';
        $inputId = 'date-range-input';
        $menuId = 'date-range-menu';

        HtmlStack::jsWithVars(
            fn ($buttonId, $inputId) => <<<JS
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
                InputNamespace::namespaceId($buttonId),
                InputNamespace::namespaceId($inputId),
            ]
        );

        $html = Html::button($this->rangeTypeOptions()[$this->rangeType], [
            'id' => $buttonId,
            'class' => ['btn', 'menubtn'],
            'autofocus' => false,
            'aria' => [
                'label' => t('Date Range'),
            ],
        ]).
            Html::tag('div', $rangeTypeOptionsHtml, [
                'id' => $menuId,
                'class' => 'menu',
            ]).
            Html::hiddenInput('rangeType', $this->rangeType, [
                'id' => $inputId,
                'hx' => [
                    'post' => URL::actionUrl('conditions/render'),
                ],
            ]);

        if ($this->rangeType === DateRangeType::Range->value) {
            $html .= Html::tag(
                'div',
                attributes: ['class' => ['flex', 'flex-nowrap']],
                content: Html::label(t('From'), 'start-date-date').
                Html::tag('div',
                    FormFields::dateHtml([
                        'id' => 'start-date',
                        'name' => 'startDate',
                        'value' => $this->getStartDate(),
                    ])
                )
            ).
                Html::tag(
                    'div',
                    attributes: ['class' => ['flex', 'flex-nowrap']],
                    content: Html::label(t('To'), 'end-date-date').
                    Html::tag('div',
                        FormFields::dateHtml([
                            'id' => 'end-date',
                            'name' => 'endDate',
                            'value' => $this->getEndDate(),
                        ])
                    )
                );
        } elseif (in_array($this->rangeType, [DateRangeType::Before->value, DateRangeType::After->value])) {
            $periodValueId = 'period-value';
            $periodTypeId = 'period-type';

            $html .= Html::hiddenLabel(t('Period Value'), $periodValueId).
                Html::tag(
                    'div',
                    attributes: ['class' => ['flex', 'flex-nowrap']],
                    content: FormFields::textHtml([
                        'id' => $periodValueId,
                        'name' => 'periodValue',
                        'value' => $this->periodValue,
                        'size' => '5',
                    ]).
                    Html::hiddenLabel(t('Period Type'), $periodTypeId).
                    FormFields::selectHtml([
                        'id' => $periodTypeId,
                        'name' => 'periodType',
                        'value' => $this->periodType,
                        'options' => $this->periodTypeOptions(),
                    ])
                );
        }

        return Html::tag('div', $html, ['class' => ['flex']]);
    }

    /**
     * Returns the available range type options for the rule.
     */
    protected function rangeTypeOptions(): array
    {
        return [
            DateRangeType::Today->value => DateRangeType::Today->label(),
            DateRangeType::ThisWeek->value => DateRangeType::ThisWeek->label(),
            DateRangeType::ThisMonth->value => DateRangeType::ThisMonth->label(),
            DateRangeType::ThisYear->value => DateRangeType::ThisYear->label(),
            DateRangeType::Past7Days->value => DateRangeType::Past7Days->label(),
            DateRangeType::Past30Days->value => DateRangeType::Past30Days->label(),
            DateRangeType::Past90Days->value => DateRangeType::Past90Days->label(),
            DateRangeType::PastYear->value => DateRangeType::PastYear->label(),
            DateRangeType::Before->value => DateRangeType::Before->label(),
            DateRangeType::After->value => DateRangeType::After->label(),
            DateRangeType::Range->value => DateRangeType::Range->label(),
            self::OPERATOR_NOT_EMPTY => t('has a value'),
            self::OPERATOR_EMPTY => t('is empty'),
        ];
    }

    /**
     * Returns the available period type options for the rule.
     */
    protected function periodTypeOptions(): array
    {
        return [
            DateRangePeriod::MinutesAgo->value => DateRangePeriod::MinutesAgo->label(),
            DateRangePeriod::HoursAgo->value => DateRangePeriod::HoursAgo->label(),
            DateRangePeriod::DaysAgo->value => DateRangePeriod::DaysAgo->label(),
            DateRangePeriod::MinutesFromNow->value => DateRangePeriod::MinutesFromNow->label(),
            DateRangePeriod::HoursFromNow->value => DateRangePeriod::HoursFromNow->label(),
            DateRangePeriod::DaysFromNow->value => DateRangePeriod::DaysFromNow->label(),
        ];
    }

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'startDate' => ['nullable'],
            'endDate' => ['nullable'],
            'rangeType' => ['nullable', Rule::in(array_keys($this->rangeTypeOptions()))],
            'periodType' => ['nullable', Rule::in(array_keys($this->periodTypeOptions()))],
            'periodValue' => ['nullable', 'numeric'],
            'timeFrameUnits' => ['nullable'],
            'timeFrameValue' => ['nullable'],
        ]);
    }

    /**
     * Returns the rule’s value, prepped for {@see QueryParam::parse()}.
     */
    protected function queryParamValue(): array|string|null
    {
        $periodType = DateRangePeriod::from($this->periodType);

        switch ($this->rangeType) {
            case DateRangeType::Range->value:
                if (! $this->_startDate && ! $this->_endDate) {
                    return null;
                }

                return array_filter([
                    'and',
                    $this->_startDate ? ">= $this->_startDate" : null,
                    $this->_endDate ? '< '.DateTimeHelper::toIso8601($this->_inclusiveEndDate()) : null,
                ]);

            case DateRangeType::Before->value:
            case DateRangeType::After->value:
                if (! $this->periodValue) {
                    return null;
                }

                $dateInterval = $periodType->interval($this->periodValue);

                return ($this->rangeType === DateRangeType::After->value ? '>=' : '<').' '.
                    DateTimeHelper::toIso8601(DateTimeHelper::now()->add($dateInterval));

            case self::OPERATOR_EMPTY:
                return ':empty:';

            case self::OPERATOR_NOT_EMPTY:
                return 'not :empty:';

            default:
                [$startDate, $endDate] = DateRangeType::from($this->rangeType)->range();
                $startDate = DateTimeHelper::toIso8601($startDate);
                $endDate = DateTimeHelper::toIso8601($endDate);

                return ['and', ">= $startDate", "< $endDate"];
        }
    }

    /**
     * Returns whether the condition rule matches the given value.
     *
     * @throws Exception
     */
    protected function matchValue(?DateTime $value): bool
    {
        switch ($this->rangeType) {
            case DateRangeType::Range->value:
                return
                    (! $this->_startDate || ($value && $value >= DateTimeHelper::toDateTime($this->_startDate))) &&
                    (! $this->_endDate || ($value && $value < $this->_inclusiveEndDate()));

            case DateRangeType::Before->value:
            case DateRangeType::After->value:
                if (! $this->periodValue) {
                    return true;
                }

                $date = DateTimeHelper::now()->add(DateRangePeriod::from($this->periodType)->interval($this->periodValue));

                if ($this->rangeType === DateRangeType::After->value) {
                    return $value && $value >= $date;
                }

                return $value && $value < $date;

            case self::OPERATOR_EMPTY:
                return ! $value;

            case self::OPERATOR_NOT_EMPTY:
                return (bool) $value;

            default:
                [$startDate, $endDate] = DateRangeType::from($this->rangeType)->range();

                return $value && $value >= $startDate && $value < $endDate;
        }
    }

    private function _inclusiveEndDate(): DateTime
    {
        return DateTimeHelper::toDateTime($this->_endDate)->modify('+1 day');
    }
}
