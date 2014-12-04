<?php
namespace Craft;

/**
 * Will validate that the given attribute is a valid URI.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.validators
 * @since     1.0
 */
class UriValidator extends \CValidator
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
	 * @param $object
	 * @param $attribute
	 *
	 * @return null
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
