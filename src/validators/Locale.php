<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\validators;

use craft\app\Craft;

/**
 * Will validate that the given attribute is a valid site locale ID.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Locale extends \CValidator
{
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
		$locale = $object->$attribute;

		if ($locale && !in_array($locale, Craft::$app->i18n->getSiteLocaleIds()))
		{
			$message = Craft::t('Your site isn’t set up to save content for the locale “{locale}”.', array('locale' => $locale));
			$this->addError($object, $attribute, $message);
		}
	}
}
