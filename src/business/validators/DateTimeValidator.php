<?php
namespace Blocks;

/**
 * Blocks Pro by Pixel & Tonic
 *
 * @package   Blocks Pro
 * @author    Pixel & Tonic, Inc.
 * @copyright Copyright (c) 2012, Pixel & Tonic, Inc.
 * @license   http://pixelandtonic.com/blockspro/license1.0.html Blocks Pro License
 * @link      http://pixelandtonic.com/blockspro
 */

/**
 *
 */
class DateTimeValidator extends \CValidator
{
	/**
	 * @param $object
	 * @param $attribute
	 */
	protected function validateAttribute($object, $attribute)
	{
		$value = $object->$attribute;

		if ($value)
		{
			if (!DateTimeHelper::isValidTimeStamp((string)$value))
			{
				if (gettype($value) !== gettype(new DateTime()))
				{
					$message = Blocks::t('“{object}->{attribute}” must be a DateTime object or a valid Unix timestamp.', array('object' => get_class($object), 'attribute' => $attribute));
					$this->addError($object, $attribute, $message);
				}
			}
		}
	}
}
