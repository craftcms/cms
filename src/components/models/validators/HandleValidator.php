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

		// Handles are always required, so if it's blank, the required validator will catch this.
		if ($handle)
		{
			$reservedWords = array_merge($this->reservedWords, static::$baseReservedWords);

			if (in_array($handle, $reservedWords))
			{
				$message = Blocks::t('“{handle}” is a reserved word.', array('handle' => $handle));
				$this->addError($object, $attribute, $message);
			}
			else
			{
				if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $handle))
				{
					$altMessage = Blocks::t('“{handle}” isn’t a valid handle.', array('handle' => $handle));
					$message = $this->message !== null ? $this->message : $altMessage;
					$this->addError($object, $attribute, $message);
				}
			}
		}
	}
}
