<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use yii\validators\StringValidator;

/**
 * Class UserPasswordValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UserPasswordValidator extends StringValidator
{
    // Properties
    // =========================================================================

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

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct(array $config = [])
    {
        // Default min
        if (!isset($config['min'])) {
            $config['min'] = 6;
        }

        // Default max
        if (!isset($config['max'])) {
            $config['max'] = 160;
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
