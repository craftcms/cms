<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\base\SortableFieldInterface;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\fields\conditions\DateFieldConditionRule;
use craft\gql\directives\FormatDateTime;
use craft\gql\types\DateTime as DateTimeType;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\Gql;
use craft\helpers\Html;
use craft\i18n\Locale;
use craft\validators\DateTimeValidator;
use DateTime;
use DateTimeZone;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use yii\db\Schema;

/**
 * Date represents a Date/Time field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Date extends Field implements PreviewableFieldInterface, SortableFieldInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Date');
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return sprintf('\\%s|null', DateTime::class);
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
     * time zone will always be used.
     * @since 3.7.0
     */
    public bool $showTimeZone = false;

    /**
     * @var DateTime|null The minimum allowed date
     * @since 3.5.0
     */
    public ?DateTime $min = null;

    /**
     * @var DateTime|null The maximum allowed date
     * @since 3.5.0
     */
    public ?DateTime $max = null;

    /**
     * @var int The number of minutes that the timepicker options should increment by
     */
    public int $minuteIncrement = 30;

    /**
     * @inheritdoc
     */
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
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        // In case nothing is selected, default to the date.
        if (!$this->showDate && !$this->showTime) {
            $this->showDate = true;
        }

        if (!$this->showTime) {
            $this->showTimeZone = false;
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'min' => Craft::t('app', 'Min Date'),
            'max' => Craft::t('app', 'Max Date'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['showDate', 'showTime'], 'boolean'];
        $rules[] = [['minuteIncrement'], 'integer', 'min' => 1, 'max' => 60];
        $rules[] = [['max'], DateTimeValidator::class, 'min' => $this->min];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): array|string
    {
        if ($this->showTimeZone) {
            return [
                'date' => Schema::TYPE_DATETIME,
                'tz' => Schema::TYPE_STRING,
            ];
        }

        return Schema::TYPE_DATETIME;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        if ($this->showDate && !$this->showTime) {
            $dateTimeValue = 'showDate';
        } elseif ($this->showTime && !$this->showDate) {
            $dateTimeValue = 'showTime';
        } else {
            $dateTimeValue = 'showBoth';
        }

        $incrementOptions = [5, 10, 15, 30, 60];
        $incrementOptions = array_combine($incrementOptions, $incrementOptions);

        $options = [
            [
                'label' => Craft::t('app', 'Show date'),
                'value' => 'showDate',
            ],
        ];

        // Only allow the "Show date and time" option if it's already selected
        if ($dateTimeValue === 'showTime') {
            $options[] = [
                'label' => Craft::t('app', 'Show time'),
                'value' => 'showTime',
            ];
        }

        $options[] = [
            'label' => Craft::t('app', 'Show date and time'),
            'value' => 'showBoth',
        ];

        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Date/settings.twig', [
            'options' => $options,
            'value' => $dateTimeValue,
            'incrementOptions' => $incrementOptions,
            'field' => $this,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getInputId(): string
    {
        return sprintf('%s-date', parent::getInputId());
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        /** @var DateTime|null $value */
        $view = Craft::$app->getView();
        $timezone = $this->showTimeZone && $value ? $value->getTimezone()->getName() : Craft::$app->getTimeZone();

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
            $view->setInitialDeltaValue($this->handle, $initialValue);
        }

        $components = [];

        $variables = [
            'id' => parent::getInputId(), // can't use $this->getInputId() here because the template adds the "-date"
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'value' => $value,
            'outputTzParam' => false,
            'minuteIncrement' => $this->minuteIncrement,
            'isDateTime' => $this->showTime,
            'hasOuterContainer' => true,
        ];

        if ($this->showDate) {
            $components[] = $view->renderTemplate('_includes/forms/date.twig', $variables);
        }

        if ($this->showTime) {
            $components[] = $view->renderTemplate('_includes/forms/time.twig', $variables);
        }

        if ($this->showTimeZone) {
            $components[] = $view->renderTemplate('_includes/forms/timeZone.twig', [
                'describedBy' => $this->describedBy,
                'name' => "$this->handle[timezone]",
                'value' => $timezone,
            ]);
        } else {
            $components[] = Html::hiddenInput("$this->handle[timezone]", $timezone);
        }

        return Html::tag('div', implode("\n", $components), [
            'class' => 'datetimewrapper',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        return [
            [
                DateTimeValidator::class,
                'min' => $this->min?->setTime(0, 0, 0),
                'max' => $this->max?->setTime(23, 59, 59),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function searchKeywords(mixed $value, ElementInterface $element): string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getTableAttributeHtml(mixed $value, ElementInterface $element): string
    {
        if (!$value) {
            return '';
        }

        if ($this->showDate && $this->showTime) {
            return Craft::$app->getFormatter()->asDatetime($value, Locale::LENGTH_SHORT);
        }

        if ($this->showDate) {
            return Craft::$app->getFormatter()->asDate($value, Locale::LENGTH_SHORT);
        }

        return Craft::$app->getFormatter()->asTime($value, Locale::LENGTH_SHORT);
    }

    /**
     * @inheritdoc
     */
    public function useFieldset(): bool
    {
        return $this->showTime;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
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
            !$value ||
            (is_array($value) && empty($value['date']) && empty($value['time']) && empty($value['datetime']))
        ) {
            return null;
        }

        $date = DateTimeHelper::toDateTime($value);

        if ($date === false) {
            return null;
        }

        if ($this->showTimeZone && (isset($timeZone) || (is_array($value) && isset($value['timezone'])))) {
            $date->setTimezone(new DateTimeZone($timeZone ?? $value['timezone']));
        }

        return $date;
    }

    /**
     * @inheritdoc
     */
    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if (!$value) {
            return null;
        }

        /** @var DateTime $value */
        if (!$this->showTimeZone) {
            return Db::prepareDateForDb($value);
        }

        return [
            'date' => Db::prepareDateForDb($value),
            'tz' => $value->getTimezone()->getName(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getElementConditionRuleType(): array|string|null
    {
        return DateFieldConditionRule::class;
    }

    /**
     * @inheritdoc
     */
    public function modifyElementsQuery(ElementQueryInterface $query, mixed $value): void
    {
        /** @var ElementQuery $query */
        if ($value !== null) {
            $column = ElementHelper::fieldColumnFromField($this);
            $query->subQuery->andWhere(Db::parseDateParam("content.$column", $value));
        }
    }

    /**
     * @inheritdoc
     */
    public function getContentGqlType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => DateTimeType::getType(),
            'resolve' => function($source, array $arguments, $context, ResolveInfo $resolveInfo) {
                $fieldName = Gql::getFieldNameWithAlias($resolveInfo, $source, $context);
                $value = $source->getFieldValue($fieldName);

                // Set the timezone, unless it has been already set by the field itself.
                if (!$this->showTimeZone && $value instanceof DateTime) {
                    $value->setTimeZone(new DateTimeZone(FormatDateTime::defaultTimezone()));
                }

                return Gql::applyDirectives($source, $resolveInfo, $value);
            },
        ];
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getContentGqlMutationArgumentType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => DateTimeType::getType(),
            'description' => $this->instructions,
        ];
    }
}
