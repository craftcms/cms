<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fields;

use Craft;
use craft\app\base\ElementInterface;
use craft\app\base\Field;
use craft\app\base\Element;
use craft\app\elements\db\ElementQuery;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\helpers\StringHelper;
use craft\app\tasks\LocalizeRelations;

/**
 * BaseRelationField is the base class for classes representing a relational field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class BaseRelationField extends Field
{
	// Static
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public static function hasContentColumn()
	{
		return false;
	}

	/**
	 * Returns the element class associated with this field type.
	 *
	 * @return Element The Element class name
	 */
	protected static function elementType()
	{
	}

	// Properties
	// =========================================================================

	/**
	 * @var string[] The source keys that this field can relate elements from (used if [[allowMultipleSources]] is set to true)
	 */
	public $sources;

	/**
	 * @var string The source key that this field can relate elements from (used if [[allowMultipleSources]] is set to false)
	 */
	public $source;

	/**
	 * @var string The locale that this field should relate elements from
	 */
	public $targetLocale;

	/**
	 * @var integer The maximum number of relations this field can have (used if [[allowLimit]] is set to true)
	 */
	public $limit;

	/**
	 * @var string|null The JS class that should be initialized for the input
	 */
	protected $inputJsClass;

	/**
	 * @var boolean Whether to allow multiple source selection in the settings
	 */
	protected $allowMultipleSources = true;

	/**
	 * @var boolean Whether to allow the Limit setting
	 */
	protected $allowLimit = true;

	/**
	 * @var string Template to use for field rendering
	 */
	protected $inputTemplate = '_includes/forms/elementSelect';

	/**
	 * @var boolean Whether the elements have a custom sort order
	 */
	protected $sortable = true;

	/**
	 * @var boolean Whether existing relations should be made translatable after the field is saved
	 */
	private $_makeExistingRelationsTranslatable = false;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function settingsAttributes()
	{
		$attributes = parent::settingsAttributes();
		$attributes[] = 'sources';
		$attributes[] = 'source';
		$attributes[] = 'targetLocale';
		$attributes[] = 'limit';
		return $attributes;
	}

	/**
	 * @inheritdoc
	 */
	public function beforeSave()
	{
		$this->_makeExistingRelationsTranslatable = false;

		if ($this->id && $this->translatable)
		{
			$existingField = Craft::$app->getFields()->getFieldById($this->id);

			if ($existingField && !$existingField->translatable)
			{
				$this->_makeExistingRelationsTranslatable = true;
			}
		}

		return parent::beforeSave();
	}

	/**
	 * @inheritdoc
	 */
	public function afterSave()
	{
		if ($this->_makeExistingRelationsTranslatable)
		{
			Craft::$app->getTasks()->queueTask([
				'type' => LocalizeRelations::className(),
				'fieldId' => $this->id,
			]);
		}

		parent::afterSave();
	}

	/**
	 * Returns the label for the "Add" button.
	 *
	 * @return string
	 */
	abstract public function getAddButtonLabel();

	/**
	 * @inheritdoc
	 */
	public function getSettingsHtml()
	{
		$sources = [];

		$class = static::elementType();

		foreach ($class::getSources() as $key => $source)
		{
			if (!isset($source['heading']))
			{
				$sources[] = ['label' => $source['label'], 'value' => $key];
			}
		}

		return Craft::$app->getView()->renderTemplate('_components/fieldtypes/elementfieldsettings', [
			'allowMultipleSources' => $this->allowMultipleSources,
			'allowLimit'           => $this->allowLimit,
			'sources'              => $sources,
			'targetLocaleField'    => $this->getTargetLocaleFieldHtml(),
			'field'                => $this,
			'displayName'          => static::displayName(),
		]);
	}

	/**
	 * @inheritdoc
	 */
	function validateValue($value, $element)
	{
		$errors = [];

		if ($this->allowLimit && $this->limit && is_array($value) && count($value) > $this->limit)
		{
			if ($this->limit == 1)
			{
				$errors[] = Craft::t('app', 'There can’t be more than one selection.');
			}
			else
			{
				$errors[] = Craft::t('app', 'There can’t be more than {limit} selections.', ['limit' => $this->limit]);
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
	 * @inheritdoc
	 */
	public function prepareValue($value, $element)
	{
		$class = static::elementType();
		/** @var ElementQuery $query */
		$query = $class::find()
			->locale($this->getTargetLocale($element));

		// $value will be an array of element IDs if there was a validation error or we're loading a draft/version.
		if (is_array($value))
		{
			$query
				->id(array_values(array_filter($value)))
				->fixedOrder();
		}
		else if ($value !== '' && !empty($element->id))
		{
			$query->relatedTo([
				'sourceElement' => $element->id,
				'sourceLocale'  => $element->locale,
				'field'         => $this->id
			]);

			if ($this->sortable)
			{
				$query->orderBy('sortOrder');
			}

			if (!$this->allowMultipleSources && $this->source)
			{
				$source = $class::getSourceByKey($this->source);

				// Does the source specify any criteria attributes?
				if (isset($source['criteria']))
				{
					$query->configure($source['criteria']);
				}
			}
		}
		else
		{
			$query->id(false);
		}

		if ($this->allowLimit && $this->limit)
		{
			$query->limit($this->limit);
		}
		else
		{
			$query->limit(null);
		}

		return $query;
	}

	/**
	 * @inheritdoc
	 */
	public function getInputHtml($value, $element)
	{
		$variables = $this->getInputTemplateVariables($value, $element);
		return Craft::$app->getView()->renderTemplate($this->inputTemplate, $variables);
	}

	/**
	 * @inheritdoc
	 */
	public function getSearchKeywords($value, $element)
	{
		$titles = [];

		foreach ($value->all() as $element)
		{
			$titles[] = (string) $element;
		}

		return parent::getSearchKeywords($titles, $element);
	}

	/**
	 * @inheritdoc
	 */
	public function afterElementSave(ElementInterface $element)
	{
		$value = $this->getElementValue($element);

		if ($value instanceof ElementQueryInterface)
		{
			$value = $value->id;
		}

		if ($value !== null)
		{
			Craft::$app->getRelations()->saveRelations($this, $element, $value);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getStaticHtml($value, $element)
	{
		if (count($value))
		{
			$html = '<div class="elementselect"><div class="elements">';

			foreach ($value as $relatedElement)
			{
				$html .= Craft::$app->getView()->renderTemplate('_elements/element', [
					'element' => $relatedElement
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
	 * Returns an array of variables that should be passed to the input template.
	 *
	 * @param ElementQueryInterface|null $selectedElementsQuery
	 * @param ElementInterface|Element   $element
	 *
	 * @return array
	 */
	protected function getInputTemplateVariables($selectedElementsQuery, $element)
	{
		if (!($selectedElementsQuery instanceof ElementQueryInterface))
		{
			$class = static::elementType();
			$selectedElementsQuery = $class::find()
				->id(false);
		}
		else
		{
			$selectedElementsQuery
				->status(null)
				->localeEnabled(null);
		}

		$selectionCriteria = $this->getInputSelectionCriteria();
		$selectionCriteria['localeEnabled'] = null;
		$selectionCriteria['locale'] = $this->getTargetLocale($element);

		return [
			'jsClass'            => $this->inputJsClass,
			'elementType'        => static::elementType(),
			'id'                 => Craft::$app->getView()->formatInputId($this->handle),
			'fieldId'            => $this->id,
			'storageKey'         => 'field.'.$this->id,
			'name'               => $this->handle,
			'elements'           => $selectedElementsQuery,
			'sources'            => $this->getInputSources($element),
			'criteria'           => $selectionCriteria,
			'sourceElementId'    => (!empty($element->id) ? $element->id : null),
			'limit'              => ($this->allowLimit ? $this->limit : null),
			'addButtonLabel'     => $this->getAddButtonLabel(),
		];
	}

	/**
	 * Returns an array of the source keys the field should be able to select elements from.
	 *
	 * @param ElementInterface|Element|null $element
	 * @return array
	 */
	protected function getInputSources($element)
	{
		if ($this->allowMultipleSources)
		{
			$sources = $this->sources;
		}
		else
		{
			$sources = [$this->source];
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
	 * @param ElementInterface|Element|null $element
	 * @return string
	 */
	protected function getTargetLocale($element)
	{
		if (Craft::$app->isLocalized())
		{
			if ($this->targetLocale)
			{
				return $this->targetLocale;
			}
			else if (!empty($element))
			{
				return $element->locale;
			}
		}

		return Craft::$app->language;
	}

	/**
	 * Returns the HTML for the Target Locale setting.
	 *
	 * @return string|null
	 */
	protected function getTargetLocaleFieldHtml()
	{
		$class = static::elementType();

		if (Craft::$app->isLocalized() && $class::isLocalized())
		{
			$localeOptions = [
				['label' => Craft::t('app', 'Same as source'), 'value' => null]
			];

			foreach (Craft::$app->getI18n()->getSiteLocales() as $locale)
			{
				$localeOptions[] = ['label' => $locale->getDisplayName(Craft::$app->language), 'value' => $locale->id];
			}

			return Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'selectField', [
				[
					'label' => Craft::t('app', 'Target Locale'),
					'instructions' => Craft::t('app', 'Which locale do you want to select {type} in?', ['type' => StringHelper::toLowerCase(static::displayName())]),
					'id' => 'targetLocale',
					'name' => 'targetLocale',
					'options' => $localeOptions,
					'value' => $this->targetLocale
				]
			]);
		}
	}
}
