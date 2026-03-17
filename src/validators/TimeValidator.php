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
use DateTime;
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
    public ?string $min = null;

    /**
     * @var string|null The maximum time allowed. Should be in the format `HH:MM`.
     * @see $tooLate for the customized message used when the time is too late
     */
    public ?string $max = null;

    /**
     * @var string|null user-defined error message used when the value is earlier than [[min]]
     */
    public ?string $tooEarly = null;

    /**
     * @var string|null user-defined error message used when the value is later than [[max]]
     */
    public ?string $tooLate = null;

    /**
     * @var string|null user-defined error message used when the value is out of range and [[min]] is greater than [[max]]
     * @since 5.10.0
     */
    public ?string $outOfRange = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->message)) {
            $this->message = Craft::t('app', '{attribute} must be a time.');
        }

        if (isset($this->min) && !isset($this->tooEarly)) {
            $this->tooEarly = Craft::t('app', '{attribute} must be no earlier than {min}.');
        }

        if (isset($this->max) && !isset($this->tooLate)) {
            $this->tooLate = Craft::t('app', '{attribute} must be no later than {max}.');
        }

        if (isset($this->min) && isset($this->max) && !isset($this->outOfRange)) {
            $this->outOfRange = Craft::t('app', '{attribute} must be between {min} and {max}.');
        }
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function validateAttribute($model, $attribute): void
    {
        $value = $model->$attribute;
        if (!$value instanceof DateTime) {
            $value = DateTimeHelper::toDateTime(['time' => $value], true);
        }

        if (!$value) {
            $this->addError($model, $attribute, $this->message);
            return;
        }

        // If min > max (e.g. 10pm - 2am),

        $min = $max = null;

        if (isset($this->min)) {
            $min = DateTimeHelper::toDateTime(['time' => $this->min], true);
            if (!$min) {
                throw new InvalidConfigException("Invalid minimum time: $this->min");
            }
        }

        if (isset($this->max)) {
            $max = DateTimeHelper::toDateTime(['time' => $this->max], true);
            if (!$max) {
                throw new InvalidConfigException("Invalid maximum time: $this->max");
            }
        }

        // Overnight time range? (e.g. 10pm-2am)
        if ($min !== null && $max !== null && $min > $max) {
            if ($value < $min && $value > $max) {
                $this->addError($model, $attribute, $this->outOfRange, [
                    'min' => Craft::$app->getFormatter()->asTime($min, Locale::LENGTH_SHORT),
                    'max' => Craft::$app->getFormatter()->asTime($max, Locale::LENGTH_SHORT),
                ]);
            }
            return;
        }

        if ($min !== null && $value < $min) {
            $this->addError($model, $attribute, $this->tooEarly, [
                'min' => Craft::$app->getFormatter()->asTime($min, Locale::LENGTH_SHORT),
            ]);
        }

        if ($max !== null && $value > $max) {
            $this->addError($model, $attribute, $this->tooLate, [
                'max' => Craft::$app->getFormatter()->asTime($max, Locale::LENGTH_SHORT),
            ]);
        }
    }
}
