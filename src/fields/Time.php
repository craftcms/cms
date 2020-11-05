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
use craft\gql\types\DateTime as DateTimeType;
use craft\helpers\DateTimeHelper;
use craft\helpers\Html;
use craft\i18n\Locale;
use craft\validators\TimeValidator;
use DateTime;
use yii\db\Schema;

/**
 * Date represents a Time field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.12
 */
class Time extends Field implements PreviewableFieldInterface, SortableFieldInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Time');
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return DateTime::class . '|null';
    }

    /**
     * @var string|null The minimum allowed time
     */
    public $min;

    /**
     * @var string|null The maximum allowed time
     */
    public $max;

    /**
     * @var int The number of minutes that the timepicker options should increment by
     */
    public $minuteIncrement = 30;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (is_array($this->min)) {
            $min = DateTimeHelper::toDateTime($this->min, true);
            $this->min = $min ? $min->format('H:i') : null;
        }
        if (is_array($this->max)) {
            $max = DateTimeHelper::toDateTime($this->max, true);
            $this->max = $max ? $max->format('H:i') : null;
        }

        $this->minuteIncrement = (int)$this->minuteIncrement;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'min' => Craft::t('app', 'Min Time'),
            'max' => Craft::t('app', 'Max Time'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['minuteIncrement'], 'integer', 'min' => 1, 'max' => 60];
        $rules[] = [['max'], TimeValidator::class, 'min' => $this->min];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_TIME;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        $incrementOptions = [5, 10, 15, 30, 60];
        $incrementOptions = array_combine($incrementOptions, $incrementOptions);

        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/Time/settings', [
            'incrementOptions' => $incrementOptions,
            'field' => $this,
            'min' => $this->min ? DateTimeHelper::toDateTime(['time' => $this->min], true) : null,
            'max' => $this->max ? DateTimeHelper::toDateTime(['time' => $this->max], true) : null,
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml($value, ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('_includes/forms/time', [
            'id' => Html::id($this->handle),
            'name' => $this->handle,
            'value' => $value,
            'minTime' => $this->min,
            'maxTime' => $this->max,
            'minuteIncrement' => $this->minuteIncrement,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        return [
            [TimeValidator::class, 'min' => $this->min, 'max' => $this->max],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function searchKeywords($value, ElementInterface $element): string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getTableAttributeHtml($value, ElementInterface $element): string
    {
        if (!$value) {
            return '';
        }

        return Craft::$app->getFormatter()->asTime($value, Locale::LENGTH_SHORT);
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        if (!$value) {
            return null;
        }

        if ($value instanceof DateTime) {
            return $value;
        }

        if (is_array($value)) {
            if (empty($value['time'])) {
                return null;
            }

            return DateTimeHelper::toDateTime($value, true) ?: null;
        }

        return DateTimeHelper::toDateTime(['time' => $value], true) ?: null;
    }

    /**
     * @inheritdoc
     */
    public function serializeValue($value, ElementInterface $element = null)
    {
        /** @var DateTime|null $value */
        return $value ? $value->format('H:i:s') : null;
    }

    /**
     * @inheritdoc
     */
    public function getContentGqlType()
    {
        return DateTimeType::getType();
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getContentGqlMutationArgumentType()
    {
        return [
            'name' => $this->handle,
            'type' => DateTimeType::getType(),
            'description' => $this->instructions,
        ];
    }
}
