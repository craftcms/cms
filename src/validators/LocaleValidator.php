<?php
namespace Craft;

/**
 * Will validate that the given attribute is a valid site locale ID.
 *
 * @package craft.app.validators
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

		if ($locale && !in_array($locale, craft()->i18n->getSiteLocaleIds()))
		{
			$message = Craft::t('Your site isn’t set up to save content for the locale “{locale}”.', array('locale' => $locale));
			$this->addError($object, $attribute, $message);
		}
	}
}
