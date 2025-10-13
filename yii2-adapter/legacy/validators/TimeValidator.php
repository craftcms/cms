<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use craft\helpers\DateTimeHelper;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Translation\Locale;
use DateTime;
use yii\base\InvalidConfigException;
use yii\validators\Validator;
use function CraftCms\Cms\t;

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
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->message)) {
            $this->message = t('{attribute} must be a time.');
        }

        if (isset($this->min) && !isset($this->tooEarly)) {
            $this->tooEarly = t('{attribute} must be no earlier than {min}.');
        }

        if (isset($this->max) && !isset($this->tooLate)) {
            $this->tooLate = t('{attribute} must be no later than {max}.');
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

        if (isset($this->min)) {
            $min = DateTimeHelper::toDateTime(['time' => $this->min], true);
            if (!$min) {
                throw new InvalidConfigException("Invalid minimum time: $this->min");
            }
            if ($value < $min) {
                $this->addError($model, $attribute, $this->tooEarly, [
                    'min' => I18N::getFormatter()->asTime($min, Locale::LENGTH_SHORT),
                ]);
            }
        }

        if (isset($this->max)) {
            $max = DateTimeHelper::toDateTime(['time' => $this->max], true);
            if (!$max) {
                throw new InvalidConfigException("Invalid maximum time: $this->max");
            }
            if ($value > $max) {
                $this->addError($model, $attribute, $this->tooLate, [
                    'max' => I18N::getFormatter()->asTime($max, Locale::LENGTH_SHORT),
                ]);
            }
        }
    }
}
