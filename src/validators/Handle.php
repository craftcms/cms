<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\validators;

use Craft;
use craft\app\helpers\StringHelper;
use yii\validators\Validator;

/**
 * Class Handle validator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Handle extends Validator
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
	public $reservedWords = [];

	/**
	 * @var array
	 */
	protected static $baseReservedWords = ['id', 'dateCreated', 'dateUpdated', 'uid', 'this', 'true', 'false', 'y', 'n', 'yes', 'no', 'classHandle', 'handle', 'name', 'attributeNames', 'attributes', 'attribute', 'rules', 'attributeLabels', 'fields', 'content', 'rawContent', 'section'];

	// Protected Methods
	// =========================================================================

	/**
	 * @param $object
	 * @param $attribute
	 *
	 * @return null
	 */
	public function validateAttribute($object, $attribute)
	{
		$handle = $object->$attribute;

		// Handles are always required, so if it's blank, the required validator will catch this.
		if ($handle)
		{
			$reservedWords = array_merge($this->reservedWords, static::$baseReservedWords);
			$reservedWords = array_map(['\craft\app\helpers\StringHelper', 'toLowerCase'], $reservedWords);
			$lcHandle = StringHelper::toLowerCase($handle);

			if (in_array($lcHandle, $reservedWords))
			{
				$message = Craft::t('app', '“{handle}” is a reserved word.', ['handle' => $handle]);
				$this->addError($object, $attribute, $message);
			}
			else
			{
				if (!preg_match('/^'.static::$handlePattern.'$/', $handle))
				{
					$altMessage = Craft::t('app', '“{handle}” isn’t a valid handle.', ['handle' => $handle]);
					$message = $this->message !== null ? $this->message : $altMessage;
					$this->addError($object, $attribute, $message);
				}
			}
		}
	}
}
