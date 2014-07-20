<?php
namespace Craft;

/**
 * Will validate that the given attribute is a valid URI.
 *
 * @package craft.app.validators
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
