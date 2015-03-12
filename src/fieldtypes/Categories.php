<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\fieldtypes;

use Craft;
use craft\app\elements\Category;

/**
 * Categories fieldtype.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Categories extends BaseElementFieldType
{
	// Properties
	// =========================================================================

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
	 * @inheritdoc
	 */
	public function getName()
	{
		return Craft::t('app', 'Categories');
	}

	/**
	 * @inheritdoc
	 * @return Category
	 */
	public function getElementClass()
	{
		return Category::className();
	}

	/**
	 * @inheritdoc
	 */
	public function getAddButtonLabel()
	{
		return Craft::t('app', 'Add a category');
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
		// Make sure the field is set to a valid category group
		$sourceKey = $this->getSettings()->source;

		if ($sourceKey)
		{
			$class = $this->getElementClass();
			$source = $class::getSourceByKey($sourceKey, 'field');
		}

		if (empty($source))
		{
			return '<p class="error">'.Craft::t('app', 'This field is not set to a valid category group.').'</p>';
		}

		return parent::getInputHtml($name, $criteria);
	}

	/**
	 * @inheritDoc FieldTypeInterface::onAfterElementSave()
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
			$categoryIds = Craft::$app->categories->fillGapsInCategoryIds($categoryIds);

			Craft::$app->relations->saveRelations($this->model, $this->element, $categoryIds);
		}
	}
}
