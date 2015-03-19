<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fields;

use Craft;
use craft\app\base\Field;
use craft\app\base\Element;
use craft\app\elements\db\ElementQuery;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\helpers\StringHelper;

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

		return Craft::$app->templates->render('_components/fieldtypes/elementfieldsettings', [
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
	function validateValue($value)
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
	public function prepValue($value)
	{
		$class = static::elementType();
		/** @var ElementQuery $query */
		$query = $class::find()
			->locale($this->getTargetLocale());

		// $value will be an array of element IDs if there was a validation error or we're loading a draft/version.
		if (is_array($value))
		{
			$query
				->id(array_values(array_filter($value)))
				->fixedOrder();
		}
		else if ($value !== '' && isset($this->element) && $this->element->id)
		{
			$query->relatedTo([
				'sourceElement' => $this->element->id,
				'sourceLocale'  => $this->element->locale,
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
	public function getInputHtml($name, $value)
	{
		$variables = $this->getInputTemplateVariables($name, $value);
		return Craft::$app->templates->render($this->inputTemplate, $variables);
	}

	/**
	 * @inheritdoc
	 */
	public function getSearchKeywords($value)
	{
		$titles = [];

		foreach ($value->all() as $element)
		{
			$titles[] = (string) $element;
		}

		return parent::getSearchKeywords($titles);
	}

	/**
	 * @inheritdoc
	 */
	public function afterElementSave()
	{
		$handle = $this->handle;
		$targetIds = $this->element->getContent()->$handle;

		if ($targetIds !== null)
		{
			Craft::$app->relations->saveRelations($this, $this->element, $targetIds);
		}
	}

	/**
	 * @inheritdoc
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
	 * Returns an array of variables that should be passed to the input template.
	 *
	 * @param string                     $name
	 * @param ElementQueryInterface|null $selectedElementsQuery
	 *
	 * @return array
	 */
	protected function getInputTemplateVariables($name, $selectedElementsQuery)
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
		$selectionCriteria['locale'] = $this->getTargetLocale();

		return [
			'jsClass'            => $this->inputJsClass,
			'elementType'        => static::elementType(),
			'id'                 => Craft::$app->templates->formatInputId($name),
			'fieldId'            => $this->id,
			'storageKey'         => 'field.'.$this->id,
			'name'               => $name,
			'elements'           => $selectedElementsQuery,
			'sources'            => $this->getInputSources(),
			'criteria'           => $selectionCriteria,
			'sourceElementId'    => (isset($this->element->id) ? $this->element->id : null),
			'limit'              => ($this->allowLimit ? $this->limit : null),
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
	 * @return string
	 */
	protected function getTargetLocale()
	{
		if (Craft::$app->isLocalized())
		{
			if ($this->targetLocale)
			{
				return $this->targetLocale;
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
		$class = static::elementType();

		if (Craft::$app->isLocalized() && $class::isLocalized())
		{
			$localeOptions = [
				['label' => Craft::t('app', 'Same as source'), 'value' => null]
			];

			foreach (Craft::$app->getI18n()->getSiteLocales() as $locale)
			{
				$localeOptions[] = ['label' => $locale->getName(), 'value' => $locale->id];
			}

			return Craft::$app->templates->renderMacro('_includes/forms', 'selectField', [
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
