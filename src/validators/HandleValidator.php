<?php
namespace Craft;

/**
 * Class HandleValidator
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.validators
 * @since     1.0
 */
class HandleValidator extends \CValidator
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	public static $handlePattern = '[a-zA-Z][a-zA-Z0-9_]*';

	/**
	 * @var array
	 */
	public $reservedWords = array();

	/**
	 * @var array
	 */
	protected static $baseReservedWords = array('id', 'dateCreated', 'dateUpdated', 'uid', 'this', 'true', 'false', 'y', 'n', 'yes', 'no', 'classHandle', 'handle', 'name', 'attributeNames', 'attributes', 'attribute', 'rules', 'attributeLabels', 'fields', 'content', 'rawContent', 'section');

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
