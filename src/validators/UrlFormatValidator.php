<?php
namespace Craft;

/**
 * Will validate that the given attribute is a valid URL format.
 */
class UrlFormatValidator extends \CValidator
{
	/**
	 * @param $object
	 * @param $attribute
	 */
	protected function validateAttribute($object, $attribute)
	{
		$urlFormat = $object->$attribute;

		if ($urlFormat)
		{
			$element = (object) array('slug' => StringHelper::randomString());
			$uri = craft()->templates->renderObjectTemplate($urlFormat, $element);

			if (strpos($uri, $element->slug) === false)
			{
				$this->addError($object, $attribute, Craft::t('{attribute} must contain “{slug}”'));
			}
		}
	}
}
