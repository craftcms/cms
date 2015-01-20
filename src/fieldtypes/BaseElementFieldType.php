<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fieldtypes;

use Craft;
use craft\app\elementtypes\BaseElementType;
use craft\app\enums\AttributeType;
use craft\app\errors\Exception;
use craft\app\helpers\StringHelper;
use craft\app\models\ElementCriteria as ElementCriteriaModel;
use craft\app\variables\ElementType;

/**
 * Base element fieldtype class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->getElementType()->getName();
	}

	/**
	 * @inheritDoc FieldTypeInterface::defineContentAttribute()
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return false;
	}

	/**
	 * @inheritDoc SavableComponentTypeInterface::getSettingsHtml()
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		$sources = [];

		foreach ($this->getElementType()->getSources() as $key => $source)
		{
			if (!isset($source['heading']))
			{
				$sources[] = ['label' => $source['label'], 'value' => $key];
			}
		}

		return Craft::$app->templates->render('_components/fieldtypes/elementfieldsettings', [
			'allowMultipleSources' => $this->allowMultipleSources,
			'allowLimit'           => $this->allowLimit,
			'sources'              => $sources,
			'targetLocaleField'    => $this->getTargetLocaleFieldHtml(),
			'settings'             => $this->getSettings(),
			'type'                 => $this->getName()
		]);
	}

	/**
	 * @inheritDoc FieldTypeInterface::validate()
	 *
	 * @param array $value
	 *
	 * @return true|string|array
	 */
	public function validate($value)
	{
		$errors = [];

		if ($this->allowLimit && ($limit = $this->getSettings()->limit) && is_array($value) && count($value) > $limit)
		{
			if ($limit == 1)
			{
				$errors[] = Craft::t('app', 'There can’t be more than one selection.');
			}
			else
			{
				$errors[] = Craft::t('app', 'There can’t be more than {limit} selections.', ['limit' => $limit]);
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
	 * @inheritDoc FieldTypeInterface::prepValue()
	 *
	 * @param mixed $value
	 *
	 * @return ElementCriteriaModel
	 */
	public function prepValue($value)
	{
		$criteria = Craft::$app->elements->getCriteria($this->elementType);
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
			$criteria->relatedTo = [
				'sourceElement' => $this->element->id,
				'sourceLocale'  => $this->element->locale,
				'field'         => $this->model->id
			];

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
	 * @inheritDoc FieldTypeInterface::getInputHtml()
	 *
	 * @param string $name
	 * @param mixed  $criteria
	 *
	 * @return string
	 */
	public function getInputHtml($name, $criteria)
	{
		$variables = $this->getInputTemplateVariables($name, $criteria);
		return Craft::$app->templates->render($this->inputTemplate, $variables);
	}

	/**
	 * @inheritDoc FieldTypeInterface::getSearchKeywords()
	 *
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return string
	 */
	public function getSearchKeywords($criteria)
	{
		$titles = [];

		foreach ($criteria->find() as $element)
		{
			$titles[] = (string) $element;
		}

		return parent::getSearchKeywords($titles);
	}

	/**
	 * @inheritDoc FieldTypeInterface::onAfterElementSave()
	 *
	 * @return null
	 */
	public function onAfterElementSave()
	{
		$targetIds = $this->element->getContent()->getAttribute($this->model->handle);

		if ($targetIds !== null)
		{
			Craft::$app->relations->saveRelations($this->model, $this->element, $targetIds);
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
				$html .= Craft::$app->templates->render('_elements/element', [
					'element' => $element
				]);
			}

			$html .= '</div></div>';
			return $html;
		}
		else
		{
			return '<p class="light">'.Craft::t('app', 'Nothing selected.').'</p>';
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
		return Craft::t('app', 'Add {type}', [
			'type' => StringHelper::toLowerCase($this->getElementType()->getClassHandle())
		]);
	}

	/**
	 * Returns the element type.
	 *
	 * @throws Exception
	 * @return BaseElementType
	 */
	protected function getElementType()
	{
		$elementType = Craft::$app->elements->getElementType($this->elementType);

		if (!$elementType)
		{
			throw new Exception(Craft::t('app', 'No element type exists with the class “{class}”', ['class' => $this->elementType]));
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
			$criteria = Craft::$app->elements->getCriteria($this->elementType);
			$criteria->id = false;
		}

		$criteria->status = null;
		$criteria->localeEnabled = null;

		$selectionCriteria = $this->getInputSelectionCriteria();
		$selectionCriteria['localeEnabled'] = null;
		$selectionCriteria['locale'] = $this->getTargetLocale();

		return [
			'jsClass'            => $this->inputJsClass,
			'elementType'        => new ElementType($this->getElementType()),
			'id'                 => Craft::$app->templates->formatInputId($name),
			'fieldId'            => $this->model->id,
			'storageKey'         => 'field.'.$this->model->id,
			'name'               => $name,
			'elements'           => $criteria,
			'sources'            => $this->getInputSources(),
			'criteria'           => $selectionCriteria,
			'sourceElementId'    => (isset($this->element->id) ? $this->element->id : null),
			'limit'              => ($this->allowLimit ? $settings->limit : null),
			'addButtonLabel'     => $this->getAddButtonLabel(),
		];
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
			$sources = [$this->getSettings()->source];
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
		return [];
	}

	/**
	 * Returns the locale that target elements should have.
	 *
	 * @return string
	 */
	protected function getTargetLocale()
	{
		if (Craft::$app->isLocalized())
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

		return Craft::$app->getLanguage();
	}

	/**
	 * Returns the HTML for the Target Locale setting.
	 *
	 * @return string|null
	 */
	protected function getTargetLocaleFieldHtml()
	{
		if (Craft::$app->isLocalized() && $this->getElementType()->isLocalized())
		{
			$localeOptions = [
				['label' => Craft::t('app', 'Same as source'), 'value' => null]
			];

			foreach (Craft::$app->getI18n()->getSiteLocales() as $locale)
			{
				$localeOptions[] = ['label' => $locale->getName(), 'value' => $locale->getId()];
			}

			return Craft::$app->templates->renderMacro('_includes/forms', 'selectField', [
				[
					'label' => Craft::t('app', 'Target Locale'),
					'instructions' => Craft::t('app', 'Which locale do you want to select {type} in?', ['type' => StringHelper::toLowerCase($this->getName())]),
					'id' => 'targetLocale',
					'name' => 'targetLocale',
					'options' => $localeOptions,
					'value' => $this->getSettings()->targetLocale
				]
			]);
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
			$settings['limit'] = [AttributeType::Number, 'min' => 0];
		}

		return $settings;
	}
}
