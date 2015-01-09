<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\validators;

use Craft;
use craft\app\errors\Exception;

/**
 * Class CompositeUnique validator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class CompositeUnique extends \CValidator
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	public $with;

	// Protected Methods
	// =========================================================================

	/**
	 * @param \CModel $object
	 * @param string  $attribute
	 *
	 * @throws Exception
	 * @return null
	 */
	protected function validateAttribute($object, $attribute)
	{
		$with = explode(',', $this->with);

		if (count($with) < 1)
		{
			throw new Exception(Craft::t('Attribute “with” not set.'));
		}

		$uniqueValidator = new \CUniqueValidator();
		$uniqueValidator->attributes = [$attribute];
		$uniqueValidator->message = $this->message;
		$uniqueValidator->on = $this->on;

		$conditionParams = [];
		$params = [];

		foreach ($with as $column)
		{
			$conditionParams[] = "`{$column}`=:{$column}";
			$params[":{$column}"] = $object->$column;
		}

		$condition = implode(' AND ', $conditionParams);
		$uniqueValidator->criteria = [
			'condition' => $condition,
			'params' => $params
		];

		$uniqueValidator->validate($object);
	}
}
