<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\i18n\Locale;
use yii\base\InvalidConfigException;
use yii\validators\Validator;

/**
 * Class DateTimeValidator.
 *
 * This is preferred over [[\yii\validators\DateValidator]] because it has tighter integration with Craft localization features,
 * without requiring as much configuration.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class DateTimeValidator extends Validator
{
    /**
     * @var string|null The minimum date allowed
     * @see $tooEarly for the customized message used when the date is too early
     * @since 3.5.12
     */
    public $min;

    /**
     * @var string|null The maximum date allowed
     * @see $tooLate for the customized message used when the date is too late
     * @since 3.5.12
     */
    public $max;

    /**
     * @var string user-defined error message used when the value is earlier than [[min]]
     * @since 3.5.12
     */
    public $tooEarly;

    /**
     * @var string user-defined error message used when the value is later than [[max]]
     * @since 3.5.12
     */
    public $tooLate;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->message === null) {
            $this->message = Craft::t('app', '{attribute} must be a date.');
        }

        if ($this->min !== null && $this->tooEarly === null) {
            $this->tooEarly = Craft::t('app', '{attribute} must be no earlier than {min}.');
        }

        if ($this->max !== null && $this->tooLate === null) {
            $this->tooLate = Craft::t('app', '{attribute} must be no later than {max}.');
        }
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;
        if ($normalized = (!$value instanceof \DateTime)) {
            $value = DateTimeHelper::toDateTime($value);
        }

        if (!$value) {
            $this->addError($model, $attribute, $this->message);
            return;
        }

        if ($this->min !== null) {
            $min = DateTimeHelper::toDateTime($this->min);
            if (!$min) {
                throw new InvalidConfigException("Invalid minimum date: $this->min");
            }
            if ($value < $min) {
                $this->addError($model, $attribute, $this->tooEarly, [
                    'min' => Craft::$app->getFormatter()->asDate($this->min, Locale::LENGTH_SHORT),
                ]);
            }
        }

        if ($this->max !== null) {
            $max = DateTimeHelper::toDateTime($this->max);
            if (!$max) {
                throw new InvalidConfigException("Invalid maximum date: $this->max");
            }
            if ($value > $max) {
                $this->addError($model, $attribute, $this->tooLate, [
                    'max' => Craft::$app->getFormatter()->asDate($this->max, Locale::LENGTH_SHORT),
                ]);
            }
        }

        if ($normalized) {
            // Update the value on the model to the DateTime object
            $model->$attribute = $value;
        }
    }
}
