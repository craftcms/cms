<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\validators;

use Craft;
use craft\app\helpers\ElementHelper;
use yii\validators\Validator;

/**
 * Will validate that the given attribute is a valid URL format.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UrlFormat extends Validator
{
    // Properties
    // =========================================================================

    /**
     * Whether we should ensure that "{slug}" is used within the URL format.
     *
     * @var bool
     */
    public $requireSlug = false;

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $urlFormat = $model->$attribute;

        if ($urlFormat) {
            // Remove any leading or trailing slashes/spaces
            $urlFormat = trim($urlFormat, '/ ');
            $model->$attribute = $urlFormat;

            if ($this->requireSlug) {
                if (!ElementHelper::doesUrlFormatHaveSlugTag($urlFormat)) {
                    $this->addError($model, $attribute, Craft::t('app', '{attribute} must contain “{slug}”', ['attribute' => $model->$attribute]));
                }
            }
        }
    }
}
