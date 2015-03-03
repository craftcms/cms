<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

use Craft;
use craft\app\helpers\ModelHelper;
use craft\app\helpers\StringHelper;
use yii\base\UnknownMethodException;

/**
 * Model base class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class Model extends \yii\base\Model
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	protected $classSuffix = 'Model';

	/**
	 * @var
	 */
	private $_classHandle;

	// Public Methods
	// =========================================================================

	/**
	 * Magic __call() method, used for chain-setting attribute values.
	 *
	 * @param string $name
	 * @param array  $arguments
	 *
	 * @return Model
	 * @throws UnknownMethodException when calling an unknown method
	 */
	public function __call($name, $arguments)
	{
		try
		{
			return parent::__call($name, $arguments);
		}
		catch (UnknownMethodException $e)
		{
			// Is this one of our attributes?
			if (in_array($name, $this->attributes()))
			{
				$copy = $this->copy();

				if (count($arguments) == 1)
				{
					$copy->$name = $arguments[0];
				}
				else
				{
					$copy->$name = $arguments;
				}

				return $copy;
			}

			throw $e;
		}
	}

	/**
	 * Populates a new model instance with a given set of attributes.
	 *
	 * @param mixed $values
	 *
	 * @return Model
	 */
	public static function populateModel($values)
	{
		$class = get_called_class();
		return new $class($values);
	}

	/**
	 * Mass-populates models based on an array of attribute arrays.
	 *
	 * @param array       $data
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public static function populateModels($data, $indexBy = null)
	{
		$models = [];

		if (is_array($data))
		{
			foreach ($data as $values)
			{
				$model = static::populateModel($values);

				if ($indexBy)
				{
					$models[$model->$indexBy] = $model;
				}
				else
				{
					$models[] = $model;
				}
			}
		}

		return $models;
	}

	/**
	 * Get the class name, sans namespace and suffix.
	 *
	 * @return string
	 */
	public function getClassHandle()
	{
		if (!isset($this->_classHandle))
		{
			// Chop off the namespace
			$classHandle = mb_substr(get_class($this), StringHelper::length(__NAMESPACE__) + 1);

			// Chop off the class suffix
			$suffixLength = StringHelper::length($this->classSuffix);

			if (mb_substr($classHandle, -$suffixLength) == $this->classSuffix)
			{
				$classHandle = mb_substr($classHandle, 0, -$suffixLength);
			}

			$this->_classHandle = $classHandle;
		}

		return $this->_classHandle;
	}

	/**
	 * Returns all errors in a single list.
	 *
	 * @return array
	 */
	public function getAllErrors()
	{
		$errors = [];

		foreach ($this->getErrors() as $attributeErrors)
		{
			$errors = array_merge($errors, $attributeErrors);
		}

		return $errors;
	}

	/**
	 * Returns a copy of this model.
	 *
	 * @return Model
	 */
	public function copy()
	{
		$class = get_class($this);
		return new $class($this->getAttributes());
	}

	// Deprecated Methods
	// -------------------------------------------------------------------------

	/**
	 * Returns the first error of the specified attribute.
	 *
	 * @param string $attribute The attribute name.
	 * @return string The error message. Null is returned if no error.
	 *
	 * @deprecated in 3.0. Use [[getFirstError()]] instead.
	 */
	public function getError($attribute)
	{
		Craft::$app->deprecator->log('Model::getError()', 'getError() has been deprecated. Use getFirstError() instead.');
		return $this->getFirstError($attribute);
	}
}
