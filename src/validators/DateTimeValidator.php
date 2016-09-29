<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\validators;

use craft\app\helpers\DateTimeHelper;
use yii\validators\Validator;

/**
 * Class DateTimeValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class DateTimeValidator extends Validator
{
    // Protected Methods
    // =========================================================================

    /**
     * @param $object
     * @param $attribute
     *
     * @return void
     */
    public function validateAttribute($object, $attribute)
    {
        $value = $object->$attribute;

        if ($value && !$value instanceof \DateTime) {
            // Just automatically convert it rather than complaining about it
            $object->$attribute = DateTimeHelper::toDateTime($value);
        }
    }
}
