<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
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

		if ($value)
		{
			if (!($value instanceof \DateTime))
			{
				if (!DateTimeHelper::isValidTimeStamp((string)$value))
				{
					$message = Craft::t('app', '“{object}->{attribute}” must be a DateTime object or a valid Unix timestamp.', ['object' => get_class($object), 'attribute' => $attribute]);
					$this->addError($object, $attribute, $message);
				}
			}
		}
	}
}
