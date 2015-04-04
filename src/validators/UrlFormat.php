<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\validators;

use Craft;
use craft\app\helpers\ElementHelper;
use yii\validators\Validator;

/**
 * Will validate that the given attribute is a valid URL format.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UrlFormat extends Validator
{
	// Properties
	// =========================================================================

	/**
	 * Whether we should ensure that "{slug}" is used within the URL format.
	 *
	 * @var bool
	 */
	public $requireSlug = false;

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
		$urlFormat = $object->$attribute;

		if ($urlFormat)
		{
			// Remove any leading or trailing slashes
			$urlFormat = trim($urlFormat, '/');
			$object->$attribute = $urlFormat;

			if ($this->requireSlug)
			{
				if (!ElementHelper::doesUrlFormatHaveSlugTag($urlFormat))
				{
					$this->addError($object, $attribute, Craft::t('app', '{attribute} must contain “{slug}”', ['attribute' => $object->attribute]));
				}
			}
		}
	}
}
