<?php
namespace Craft;

/**
 * The MatrixBlockElementType class is responsible for implementing and defining Matrix blocks as a native element type
 * in Craft.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.elementtypes
 * @since     1.3
 */
class MatrixBlockElementType extends BaseElementType
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Matrix Blocks');
	}

	/**
	 * @inheritDoc IElementType::hasContent()
	 *
	 * @return bool
	 */
	public function hasContent()
	{
		return true;
	}

	/**
	 * @inheritDoc IElementType::isLocalized()
	 *
	 * @return bool
	 */
	public function isLocalized()
	{
		return true;
	}

	/**
	 * @inheritDoc IElementType::defineCriteriaAttributes()
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
	 * @inheritDoc IElementType::getContentTableForElementsQuery()
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
	 * @inheritDoc IElementType::getFieldsForElementsQuery()
	 *
	 * @param ElementCriteriaModel $criteria
	 *
	 * @return FieldModel[]
	 */
	public function getFieldsForElementsQuery(ElementCriteriaModel $criteria)
	{
		$blockTypes = craft()->matrix->getBlockTypesByFieldId($criteria->fieldId);

		// Preload all of the fields up front to save ourselves some DB queries, and discard
		$contexts = array();

		foreach ($blockTypes as $blockType)
		{
			$contexts[] = 'matrixBlockType:'.$blockType->id;
		}

		craft()->fields->getAllFields(null, $contexts);

		// Now assemble the actual fields list
		$fields = array();

		foreach ($blockTypes as $blockType)
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
	 * @inheritDoc IElementType::modifyElementsQuery()
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
	 * @inheritDoc IElementType::populateElementModel()
	 *
	 * @param array $row
	 *
	 * @return array
	 */
	public function populateElementModel($row)
	{
		return MatrixBlockModel::populateModel($row);
	}

	/**
	 * @inheritDoc IElementType::getEagerLoadingMap()
	 *
	 * @param BaseElementModel[]  $sourceElements
	 * @param string $handle
	 *
	 * @return array|false
	 */
	public function getEagerLoadingMap($sourceElements, $handle)
	{
		// $handle *must* be set as "blockTypeHandle:fieldHandle" so we know _which_ myRelationalField to resolve to
		$handleParts = explode(':', $handle);

		if (count($handleParts) != 2)
		{
			return false;
		}

		list($blockTypeHandle, $fieldHandle) = $handleParts;

		// Get the block type
		$matrixFieldId = $sourceElements[0]->fieldId;
		$blockTypes = craft()->matrix->getBlockTypesByFieldId($matrixFieldId, 'handle');

		if (!isset($blockTypes[$blockTypeHandle]))
		{
			// Not a valid block type handle (assuming all $sourceElements are blocks from the same Matrix field)
			return false;
		}

		$blockType = $blockTypes[$blockTypeHandle];

		// Set the field context
		$contentService = craft()->content;
		$originalFieldContext = $contentService->fieldContext;
		$contentService->fieldContext = 'matrixBlockType:'.$blockType->id;

		$map = parent::getEagerLoadingMap($sourceElements, $fieldHandle);

		$contentService->fieldContext = $originalFieldContext;

		return $map;
	}
}
