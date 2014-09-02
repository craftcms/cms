<?php
namespace Craft;

/**
 * The GlobalSetElementType class is responsible for implementing and defining globals as a native element type in
 * Craft.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.elementtypes
 * @since     1.0
 */
class GlobalSetElementType extends BaseElementType
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns the element type name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Global Sets');
	}

	/**
	 * Returns whether this element type has content.
	 *
	 * @return bool
	 */
	public function hasContent()
	{
		return true;
	}

	/**
	 * Returns whether this element type stores data on a per-locale basis.
	 *
	 * @return bool
	 */
	public function isLocalized()
	{
		return true;
	}

	/**
	 * Defines any custom element criteria attributes for this element type.
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return array(
			'handle' => AttributeType::Mixed,
			'order' => array(AttributeType::String, 'default' => 'name'),
		);
	}

	/**
	 * Modifies an element query targeting elements of this type.
	 *
	 * @param DbCommand $query
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return mixed
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect('globalsets.name, globalsets.handle, globalsets.fieldLayoutId')
			->join('globalsets globalsets', 'globalsets.id = elements.id');

		if ($criteria->handle)
		{
			$query->andWhere(DbHelper::parseParam('globalsets.handle', $criteria->handle, $query->params));
		}
	}

	/**
	 * Populates an element model based on a query result.
	 *
	 * @param array $row
	 *
	 * @return array
	 */
	public function populateElementModel($row)
	{
		return GlobalSetModel::populateModel($row);
	}
}
