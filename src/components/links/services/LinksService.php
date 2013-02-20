<?php
namespace Blocks;

/**
 *
 */
class LinksService extends BaseApplicationComponent
{
	private $_criteriaRecordsByTypeAndHandle;

	/**
	 * Returns all installed linkable entry types.
	 *
	 * @return array
	 */
	public function getAllLinkableEntryTypes()
	{
		$entryTypes = blx()->entries->getAllEntryTypes();
		$linkableEntryTypes = array();

		foreach ($entryTypes as $entryType)
		{
			if ($entryType->isLinkable())
			{
				$classHandle = $entryType->getClassHandle();
				$linkableEntryTypes[$classHandle] = $entryType;
			}
		}

		return $linkableEntryTypes;
	}

	/**
	 * Returns a linkable entry type.
	 *
	 * @param string $class
	 * @return BaseEntryType|null
	 */
	public function getLinkableEntryType($class)
	{
		$entryType = blx()->entries->getEntryType($class);

		if ($entryType && $entryType->isLinkable())
		{
			return $entryType;
		}
	}

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
	 * Returns a link criteria model by the its entry type and a given handle.
	 *
	 * @param string $entryType
	 * @param string $handle
	 * @param string $dir The direction we should be going ('ltr' or 'rtl')
	 * @return LinkCriteriaModel|null
	 */
	public function getCriteriaByTypeAndHandle($entryType, $handle, $dir = 'ltr')
	{
		if (!isset($this->_criteriaRecordsByTypeAndHandle[$dir][$entryType]) || !array_key_exists($handle, $this->_criteriaRecordsByTypeAndHandle[$dir][$entryType]))
		{
			list($source, $target) = $this->_getDirProps($dir);

			$record = LinkCriteriaRecord::model()->findByAttributes(array(
				"{$source}EntryType" => $entryType,
				"{$dir}Handle"       => $handle
			));

			if ($record)
			{
				$this->_criteriaRecordsByTypeAndHandle[$dir][$entryType][$handle] = LinkCriteriaModel::populateModel($record);
			}
			else
			{
				$this->_criteriaRecordsByTypeAndHandle[$dir][$entryType][$handle] = null;
			}
		}

		return $this->_criteriaRecordsByTypeAndHandle[$dir][$entryType][$handle];
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
				throw new Exception(Blocks::t('No link criteria exists with the ID “{id}”.', array('id' => $criteria->id)));
			}

			$oldCriteria = LinkCriteriaModel::populateModel($criteriaRecord);
		}
		else
		{
			$criteriaRecord = new LinkCriteriaRecord();
		}

		$criteriaRecord->ltrHandle      = $criteria->ltrHandle;
		$criteriaRecord->rtlHandle      = $criteria->rtlHandle;
		$criteriaRecord->leftEntryType  = $criteria->leftEntryType;
		$criteriaRecord->rightEntryType = $criteria->rightEntryType;
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
				// Have either of the entry types changed?
				if ($criteria->leftEntryType != $oldCriteria->leftEntryType || $criteria->rightEntryType != $oldCriteria->rightEntryType)
				{
					// Delete the links that were previously created with this criteria
					blx()->db->createCommand()->delete('links', array('criteriaId' => $criteria->id));
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
	 * Returns the linked entries by a given criteria and the source entry ID.
	 *
	 * @param LinkCriteriaModel $criteria
	 * @param int $sourceEntryId
	 * @param string $dir The direction we should be going ('ltr' or 'rtl')
	 * @return array
	 */
	public function getLinkedEntries(LinkCriteriaModel $criteria, $sourceEntryId, $dir = 'ltr')
	{
		list($source, $target) = $this->_getDirProps($dir);

		$entryTypeClassProperty = $target.'EntryType';
		$entryTypeClass = $criteria->$entryTypeClassProperty;
		$entryType = $this->getLinkableEntryType($entryTypeClass);

		if (!$entryType)
		{
			return array();
		}

		$entryCriteria = blx()->entries->getEntryCriteria($entryTypeClass);
		$query = blx()->entries->buildEntriesQuery($entryCriteria);

		if ($query)
		{
			$query
				->addSelect("l.{$target}SortOrder")
				->join('links l', "l.{$target}EntryId = e.id")
				->andWhere(array('l.criteriaId'  => $criteria->id, "l.{$source}EntryId" => $sourceEntryId));

			return $this->_getEntriesFromQuery($entryType, $query, "{$target}SortOrder");
		}
		else
		{
			return array();
		}
	}

	/**
	 * Gets entries by their ID.
	 *
	 * @param string $entryTypeClass
	 * @param array $entryIds
	 * @return array
	 */
	public function getEntriesById($entryTypeClass, $entryIds)
	{
		if (!$entryIds)
		{
			return array();
		}

		$entryType = $this->getLinkableEntryType($entryTypeClass);

		if (!$entryType)
		{
			return array();
		}

		$entryCriteria = blx()->entries->getEntryCriteria($entryTypeClass, array(
			'id' => $entryIds
		));

		$query = blx()->entries->buildEntriesQuery($entryCriteria);

		if ($query)
		{
			$entries = $this->_getEntriesFromQuery($entryType, $query);

			// Put them into the requested order
			$orderedEntries = array();
			$entriesById = array();

			foreach ($entries as $entry)
			{
				$entriesById[$entry->id] = $entry;
			}

			foreach ($entryIds as $id)
			{
				if (isset($entriesById[$id]))
				{
					$orderedEntries[] = $entriesById[$id];
				}
			}

			return $orderedEntries;
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
	 * Sets the linked entries for a Links field.
	 *
	 * @param int $criteriaId
	 * @param int $sourceEntryId
	 * @param array $linkedEntryIds
	 * @param string $dir The direction we should be going ('ltr' or 'rtl')
	 * @throws \Exception
	 * @return void
	 */
	public function saveLinks($criteriaId, $sourceEntryId, $linkedEntryIds, $dir = 'ltr')
	{
		list($source, $target) = $this->_getDirProps($dir);

		$transaction = blx()->db->beginTransaction();
		try
		{
			// Delete the existing links
			blx()->db->createCommand()->delete('links', array(
				'criteriaId'       => $criteriaId,
				"{$source}EntryId" => $sourceEntryId
			));

			if ($linkedEntryIds)
			{
				$values = array();

				foreach ($linkedEntryIds as $sortOrder => $entryId)
				{
					$values[] = array($criteriaId, $sourceEntryId, $entryId, $sortOrder+1);
				}

				$columns = array('criteriaId', "{$source}EntryId", "{$target}EntryId", "{$target}SortOrder");
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
	 * Returns entries based on a given query.
	 *
	 * @access private
	 * @param BaseEntryType $entryType
	 * @param DbCommand $subquery
	 * @param string $order
	 * @return array
	 */
	private function _getEntriesFromQuery(BaseEntryType $entryType, DbCommand $subquery, $order = null)
	{
		// Only get the unique entries (no locale duplicates)
		$query = blx()->db->createCommand()
			->select('*')
			->from('('.$subquery->getText().') AS '.blx()->db->quoteTableName('r'))
			->group('r.id');

		$query->params = $subquery->params;

		if ($order)
		{
			$query->order($order);
		}

		$result = $query->queryAll();
		$entries = array();

		foreach ($result as $row)
		{
			$entries[] = $entryType->populateEntryModel($row);
		}

		return $entries;
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
