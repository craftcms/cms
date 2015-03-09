<?php
namespace Craft;

/**
 * Field type base class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.fieldtypes
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
	 * The type of component, e.g. "Plugin", "Widget", "FieldType", etc. Defined by the component type's base class.
	 *
	 * @var string
	 */
	protected $componentType = 'FieldType';

	/**
	 * @var bool Whether the field is fresh.
	 * @see isFresh()
	 * @see setIsFresh()
	 */
	private $_isFresh;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IFieldType::setElement()
	 *
	 * @param $element
	 *
	 * @return null
	 */
	public function setElement(BaseElementModel $element)
	{
		$this->element = $element;
	}

	/**
	 * @inheritDoc IFieldType::defineContentAttribute()
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return AttributeType::String;
	}

	/**
	 * @inheritDoc IFieldType::onBeforeSave()
	 *
	 * @return null
	 */
	public function onBeforeSave()
	{
	}

	/**
	 * @inheritDoc IFieldType::onAfterSave()
	 *
	 * @return null
	 */
	public function onAfterSave()
	{
	}

	/**
	 * @inheritDoc IFieldType::onBeforeDelete()
	 *
	 * @return null
	 */
	public function onBeforeDelete()
	{
	}

	/**
	 * @inheritDoc IFieldType::onAfterDelete()
	 *
	 * @return null
	 */
	public function onAfterDelete()
	{
	}

	/**
	 * @inheritDoc IFieldType::getInputHtml()
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return string
	 */
	public function getInputHtml($name, $value)
	{
		return HtmlHelper::encodeParams('<textarea name="{name}">{value}</textarea>', array('name' => $name, 'value' => $value));
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
	 * @inheritDoc IFieldType::prepValueFromPost()
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
	 * @inheritDoc IFieldType::validate()
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
	 * @inheritDoc IFieldType::onAfterElementSave()
	 *
	 * @return null
	 */
	public function onAfterElementSave()
	{
	}

	/**
	 * @inheritDoc IFieldType::getSearchKeywords()
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
	 * @inheritDoc IFieldType::prepValue()
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
	 * @inheritDoc IFieldType::modifyElementsQuery()
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

	/**
	 * Sets whether the field is fresh.
	 *
	 * @param bool|null $isFresh
	 *
	 * @return null
	 */
	public function setIsFresh($isFresh)
	{
		$this->_isFresh = $isFresh;
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
		if (!isset($this->_isFresh))
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

			$this->_isFresh = (!$element || (empty($element->getContent()->id) && !$element->hasErrors()));
		}

		return $this->_isFresh;
	}
}
