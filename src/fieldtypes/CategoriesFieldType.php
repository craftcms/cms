<?php
namespace Craft;

/**
 * Categories fieldtype.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.fieldtypes
 * @since     2.0
 */
class CategoriesFieldType extends BaseElementFieldType
{
	// Properties
	// =========================================================================

	/**
	 * The element type this field deals with.
	 *
	 * @var string $elementType
	 */
	protected $elementType = 'Category';

	/**
	 * Whether to allow multiple source selection in the settings.
	 *
	 * @var bool $allowMultipleSources
	 */
	protected $allowMultipleSources = false;

	/**
	 * The JS class that should be initialized for the input.
	 *
	 * @var string|null $inputJsClass
	 */
	protected $inputJsClass = 'Craft.CategorySelectInput';

	/**
	 * Template to use for field rendering
	 *
	 * @var string
	 */
	protected $inputTemplate = '_components/fieldtypes/Categories/input';

	/**
	 * Whether the elements have a custom sort order.
	 *
	 * @var bool $sortable
	 */
	protected $sortable = false;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IFieldType::onAfterElementSave()
	 *
	 * @return null
	 */
	public function onAfterElementSave()
	{
		$categoryIds = $this->element->getContent()->getAttribute($this->model->handle);

		// Make sure something was actually posted
		if ($categoryIds !== null)
		{
			// Fill in any gaps
			$categoryIds = craft()->categories->fillGapsInCategoryIds($categoryIds);

			craft()->relations->saveRelations($this->model, $this->element, $categoryIds);
		}
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseElementFieldType::getInputTemplateVariables()
	 *
	 * @param string $name
	 * @param mixed  $criteria
	 *
	 * @return array
	 */
	protected function getInputTemplateVariables($name, $criteria)
	{
		$variables = parent::getInputTemplateVariables($name, $criteria);

		if ($variables['sources'])
		{
			$sourceKey = $variables['sources'][0];
			$source = $this->getElementType()->getSource($sourceKey, 'field');

			if ($source)
			{
				$criteria = craft()->elements->getCriteria(ElementType::Category);
				$criteria->locale = $this->getTargetLocale();
				$criteria->groupId = $source['criteria']['groupId'];
				$criteria->status = null;
				$criteria->localeEnabled = false;
				$criteria->limit = null;
				$variables['categories'] = $criteria->find();
			}
		}

		$variables['selectedCategoryIds'] = $variables['elements']->ids();

		return $variables;
	}
}
