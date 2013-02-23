<?php
namespace Blocks;

/**
 * Will validate that the given attribute is a valid site locale ID.
 */
class LocaleValidator extends \CValidator
{
	/**
	 * @param $object
	 * @param $attribute
	 */
	protected function validateAttribute($object, $attribute)
	{
		$locale = $object->$attribute;

		if ($locale && !in_array($locale, blx()->i18n->getSiteLocaleIds()))
		{
			$message = Blocks::t('Your site isn’t set up to save content for the locale “{locale}”.', array('locale' => $locale));
			$this->addError($object, $attribute, $message);
		}
	}
}
