<?php
namespace Blocks;

/**
 *
 */
class LinksService extends BaseApplicationComponent
{
	private $_criteriaRecordsByRightTypeAndHandle;

	/**
	 * Returns all installed link types.
	 *
	 * @return array
	 */
	public function getAllLinkTypes()
	{
		return blx()->components->getComponentsByType(ComponentType::Link);
	}

	/**
	 * Gets a link type.
	 *
	 * @param string $class
	 * @return BaseLinkType|null
	 */
	public function getLinkType($class)
	{
		return blx()->components->getComponentByTypeAndClass(ComponentType::Link, $class);
	}

	/**
	 * Returns a criteria record by its ID.
	 *
	 * @param int $criteriaId
	 * @return LinkCriteriaRecord
	 */
	public function getCriteriaRecordById($criteriaId)
	{
		return LinkCriteriaRecord::model()->findById($criteriaId);
	}

	/**
	 * Returns a criteria ID by the right-hand entity type and RTL handle
	 *
	 * @param string $type
	 * @param string $rtlHandle
	 * @return int
	 */
	public function getCriteriaRecordByRightTypeAndHandle($type, $rtlHandle)
	{
		if (!isset($this->_criteriaRecordsByRightTypeAndHandle[$type][$rtlHandle]))
		{
			$record = LinkCriteriaRecord::model()->findByAttributes(array(
				'rightEntityType' => $type,
				'rtlHandle' => $rtlHandle
			));

			$this->_criteriaRecordsByRightTypeAndHandle[$type][$rtlHandle] = $record;
		}

		return $this->_criteriaRecordsByRightTypeAndHandle[$type][$rtlHandle];
	}

	/**
	 * Gets the linked entities.
	 *
	 * @param int $criteriaId
	 * @param int $leftEntityId
	 * @return array
	 */
	public function getLinkedEntities($criteriaId, $leftEntityId)
	{
		$criteria = $this->getCriteriaRecordById($criteriaId);
		$linkType = $this->_getLinkType($criteria->rightEntityType);

		$table = $linkType->getEntityTableName();
		$query = blx()->db->createCommand()
			->select($table.'.*')
			->from($table.' '.$table)
			->join('links l', 'l.rightEntityId = '.$table.'.id')
			->where(array(
				'l.criteriaId'   => $criteriaId,
				'l.leftEntityId' => $leftEntityId
			))
			->order('l.rightSortOrder');

		// Give the link type a chance to make any changes
		$query = $linkType->modifyLinkedEntitiesQuery($query);

		$rows = $query->queryAll();
		return $linkType->populateEntities($rows);
	}

	/**
	* Gets the reverse linked entities.
	*
	 * @param string $type
	 * @param string $rtlHandle
	 * @param int $rightEntityId
	* @return array
	*/
	public function getReverseLinkedEntities($type, $rtlHandle, $rightEntityId)
	{
		$criteria = $this->getCriteriaRecordByRightTypeAndHandle($type, $rtlHandle);

		if ($criteria)
		{
			$linkType = $this->getLinkType($criteria->leftEntityType);

			if ($linkType)
			{
				$table = $linkType->getEntityTableName();
				$query = blx()->db->createCommand()
					->select($table.'.*')
					->from($table.' '.$table)
					->join('links l', 'l.leftEntityId = '.$table.'.id')
					->where(array(
						'l.criteriaId'   => $criteria->id,
						'l.rightEntityId' => $rightEntityId
					))
					->order('l.leftSortOrder');

				// Give the link type a chance to make any changes
				$query = $linkType->modifyLinkedEntitiesQuery($query);

				$rows = $query->queryAll();
				return $linkType->populateEntities($rows);
			}
		}

		return false;
	}

	/**
	 * Gets entities by their ID.
	 *
	 * @param string $type
	 * @param array $entityIds
	 * @return array
	 */
	public function getEntitiesById($type, $entityIds)
	{
		if (!$entityIds)
		{
			return array();
		}

		$linkType = $this->_getLinkType($type);

		$table = $linkType->getEntityTableName();
		$query = blx()->db->createCommand()
			->select($table.'.*')
			->from($table.' '.$table)
			->where(array('in', $table.'.id', $entityIds));

		// Give the link type a chance to make any changes
		$query = $linkType->modifyLinkedEntitiesQuery($query);

		$rows = $query->queryAll();

		$rowsById = array();
		foreach ($rows as $row)
		{
			$rowsById[$row['id']] = $row;
		}

		$orderedRows = array();
		foreach ($entityIds as $id)
		{
			if (isset($rowsById[$id]))
			{
				$orderedRows[] = $rowsById[$id];
			}
		}

		return $linkType->populateEntities($orderedRows);
	}

	/**
	 * Sets the linked entities for a Links block.
	 *
	 * @param int $criteriaId
	 * @param int $leftEntityId
	 * @param array $rightEntityIds
	 * @throws \Exception
	 * @return void
	 */
	public function setLinks($criteriaId, $leftEntityId, $rightEntityIds)
	{
		$transaction = blx()->db->beginTransaction();
		try
		{
			// Delete the existing links
			blx()->db->createCommand()->delete('links', array(
				'criteriaId' => $criteriaId,
				'leftEntityId' => $leftEntityId
			));

			if ($rightEntityIds)
			{
				$totalEntities = count($rightEntityIds);
				$values = array();

				foreach ($rightEntityIds as $index => $entityId)
				{
					$sortOrder = ($index - $totalEntities);
					$values[] = array($criteriaId, $leftEntityId, $entityId, $sortOrder);
				}

				$columns = array('criteriaId', 'leftEntityId', 'rightEntityId', 'rightSortOrder');
				blx()->db->createCommand()->insertAll('links', $columns, $values);
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
	 * Deletes any links involving a specified entity(s) by its type and ID(s).
	 *
	 * @param string $type
	 * @param int|array $entityId
	 * @return bool
	 */
	public function deleteLinksForEntity($type, $entityId)
	{
		// Get all link criteria involving this entity type
		$leftCriteriaIds = blx()->db->createCommand()
			->select('id')
			->from('linkcriteria')
			->where(array('leftEntityType' => $type))
			->queryColumn();

		$rightCriteriaIds = blx()->db->createCommand()
			->select('id')
			->from('linkcriteria')
			->where(array('rightEntityType' => $type))
			->queryColumn();

		// Delete the links
		if ($leftCriteriaIds || $rightCriteriaIds)
		{
			$leftCondition = array('and',
				array('in', 'criteriaId', $leftCriteriaIds),
				(is_array($entityId) ? array('in', 'leftEntityId', $entityId) : 'leftEntityId = '.(int)$entityId));

			$rightCondition = array('and',
				array('in', 'criteriaId', $rightCriteriaIds),
				(is_array($entityId) ? array('in', 'rightEntityId', $entityId) : 'rightEntityId = '.(int)$entityId));

			if ($leftCriteriaIds && $rightCriteriaIds)
			{
				$conditions = array('or', $leftCondition, $rightCondition);
			}
			else if ($leftCriteriaIds)
			{
				$conditions = $leftCondition;
			}
			else
			{
				$conditions = $rightCondition;
			}

			blx()->db->createCommand()->delete('links', $conditions);
		}

		return true;
	}

	/**
	 * Returns a link type instance.
	 *
	 * @param string $type
	 * @return BaseLinkType
	 * @throws Exception
	 */
	private function _getLinkType($type)
	{
		$linkType = $this->getLinkType($type);

		if ($linkType)
		{
			return $linkType;
		}
		else
		{
			throw new Exception(Blocks::t('No link type exists with the class “{class}”', array('class' => $type)));
		}
	}
}
