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
		$handle = $object->$attribute;

		$reservedWords = array_merge($this->reservedWords, static::$baseReservedWords);

		if (in_array($handle, $reservedWords))
		{
			$message = Blocks::t('“{handle}” is a reserved word.', array('handle' => $handle));
			$this->addError($object, $attribute, $message);
		}
		else
		{
			TemplateHelper::registerTwigAutoloader();

			if (!preg_match(\Twig_Lexer::REGEX_NAME, $handle))
			{
				$altMessage = Blocks::t('“{handle}” isn’t a valid handle.', array('handle' => $handle));
				$message = $this->message !== null ? $this->message : $altMessage;
				$this->addError($object, $attribute, $message);
			}
		}
	}
}
