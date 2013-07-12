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
	 * @access protected
	 * @var bool $allowMultipleSources Whether to allow multiple source selection in the settings.
	 */
	protected $allowMultipleSources = true;

	/**
	 * Returns the type of field this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->getElementType()->getName();
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
		if ($this->allowMultipleSources)
		{
			$settings['sources'] = AttributeType::Mixed;
		}
		else
		{
			$settings['source'] = AttributeType::String;
		}

		$settings['limit'] = array(AttributeType::Number, 'min' => 0);

		return $settings;
	}

	/**
	 * Returns the field's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return craft()->templates->render('_components/fieldtypes/elementfieldsettings', array(
			'allowMultipleSources' => $this->allowMultipleSources,
			'sources'              => $this->getElementType()->getSources(),
			'settings'             => $this->getSettings(),
			'type'                 => $this->getName()
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

		return new RelationFieldData($elements);
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

		if (!($elements instanceof RelationFieldData))
		{
			$elements = new RelationFieldData();
		}

		$criteria = array('status' => null);

		if (!empty($this->element->id))
		{
			$criteria['id'] = 'not '.$this->element->id;
		}

		if ($this->allowMultipleSources)
		{
			$sources = $this->getSettings()->sources;
		}
		else
		{
			$sources = array($this->getSettings()->source);
		}

		return craft()->templates->render('_includes/forms/elementSelect', array(
			'jsClass'        => $this->inputJsClass,
			'elementType'    => new ElementTypeVariable($this->getElementType()),
			'id'             => $id,
			'name'           => $name,
			'elements'       => $elements->all,
			'sources'        => $sources,
			'criteria'       => $criteria,
			'limit'          => $this->getSettings()->limit,
			'addButtonLabel' => $this->getAddButtonLabel(),
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
	 * Returns the label for the "Add" button.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getAddButtonLabel()
	{
		return Craft::t('Add {type}', array(
			'type' => strtolower($this->getElementType()->getClassHandle())
		));
	}

	/**
	 * Returns the element type.
	 *
	 * @access protected
	 * @return BaseElementType
	 * @throws Exception
	 */
	protected function getElementType()
	{
		$elementType = craft()->elements->getElementType($this->elementType);

		if (!$elementType)
		{
			throw new Exception(Craft::t('No element type exists with the class “{class}”', array('class' => $this->elementType)));
		}

		return $elementType;
	}
}
