<?php
namespace Blocks;

/**
 * Model template variable class
 *
 * Limits templates to only access a model's attributes.
 */
class ModelVariable
{
	protected $model;

	/**
	 * Constructor
	 *
	 * @param \CModel $model
	 */
	function __construct($model)
	{
		$this->model = $model;
	}

	/**
	 * Returns whether an attribute exists.
	 *
	 * @param string $name
	 */
	function __isset($name)
	{
		return in_array($name, $this->model->attributeNames());
	}

	/**
	 * Attribute getter
	 *
	 * @param string $name
	 * @return mixed
	 */
	function __get($name)
	{
		return $this->model->getAttribute($name);
	}

	/**
	 * Returns the model's ID.
	 *
	 * @return int|null
	 */
	public function id()
	{
		if ($this->model instanceof BaseRecord)
			return $this->model->id;
	}

	/**
	 * Returns the model's creation date.
	 *
	 * @return DateTime|null
	 */
	public function dateCreated()
	{
		if ($this->model instanceof BaseRecord)
			return $this->model->dateCreated;
	}

	/**
	 * Returns the model's last updated date.
	 *
	 * @return int|null
	 */
	public function dateUpdated()
	{
		if ($this->model instanceof BaseRecord)
			return $this->model->dateUpdated;
	}

	/**
	 * Returns the model's errors.
	 *
	 * @return array
	 */
	public function errors()
	{
		return $this->model->getErrors();
	}
}
