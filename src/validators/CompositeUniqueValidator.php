<?php
namespace Craft;

/**
 * Class CompositeUniqueValidator
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.validators
 * @since     1.0
 */
class CompositeUniqueValidator extends \CValidator
{
	public $with;

	/**
	 * @param \CModel $object
	 * @param string  $attribute
	 * @throws Exception
	 */
	protected function validateAttribute($object, $attribute)
	{
		$with = explode(',', $this->with);

		if (count($with) < 1)
		{
			throw new Exception(Craft::t('Attribute “with” not set.'));
		}

		$uniqueValidator = new \CUniqueValidator();
		$uniqueValidator->attributes = array($attribute);
		$uniqueValidator->message = $this->message;
		$uniqueValidator->on = $this->on;

		$conditionParams = array();
		$params = array();

		foreach ($with as $column)
		{
			$conditionParams[] = "`{$column}`=:{$column}";
			$params[":{$column}"] = $object->$column;
		}

		$condition = implode(' AND ', $conditionParams);
		$uniqueValidator->criteria = array(
			'condition' => $condition,
			'params' => $params
		);

		$uniqueValidator->validate($object);
	}
}
