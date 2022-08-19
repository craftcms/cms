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
 * @since 3.0.0
 */
class LanguageValidator extends Validator
{
    /**
     * @var bool Whether to limit the value to the sites' languages
     */
    public bool $onlySiteLanguages = true;

    /**
     * @var string|null The error message to use if the value isn't allowed
     */
    public ?string $notAllowed = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        if (!isset($this->notAllowed)) {
            if ($this->onlySiteLanguages) {
                $this->notAllowed = Craft::t('app', '{value} is not a valid site language.');
            } else {
                $this->notAllowed = Craft::t('app', '{value} is not a valid language.');
            }
        }

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute): void
    {
        $original = $model->$attribute;
        try {
            $value = Localization::normalizeLanguage($original);
        } catch (InvalidArgumentException) {
            $this->addError($model, $attribute, $this->notAllowed);
            return;
        }

        $result = $this->validateValue($value);
        if (!empty($result)) {
            $this->addError($model, $attribute, $result[0], $result[1]);
        } elseif ($value !== $original) {
            // update the model with the normalized value
            try {
                $model->$attribute = $value;
            } catch (UnknownPropertyException) {
                // fine, validate the original value
                parent::validateAttribute($model, $attribute);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function validateValue($value): ?array
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
