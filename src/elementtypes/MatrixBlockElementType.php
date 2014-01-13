<?php
namespace Craft;

/**
 * Matrix block element type
 */
class MatrixBlockElementType extends BaseElementType
{
	/**
	 * Returns the element type name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Matrix Blocks');
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
			'fieldId'     => AttributeType::Number,
			'order'       => array(AttributeType::String, 'default' => 'sortOrder'),
			'ownerId'     => AttributeType::Number,
			'ownerLocale' => AttributeType::Locale,
			'type'        => AttributeType::Mixed,
		);
	}

	/**
	 * Returns the content table name that should be joined in for an elements query.
	 *
	 * @param ElementCriteriaModel
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
	 * Returns the field column names that should be selected from the content table.
	 *
	 * @param ElementCriteriaModel
	 * @return array
	 */
	public function getContentFieldColumnsForElementsQuery(ElementCriteriaModel $criteria)
	{
		$columns = array();

		foreach (craft()->matrix->getBlockTypesByFieldId($criteria->fieldId) as $blockType)
		{
			$fieldColumnPrefix = 'field_'.$blockType->handle.'_';

			foreach ($blockType->getFields() as $field)
			{
				$fieldType = $field->getFieldType();

				if ($fieldType && $fieldType->defineContentAttribute())
				{
					$columns[] = array('handle' => $field->handle, 'column' => $fieldColumnPrefix.$field->handle);
				}
			}
		}

		return $columns;
	}

	/**
	 * Modifies an element query targeting elements of this type.
	 *
	 * @param DbCommand $query
	 * @param ElementCriteriaModel $criteria
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
	 * Populates an element model based on a query result.
	 *
	 * @param array $row
	 * @return array
	 */
	public function populateElementModel($row)
	{
		return MatrixBlockModel::populateModel($row);
	}
}
