<?php
namespace Blocks;

/**
 * Will validate that the given attribute is a valid language ID by calling \CLocale::getInstance, which checks
 * against the file system.
 */
class LocaleValidator extends \CValidator
{
	/**
	 * @param $object
	 * @param $attribute
	 */
	protected function validateAttribute($object, $attribute)
	{
		$value = $object->$attribute;

		if ($value !== null && !LocaleData::exists($value))
		{
			$message = Blocks::t('Couldn’t find the locale “{value}”.', array('value' => $value));
			$this->addError($object, $attribute, $message);
		}
	}
}
