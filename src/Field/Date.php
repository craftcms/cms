<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use craft\base\ElementInterface;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Conditions\DateFieldConditionRule;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\Gql\Directives\FormatDateTime;
use CraftCms\Cms\Gql\GqlHelper as Gql;
use CraftCms\Cms\Gql\Types\DateTime as DateTimeType;
use CraftCms\Cms\Support\Facades\DeltaRegistry;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Translation\Locale;
use DateTime;
use DateTimeZone;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Database\Query\Builder;
use Override;
use yii\db\Schema;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * Date represents a Date/Time field.
 */
class Date extends Field implements CrossSiteCopyableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface, SortableFieldInterface
{
    #[Override]
    public static function displayName(): string
    {
        return t('Date');
    }

    #[Override]
    public static function icon(): string
    {
        return 'calendar';
    }

    #[Override]
    public static function phpType(): string
    {
        return sprintf('\\%s|null', DateTime::class);
    }

    #[Override]
    public static function dbType(): array
    {
        return [
            'date' => Schema::TYPE_DATETIME,
            'tz' => Schema::TYPE_STRING,
        ];
    }

    #[Override]
    public static function modifyQuery(Builder $query, array $instances, mixed $value): Builder
    {
        $valueSql = self::valueSql($instances);

        return $query->whereDateParam($valueSql, $value);
    }

    /**
     * @var bool Whether a datepicker should be shown as part of the input
     */
    public bool $showDate = true;

    /**
     * @var bool Whether a timepicker should be shown as part of the input
     */
    public bool $showTime = false;

    /**
     * @var bool Whether the selected time zone should be stored with the field data. Otherwise the system
     *           time zone will always be used.
     */
    public bool $showTimeZone = false;

    /**
     * @var DateTime|null The minimum allowed date
     */
    public ?DateTime $min = null;

    /**
     * @var DateTime|null The maximum allowed date
     */
    public ?DateTime $max = null;

    /**
     * @var int The number of minutes that the timepicker options should increment by
     */
    public int $minuteIncrement = 30;

    public function __construct($config = [])
    {
        // dateTime => showDate + showTime
        if (isset($config['dateTime'])) {
            switch ($config['dateTime']) {
                case 'showBoth':
                    $config['showDate'] = true;
                    $config['showTime'] = true;
                    break;
                case 'showDate':
                    $config['showDate'] = true;
                    $config['showTime'] = false;
                    break;
                case 'showTime':
                    $config['showDate'] = false;
                    $config['showTime'] = true;
                    break;
            }

            unset($config['dateTime']);
        }

        if (isset($config['min'])) {
            $config['min'] = DateTimeHelper::toDateTime($config['min']) ?: null;
        }

        if (isset($config['max'])) {
            $config['max'] = DateTimeHelper::toDateTime($config['max']) ?: null;
        }

        parent::__construct($config);

        // In case nothing is selected, default to the date.
        if (! $this->showDate && ! $this->showTime) {
            $this->showDate = true;
        }

        if (! $this->showTime) {
            $this->showTimeZone = false;
        }
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            'min' => t('Min Date'),
            'max' => t('Max Date'),
        ];
    }

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'showDate' => 'boolean',
            'showTime' => 'boolean',
            'minuteIncrement' => ['integer', 'min:1', 'max:60'],
            'min' => ['nullable', 'date', 'before_or_equal:max'],
            'max' => ['nullable', 'date', 'after_or_equal:min'],
        ]);
    }

    public function getSettingsHtml(): string
    {
        return $this->settingsHtml(false);
    }

    #[Override]
    public function getReadOnlySettingsHtml(): string
    {
        return $this->settingsHtml(true);
    }

    private function settingsHtml(bool $readOnly): string
    {
        if ($this->showDate && ! $this->showTime) {
            $dateTimeValue = 'showDate';
        } elseif ($this->showTime && ! $this->showDate) {
            $dateTimeValue = 'showTime';
        } else {
            $dateTimeValue = 'showBoth';
        }

        $incrementOptions = [5, 10, 15, 30, 60];
        $incrementOptions = array_combine($incrementOptions, $incrementOptions);

        $options = [
            [
                'label' => t('Show date'),
                'value' => 'showDate',
            ],
        ];

        // Only allow the "Show date and time" option if it's already selected
        if ($dateTimeValue === 'showTime') {
            $options[] = [
                'label' => t('Show time'),
                'value' => 'showTime',
            ];
        }

        $options[] = [
            'label' => t('Show date and time'),
            'value' => 'showBoth',
        ];

        return template('_components/fieldtypes/Date/settings', [
            'options' => $options,
            'value' => $dateTimeValue,
            'incrementOptions' => $incrementOptions,
            'field' => $this,
            'readOnly' => $readOnly,
        ]);
    }

    #[Override]
    public function getInputId(): string
    {
        return sprintf('%s-date', parent::getInputId());
    }

    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        /** @var DateTime|null $value */
        $timezone = $this->showTimeZone && $value ? $value->getTimezone()->getName() : app()->getTimezone();

        if ($value === null) {
            // Override the initial value being set to null by CustomField::inputHtml()
            $initialValue = [];
            if ($this->showDate) {
                $initialValue['date'] = '';
            }
            if ($this->showTime) {
                $initialValue['time'] = '';
            }
            $initialValue['timezone'] = $timezone;
            DeltaRegistry::setInitialValue($this->handle, $initialValue);
        }

        $components = [];

        $variables = [
            'id' => parent::getInputId(), // can't use $this->getInputId() here because the template adds the "-date"
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'value' => $value,
            'timeZone' => $this->showTimeZone ? false : null,
            'outputTzParam' => false,
            'minuteIncrement' => $this->minuteIncrement,
            'isDateTime' => $this->showTime,
            'hasOuterContainer' => true,
        ];

        if ($this->showDate) {
            $components[] = template('_includes/forms/date', $variables);
        }

        if ($this->showTime) {
            $components[] = template('_includes/forms/time', $variables);
        }

        if ($this->showTimeZone) {
            $components[] = template('_includes/forms/timeZone', [
                'describedBy' => $this->describedBy,
                'name' => "$this->handle[timezone]",
                'value' => $timezone,
                'offsetDate' => $value,
            ]);
        } else {
            $components[] = Html::hiddenInput("$this->handle[timezone]", $timezone);
        }

        return Html::tag('div', implode("\n", $components), [
            'class' => 'datetimewrapper',
        ]);
    }

    #[Override]
    public function getElementRules(ElementInterface $element): array
    {
        $rules = ['date'];

        if ($this->min) {
            $rules[] = 'after_or_equal:'.$this->min->setTimezone(new DateTimeZone('UTC'))->setTime(0, 0)->format('Y-m-d H:i:s');
        }

        if ($this->max) {
            $rules[] = 'before_or_equal:'.$this->max->setTimezone(new DateTimeZone('UTC'))->setTime(0, 0)->format('Y-m-d H:i:s');
        }

        return $rules;
    }

    #[Override]
    protected function searchKeywords(mixed $value, ElementInterface $element): string
    {
        return '';
    }

    #[Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        /** @var DateTime|null $value */
        if (! $value) {
            return '';
        }

        $formatter = I18N::getFormatter();

        if ($this->showDate && $this->showTime) {
            if ($this->showTimeZone) {
                $timeZone = $formatter->timeZone;
                $formatter->timeZone = $value->getTimezone()->getName();
                $html = sprintf(
                    '%s %s',
                    $formatter->asDatetime($value, Locale::LENGTH_SHORT),
                    $value->format('T')
                );
                $formatter->timeZone = $timeZone;

                return $html;
            }

            return $formatter->asDatetime($value, Locale::LENGTH_SHORT);
        }

        if ($this->showDate) {
            return $formatter->asDate($value, Locale::LENGTH_SHORT);
        }

        return $formatter->asTime($value, Locale::LENGTH_SHORT);
    }

    #[Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (! $value) {
            $value = new DateTime;
        }

        return $this->getPreviewHtml($value, $element ?? new Entry);
    }

    #[Override]
    public function useFieldset(): bool
    {
        return $this->showTime;
    }

    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value instanceof DateTime) {
            return $value;
        }

        // Is this coming from the DB?
        if (is_array($value) && array_key_exists('tz', $value)) {
            $timeZone = $value['tz'];
            $value = $value['date'];
        }

        if (
            ! $value ||
            (is_array($value) && empty($value['date']) && empty($value['time']) && empty($value['datetime']))
        ) {
            return null;
        }

        $date = DateTimeHelper::toDateTime($value);

        if ($date === false) {
            return null;
        }

        if (! $this->showTimeZone) {
            return $date;
        }

        /** @phpstan-ignore-next-line */
        if (isset($timeZone) || (is_array($value) && ! empty($value['timezone']))) {
            /** @phpstan-ignore-next-line */
            $date->setTimezone(new DateTimeZone($timeZone ?? $value['timezone']));
        }

        return $date;
    }

    #[Override]
    public function serializeValueForDb(mixed $value, ElementInterface $element): mixed
    {
        if (! $value) {
            return null;
        }

        $serialized = [
            'date' => Query::prepareDateForDb($value),
        ];

        if ($this->showTimeZone && $value->getTimezone()->getLocation()) {
            $serialized += [
                'tz' => $value->getTimezone()->getName(),
            ];
        }

        return $serialized;
    }

    public function getElementConditionRuleType(): string
    {
        return DateFieldConditionRule::class;
    }

    #[Override]
    public function getContentGqlType(): array
    {
        return [
            'name' => $this->handle,
            'type' => DateTimeType::getType(),
            'resolve' => function ($source, array $arguments, $context, ResolveInfo $resolveInfo) {
                $fieldName = Gql::getFieldNameWithAlias($resolveInfo, $source, $context);
                $value = $source->getFieldValue($fieldName);

                // Set the timezone, unless it has been already set by the field itself.
                if (! $this->showTimeZone && $value instanceof DateTime) {
                    $value->setTimeZone(new DateTimeZone(FormatDateTime::defaultTimezone()));
                }

                return Gql::applyDirectives($source, $resolveInfo, $value);
            },
        ];
    }

    #[Override]
    public function getContentGqlMutationArgumentType(): array
    {
        $type = DateTimeType::getType();
        $type->setToSystemTimeZone = ! $this->showTimeZone;

        return [
            'name' => $this->handle,
            'type' => $type,
            'description' => $this->instructions,
        ];
    }
}
