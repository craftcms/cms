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
			$message = Blocks::t('“{value}” is a reserved word.', array('value', $value));
			$this->addError($object, $attribute, $message);
		}
		else if (!preg_match('/^'.TemplateParser::varPattern.'$/', $value))
		{
			$altMessage = Blocks::t('“{attribute}” isn’t a valid handle.', $attribute);;
			$message = $this->message !== null ? $this->message : $altMessage;
			$this->addError($object, $attribute, $message);
		}
	}
}
