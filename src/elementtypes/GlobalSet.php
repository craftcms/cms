<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elementtypes;

use Craft;
use craft\app\db\DbCommand;
use craft\app\enums\AttributeType;
use craft\app\helpers\DbHelper;
use craft\app\models\ElementCriteria as ElementCriteriaModel;
use craft\app\models\GlobalSet as GlobalSetModel;

/**
 * The GlobalSet class is responsible for implementing and defining globals as a native element type in
 * Craft.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class GlobalSet extends BaseElementType
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('app', 'Global Sets');
	}

	/**
	 * @inheritDoc ElementTypeInterface::hasContent()
	 *
	 * @return bool
	 */
	public function hasContent()
	{
		return true;
	}

	/**
	 * @inheritDoc ElementTypeInterface::isLocalized()
	 *
	 * @return bool
	 */
	public function isLocalized()
	{
		return true;
	}

	/**
	 * @inheritDoc ElementTypeInterface::defineCriteriaAttributes()
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return [
			'handle' => AttributeType::Mixed,
			'order' => [AttributeType::String, 'default' => 'name'],
		];
	}

	/**
	 * @inheritDoc ElementTypeInterface::modifyElementsQuery()
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
	 * @inheritDoc ElementTypeInterface::populateElementModel()
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
