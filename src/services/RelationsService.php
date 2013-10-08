<?php
namespace Craft;

/**
 *
 */
class RelationsService extends BaseApplicationComponent
{
	/**
	 * Returns the elements that are related by a given element ID, optionally via a given field ID.
	 *
	 * @param int         $parentId
	 * @param int|null    $fieldId
	 * @param string|null $elementType
	 * @return array
	 */
	public function getRelatedElements($parentId, $fieldId = null, $elementType = null)
	{
		return $this->_getRelatedElements('parent', 'child', $parentId, $fieldId, $elementType);
	}

	/**
	 * Returns the elements that relate to a given elemnet ID, optionally via a given field ID.
	 *
	 * @param int         $childId
	 * @param int|null    $fieldId
	 * @param string|null $elementType
	 * @return array
	 */
	public function getReverseRelatedElements($childId, $fieldId = null, $elementType = null)
	{
		return $this->_getRelatedElements('child', 'parent', $childId, $fieldId, $elementType);
	}

	/**
	 * Saves the relations elements for an element field.
	 *
	 * @param int $fieldId
	 * @param int $parentId
	 * @param array $childIds
	 * @throws \Exception
	 */
	public function saveRelations($fieldId, $parentId, $childIds)
	{
		// Prevent duplicate child IDs.
		$childIds = array_unique($childIds);

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// Delete the existing relations
			craft()->db->createCommand()->delete('relations', array(
				'fieldId'  => $fieldId,
				'parentId' => $parentId
			));

			if ($childIds)
			{
				$values = array();

				foreach ($childIds as $sortOrder => $childId)
				{
					$values[] = array($fieldId, $parentId, $childId, $sortOrder+1);
				}

				$columns = array('fieldId', 'parentId', 'childId', 'sortOrder');
				craft()->db->createCommand()->insertAll('relations', $columns, $values);
			}

			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}
	}

	/**
	 * Returns (reverse-)related elements.
	 *
	 * @access private
	 * @param string      $source
	 * @param string      $target
	 * @param int         $sourceId
	 * @param int|null    $fieldId
	 * @param string|null $elementTypeClass
	 */
	private function _getRelatedElements($source, $target, $sourceId, $fieldId, $elementTypeClass)
	{
		$ordered = ($source == 'parent' && $target == 'child' && $elementTypeClass !== null);

		if ($elementTypeClass)
		{
			$elementType = craft()->elements->getElementType($elementTypeClass);

			if ($elementType)
			{
				$criteria = craft()->elements->getCriteria($elementTypeClass, array(
					'status' => null
				));

				$query = craft()->elements->buildElementsQuery($criteria);

				if ($query)
				{
					$this->_addConditionsToRelationsQuery($query, $source, $target, $sourceId, $fieldId);
					return $this->_getElementsFromQuery($elementType, $query, $ordered);
				}
			}

			return array();
		}
		else
		{
			// Unsure about the element type so we'll need to get each matching element's type first
			// and then run it through the standard element query stuff
			$elements = array();

			$query = craft()->db->createCommand()->select('id,type')->from('elements');
			$this->_addConditionsToRelationsQuery($query, $source, $target, $sourceId, $fieldId);
			$result = $query->fetchAll();

			foreach ($result as $row)
			{
				$elementType = craft()->elements->getElementType($row['type']);

				if ($elementType)
				{
					$criteria = craft()->elements->getCriteria($elementTypeClass, array(
						'id'     => $row['id'],
						'status' => null
					));

					$query = craft()->elements->buildElementsQuery($criteria);

					if ($query)
					{
						$element = $this->_getElementsFromQuery($elementType, $query, $ordered);

						if ($element)
						{
							$elements[] = $element[0];
						}
					}
				}
			}

			return $element;
		}
	}

	/**
	 * Adds conditions to related elements query.
	 *
	 * @access private
	 * @param DbCommand $query
	 * @param string    $source
	 * @param string    $target
	 * @param int       $sourceId
	 * @param int|null  $fieldId
	 * @param bool      $ordered
	 */
	private function _addConditionsToRelationsQuery(DbCommand $query, $source, $target, $sourceId, $fieldId)
	{
		$query->join('relations relations', "relations.{$target}Id = elements.id")
			->andWhere(array("relations.{$source}Id" => $sourceId));

		if ($fieldId)
		{
			$query->andWhere(array('relations.fieldId' => $fieldId));
		}
	}

	/**
	 * Returns elements based on a given query.
	 *
	 * @access private
	 * @param BaseElementType $elementType
	 * @param DbCommand       $subquery
	 * @param bool            $ordered
	 * @return array
	 */
	private function _getElementsFromQuery(BaseElementType $elementType, DbCommand $subquery, $ordered = false)
	{
		if ($ordered)
		{
			$subquery->addSelect('relations.sortOrder');
		}

		// Only get the unique elements (no locale duplicates)
		$query = craft()->db->createCommand()
			->select('*')
			->from('('.$subquery->getText().') AS '.craft()->db->quoteTableName('r'))
			->group('r.id');

		$query->params = $subquery->params;

		if ($ordered)
		{
			$query->order('sortOrder');
		}

		$result = $query->queryAll();
		$elements = array();

		foreach ($result as $row)
		{
			// The locale column might be null since the element_i18n table was left-joined into the query,
			// In that case it should be removed from the $row array so that the default value can be used.
			if (!$row['locale'])
			{
				unset($row['locale']);
			}

			$elements[] = $elementType->populateElementModel($row);
		}

		return $elements;
	}
}
