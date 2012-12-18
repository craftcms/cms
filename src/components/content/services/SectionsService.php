<?php
namespace Blocks;

/**
 *
 */
class SectionsService extends BaseEntityService
{
	private $_blocksBySection;
	private $_allSectionIds;
	private $_editableSectionIds;
	private $_sections;

	// -------------------------------------------
	//  Section Blocks
	// -------------------------------------------

	/**
	 * The block model class name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $blockModelClass = 'EntryBlockModel';

	/**
	 * The block record class name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $blockRecordClass = 'EntryBlockRecord';

	/**
	 * The content record class name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $contentRecordClass = 'SectionContentRecord';

	/**
	 * The name of the content table column right before where the block columns should be inserted.
	 *
	 * @access protected
	 * @var string
	 */
	protected $placeBlockColumnsAfter = 'entryId';

	/**
	 * Populates a block record from a model.
	 *
	 * @access protected
	 * @param EntryBlockModel $block
	 * @return EntryBlockRecord $blockRecord;
	 */
	protected function populateBlockRecord(EntryBlockModel $block)
	{
		$blockRecord = parent::populateBlockRecord($block);
		$blockRecord->sectionId = $block->sectionId;
		return $blockRecord;
	}

	/**
	 * Returns the content table name.
	 *
	 * @param EntryBlockModel $block
	 * @access protected
	 * @return string
	 */
	protected function getContentTable(EntryBlockModel $block)
	{
		$section = $this->getSectionById($block->sectionId);
		return $this->getSectionContentTableName($section);
	}

	/**
	 * Returns all blocks by a section ID.
	 *
	 * @param int $sectionId
	 * @return array
	 */
	public function getBlocksBySectionId($sectionId)
	{
		if (!isset($this->_blocksBySection[$sectionId]))
		{
			$blockRecords = EntryBlockRecord::model()->ordered()->findAllByAttributes(array(
				'sectionId' => $sectionId
			));
			$this->_blocksBySection[$sectionId] = $this->populateBlocks($blockRecords);
		}

		return $this->_blocksBySection[$sectionId];
	}

	/**
	 * Returns the total number of blocks by a section ID.
	 *
	 * @param int $sectionId
	 * @return int
	 */
	public function getTotalBlocksBySectionId($sectionId)
	{
		return EntryBlockRecord::model()->countByAttributes(array(
			'sectionId' => $sectionId
		));
	}

	// -------------------------------------------
	//  Sections
	// -------------------------------------------

	/**
	 * Returns a section’s content table name.
	 *
	 * @param SectionModel $section
	 * @return string
	 */
	public function getSectionContentTableName(SectionModel $section)
	{
		return 'entrycontent_'.$section->handle;
	}

	/**
	 * Returns all of the section IDs.
	 *
	 * @return array
	 */
	public function getAllSectionIds()
	{
		if (!isset($this->_allSectionIds))
		{
			$this->_allSectionIds = blx()->db->createCommand()
				->select('id')
				->from('sections')
				->queryColumn();
		}

		return $this->_allSectionIds;
	}

	/**
	 * Returns all of the section IDs that are editable by the current user.
	 *
	 * @return array
	 */
	public function getEditableSectionIds()
	{
		if (!isset($this->_editableSectionIds))
		{
			$this->_editableSectionIds = array();
			$allSectionIds = $this->getAllSectionIds();

			foreach ($allSectionIds as $sectionId)
			{
				if (blx()->user->can('editEntriesInSection'.$sectionId))
				{
					$this->_editableSectionIds[] = $sectionId;
				}
			}
		}

		return $this->_editableSectionIds;
	}

	/**
	 * Gets sections.
	 *
	 * @param SectionCriteria|null $criteria
	 * @return array
	 */
	public function findSections(SectionCriteria $criteria = null)
	{
		if (!$criteria)
		{
			$criteria = new SectionCriteria();
		}

		$query = blx()->db->createCommand()
			->from('sections');

		if ($this->_applySectionConditions($query, $criteria))
		{
			if ($criteria->order)
			{
				$query->order($criteria->order);
			}

			if ($criteria->offset)
			{
				$query->offset($criteria->offset);
			}

			if ($criteria->limit)
			{
				$query->limit($criteria->limit);
			}

			$result = $query->queryAll();
			return SectionModel::populateModels($result, $criteria->indexBy);
		}
		else
		{
			return array();
		}
	}

	/**
	 * Gets a section.
	 *
	 * @param SectionCriteria|null $criteria
	 * @return SectionModel|null
	 */
	public function findSection(SectionCriteria $criteria = null)
	{
		if (!$criteria)
		{
			$criteria = new SectionCriteria();
		}

		$query = blx()->db->createCommand()
			->from('sections');

		if ($this->_applySectionConditions($query, $criteria))
		{
			$result = $query->queryRow();

			if ($result)
			{
				return SectionModel::populateModel($result);
			}
		}
	}

	/**
	 * Gets the total number of sections.
	 *
	 * @param SectionCriteria|null $criteria
	 * @return int
	 */
	public function getTotalSections(SectionCriteria $criteria = null)
	{
		if (!$criteria)
		{
			$criteria = new SectionCriteria();
		}

		$query = blx()->db->createCommand()
			->select('count(id)')
			->from('sections');

		if ($this->_applySectionConditions($query, $criteria))
		{
			return (int) $query->queryScalar();
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Applies WHERE conditions to a DbCommand query for sections.
	 *
	 * @access private
	 * @param DbCommand $query
	 * @param array $criteria
	 * @return bool
	 */
	private function _applySectionConditions($query, $criteria)
	{
		$whereConditions = array();
		$whereParams = array();

		if ($criteria->editable && !blx()->user->isAdmin())
		{
			$editableSectionIds = $this->getEditableSectionIds();

			if (!$editableSectionIds)
			{
				return false;
			}

			$whereConditions[] = array('in', 'id', $editableSectionIds);
		}

		if ($criteria->id)
		{
			$whereConditions[] = DbHelper::parseParam('id', $criteria->id, $whereParams);
		}

		if ($criteria->handle)
		{
			$whereConditions[] = DbHelper::parseParam('handle', $criteria->handle, $whereParams);
		}

		if ($criteria->hasUrls)
		{
			$whereConditions[] = DbHelper::parseParam('hasUrls', $criteria->hasUrls, $whereParams);
		}

		if ($whereConditions)
		{
			array_unshift($whereConditions, 'and');
			$query->where($whereConditions, $whereParams);
		}

		return true;
	}

	/**
	 * Gets a section by its ID.
	 *
	 * @param $sectionId
	 * @return SectionModel|null
	 */
	public function getSectionById($sectionId)
	{
		if (!isset($this->_sections) || !array_key_exists($sectionId, $this->_sections))
		{
			$sectionRecord = SectionRecord::model()->findById($sectionId);

			if ($sectionRecord)
			{
				$this->_sections[$sectionId] = SectionModel::populateModel($sectionRecord);
			}
			else
			{
				$this->_sections[$sectionId] = null;
			}
		}

		return $this->_sections[$sectionId];
	}

	/**
	 * Gets a section by its handle.
	 *
	 * @param string $sectionHandle
	 * @return SectionModel|null
	 */
	public function getSectionByHandle($sectionHandle)
	{
		$sectionRecord = SectionRecord::model()->findByAttributes(array(
			'handle' => $sectionHandle
		));

		if ($sectionRecord)
		{
			return SectionModel::populateModel($sectionRecord);
		}
	}

	/**
	 * Gets a section record or creates a new one.
	 *
	 * @access private
	 * @param int $sectionId
	 * @throws Exception
	 * @return SectionRecord
	 */
	private function _getSectionRecordById($sectionId = null)
	{
		if ($sectionId)
		{
			$sectionRecord = SectionRecord::model()->findById($sectionId);

			if (!$sectionRecord)
			{
				throw new Exception(Blocks::t('No section exists with the ID “{id}”', array('id' => $sectionId)));
			}
		}
		else
		{
			$sectionRecord = new SectionRecord();
		}

		return $sectionRecord;
	}

	/**
	 * Saves a section.
	 *
	 * @param SectionModel $section
	 * @throws \Exception
	 * @return bool
	 */
	public function saveSection(SectionModel $section)
	{
		$sectionRecord = $this->_getSectionRecordById($section->id);

		$isNewSection = $sectionRecord->isNewRecord();

		if (!$isNewSection)
		{
			$oldUrlFormat = $sectionRecord->urlFormat;
			$oldSection = SectionModel::populateModel($sectionRecord);
			$oldContentTable = $this->getSectionContentTableName($oldSection);
		}

		$sectionRecord->name       = $section->name;
		$sectionRecord->handle     = $section->handle;
		$sectionRecord->titleLabel = $section->titleLabel;
		$sectionRecord->hasUrls    = $section->hasUrls;

		if ($section->hasUrls)
		{
			$sectionRecord->urlFormat = $section->urlFormat;
			$sectionRecord->template  = $section->template;
		}
		else
		{
			$sectionRecord->urlFormat = $section->urlFormat = null;
			$sectionRecord->template  = $section->template  = null;
		}

		if ($sectionRecord->validate())
		{
			$transaction = blx()->db->beginTransaction();
			try
			{
				$sectionRecord->save(false);

				// Now that we have a section ID, save it on the model
				if (!$section->id)
				{
					$section->id = $sectionRecord->id;
				}

				if ($isNewSection)
				{
					// Create the content table
					$contentRecord = new SectionContentRecord($section, 'install');
					$contentRecord->createTable();
					$contentRecord->addForeignKeys();
				}
				else
				{
					// Rename the content table if the handle changed
					$newContentTable = $this->getSectionContentTableName($section);

					if ($newContentTable != $oldContentTable)
					{
						blx()->db->createCommand()->renameTable($oldContentTable, $newContentTable);
					}

					// Update the entry URIs if the URL format changed
					if ($section->urlFormat != $oldUrlFormat)
					{
						if ($section->hasUrls)
						{
							foreach ($sectionRecord->entries as $entryRecord)
							{
								$entryRecord->uri = str_replace('{slug}', $entryRecord->slug, $section->urlFormat);
								$entryRecord->save();
							}
						}
						else
						{
							blx()->db->createCommand()
								->update('entries', array('uri' => null), array('sectionId' => $section->id));
						}
					}
				}

				$transaction->commit();
			}
			catch (\Exception $e)
			{
				$transaction->rollBack();
				throw $e;
			}

			return true;
		}
		else
		{
			$section->addErrors($sectionRecord->getErrors());
			return false;
		}
	}

	/**
	 * Deletes a section by its ID.
	 *
	 * @param int $sectionId
	 * @throws \Exception
	 * @return bool
	*/
	public function deleteSectionById($sectionId)
	{
		$transaction = blx()->db->beginTransaction();
		try
		{
			// First delete the entries (this will take care of associated links, etc.)
			$entryIds = blx()->db->createCommand()
				->select('id')
				->from('entries')
				->where(array('sectionId' => $sectionId))
				->queryColumn();

			blx()->entries->deleteEntryById($entryIds);

			// Delete the content table.
			$sectionRecord = $this->_getSectionRecordById($sectionId);
			$section = SectionModel::populateModel($sectionRecord);
			$contentRecord = new SectionContentRecord($section);
			$contentRecord->dropForeignKeys();
			$contentRecord->dropTable();

			// Delete the section
			blx()->db->createCommand()->delete('sections', array('id' => $sectionId));

			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}

		return true;
	}
}
