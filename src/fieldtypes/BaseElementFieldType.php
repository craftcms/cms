<?php
namespace Craft;

/**
 * Base element fieldtype class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.fieldtypes
 * @since     1.0
 */
abstract class BaseElementFieldType extends BaseFieldType
{
	// Properties
	// =========================================================================

	/**
	 * List of built-in component aliases to be imported.
	 *
	 * @var string $elementType
	 */
	protected $elementType;

	/**
	 * The JS class that should be initialized for the input.
	 *
	 * @var string|null $inputJsClass
	 */
	protected $inputJsClass;

	/**
	 * Whether to allow multiple source selection in the settings.
	 *
	 * @var bool $allowMultipleSources
	 */
	protected $allowMultipleSources = true;

	/**
	 * Whether to allow the Limit setting.
	 *
	 * @var bool $allowLimit
	 */
	protected $allowLimit = true;

	/**
	 * Template to use for field rendering.
	 *
	 * @var string
	 */
	protected $inputTemplate = '_includes/forms/elementSelect';

	/**
	 * Whether the elements have a custom sort order.
	 *
	 * @var bool $sortable
	 */
	protected $sortable = true;

	/**
	 * @var bool
	 */
	private $_makeExistingRelationsTranslatable = false;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->getElementType()->getName();
	}

	/**
	 * @inheritDoc IFieldType::defineContentAttribute()
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return false;
	}

	/**
	 * @inheritDoc ISavableComponentType::getSettingsHtml()
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
			'targetLocaleField'    => $this->getTargetLocaleFieldHtml(),
			'settings'             => $this->getSettings(),
			'type'                 => $this->getName()
		));
	}

	/**
	 * @inheritDoc IFieldType::validate()
	 *
	 * @param array $value
	 *
	 * @return true|string|array
	 */
	public function validate($value)
	{
		$errors = array();

		if ($this->allowLimit && ($limit = $this->getSettings()->limit) && is_array($value) && count($value) > $limit)
		{
			if ($limit == 1)
			{
				$errors[] = Craft::t('There can’t be more than one selection.');
			}
			else
			{
				$errors[] = Craft::t('There can’t be more than {limit} selections.', array('limit' => $limit));
			}
		}

		if ($errors)
		{
			return $errors;
		}
		else
		{
			return true;
		}
	}

	/**
	 * @inheritDoc IFieldType::prepValue()
	 *
	 * @param mixed $value
	 *
	 * @return ElementCriteriaModel
	 */
	public function prepValue($value)
	{
		$criteria = craft()->elements->getCriteria($this->elementType);
		$criteria->locale = $this->getTargetLocale();

		// $value will be an array of element IDs if there was a validation error or we're loading a draft/version.
		if (is_array($value))
		{
			$criteria->id = array_values(array_filter($value));
			$criteria->fixedOrder = true;
		}
		else if ($value === '')
		{
			$criteria->id = false;
		}
		else if (isset($this->element) && $this->element->id)
		{
			$criteria->relatedTo = array(
				'sourceElement' => $this->element->id,
				'sourceLocale'  => $this->element->locale,
				'field'         => $this->model->id
			);

			if ($this->sortable)
			{
				$criteria->order = 'sortOrder';
			}

			if (!$this->allowMultipleSources && $this->getSettings()->source)
			{
				$source = $this->getElementType()->getSource($this->getSettings()->source);

				// Does the source specify any criteria attributes?
				if (!empty($source['criteria']))
				{
					$criteria->setAttributes($source['criteria']);
				}
			}
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
	 * @inheritDoc IFieldType::getInputHtml()
	 *
	 * @param string $name
	 * @param mixed  $criteria
	 *
	 * @return string
	 */
	public function getInputHtml($name, $criteria)
	{
		$variables = $this->getInputTemplateVariables($name, $criteria);
		return craft()->templates->render($this->inputTemplate, $variables);
	}

	/**
	 * @inheritDoc IFieldType::getSearchKeywords()
	 *
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return string
	 */
	public function getSearchKeywords($criteria)
	{
		$titles = array();

		foreach ($criteria->find() as $element)
		{
			$titles[] = (string) $element;
		}

		return parent::getSearchKeywords($titles);
	}

	/**
	 * @inheritDoc IFieldType::onAfterElementSave()
	 *
	 * @return null
	 */
	public function onAfterElementSave()
	{
		$targetIds = $this->element->getContent()->getAttribute($this->model->handle);

		if ($targetIds !== null)
		{
			craft()->relations->saveRelations($this->model, $this->element, $targetIds);
		}
	}

	/**
	 * @inheritDoc BaseFieldType::getStaticHtml()
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function getStaticHtml($value)
	{
		if (count($value))
		{
			$html = '<div class="elementselect"><div class="elements">';

			foreach ($value as $element)
			{
				$html .= craft()->templates->render('_elements/element', array(
					'element' => $element
				));
			}

			$html .= '</div></div>';
			return $html;
		}
		else
		{
			return '<p class="light">'.Craft::t('Nothing selected.').'</p>';
		}
	}

	/**
	 * @inheritDoc IFieldType::onBeforeSave()
	 *
	 * @return null
	 */
	public function onBeforeSave()
	{
		$this->_makeExistingRelationsTranslatable = false;

		if ($this->model->id && $this->model->translatable)
		{
			$existingField = craft()->fields->getFieldById($this->model->id);

			if ($existingField && $existingField->translatable == 0)
			{
				$this->_makeExistingRelationsTranslatable = true;
			}
		}
	}

	/**
	 * @inheritDoc IFieldType::onAfterSave()
	 *
	 * @return null
	 */
	public function onAfterSave()
	{
		if ($this->_makeExistingRelationsTranslatable)
		{
			craft()->tasks->createTask('LocalizeRelations', null, array(
				'fieldId' => $this->model->id,
			));
		}
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Returns the label for the "Add" button.
	 *
	 * @return string
	 */
	protected function getAddButtonLabel()
	{
		return Craft::t('Add {type}', array(
			'type' => StringHelper::toLowerCase($this->getElementType()->getClassHandle())
		));
	}

	/**
	 * Returns the element type.
	 *
	 * @throws Exception
	 * @return BaseElementType
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

	/**
	 * Returns an array of variables that should be passed to the input template.
	 *
	 * @param string $name
	 * @param mixed  $criteria
	 *
	 * @return array
	 */
	protected function getInputTemplateVariables($name, $criteria)
	{
		$settings = $this->getSettings();

		if (!($criteria instanceof ElementCriteriaModel))
		{
			$criteria = craft()->elements->getCriteria($this->elementType);
			$criteria->id = false;
		}

		$criteria->status = null;
		$criteria->localeEnabled = null;

		$selectionCriteria = $this->getInputSelectionCriteria();
		$selectionCriteria['localeEnabled'] = null;
		$selectionCriteria['locale'] = $this->getTargetLocale();

		return array(
			'jsClass'            => $this->inputJsClass,
			'elementType'        => new ElementTypeVariable($this->getElementType()),
			'id'                 => craft()->templates->formatInputId($name),
			'fieldId'            => $this->model->id,
			'storageKey'         => 'field.'.$this->model->id,
			'name'               => $name,
			'elements'           => $criteria,
			'sources'            => $this->getInputSources(),
			'criteria'           => $selectionCriteria,
			'sourceElementId'    => (isset($this->element->id) ? $this->element->id : null),
			'limit'              => ($this->allowLimit ? $settings->limit : null),
			'addButtonLabel'     => $this->getAddButtonLabel(),
		);
	}

	/**
	 * Returns an array of the source keys the field should be able to select elements from.
	 *
	 * @return array
	 */
	protected function getInputSources()
	{
		if ($this->allowMultipleSources)
		{
			$sources = $this->getSettings()->sources;
		}
		else
		{
			$sources = array($this->getSettings()->source);
		}

		return $sources;
	}

	/**
	 * Returns any additional criteria parameters limiting which elements the field should be able to select.
	 *
	 * @return array
	 */
	protected function getInputSelectionCriteria()
	{
		return array();
	}

	/**
	 * Returns the locale that target elements should have.
	 *
	 * @return string
	 */
	protected function getTargetLocale()
	{
		if (craft()->isLocalized())
		{
			$targetLocale = $this->getSettings()->targetLocale;

			if ($targetLocale)
			{
				return $targetLocale;
			}
			else if (isset($this->element))
			{
				return $this->element->locale;
			}
		}

		return craft()->getLanguage();
	}

	/**
	 * Returns the HTML for the Target Locale setting.
	 *
	 * @return string|null
	 */
	protected function getTargetLocaleFieldHtml()
	{
		if (craft()->isLocalized() && $this->getElementType()->isLocalized())
		{
			$localeOptions = array(
				array('label' => Craft::t('Same as source'), 'value' => null)
			);

			foreach (craft()->i18n->getSiteLocales() as $locale)
			{
				$localeOptions[] = array('label' => $locale->getName(), 'value' => $locale->getId());
			}

			return craft()->templates->renderMacro('_includes/forms', 'selectField', array(
				array(
					'label' => Craft::t('Target Locale'),
					'instructions' => Craft::t('Which locale do you want to select {type} in?', array('type' => StringHelper::toLowerCase($this->getName()))),
					'id' => 'targetLocale',
					'name' => 'targetLocale',
					'options' => $localeOptions,
					'value' => $this->getSettings()->targetLocale
				)
			));
		}
	}

	/**
	 * @inheritDoc BaseSavableComponentType::defineSettings()
	 *
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

		$settings['targetLocale'] = AttributeType::String;

		if ($this->allowLimit)
		{
			$settings['limit'] = array(AttributeType::Number, 'min' => 0);
		}

		return $settings;
	}
}
