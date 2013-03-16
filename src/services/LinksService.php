<?php
namespace Craft;

/**
 *
 */
class LinksService extends BaseApplicationComponent
{
	private $_criteriaRecordsByTypeAndHandle;

	/**
	 * Returns a link criteria model by its ID.
	 *
	 * @param int $criteriaId
	 * @return LinkCriteriaModel|null
	 */
	public function getCriteriaById($criteriaId)
	{
		$criteriaRecord = $this->getCriteriaRecordById($criteriaId);

		if ($criteriaRecord)
		{
			return LinkCriteriaModel::populateModel($criteriaRecord);
		}
	}

	/**
	 * Returns a link criteria record by its ID.
	 *
	 * @param int $criteriaId
	 * @return LinkCriteriaRecord
	 */
	public function getCriteriaRecordById($criteriaId)
	{
		return LinkCriteriaRecord::model()->findById($criteriaId);
	}

	/**
	 * Returns a link criteria model by the its element type and a given handle.
	 *
	 * @param string $elementType
	 * @param string $handle
	 * @param string $dir The direction we should be going ('ltr' or 'rtl')
	 * @return LinkCriteriaModel|null
	 */
	public function getCriteriaByTypeAndHandle($elementType, $handle, $dir = 'ltr')
	{
		if (!isset($this->_criteriaRecordsByTypeAndHandle[$dir][$elementType]) || !array_key_exists($handle, $this->_criteriaRecordsByTypeAndHandle[$dir][$elementType]))
		{
			list($source, $target) = $this->_getDirProps($dir);

			$record = LinkCriteriaRecord::model()->findByAttributes(array(
				"{$source}ElementType" => $elementType,
				"{$dir}Handle"       => $handle
			));

			if ($record)
			{
				$this->_criteriaRecordsByTypeAndHandle[$dir][$elementType][$handle] = LinkCriteriaModel::populateModel($record);
			}
			else
			{
				$this->_criteriaRecordsByTypeAndHandle[$dir][$elementType][$handle] = null;
			}
		}

		return $this->_criteriaRecordsByTypeAndHandle[$dir][$elementType][$handle];
	}

	/**
	 * Saves a link criteria.
	 *
	 * @param LinkCriteriaModel $criteria
	 * @return bool
	 */
	public function saveCriteria(LinkCriteriaModel $criteria)
	{
		if ($criteria->id)
		{
			$criteriaRecord = $this->getCriteriaRecordById($criteria->id);

			if (!$criteriaRecord)
			{
				throw new Exception(Craft::t('No link criteria exists with the ID “{id}”.', array('id' => $criteria->id)));
			}

			$oldCriteria = LinkCriteriaModel::populateModel($criteriaRecord);
		}
		else
		{
			$criteriaRecord = new LinkCriteriaRecord();
		}

		$criteriaRecord->ltrHandle      = $criteria->ltrHandle;
		$criteriaRecord->rtlHandle      = $criteria->rtlHandle;
		$criteriaRecord->leftElementType  = $criteria->leftElementType;
		$criteriaRecord->rightElementType = $criteria->rightElementType;
		$criteriaRecord->rightSettings  = $criteria->rightSettings;

		if ($criteriaRecord->save())
		{
			if (!$criteria->id)
			{
				// Update the model's ID now that we have an ID
				$criteria->id = $criteriaRecord->id;
			}
			else
			{
				// Have either of the element types changed?
				if ($criteria->leftElementType != $oldCriteria->leftElementType || $criteria->rightElementType != $oldCriteria->rightElementType)
				{
					// Delete the links that were previously created with this criteria
					craft()->db->createCommand()->delete('links', array('criteriaId' => $criteria->id));
				}
			}

			return true;
		}
		else
		{
			$criteria->addErrors($criteriaRecord->getErrors());
			return false;
		}
	}

	/**
	 * Returns the linked elements by a given criteria and the source element ID.
	 *
	 * @param LinkCriteriaModel $criteria
	 * @param int $sourceElementId
	 * @param string $dir The direction we should be going ('ltr' or 'rtl')
	 * @return array
	 */
	public function getLinkedElements(LinkCriteriaModel $criteria, $sourceElementId, $dir = 'ltr')
	{
		list($source, $target) = $this->_getDirProps($dir);

		$elementTypeClassProperty = $target.'ElementType';
		$elementTypeClass = $criteria->$elementTypeClassProperty;
		$elementType = craft()->elements->getElementType($elementTypeClass);

		if (!$elementType)
		{
			return array();
		}

		$elementCriteria = craft()->elements->getCriteria($elementTypeClass);
		$query = craft()->elements->buildElementsQuery($elementCriteria);

		if ($query)
		{
			$query
				->addSelect("links.{$target}SortOrder")
				->join('links links', "links.{$target}ElementId = elements.id")
				->andWhere(array('links.criteriaId'  => $criteria->id, "links.{$source}ElementId" => $sourceElementId));

			return $this->_getElementsFromQuery($elementType, $query, "{$target}SortOrder");
		}
		else
		{
			return array();
		}
	}

	/**
	 * Gets elements by their ID.
	 *
	 * @param string $elementTypeClass
	 * @param array $elementIds
	 * @return array
	 */
	public function getElementsById($elementTypeClass, $elementIds)
	{
		if (!$elementIds)
		{
			return array();
		}

		$elementType = craft()->elements->getElementType($elementTypeClass);

		if (!$elementType)
		{
			return array();
		}

		$elementCriteria = craft()->elements->getCriteria($elementTypeClass, array(
			'id' => $elementIds
		));

		$query = craft()->elements->buildElementsQuery($elementCriteria);

		if ($query)
		{
			$elements = $this->_getElementsFromQuery($elementType, $query);

			// Put them into the requested order
			$orderedElements = array();
			$elementsById = array();

			foreach ($elements as $element)
			{
				$elementsById[$element->id] = $element;
			}

			foreach ($elementIds as $id)
			{
				if (isset($elementsById[$id]))
				{
					$orderedElements[] = $elementsById[$id];
				}
			}

			return $orderedElements;
		}
		else
		{
			return array();
		}
	}

	/**
	 * Returns links by their criteria ID.
	 *
	 * @param int $criteriaId
	 * @return array
	 */
	public function getLinksByCriteriaId($criteriaId)
	{
		$linkRecords = LinkRecord::model()->findByAttributes(array(
			'criteriaId' => $criteriaId
		));

		return LinkModel::populateModels($linkRecords);
	}

	/**
	 * Sets the linked elements for a Links field.
	 *
	 * @param int $criteriaId
	 * @param int $sourceElementId
	 * @param array $linkedElementIds
	 * @param string $dir The direction we should be going ('ltr' or 'rtl')
	 * @throws \Exception
	 * @return void
	 */
	public function saveLinks($criteriaId, $sourceElementId, $linkedElementIds, $dir = 'ltr')
	{
		list($source, $target) = $this->_getDirProps($dir);

		$transaction = craft()->db->beginTransaction();
		try
		{
			// Delete the existing links
			craft()->db->createCommand()->delete('links', array(
				'criteriaId'       => $criteriaId,
				"{$source}ElementId" => $sourceElementId
			));

			if ($linkedElementIds)
			{
				$values = array();

				foreach ($linkedElementIds as $sortOrder => $elementId)
				{
					$values[] = array($criteriaId, $sourceElementId, $elementId, $sortOrder+1);
				}

				$columns = array('criteriaId', "{$source}ElementId", "{$target}ElementId", "{$target}SortOrder");
				craft()->db->createCommand()->insertAll('links', $columns, $values);
			}

			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}
	}

	/**
	 * Returns elements based on a given query.
	 *
	 * @access private
	 * @param BaseElementType $elementType
	 * @param DbCommand $subquery
	 * @param string $order
	 * @return array
	 */
	private function _getElementsFromQuery(BaseElementType $elementType, DbCommand $subquery, $order = null)
	{
		// Only get the unique elements (no locale duplicates)
		$query = craft()->db->createCommand()
			->select('*')
			->from('('.$subquery->getText().') AS '.craft()->db->quoteTableName('r'))
			->group('r.id');

		$query->params = $subquery->params;

		if ($order)
		{
			$query->order($order);
		}

		$result = $query->queryAll();
		$elements = array();

		foreach ($result as $row)
		{
			$elements[] = $elementType->populateElementModel($row);
		}

		return $elements;
	}

	/**
	 * Returns the source and target directions.
	 *
	 * @access private
	 * @param string $dir
	 */
	private function _getDirProps($dir)
	{
		if ($dir == 'ltr')
		{
			return array('left', 'right');
		}
		else
		{
			return array('right', 'left');
		}
	}
}
