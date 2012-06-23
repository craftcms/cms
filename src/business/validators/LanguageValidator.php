<?php
namespace Blocks;

/**
 * Will validate that the given attribute is a valid language ID by calling \CLocale::getInstance, which checks
 * against the file system.
 */
class LanguageValidator extends \CValidator
{
	/**
	 * @param $object
	 * @param $attribute
	 */
	protected function validateAttribute($object, $attribute)
	{
		$value = $object->$attribute;

		if (!Locale::exists($value))
		{
			$message = "Couldn’t find the language id “{$value}”.";
			$this->addError($object, $attribute, $message);
		}
	}
}
