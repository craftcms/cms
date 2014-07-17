<?php
namespace Craft;

/**
 * Class DateTimeValidator
 *
 * @package craft.app.validators
 */
class DateTimeValidator extends \CValidator
{
	/**
	 * @param $object
	 * @param $attribute
	 */
	protected function validateAttribute($object, $attribute)
	{
		$value = $object->$attribute;

		if ($value)
		{
			if (!($value instanceof \DateTime))
			{
				if (!DateTimeHelper::isValidTimeStamp((string)$value))
				{
					$message = Craft::t('“{object}->{attribute}” must be a DateTime object or a valid Unix timestamp.', array('object' => get_class($object), 'attribute' => $attribute));
					$this->addError($object, $attribute, $message);
				}
			}
		}
	}
}
