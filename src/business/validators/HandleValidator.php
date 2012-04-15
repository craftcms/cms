<?php
namespace Blocks;

/**
 *
 */
class HandleValidator extends \CValidator
{
	public $reservedWords = array();
	protected static $baseReservedWords = array('this', 'true', 'false', 'y', 'n', 'yes', 'no');


	/**
	 * @param $object
	 * @param $attribute
	 */
	protected function validateAttribute($object, $attribute)
	{
		$value = $object->$attribute;

		$reservedWords = array_merge($this->reservedWords, self::$baseReservedWords);

		if (in_array($value, $reservedWords))
		{
			$message = "“{$value}” is a reserved word.";
			$this->addError($object, $attribute, $message);
		}
		else if (!preg_match('/^'.TemplateParser::varPattern.'$/', $value))
		{
			$message = $this->message !== null ? $this->message : '{attribute} is not a valid handle.';
			$this->addError($object, $attribute, $message);
		}
	}
}
