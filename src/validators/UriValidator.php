<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\validators;

use Craft;
use yii\validators\Validator;

/**
 * Will validate that the given attribute is a valid URI.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UriValidator extends Validator
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public $pattern = '/^[^\s]+$/u';

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $uri = $model->$attribute;

        if ($uri && !preg_match($this->pattern, $uri)) {
            $message = Craft::t('app', '{attribute} is not a valid URI', ['attribute' => $model->$attribute]);
            $this->addError($model, $attribute, $message);
        }
    }
}
