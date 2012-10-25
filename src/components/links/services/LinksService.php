<?php
namespace Blocks;

/**
 *
 */
class LinksService extends BaseApplicationComponent
{
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
		$criteria = $this->getCriteriaRecordById($criteriaId);
		$linkType = $this->_getLinkType($criteria->rightEntityType);

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
