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

		// Handles are always required, so if it's blank, the required validator will catch this.
		if ($value &&  gettype($value) !== gettype(new DateTime()))
		{
			$message = Blocks::t('“{object}->{attribute}” must be a DateTime object.', array('object' => get_class($object), 'attribute' => $attribute));
			$this->addError($object, $attribute, $message);
		}
	}
}
