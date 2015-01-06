<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elementtypes;

use craft\app\Craft;
use craft\app\enums\AttributeType;
use craft\app\models\ElementCriteria as ElementCriteriaModel;
use craft\app\models\MatrixBlock     as MatrixBlockModel;
use craft\app\models\Field           as FieldModel;

/**
 * The MatrixBlock class is responsible for implementing and defining Matrix blocks as a native element type
 * in Craft.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MatrixBlock extends BaseElementType
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
		return Craft::t('Matrix Blocks');
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
		return array(
			'fieldId'     => AttributeType::Number,
			'order'       => array(AttributeType::String, 'default' => 'matrixblocks.sortOrder'),
			'ownerId'     => AttributeType::Number,
			'ownerLocale' => AttributeType::Locale,
			'type'        => AttributeType::Mixed,
		);
	}

	/**
	 * @inheritDoc ElementTypeInterface::getContentTableForElementsQuery()
	 *
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return string
	 */
	public function getContentTableForElementsQuery(ElementCriteriaModel $criteria)
	{
		if (!$criteria->fieldId && $criteria->id && is_numeric($criteria->id))
		{
			$criteria->fieldId = craft()->db->createCommand()
				->select('fieldId')
				->from('matrixblocks')
				->where('id = :id', array(':id' => $criteria->id))
				->queryScalar();
		}

		if ($criteria->fieldId && is_numeric($criteria->fieldId))
		{
			$matrixField = craft()->fields->getFieldById($criteria->fieldId);

			if ($matrixField)
			{
				return craft()->matrix->getContentTableName($matrixField);
			}
		}
	}

	/**
	 * @inheritDoc ElementTypeInterface::getFieldsForElementsQuery()
	 *
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return FieldModel[]
	 */
	public function getFieldsForElementsQuery(ElementCriteriaModel $criteria)
	{
		$fields = array();

		foreach (craft()->matrix->getBlockTypesByFieldId($criteria->fieldId) as $blockType)
		{
			$fieldColumnPrefix = 'field_'.$blockType->handle.'_';

			foreach ($blockType->getFields() as $field)
			{
				$field->columnPrefix = $fieldColumnPrefix;
				$fields[] = $field;
			}
		}

		return $fields;
	}

	/**
	 * @inheritDoc ElementTypeInterface::modifyElementsQuery()
	 *
	 * @param DbCommand            $query
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return mixed
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect('matrixblocks.fieldId, matrixblocks.ownerId, matrixblocks.ownerLocale, matrixblocks.typeId, matrixblocks.sortOrder')
			->join('matrixblocks matrixblocks', 'matrixblocks.id = elements.id');

		if ($criteria->fieldId)
		{
			$query->andWhere(DbHelper::parseParam('matrixblocks.fieldId', $criteria->fieldId, $query->params));
		}

		if ($criteria->ownerId)
		{
			$query->andWhere(DbHelper::parseParam('matrixblocks.ownerId', $criteria->ownerId, $query->params));
		}

		if ($criteria->ownerLocale)
		{
			$query->andWhere(DbHelper::parseParam('matrixblocks.ownerLocale', $criteria->ownerLocale, $query->params));
		}

		if ($criteria->type)
		{
			$query->join('matrixblocktypes matrixblocktypes', 'matrixblocktypes.id = matrixblocks.typeId');
			$query->andWhere(DbHelper::parseParam('matrixblocktypes.handle', $criteria->type, $query->params));
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
		return MatrixBlockModel::populateModel($row);
	}
}
