<?php
namespace Craft;

/**
 *
 */
class HandleValidator extends \CValidator
{
	public static $handlePattern = '[a-zA-Z][a-zA-Z0-9_]*';

	public $reservedWords = array();

	protected static $baseReservedWords = array('id', 'dateCreated', 'dateUpdated', 'uid', 'this', 'true', 'false', 'y', 'n', 'yes', 'no', 'classHandle', 'handle', 'name', 'attributeNames', 'attributes', 'attribute', 'rules', 'attributeLabels', 'fields', 'content', 'rawContent');

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
			$reservedWords = array_map(array('Craft\StringHelper', 'toLowerCase'), $reservedWords);
			$lcHandle = StringHelper::toLowerCase($handle);

			if (in_array($lcHandle, $reservedWords))
			{
				$message = Craft::t('“{handle}” is a reserved word.', array('handle' => $handle));
				$this->addError($object, $attribute, $message);
			}
			else
			{
				if (!preg_match('/^'.static::$handlePattern.'$/', $handle))
				{
					$altMessage = Craft::t('“{handle}” isn’t a valid handle.', array('handle' => $handle));
					$message = $this->message !== null ? $this->message : $altMessage;
					$this->addError($object, $attribute, $message);
				}
			}
		}
	}
}
