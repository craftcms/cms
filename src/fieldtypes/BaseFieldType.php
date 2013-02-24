<?php
namespace Craft;

/**
 * Field type base class
 */
abstract class BaseFieldType extends BaseSavableComponentType implements IFieldType
{
	/**
	 * @var ElementModel The element that the current instance is associated with
	 */
	public $element;

	/**
	 * @access protected
	 * @var string The type of component this is
	 */
	protected $componentType = 'FieldType';

	/**
	 * Returns the content attribute config.
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return AttributeType::String;
	}

	/**
	 * Returns the field's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $value
	 * @return string
	 */
	public function getInputHtml($name, $value)
	{
		return '<textarea name="'.$name.'">'.$value.'</textarea>';
	}

	/**
	 * Returns the input value as it should be saved to the database.
	 *
	 * @return mixed
	 */
	public function getPostData()
	{
		$fieldHandle = $this->model->handle;
		$value = $this->element->getRawContent($fieldHandle);
		return $this->prepPostData($value);
	}

	/**
	 * Performs any actions before a field is saved.
	 */
	public function onBeforeSave()
	{
	}

	/**
	 * Performs any actions after a field is saved.
	 */
	public function onAfterSave()
	{
	}

	/**
	 * Performs any additional actions after the element has been saved.
	 */
	public function onAfterElementSave()
	{
	}

	/**
	 * Preps the field value for use.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function prepValue($value)
	{
		return $value;
	}

	/**
	 * Preps the post data before it's saved to the database.
	 *
	 * @access protected
	 * @param mixed $value
	 * @return mixed
	 */
	protected function prepPostData($value)
	{
		return $value;
	}
}
