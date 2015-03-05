<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elements;

use Craft;
use craft\app\db\Query;
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
	public static function getName()
	{
		return Craft::t('app', 'Global Sets');
	}

	/**
	 * @inheritDoc ElementTypeInterface::hasContent()
	 *
	 * @return bool
	 */
	public static function hasContent()
	{
		return true;
	}

	/**
	 * @inheritDoc ElementTypeInterface::isLocalized()
	 *
	 * @return bool
	 */
	public static function isLocalized()
	{
		return true;
	}

	/**
	 * @inheritDoc ElementTypeInterface::defineCriteriaAttributes()
	 *
	 * @return array
	 */
	public static function defineCriteriaAttributes()
	{
		return [
			'handle' => AttributeType::Mixed,
			'order' => [AttributeType::String, 'default' => 'name'],
		];
	}

	/**
	 * @inheritDoc ElementTypeInterface::modifyElementsQuery()
	 *
	 * @param Query                $query
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return mixed
	 */
	public static function modifyElementsQuery(Query $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect('globalsets.name, globalsets.handle, globalsets.fieldLayoutId')
			->innerJoin('{{%globalsets}} globalsets', 'globalsets.id = elements.id');

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
	public static function populateElementModel($row)
	{
		return GlobalSetModel::populateModel($row);
	}
}
