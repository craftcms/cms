<?php
namespace Craft;

/**
 * Base element fieldtype class
 */
abstract class BaseElementFieldType extends BaseFieldType
{
	/**
	 * @access protected
	 * @var string $elementType The element type this field deals with.
	 */
	protected $elementType;

	/**
	 * @access protected
	 * @var string|null $inputJsClass The JS class that should be initialized for the input.
	 */
	protected $inputJsClass;

	/**
	 * Returns the type of field this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->_getElementType()->getName();
	}

	/**
	 * Returns the content attribute config.
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return false;
	}

	/**
	 * Defines the settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'sources' => AttributeType::Mixed,
			'limit'   => array(AttributeType::Number, 'min' => 0),
		);
	}

	/**
	 * Returns the field's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return craft()->templates->render('_components/fieldtypes/elementfieldsettings', array(
			'sources'  => $this->_getElementType()->getSources(),
			'settings' => $this->getSettings(),
			'type'     => $this->getName()
		));
	}

	/**
	 * Preps the field value for use.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function prepValue($value)
	{
		// $value will be an array of element IDs if there was a validation error
		// or we're loading a draft/version.
		if (is_array($value))
		{
			$elements = craft()->relations->getElementsById($this->elementType, array_filter($value));
		}
		else if (isset($this->element) && $this->element->id)
		{
			$elements = craft()->relations->getRelatedElements($this->element->id, $this->model->id, $this->elementType);
		}
		else
		{
			$elements = array();
		}

		if ($this->getSettings()->limit)
		{
			$elements = array_slice($elements, 0, $this->getSettings()->limit);
		}

		return $elements;
	}

	/**
	 * Returns the field's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $elements
	 * @return string
	 */
	public function getInputHtml($name, $elements)
	{
		$id = rtrim(preg_replace('/[\[\]]+/', '-', $name), '-');

		if (!is_array($elements))
		{
			$elements = array();
		}

		return craft()->templates->render('_includes/forms/elementSelect', array(
			'jsClass'     => $this->inputJsClass,
			'elementType' => new ElementTypeVariable($this->_getElementType()),
			'id'          => $id,
			'name'        => $name,
			'elements'    => $elements,
			'sources'     => $this->getSettings()->sources,
			'limit'       => $this->getSettings()->limit,
		));
	}

	/**
	 * Performs any additional actions after the element has been saved.
	 */
	public function onAfterElementSave()
	{
		$rawValue = $this->element->getRawContent($this->model->handle);
		$elementIds = is_array($rawValue) ? array_filter($rawValue) : array();
		craft()->relations->saveRelations($this->model->id, $this->element->id, $elementIds);
	}

	/**
	 * Returns the element type.
	 *
	 * @access private
	 * @return BaseElementType
	 * @throws Exception
	 */
	private function _getElementType()
	{
		$elementType = craft()->elements->getElementType($this->elementType);

		if (!$elementType)
		{
			throw new Exception(Craft::t('No element type exists with the class “{class}”', array('class' => $this->elementType)));
		}

		return $elementType;
	}
}
