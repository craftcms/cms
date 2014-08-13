<?php
namespace Craft;

/**
 * Field type base class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.fieldtypes
 * @since     1.0
 */
abstract class BaseFieldType extends BaseSavableComponentType implements IFieldType
{
	// Properties
	// =========================================================================

	/**
	 * The element that the current instance is associated with.
	 *
	 * @var BaseElementModel
	 */
	public $element;

	/**
	 * The type of component this is.
	 *
	 * @var string
	 */
	protected $componentType = 'FieldType';

	// Public Methods
	// =========================================================================

	/**
	 * @return mixed Returns the content attribute config.
	 */
	public function defineContentAttribute()
	{
		return AttributeType::String;
	}

	/**
	 * Performs any actions before a field is saved.
	 *
	 * @return null
	 */
	public function onBeforeSave()
	{
	}

	/**
	 * Performs any actions after a field is saved.
	 *
	 * @return null
	 */
	public function onAfterSave()
	{
	}

	/**
	 * Performs any actions before a field is deleted.
	 *
	 * @return null
	 */
	public function onBeforeDelete()
	{
	}

	/**
	 * Performs any actions after a field is deleted.
	 *
	 * @return null
	 */
	public function onAfterDelete()
	{
	}

	/**
	 * Returns the field's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return string
	 */
	public function getInputHtml($name, $value)
	{
		return '<textarea name="'.$name.'">'.$value.'</textarea>';
	}

	/**
	 * Returns static HTML for the field's value.
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function getStaticHtml($value)
	{
		// Just return the input HTML with disabled inputs by default
		craft()->templates->startJsBuffer();
		$inputHtml = $this->getInputHtml(StringHelper::randomString(), $value);
		$inputHtml = preg_replace('/<(?:input|textarea|select)\s[^>]*/i', '$0 disabled', $inputHtml);
		craft()->templates->clearJsBuffer();

		return $inputHtml;
	}

	/**
	 * Returns the input value as it should be saved to the database.
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function prepValueFromPost($value)
	{
		if (method_exists($this, 'prepPostData'))
		{
			craft()->deprecator->log('BaseFieldType::prepPostData()', 'BaseFieldType::prepPostData() has been deprecated. Use prepValueFromPost() instead.');
			return $this->prepPostData($value);
		}
		else
		{
			return $value;
		}
	}

	/**
	 * Validates the value beyond the checks that were assumed based on the content attribute.
	 *
	 * Returns 'true' or any custom validation errors.
	 *
	 * @param mixed $value
	 *
	 * @return true|string|array
	 */
	public function validate($value)
	{
		return true;
	}

	/**
	 * Performs any additional actions after the element has been saved.
	 *
	 * @return null
	 */
	public function onAfterElementSave()
	{
	}

	/**
	 * Returns the search keywords that should be associated with this field,
	 * based on the prepped post data.
	 *
	 * @param mixed $value
	 *
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
	 *
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
	 *
	 * @return null|false
	 */
	public function modifyElementsQuery(DbCommand $query, $value)
	{
		if ($value !== null)
		{
			if ($this->defineContentAttribute())
			{
				$handle = $this->model->handle;
				$query->andWhere(DbHelper::parseParam('content.'.craft()->content->fieldColumnPrefix.$handle, $value, $query->params));
			}
			else
			{
				return false;
			}
		}
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Returns the location in POST that this field's content was pulled from.
	 *
	 * @return string|null
	 */
	protected function getContentPostLocation()
	{
		if (isset($this->element) && isset($this->model))
		{
			$elementContentPostLocation = $this->element->getContentPostLocation();

			if ($elementContentPostLocation)
			{
				return $elementContentPostLocation.'.'.$this->model->handle;
			}
		}
	}

	/**
	 * Returns whether this is the first time the element's content has been edited.
	 *
	 * @return bool
	 */
	protected function isFresh()
	{
		// If this is for a Matrix block, we're more interested in its owner
		if (isset($this->element) && $this->element->getElementType() == ElementType::MatrixBlock)
		{
			$element = $this->element->getOwner();
		}
		else
		{
			$element = $this->element;
		}

		return (!$element || (empty($element->getContent()->id) && !$element->hasErrors()));
	}
}
