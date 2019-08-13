<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use craft\helpers\ElementHelper;
use craft\helpers\StringHelper;
use yii\base\Model;
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

            self::validateActionTrigger($model, $attribute);
        }
    }

    /**
     * @param Model $model - The model to run validation on
     * @param $attribute - The name of the property on $model to validate
     */
    public static function validateActionTrigger(Model $model, $attribute)
    {
        $actionTrigger = Craft::$app->getConfig()->getGeneral()->actionTrigger;

        // https://github.com/craftcms/cms/issues/4154
        if (StringHelper::startsWith($model->$attribute, $actionTrigger)) {
            $model->addError($attribute, Craft::t('app', 'The {attribute} cannot start with the “actionTrigger”: {actionTrigger}', [
                'actionTrigger' => $actionTrigger,
                'attribute' => $attribute
            ]));
        }
    }
}
