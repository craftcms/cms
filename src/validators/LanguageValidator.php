<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\validators;

use Craft;
use yii\validators\Validator;

/**
 * Will validate that the given attribute is a valid site language.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class LanguageValidator extends Validator
{
    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $language = $model->$attribute;

        if ($language && !in_array($language, Craft::$app->getI18n()->getSiteLocaleIds(), true)) {
            $message = Craft::t('app', 'Your system isn’t set up to save content for the language “{language}”.', ['language' => $language]);
            $this->addError($model, $attribute, $message);
        }
    }
}
