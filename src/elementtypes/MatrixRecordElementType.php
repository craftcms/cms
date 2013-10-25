<?php
namespace Craft;

/**
 * Matrix record element type
 */
class MatrixRecordElementType extends BaseElementType
{
	/**
	 * Returns the element type name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Matrix Records');
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
			'fieldId' => AttributeType::Number,
			'order'   => array(AttributeType::String, 'default' => 'sortOrder'),
			'ownerId' => AttributeType::Number,
			'type'    => AttributeType::Mixed,
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

		foreach (craft()->matrix->getRecordTypesByFieldId($criteria->fieldId) as $recordType)
		{
			$fieldColumnPrefix = 'field_'.$recordType->handle.'_';

			foreach ($recordType->getFields() as $field)
			{
				$fieldType = $field->getFieldType();

				if ($fieldType && $fieldType->defineContentAttribute())
				{
					$columns[$field->handle] = $fieldColumnPrefix.$field->handle;
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
			->addSelect('matrixrecords.fieldId, matrixrecords.ownerId, matrixrecords.typeId, matrixrecords.sortOrder')
			->join('matrixrecords matrixrecords', 'matrixrecords.id = elements.id');

		if ($criteria->fieldId)
		{
			$query->andWhere(DbHelper::parseParam('matrixrecords.fieldId', $criteria->fieldId, $query->params));
		}

		if ($criteria->ownerId)
		{
			$query->andWhere(DbHelper::parseParam('matrixrecords.ownerId', $criteria->ownerId, $query->params));
		}

		if ($criteria->type)
		{
			$query->join('matrixrecordtypes matrixrecordtypes', 'matrixrecordtypes.id = matrixrecords.typeId');
			$query->andWhere(DbHelper::parseParam('matrixrecordtypes.handle', $criteria->type, $query->params));
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
		return MatrixRecordModel::populateModel($row);
	}
}
