<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use craft\elements\Asset;
use craft\helpers\Assets;
use craft\helpers\Assets as AssetsHelper;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\validators\Validator;

/**
 * Class AuthChainStepValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AuthStepConfigValidator extends Validator
{
    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        /** @var array $steps */
        $steps = $model->$attribute;

        foreach ($steps as $stepConfig) {
            if (!array_key_exists('required', $stepConfig)) {
                $this->addError($model, $attribute, 'Invalid authentication step configuration: the `required` key is missing.');
            }
            if (empty($stepConfig['choices'])) {
                $this->addError($model, $attribute, 'An authentication step must have at least one choice defined.');
            }

        }
    }
}
