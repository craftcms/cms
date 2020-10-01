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
 * Class TimeValidator.
 *
 * This is preferred over [[\yii\validators\DateValidator]] because it has tighter integration with Craft localization features,
 * without requiring as much configuration.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.12
 */
class TimeValidator extends Validator
{
    /**
     * @var string|null The minimum time allowed. Should be in the format `HH:MM`.
     * @see $tooEarly for the customized message used when the time is too early
     */
    public $min;

    /**
     * @var string|null The maximum time allowed. Should be in the format `HH:MM`.
     * @see $tooLate for the customized message used when the time is too late
     */
    public $max;

    /**
     * @var string user-defined error message used when the value is earlier than [[min]]
     */
    public $tooEarly;

    /**
     * @var string user-defined error message used when the value is later than [[max]]
     */
    public $tooLate;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->message === null) {
            $this->message = Craft::t('app', '{attribute} must be a time.');
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
        if (!$value instanceof \DateTime) {
            $value = DateTimeHelper::toDateTime(['time' => $value], true);
        }

        if (!$value) {
            $this->addError($model, $attribute, $this->message);
            return;
        }

        if ($this->min !== null) {
            $min = DateTimeHelper::toDateTime(['time' => $this->min], true);
            if (!$min) {
                throw new InvalidConfigException("Invalid minimum time: $this->min");
            }
            if ($value < $min) {
                $this->addError($model, $attribute, $this->tooEarly, [
                    'min' => Craft::$app->getFormatter()->asTime($min, Locale::LENGTH_SHORT)
                ]);
            }
        }

        if ($this->max !== null) {
            $max = DateTimeHelper::toDateTime(['time' => $this->max], true);
            if (!$max) {
                throw new InvalidConfigException("Invalid maximum time: $this->max");
            }
            if ($value > $max) {
                $this->addError($model, $attribute, $this->tooLate, [
                    'max' => Craft::$app->getFormatter()->asTime($max, Locale::LENGTH_SHORT)
                ]);
            }
        }
    }
}
