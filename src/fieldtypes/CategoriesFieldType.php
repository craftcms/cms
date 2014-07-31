<?php
namespace Craft;

/**
 * Categories fieldtype.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.etc.fieldtypes
 * @since     2.0
 */
class CategoriesFieldType extends BaseElementFieldType
{
	/**
	 * @var string $elementType The element type this field deals with.
	 */
	protected $elementType = 'Category';

	/**
	 * @var bool $allowMultipleSources Whether to allow multiple source selection in the settings.
	 */
	protected $allowMultipleSources = false;

	/**
	 * @var string|null $inputJsClass The JS class that should be initialized for the input.
	 */
	protected $inputJsClass = 'Craft.CategorySelectInput';

	/**
	 * Template to use for field rendering
	 * @var string
	 */
	protected $inputTemplate = '_components/fieldtypes/Categories/input';

	/**
	 * @var bool $sortable Whether the elements have a custom sort order.
	 */
	protected $sortable = false;

	/**
	 * Performs any additional actions after the element has been saved.
	 */
	public function onAfterElementSave()
	{
		$categoryIds = $this->element->getContent()->getAttribute($this->model->handle);

		if ($categoryIds !== null)
		{
			// Still could be empty though...
			if ($categoryIds)
			{
				// Make sure that for each selected category, all of its parents are also selected.
				$criteria = craft()->elements->getCriteria(ElementType::Category);
				$criteria->id = $categoryIds;
				$criteria->status = null;
				$criteria->localeEnabled = false;
				$categories = $criteria->find();

				$prevCategory = null;

				foreach ($categories as $i => $category)
				{
					// Did we just skip any categories?
					if ($category->level != 1 && (
						($i == 0) ||
						(!$category->isSiblingOf($prevCategory) && !$category->isChildOf($prevCategory))
					))
					{
						// Merge in all of the entry's ancestors
						$ancestorIds = $category->getAncestors()->ids();
						$categoryIds = array_merge($categoryIds, $ancestorIds);
					}

					$prevCategory = $category;
				}
			}

			craft()->relations->saveRelations($this->model, $this->element, $categoryIds);
		}
	}

	/**
	 * Returns an array of variables that should be passed to the input template.
	 *
	 * @param string $name
	 * @param mixed  $criteria
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
