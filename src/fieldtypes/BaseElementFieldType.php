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
	 * @access protected
	 * @var bool $allowLimit Whether to allow the Limit setting.
	 */
	protected $allowLimit = true;

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

		if ($this->allowLimit)
		{
			$settings['limit'] = array(AttributeType::Number, 'min' => 0);
		}

		return $settings;
	}

	/**
	 * Returns the field's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		$sources = array();

		foreach ($this->getElementType()->getSources() as $key => $source)
		{
			if (!isset($source['heading']))
			{
				$sources[] = array('label' => $source['label'], 'value' => $key);
			}
		}

		return craft()->templates->render('_components/fieldtypes/elementfieldsettings', array(
			'allowMultipleSources' => $this->allowMultipleSources,
			'allowLimit'           => $this->allowLimit,
			'sources'              => $sources,
			'settings'             => $this->getSettings(),
			'type'                 => $this->getName()
		));
	}

	/**
	 * Preps the field value for use.
	 *
	 * @param mixed $value
	 * @return ElementCriteriaModel
	 */
	public function prepValue($value)
	{
		$criteria = craft()->elements->getCriteria($this->elementType);

		// $value will be an array of element IDs if there was a validation error
		// or we're loading a draft/version.
		if (is_array($value))
		{
			$criteria->id = array_filter($value);
			$criteria->fixedOrder = true;
		}
		else if ($value === '')
		{
			$criteria->id = false;
		}
		else if (isset($this->element) && $this->element->id)
		{
			$criteria->childOf = $this->element->id;
			$criteria->childField = $this->model->id;
			$criteria->order = 'sortOrder';
		}
		else
		{
			$criteria->id = false;
		}

		if ($this->allowLimit && $this->getSettings()->limit)
		{
			$criteria->limit = $this->getSettings()->limit;
		}
		else
		{
			$criteria->limit = null;
		}

		return $criteria;
	}

	/**
	 * Returns the field's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $criteria
	 * @return string
	 */
	public function getInputHtml($name, $criteria)
	{
		if (!($criteria instanceof ElementCriteriaModel))
		{
			$criteria = craft()->elements->getCriteria($this->elementType);
			$criteria->id = false;
		}

		$criteria->status = null;
		$selectionCriteria = array('status' => null);

		if (!empty($this->element->id))
		{
			$selectionCriteria['id'] = 'not '.$this->element->id;
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
			'id'             => craft()->templates->formatInputId($name),
			'storageKey'     => 'field.'.$this->model->id,
			'name'           => $name,
			'elements'       => $criteria,
			'sources'        => $sources,
			'criteria'       => $selectionCriteria,
			'limit'          => ($this->allowLimit ? $this->getSettings()->limit : null),
			'addButtonLabel' => $this->getAddButtonLabel(),
		));
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
		$criteria = $this->prepValue(null);
		$titles = array();

		foreach ($criteria->find() as $element)
		{
			$titles[] = (string) $element;
		}

		return parent::getSearchKeywords($titles);
	}

	/**
	 * Performs any additional actions after the element has been saved.
	 */
	public function onAfterElementSave()
	{
		$rawValue = $this->element->getContent()->getAttribute($this->model->handle);

		if ($rawValue !== null)
		{
			$elementIds = is_array($rawValue) ? array_filter($rawValue) : array();
			craft()->relations->saveRelations($this->model->id, $this->element->id, $elementIds);
		}
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
			'type' => mb_strtolower($this->getElementType()->getClassHandle())
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
