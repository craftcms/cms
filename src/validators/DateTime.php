<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\validators;

use Craft;
use craft\app\helpers\DateTimeHelper;
use yii\validators\Validator;

/**
 * Class DateTime validator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class DateTime extends Validator
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
