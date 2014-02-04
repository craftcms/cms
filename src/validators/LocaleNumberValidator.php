<?php
namespace Craft;

/**
 * By default Yii doesn't take into account countries that use a comma for their decimal separator.  This fixes that.
 */
class LocaleNumberValidator extends \CNumberValidator
{
	/**
	 * Validates the attribute of the object.
	 * If there is any error, the error message is added to the object.
	 * @param \CModel $object the object being validated
	 * @param string $attribute the attribute being validated
	 */
	protected function validateAttribute($object, $attribute)
	{
		$value = $object->$attribute;

		if ($this->allowEmpty && $this->isEmpty($value))
		{
			return;
		}

		if ($this->integerOnly)
		{
			if (!preg_match($this->integerPattern, "$value"))
			{
				$message = $this->message !== null ? $this->message : Craft::t('“{attribute}” must be an integer.');
				$this->addError($object, $attribute, $message);
			}
		}
		else
		{
			// For decimals, convert from a locale specific format to a normalized one before we check validity.
			$value = $object->$attribute = LocalizationHelper::normalizeNumber($value);

			if (!preg_match($this->numberPattern, "$value"))
			{
				$message = $this->message !== null ? $this->message : Craft::t('“{attribute}” must be a number.');
				$this->addError($object, $attribute, $message);
			}
		}

		if (is_numeric($value))
		{
			if ($this->min !== null && $value < $this->min)
			{
				$message = $this->tooSmall !== null ? $this->tooSmall : Craft::t('“{attribute}” is too small (minimum is {min}).');
				$this->addError($object, $attribute, $message, array('{min}' => $this->min));
			}

			if ($this->max !== null && $value > $this->max)
			{
				$message = $this->tooBig !== null ? $this->tooBig : Craft::t('“{attribute}” is too big (maximum is {max}).');
				$this->addError($object, $attribute, $message, array('{max}' => $this->max));
			}
		}
	}
}
