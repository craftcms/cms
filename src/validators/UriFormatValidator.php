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
 * @since 3.0.0
 */
class UriFormatValidator extends Validator
{
    /**
     * @var bool Whether we should ensure that "{slug}" is used within the URI format.
     */
    public $requireSlug = false;

    /**
     * @var bool Whether to ensure that the URI format doesn’t begin with the actionTrigger or cpTrigger.
     * @since 3.2.10
     */
    public $disallowTriggers = true;

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

            if ($this->disallowTriggers) {
                $generalConfig = Craft::$app->getConfig()->getGeneral();
                $firstSeg = explode('/', $uriFormat, 2)[0];

                if ($firstSeg === $generalConfig->actionTrigger) {
                    $this->addError($model, $attribute, Craft::t('app', '{attribute} cannot start with the {setting} config setting.', [
                        'setting' => 'actionTrigger',
                    ]));
                } else if ($generalConfig->cpTrigger && $firstSeg === $generalConfig->cpTrigger) {
                    $this->addError($model, $attribute, Craft::t('app', '{attribute} cannot start with the {setting} config setting.', [
                        'setting' => 'cpTrigger',
                    ]));
                }
            }
        }
    }
}
