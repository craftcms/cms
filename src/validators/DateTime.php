<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\validators;

use Craft;
use craft\app\helpers\DateTimeHelper;
use yii\validators\Validator;

/**
 * Class DateTime validator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DateTime extends Validator
{
	// Protected Methods
	// =========================================================================

	/**
	 * @param $object
	 * @param $attribute
	 *
	 * @return null
	 */
	public function validateAttribute($object, $attribute)
	{
		$value = $object->$attribute;

		if ($value && !$value instanceof \DateTime)
		{
			// Just automatically convert it rather than complaining about it
			$object->$attribute = DateTimeHelper::toDateTime($value);
		}
	}
}
