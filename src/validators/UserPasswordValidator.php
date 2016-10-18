<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\validators;

use Craft;
use yii\validators\StringValidator;
use yii\validators\ValidationAsset;
use yii\validators\Validator;

/**
 * Class UserPasswordValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UserPasswordValidator extends StringValidator
{
    // Properties
    // =========================================================================

    /**
     * @var boolean Whether the password must be different from the existing password.
     */
    public $forceDifferent = false;

    /**
     * @var string The user's current (hashed) password.
     */
    public $currentPassword;

    /**
     * @var string User-defined error message used when the new password is the same as [[currentPassword]].
     */
    public $sameAsCurrent;

    /**
     * @inheritdoc
     */
    public $min = 6;

    /**
     * @inheritdoc
     */
    public $max = 160;

    // Public Methods
    // =========================================================================

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
