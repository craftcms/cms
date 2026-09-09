<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\QueryParam;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\DateTime;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Shared\Enums\DateRangePeriod;
use CraftCms\Cms\Shared\Enums\DateRangeType;
use CraftCms\Cms\Shared\Enums\TimePeriod;
use CraftCms\Cms\Support\DateTimeHelper;
use DateTimeInterface;
use Exception;
use Illuminate\Support\Facades\Date;
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
        set(mixed $value) {
            $this->setStartDate($value);
        }
    }

    private ?string $_endDate = null;

    public ?string $endDate {
        get => $this->getEndDate();
        set(mixed $value) {
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
        if (is_array($value) && empty($value['date'])) {
            $value = null;
        }

        $this->_startDate = ($value ? DateTimeHelper::toIso8601($value) : null);
    }

    public function getEndDate(): ?string
    {
        return $this->_endDate;
    }

    public function setEndDate(mixed $value): void
    {
        if (is_array($value) && empty($value['date'])) {
            $value = null;
        }

        $this->_endDate = ($value ? DateTimeHelper::toIso8601($value) : null);
    }

    /** @return array<string, mixed> */
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
     *
     * @return list<Node>
     */
    #[Override]
    protected function inputNodes(): array
    {
        $nodes = [
            Field::make(t('Date Range'), Choice::make('rangeType')
                ->options($this->formOptions($this->rangeTypeOptions()))
                ->withoutPlaceholder()
                ->value($this->rangeType)
                ->reactive()),
        ];

        if ($this->rangeType === DateRangeType::Range->value) {
            array_push($nodes,
                Field::make(t('From'), DateTime::make('startDate')->value($this->dateControlValue($this->getStartDate()))),
                Field::make(t('To'), DateTime::make('endDate')->value($this->dateControlValue($this->getEndDate()))),
            );
        } elseif (in_array($this->rangeType, [DateRangeType::Before->value, DateRangeType::After->value], true)) {
            array_push($nodes,
                Field::make(t('Period Value'), Text::make('periodValue')->size(5)->value($this->periodValue)),
                Field::make(t('Period Type'), Choice::make('periodType')
                    ->options($this->formOptions($this->periodTypeOptions()))
                    ->withoutPlaceholder()
                    ->value($this->periodType)),
            );
        }

        return $nodes;
    }

    /** @return array{date: string, timezone: string} */
    private function dateControlValue(?string $value): array
    {
        $date = $value !== null ? DateTimeHelper::toDateTime($value) : null;

        return [
            'date' => $date ? $date->format('Y-m-d') : '',
            'timezone' => $date ? $date->getTimezone()->getName() : Cms::timezone(),
        ];
    }

    /**
     * Returns the available range type options for the rule.
     *
     * @return array<string, string>
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
     *
     * @return array<string, string>
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
     *
     * @return array<int, string>|string|null
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
                    DateTimeHelper::toIso8601(now()->add($dateInterval));

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
    protected function matchValue(?DateTimeInterface $value): bool
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

                $date = now()->add(DateRangePeriod::from($this->periodType)->interval($this->periodValue));

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

    private function _inclusiveEndDate(): DateTimeInterface
    {
        $endDate = DateTimeHelper::toDateTime($this->_endDate);

        if ($endDate === false) {
            throw new Exception('Invalid end date.');
        }

        return Date::instance($endDate)->modify('+1 day');
    }
}
