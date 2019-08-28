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
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\i18n\Locale;
use DateTime;
use yii\db\Schema;

/**
 * Date represents a Date/Time field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Date extends Field implements PreviewableFieldInterface, SortableFieldInterface
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Date/Time');
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return DateTime::class . '|null';
    }

    // Properties
    // =========================================================================

    /**
     * @var bool Whether a datepicker should be shown as part of the input
     */
    public $showDate = true;

    /**
     * @var bool Whether a timepicker should be shown as part of the input
     */
    public $showTime = false;

    /**
     * @var int The number of minutes that the timepicker options should increment by
     */
    public $minuteIncrement = 30;

    // Public Methods
    // =========================================================================

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

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // In case nothing is selected, default to the date.
        if (!$this->showDate && !$this->showTime) {
            $this->showDate = true;
        }
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['showDate', 'showTime'], 'boolean'];
        $rules[] = [['minuteIncrement'], 'integer', 'min' => 1, 'max' => 60];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_DATETIME;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        // If they are both selected or nothing is selected, the select showBoth.
        if ($this->showDate && $this->showTime) {
            $dateTimeValue = 'showBoth';
        } else if ($this->showDate) {
            $dateTimeValue = 'showDate';
        } else if ($this->showTime) {
            $dateTimeValue = 'showTime';
        }

        $options = [15, 30, 60];
        $options = array_combine($options, $options);

        /** @noinspection PhpUndefinedVariableInspection */
        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Date/settings',
            [
                'options' => [
                    [
                        'label' => Craft::t('app', 'Show date'),
                        'value' => 'showDate',
                    ],
                    [
                        'label' => Craft::t('app', 'Show time'),
                        'value' => 'showTime',
                    ],
                    [
                        'label' => Craft::t('app', 'Show date and time'),
                        'value' => 'showBoth',
                    ]
                ],
                'value' => $dateTimeValue,
                'incrementOptions' => $options,
                'field' => $this,
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $variables = [
            'id' => Craft::$app->getView()->formatInputId($this->handle),
            'name' => $this->handle,
            'value' => $value,
            'minuteIncrement' => $this->minuteIncrement
        ];

        $input = '';

        if ($this->showDate && $this->showTime) {
            $input .= '<div class="datetimewrapper">';
        }

        if ($this->showDate) {
            $input .= Craft::$app->getView()->renderTemplate('_includes/forms/date', $variables);
        }

        if ($this->showTime) {
            $input .= ' ' . Craft::$app->getView()->renderTemplate('_includes/forms/time', $variables);
        }

        if ($this->showDate && $this->showTime) {
            $input .= '</div>';
        }

        return $input;
    }

    /**
     * @inheritdoc
     */
    public function getSearchKeywords($value, ElementInterface $element): string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getTableAttributeHtml($value, ElementInterface $element): string
    {
        if ($value) {
            $formatter = Craft::$app->getFormatter();

            /** @var DateTime $value */
            return '<span title="' . $formatter->asDatetime($value, Locale::LENGTH_SHORT) . '">' . $formatter->asTimestamp($value, Locale::LENGTH_SHORT) . '</span>';
        }

        return '';
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        if ($value && ($date = DateTimeHelper::toDateTime($value)) !== false) {
            return $date;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function modifyElementsQuery(ElementQueryInterface $query, $value)
    {
        if ($value !== null) {
            $handle = $this->handle;
            /** @var ElementQuery $query */
            $query->subQuery->andWhere(Db::parseDateParam('content.' . Craft::$app->getContent()->fieldColumnPrefix . $handle, $value));
        }
    }
}
