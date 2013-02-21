<?php
namespace Blocks;

/**
 * Field type base class
 */
abstract class BaseFieldType extends BaseSavableComponentType
{
	/**
	 * @var EntryModel The entry that the current instance is associated with
	 */
	public $entry;

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
	 * @abstract
	 * @param string $name
	 * @param mixed  $value
	 * @return string
	 */
	abstract public function getInputHtml($name, $value);

	/**
	 * Returns the input value as it should be saved to the database.
	 *
	 * @return mixed
	 */
	public function getPostData()
	{
		$fieldHandle = $this->model->handle;
		$value = $this->entry->getRawContent($fieldHandle);
		return $this->prepPostData($value);
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
	 * Performs any additional actions after the entry has been saved.
	 */
	public function onAfterEntrySave()
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
}
