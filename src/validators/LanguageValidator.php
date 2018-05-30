<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use craft\helpers\Localization;
use yii\base\InvalidArgumentException;
use yii\base\UnknownPropertyException;
use yii\validators\Validator;

/**
 * Will validate that the given attribute is a valid site language.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class LanguageValidator extends Validator
{
    // Properties
    // =========================================================================

    /**
     * @param bool Whether to limit the value to the sites' languages
     */
    public $onlySiteLanguages = true;

    /**
     * @param string The error message to use if the value isn't allowed.
     */
    public $notAllowed;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->notAllowed === null) {
            if ($this->onlySiteLanguages) {
                $this->notAllowed = Craft::t('app', '{value} is not a valid site language.');
            } else {
                $this->notAllowed = Craft::t('app', '{value} is not a valid language.');
            }
        }

        parent::init();
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $original = $model->$attribute;
        try {
            $value = Localization::normalizeLanguage($original);
        } catch (InvalidArgumentException $e) {
            $this->addError($model, $attribute, $this->notAllowed);
            return;
        }

        $result = $this->validateValue($value);
        if (!empty($result)) {
            $this->addError($model, $attribute, $result[0], $result[1]);
        } else if ($value !== $original) {
            // update the model with the normalized value
            try {
                $model->$attribute = $value;
            } catch (UnknownPropertyException $e) {
                // fine, validate the original value
                parent::validateAttribute($model, $attribute);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function validateValue($value)
    {
        if ($this->onlySiteLanguages) {
            $allowed = Craft::$app->getI18n()->getSiteLocaleIds();
        } else {
            $allowed = Craft::$app->getI18n()->getAllLocaleIds();
        }

        if ($value && !in_array($value, $allowed, true)) {
            return [$this->notAllowed, []];
        }

        return null;
    }
}
