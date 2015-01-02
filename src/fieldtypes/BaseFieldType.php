<?php
namespace craft\app\fieldtypes;

use craft\app\components\BaseSavableComponentType;
use craft\app\models\BaseElementModel;

/**
 * Field type base class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.fieldtypes
 * @since     3.0
 */
abstract class BaseFieldType extends BaseSavableComponentType implements FieldTypeInterface
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
	 * The type of component, e.g. "Plugin", "Widget", "FieldType", etc. Defined by the component type's base class.
	 *
	 * @var string
	 */
	protected $componentType = 'FieldType';

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc FieldTypeInterface::setElement()
	 *
	 * @param BaseElementModel $element
	 *
	 * @return null
	 */
	public function setElement(BaseElementModel $element)
	{
		$this->element = $element;
	}

	/**
	 * @inheritDoc FieldTypeInterface::defineContentAttribute()
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return AttributeType::String;
	}

	/**
	 * @inheritDoc FieldTypeInterface::onBeforeSave()
	 *
	 * @return null
	 */
	public function onBeforeSave()
	{
	}

	/**
	 * @inheritDoc FieldTypeInterface::onAfterSave()
	 *
	 * @return null
	 */
	public function onAfterSave()
	{
	}

	/**
	 * @inheritDoc FieldTypeInterface::onBeforeDelete()
	 *
	 * @return null
	 */
	public function onBeforeDelete()
	{
	}

	/**
	 * @inheritDoc FieldTypeInterface::onAfterDelete()
	 *
	 * @return null
	 */
	public function onAfterDelete()
	{
	}

	/**
	 * @inheritDoc FieldTypeInterface::getInputHtml()
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
	 * @inheritDoc FieldTypeInterface::prepValueFromPost()
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function prepValueFromPost($value)
	{
		return $value;
	}

	/**
	 * @inheritDoc FieldTypeInterface::validate()
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
	 * @inheritDoc FieldTypeInterface::onAfterElementSave()
	 *
	 * @return null
	 */
	public function onAfterElementSave()
	{
	}

	/**
	 * @inheritDoc FieldTypeInterface::getSearchKeywords()
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
	 * @inheritDoc FieldTypeInterface::prepValue()
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
	 * @inheritDoc FieldTypeInterface::modifyElementsQuery()
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
