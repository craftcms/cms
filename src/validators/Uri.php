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
 * Will validate that the given attribute is a valid URI.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Uri extends Validator
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
	public function validateAttribute($object, $attribute)
	{
		$uri = $object->$attribute;

		if ($uri && !preg_match($this->pattern, $uri))
		{
			$message = Craft::t('app', '{attribute} is not a valid URI', ['attribute' => $object->attribute]);
			$this->addError($object, $attribute, $message);
		}
	}
}
