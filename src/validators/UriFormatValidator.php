<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use craft\helpers\ElementHelper;
use yii\validators\Validator;

/**
 * Will validate that the given attribute is a valid URI format.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UriFormatValidator extends Validator
{
    // Properties
    // =========================================================================

    /**
     * Whether we should ensure that "{slug}" is used within the URI format.
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
        $uriFormat = $model->$attribute;

        if ($uriFormat) {
            // Remove any leading or trailing slashes/spaces
            $uriFormat = trim($uriFormat, '/ ');
            $model->$attribute = $uriFormat;

            if ($this->requireSlug && !ElementHelper::doesUriFormatHaveSlugTag($uriFormat)) {
                $this->addError($model, $attribute, Craft::t('app', '{attribute} must contain “{slug}”', [
                    'attribute' => $model->$attribute
                ]));
            }
        }
    }
}
