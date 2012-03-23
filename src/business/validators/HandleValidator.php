<?php
namespace Blocks;

/**
 *
 */
class HandleValidator extends \CValidator
{
	public $reservedWords = array('this', 'true', 'false', 'id', 'date_created', 'date_updated', 'uid');

	/**
	 * @param $object
	 * @param $attribute
	 */
	protected function validateAttribute($object, $attribute)
	{
		$value = $object->$attribute;

		if (in_array($value, $this->reservedWords))
		{
			$message = "“{$value}” is a reserved word.";
			$this->addError($object, $attribute, $message);
		}
		else if (!preg_match('/^'.TemplateParser::tagPattern.'$/', $value))
		{
			$message = $this->message !== null ? $this->message : '{attribute} is not a valid handle.';
			$this->addError($object, $attribute, $message);
		}
	}
}
