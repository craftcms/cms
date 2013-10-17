<?php
namespace Craft;

/**
 * Field type base class
 */
abstract class BaseFieldType extends BaseSavableComponentType implements IFieldType
{
	/**
	 * @var BaseElementModel The element that the current instance is associated with
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
	 * @param mixed $value
	 * @return mixed
	 */
	public function prepValueFromPost($value)
	{
		// TODO: Remove redundant prepPostData() in Craft 2.0
		return $this->prepPostData($value);
	}

	/**
	 * Performs any additional actions after the element has been saved.
	 */
	public function onAfterElementSave()
	{
	}

	/**
	 * Returns the search keywords that should be associated with this field,
	 * based on the prepped post data.
	 *
	 * @param mixed $value
	 * @return string
	 */
	public function getSearchKeywords($value)
	{
		return StringHelper::arrayToString($value, ' ');
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
	 * Modifies an element query that's filtering by this field.
	 *
	 * @param DbCommand $query
	 * @param mixed     $value
	 * @return null|false
	 */
	public function modifyElementsQuery(DbCommand $query, $value)
	{
		if ($this->defineContentAttribute())
		{
			$handle = $this->model->handle;
			$query->andWhere(DbHelper::parseParam('content.field_'.$handle, $value, $query->params));
		}
		else
		{
			return false;
		}
	}

	/**
	 * Preps the post data before it's saved to the database.
	 *
	 * @access protected
	 * @param mixed $value
	 * @return mixed
	 * @deprecated Deprecated since 1.1
	 */
	protected function prepPostData($value)
	{
		return $value;
	}

	/**
	 * Returns whether this is the first time the element's content has been edited.
	 *
	 * @access protected
	 * @return bool
	 */
	protected function isFresh()
	{
		return (!isset($this->element) || (empty($this->element->getContent()->id) && !$this->element->hasErrors()));
	}
}
