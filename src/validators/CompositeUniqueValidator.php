<?php
namespace Craft;

/**
 * Class CompositeUniqueValidator
 *
 * @package craft.app.validators
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
