<?php
namespace Craft;

/**
 * Will validate that the given attribute is a valid URL format.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.validators
 * @since     2.0
 */
class UrlFormatValidator extends \CValidator
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
	protected function validateAttribute($object, $attribute)
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
					$this->addError($object, $attribute, Craft::t('{attribute} must contain “{slug}”'));
				}
			}
		}
	}
}
