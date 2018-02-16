<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use craft\helpers\DateTimeHelper;
use yii\validators\Validator;

/**
 * Class DateTimeValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DateTimeValidator extends Validator
{
    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;

        if ($value && !$value instanceof \DateTime) {
            // Just automatically convert it rather than complaining about it
            $model->$attribute = DateTimeHelper::toDateTime($value);
        }
    }
}
