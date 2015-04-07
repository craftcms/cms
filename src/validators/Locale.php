<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\validators;

use Craft;
use yii\validators\Validator;

/**
 * Will validate that the given attribute is a valid site locale ID.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Locale extends Validator
{
	// Protected Methods
	// =========================================================================

	/**
	 * @param $object
	 * @param $attribute
	 *
	 * @return null
	 */
	public function validateAttribute($object, $attribute)
	{
		$locale = $object->$attribute;

		if ($locale && !in_array($locale, Craft::$app->getI18n()->getSiteLocaleIds()))
		{
			$message = Craft::t('app', 'Your site isn’t set up to save content for the locale “{locale}”.', ['locale' => $locale]);
			$this->addError($object, $attribute, $message);
		}
	}
}
