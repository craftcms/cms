<?php
namespace Craft;

/**
 * Will validate that the given attribute is a valid URI.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.validators
 * @since     1.0
 */
class UriValidator extends \CValidator
{
	public $pattern = '/^[^\s]+$/';

	/**
	 * @param $object
	 * @param $attribute
	 */
	protected function validateAttribute($object, $attribute)
	{
		$uri = $object->$attribute;

		if ($uri && !preg_match($this->pattern, $uri))
		{
			$message = Craft::t('{attribute} is not a valid URI');
			$this->addError($object, $attribute, $message);
		}
	}
}
