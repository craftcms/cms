<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use yii\base\Model;
use yii\validators\StringValidator;

/**
 * Class UserPasswordValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UserPasswordValidator extends StringValidator
{
    /**
     * @since 3.5.18
     */
    const MIN_PASSWORD_LENGTH = 6;

    /**
     * @since 3.5.18
     */
    const MAX_PASSWORD_LENGTH = 160;

    /**
     * @var bool Whether the password must be different from the existing password.
     */
    public $forceDifferent = false;

    /**
     * @var string|null The user's current (hashed) password.
     */
    public $currentPassword;

    /**
     * @var string|null User-defined error message used when the new password is the same as [[currentPassword]].
     */
    public $sameAsCurrent;

    /**
     * @inheritdoc
     */
    public function __construct(array $config = [])
    {
        // Default min
        if (!isset($config['min'])) {
            $config['min'] = self::MIN_PASSWORD_LENGTH;
        }

        // Default max
        if (!isset($config['max'])) {
            $config['max'] = self::MAX_PASSWORD_LENGTH;
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->forceDifferent && $this->sameAsCurrent === null) {
            $this->sameAsCurrent = Craft::t('app', '{attribute} must be set to a new password.');
        }
    }

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        /** @var Model $model */
        parent::validateAttribute($model, $attribute);

        if ($model->hasErrors($attribute)) {
            return;
        }

        if ($this->forceDifferent && $this->currentPassword) {
            $newPassword = $model->$attribute;
            if (Craft::$app->getSecurity()->validatePassword($newPassword, $this->currentPassword)) {
                $this->addError($model, $attribute, $this->sameAsCurrent);
            }
        }
    }


    /**
     * @inheritdoc
     */
    public function isEmpty($value)
    {
        if ($this->isEmpty !== null) {
            return call_user_func($this->isEmpty, $value);
        }

        // Don't let an empty string count as empty
        return $value === null;
    }
}
