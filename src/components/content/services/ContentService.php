<?php
namespace Blocks;

/**
 *
 */
class ContentService extends BaseApplicationComponent
{
	/* BLOCKSPRO ONLY */
	// -------------------------------------------
	//  Sections
	// -------------------------------------------

	/**
	 * Populates a section package.
	 *
	 * @param array|SectionRecord $attributes
	 * @return SectionPackage
	 */
	public function populateSectionPackage($attributes)
	{
		if ($attributes instanceof SectionRecord)
		{
			$attributes = $attributes->getAttributes();
		}

		$sectionPackage = new SectionPackage();

		$sectionPackage->id = $attributes['id'];
		$sectionPackage->name = $attributes['name'];
		$sectionPackage->handle = $attributes['handle'];
		$sectionPackage->hasUrls = $attributes['hasUrls'];
		$sectionPackage->urlFormat = $attributes['urlFormat'];
		$sectionPackage->template = $attributes['template'];

		return $sectionPackage;
	}

	/**
	 * Mass-populates section packages.
	 *
	 * @param array  $data
	 * @param string $index
	 * @return array
	 */
	public function populateSectionPackages($data, $index = 'id')
	{
		$sectionPackages = array();

		foreach ($data as $attributes)
		{
			$sectionPackage = $this->populateSectionPackage($attributes);
			$sectionPackages[$sectionPackage->$index] = $sectionPackage;
		}

		return $sectionPackages;
	}

	/**
	 * Gets sections.
	 *
	 * @param SectionParams|null $params
	 * @return array
	 */
	public function getSections(SectionParams $params = null)
	{
		if (!$params)
		{
			$params = new SectionParams();
		}

		$query = blx()->db->createCommand()
			->from('sections');

		$this->_applySectionConditions($query, $params);

		if ($params->order)
		{
			$query->order($params->order);
		}

		if ($params->offset)
		{
			$query->offset($params->offset);
		}

		if ($params->limit)
		{
			$query->limit($params->limit);
		}

		$result = $query->queryAll();
		return $this->populateSectionPackages($result);
	}

	/**
	 * Gets a section.
	 *
	 * @param SectionParams|null $params
	 * @return SectionPackage|null
	 */
	public function getSection(SectionParams $params = null)
	{
		if (!$params)
		{
			$params = new SectionParams();
		}

		$query = blx()->db->createCommand()
			->from('sections');

		$this->_applySectionConditions($query, $params);

		$result = $query->queryRow();
		if ($result)
		{
			return $this->populateSectionPackage($result);
		}
	}

	/**
	 * Gets the total number of sections.
	 *
	 * @param SectionParams|null $params
	 * @return int
	 */
	public function getTotalSections(SectionParams $params = null)
	{
		if (!$params)
		{
			$params = new SectionParams();
		}

		$query = blx()->db->createCommand()
			->select('count(id)')
			->from('sections');

		$this->_applySectionConditions($query, $params);

		return (int) $query->queryScalar();
	}

	/**
	 * Applies WHERE conditions to a DbCommand query for sections.
	 *
	 * @access private
	 * @param DbCommand $query
	 * @param array $params
	 */
	private function _applySectionConditions($query, $params)
	{
		$whereConditions = array();
		$whereParams = array();

		if ($params->id)
		{
			$whereConditions[] = DbHelper::parseParam('id', $params->id, $whereParams);
		}

		if ($params->handle)
		{
			$whereConditions[] = DbHelper::parseParam('handle', $params->handle, $whereParams);
		}

		if ($params->hasUrls)
		{
			$whereConditions[] = DbHelper::parseParam('hasUrls', $params->hasUrls, $whereParams);
		}

		if ($whereConditions)
		{
			array_unshift($whereConditions, 'and');
			$query->where($whereConditions, $whereParams);
		}
	}

	/**
	 * Gets a section by its ID.
	 *
	 * @param int $sectionid
	 * @return SectionPackage|null
	 */
	public function getSectionById($sectionId)
	{
		$sectionRecord = SectionRecord::model()->findById($sectionId);
		if ($sectionRecord)
		{
			return $this->populateSectionPackage($sectionRecord);
		}
	}

	/**
	 * Gets a section by its handle.
	 *
	 * @param string $sectionHandle
	 * @return SectionPackage|null
	 */
	public function getSectionByHandle($sectionHandle)
	{
		$sectionRecord = SectionRecord::model()->findByAttributes(array(
			'handle' => $sectionHandle
		));

		if ($sectionRecord)
		{
			return $this->populateSectionPackage($sectionRecord);
		}
	}

	/**
	 * Gets a section record or creates a new one.
	 *
	 * @access private
	 * @param int $sectionId
	 * @return SectionRecord
	 */
	private function _getSectionRecordById($sectionId = null)
	{
		if ($sectionId)
		{
			$sectionRecord = SectionRecord::model()->findById($sectionId);

			if (!$sectionRecord)
			{
				$this->_noSectionExists($sectionId);
			}
		}
		else
		{
			$sectionRecord = new SectionRecord();
		}

		return $sectionRecord;
	}

	/**
	 * Throws a "No section exists" exception.
	 *
	 * @access private
	 * @param int $sectionId
	 * @throws Exception
	 */
	private function _noSectionExists($sectionId)
	{
		throw new Exception(Blocks::t('No section exists with the ID “{id}”', array('id' => $sectionId)));
	}

	/**
	 * Saves a section.
	 *
	 * @param SectionPackage $sectionPackage
	 * @throws \Exception
	 * @return bool
	 */
	public function saveSection(SectionPackage $sectionPackage)
	{
		$sectionRecord = $this->_getSectionRecordById($sectionPackage->id);

		$isNewSection = $sectionRecord->isNewRecord();
		if (!$isNewSection)
		{
			$oldUrlFormat = $sectionRecord->urlFormat;
			$oldSectionPackage = $this->populateSectionPackage($sectionRecord);
			$oldContentTable = EntryContentRecord::getTableNameForSection($oldSectionPackage);
		}

		$sectionRecord->name      = $sectionPackage->name;
		$sectionRecord->handle    = $sectionPackage->handle;
		$sectionRecord->hasUrls   = $sectionPackage->hasUrls;
		$sectionRecord->urlFormat = $sectionPackage->urlFormat;
		$sectionRecord->template  = $sectionPackage->template;

		if ($sectionRecord->validate())
		{
			$transaction = blx()->db->beginTransaction();
			try
			{
				$sectionRecord->save(false);

				// Now that we have a section ID, save it on the package
				if (!$sectionPackage->id)
				{
					$sectionPackage->id = $sectionRecord->id;
				}

				if ($isNewSection)
				{
					// Create the content table
					$contentRecord = new EntryContentRecord($sectionPackage, 'install');
					$contentRecord->createTable();
					$contentRecord->addForeignKeys();
				}
				else
				{
					// Rename the content table if the handle changed
					$newContentTable = EntryContentRecord::getTableNameForSection($sectionPackage);
					if ($newContentTable != $oldContentTable)
						blx()->db->createCommand()->renameTable($oldContentTable, $newContentTable);

					// Update the entry URIs if the URL format changed
					if ($sectionRecord->urlFormat != $oldUrlFormat)
					{
						foreach ($sectionRecord->entries as $entryRecord)
						{
							$entry->uri = $this->getEntryUri($entryRecord);
							$entry->save();
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
		$sectionRecord = $this->_getSectionRecordById($sectionId);

		$transaction = blx()->db->beginTransaction();
		try
		{
			// Delete the entire content table
			$sectionPackage = $this->populateSectionPackage($sectionRecord);
			$contentRecord = new EntryContentRecord($sectionPackage);
			$contentRecord->dropForeignKeys();
			$contentRecord->dropTable();

			// Delete the entries and titles
			blx()->db->createCommand()
				->setText('delete e, t from {{entries}} e inner join {{entrytitles}} t
				           where e.id = t.entryId and e.id = 4')
				->query();

			// Delete the entry blocks
			blx()->db->createCommand()
				->where(array('sectionId' => $sectionId))
				->delete('entryblocks');

			// Delete the section
			$sectionRecord->delete();

			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}

		return true;
	}

	/* end BLOCKSPRO ONLY */
	// -------------------------------------------
	//  Entry Blocks
	// -------------------------------------------

	/**
	 * Populates an entry block package.
	 *
	 * @param array|EntryBlockRecord $attributes
	 * @return EntryBlockPackage
	 */
	public function populateEntryBlockPackage($attributes)
	{
		if ($attributes instanceof EntryBlockRecord)
		{
			$attributes = $attributes->getAttributes();
		}

		$blockPackage = new EntryBlockPackage();

		$blockPackage->id = $attributes['id'];
		/* BLOCKSPRO ONLY */
		$blockPackage->sectionId = $attributes['sectionId'];
		/* end BLOCKSPRO ONLY */
		$blockPackage->name = $attributes['name'];
		$blockPackage->handle = $attributes['handle'];
		$blockPackage->instructions = $attributes['instructions'];
		/* BLOCKSPRO ONLY */
		$blockPackage->required = $attributes['required'];
		$blockPackage->translatable = $attributes['translatable'];
		/* end BLOCKSPRO ONLY */
		$blockPackage->class = $attributes['class'];
		$blockPackage->settings = $attributes['settings'];

		return $blockPackage;
	}

	/**
	 * Mass-populates entry block packages.
	 *
	 * @param array  $data
	 * @param string $index
	 * @return array
	 */
	public function populateEntryBlockPackages($data, $index = 'id')
	{
		$blockPackages = array();

		foreach ($data as $attributes)
		{
			$blockPackage = $this->populateEntryBlockPackage($attributes);
			$blockPackages[$blockPackage->$index] = $blockPackage;
		}

		return $blockPackages;
	}

	/* BLOCKS ONLY */
	/**
	 * Returns all entry blocks.
	 *
	 * @return array
	 */
	public function getEntryBlocks()
	{
		$blockRecords = EntryBlockRecord::model()->ordered()->findAll();
		return $this->populateEntryBlockPackages($blockRecords);
	}

	/* end BLOCKS ONLY */
	/* BLOCKSPRO ONLY */
	/**
	 * Returns all entry blocks by a given section ID.
	 *
	 * @param int $sectionId
	 * @return array
	 */
	public function getEntryBlocksBySectionId($sectionId)
	{
		$blockRecords = EntryBlockRecord::model()->ordered()->findAllByAttributes(array(
			'sectionId' => $sectionId
		));
		return $this->populateEntryBlockPackages($blockRecords);
	}

	/**
	 * Returns the total number of entry blocks by a given section ID.
	 *
	 * @param int $sectionId
	 * @return int
	 */
	public function getTotalEntryBlocksBySectionId($sectionId)
	{
		return EntryBlockRecord::model()->countByAttributes(array(
			'sectionId' => $sectionId
		));
	}

	/* end BLOCKSPRO ONLY */
	/**
	 * Gets an entry block by its ID.
	 *
	 * @param int $blockId
	 * @return BaseBlock
	 */
	public function getEntryBlockById($blockId)
	{
		$blockRecord = EntryBlockRecord::model()->findById($blockId);
		if ($blockRecord)
		{
			return $this->populateEntryBlockPackage($blockRecord);
		}
	}

	/**
	 * Gets an entry block or creates a new one.
	 *
	 * @access private
	 * @param int $blockId
	 * @return EntryBlockRecord
	 */
	private function _getEntryBlockRecordById($blockId = null)
	{
		if ($blockId)
		{
			$blockRecord = EntryBlockRecord::model()->findById($blockId);

			if (!$blockRecord)
			{
				$this->_noEntryBlockExists($blockId);
			}
		}
		else
		{
			$blockRecord = new EntryBlockRecord();
		}

		return $blockRecord;
	}

	/**
	 * Throws a "No entry block exists" exception.
	 *
	 * @access private
	 * @param int $blockId
	 * @throws Exception
	 */
	private function _noEntryBlockExists($blockId)
	{
		throw new Exception(Blocks::t('No entry block exists with the ID “{id}”', array('id' => $blockId)));
	}

	/**
	 * Saves an entry block.
	 *
	 * @param EntryBlockPackage $blockPackage
	 * @throws \Exception
	 * @return bool
	 */
	public function saveEntryBlock(EntryBlockPackage $blockPackage)
	{
		$blockRecord = $this->_getEntryBlockRecordById($blockPackage->id);

		$isNewBlock = $blockRecord->isNewRecord();

		if (!$isNewBlock)
		{
			$oldHandle = $blockRecord->handle;
		}

		/* BLOCKSPRO ONLY */
		$blockRecord->sectionId = $blockPackage->sectionId;
		/* end BLOCKSPRO ONLY */
		$blockRecord->name = $blockPackage->name;
		$blockRecord->handle = $blockPackage->handle;
		$blockRecord->instructions = $blockPackage->instructions;
		/* BLOCKSPRO ONLY */
		$blockRecord->required = $blockPackage->required;
		$blockRecord->translatable = $blockPackage->translatable;
		/* end BLOCKSPRO ONLY */
		$blockRecord->class = $blockPackage->class;

		$block = blx()->blocks->populateBlock($blockPackage);

		$recordValidates = $blockRecord->validate();
		$settingsValidate = $block->getSettings()->validate();

		if ($recordValidates && $settingsValidate)
		{
			// Set the record settings now that the block has had a chance to tweak them
			$blockRecord->settings = $block->getSettings()->getAttributes();

			if ($isNewBlock)
			{
				$maxSortOrder = blx()->db->createCommand()
					->select('max(sortOrder)')
					->from('entryblocks')
					->queryScalar();

				$blockRecord->sortOrder = $maxSortOrder + 1;
			}

			$transaction = blx()->db->beginTransaction();
			try
			{
				$blockRecord->save(false);

				// Now that we have a block ID, save it on the package
				if (!$blockPackage->id)
				{
					$blockPackage->id = $blockRecord->id;
				}

				// Create/alter the content table column
				/* BLOCKS ONLY */
				$contentRecord = new EntryContentRecord();
				$contentTable = $contentRecord->getTableName();
				/* end BLOCKS ONLY */
				/* BLOCKSPRO ONLY */
				$section = $this->getSectionById($blockPackage->sectionId);
				if (!$section)
				{
					$this->_noSectionExists($blockPackage->sectionId);
				}

				$contentTable = EntryContentRecord::getTableNameForSection($section);
				/* end BLOCKSPRO ONLY */

				$column = ModelHelper::normalizeAttributeConfig($block->defineContentAttribute());

				if ($isNewBlock)
				{
					blx()->db->createCommand()->addColumn($contentTable, $blockRecord->handle, $column);
				}
				else
				{
					blx()->db->createCommand()->alterColumn($contentTable, $oldHandle, $column, $blockRecord->handle);
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
			$blockPackage->errors = $blockRecord->getErrors();
			$blockPackage->settingsErrors = $block->getSettings()->getErrors();

			return false;
		}
	}

	/**
	 * Deletes an entry block by its ID.
	 *
	 * @param int $blockId
	 * @throws \Exception
	 * @return bool
	 */
	public function deleteEntryBlockById($blockId)
	{
		/* BLOCKS ONLY */
		$blockRecord = $this->_getEntryBlockRecordById($blockId);
		$contentRecord = new EntryContentRecord();
		$contentTable = $contentRecord->getTableName();
		/* end BLOCKS ONLY */
		/* BLOCKSPRO ONLY */
		$blockRecord = EntryBlockRecord::model()->with('section')->findById($blockId);
		if (!$blockRecord)
		{
			$this->_noEntryBlockExists($blockId);
		}

		$sectionPackage = $this->populateSectionPackage($blockRecord->section);
		$contentTable = EntryContentRecord::getTableNameForSection($sectionPackage);
		/* end BLOCKSPRO ONLY */

		$transaction = blx()->db->beginTransaction();
		try
		{
			$blockRecord->delete();
			blx()->db->createCommand()->dropColumn($contentTable, $blockRecord->handle);
			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}

		return true;
	}

	/**
	 * Reorders entry blocks.
	 *
	 * @param array $blockIds
	 * @throws \Exception
	 * @return bool
	 */
	public function reorderEntryBlocks($blockIds)
	{
		/* BLOCKS ONLY */
		$contentRecord = new EntryContentRecord();
		$contentTable = $contentRecord->getTableName();
		/* end BLOCKS ONLY */

		$lastColumn = 'entryId';

		$transaction = blx()->db->beginTransaction();
		try
		{
			foreach ($blockIds as $blockOrder => $blockId)
			{
				// Update the sortOrder in entryblocks
				/* BLOCKS ONLY */
				$blockRecord = $this->_getEntryBlockRecordById($blockId);
				/* end BLOCKS ONLY */
				/* BLOCKSPRO ONLY */
				$blockRecord = EntryBlockRecord::model()->with('section')->findById($blockId);
				if (!$blockRecord)
					$this->_noEntryBlockExists($blockId);
				/* end BLOCKSPRO ONLY */
				$blockRecord->sortOrder = $blockOrder+1;
				$blockRecord->save();

				// Update the column order in the content table
				/* BLOCKSPRO ONLY */
				$sectionPackage = $this->populateSectionPackage($blockRecord->section);
				$contentTable = EntryContentRecord::getTableNameForSection($sectionPackage);
				/* end BLOCKSPRO ONLY */
				$blockPackage = $this->populateEntryBlockPackage($blockRecord);
				$block = blx()->blocks->populateBlock($blockPackage);
				$column = ModelHelper::normalizeAttributeConfig($block->defineContentAttribute());
				blx()->db->createCommand()->alterColumn($contentTable, $blockRecord->handle, $column, null, $lastColumn);
				$lastColumn = $blockRecord->handle;
			}

			// Commit the transaction
			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}

		return true;
	}

	// -------------------------------------------
	//  Entries
	// -------------------------------------------

	/**
	 * Populates an entry package.
	 *
	 * @param array|EntryRecord $attributes
	 * @return EntryPackage
	 */
	public function populateEntryPackage($attributes)
	{
		if ($attributes instanceof EntryRecord)
		{
			$attributes = $attributes->getAttributes();
		}

		$entryPackage = new EntryPackage();

		$entryPackage->id = $attributes['id'];
		/* BLOCKSPRO ONLY */
		$entryPackage->authorId = $attributes['authorId'];
		$entryPackage->sectionId = $attributes['sectionId'];
		/* end BLOCKSPRO ONLY */
		$entryPackage->title = $attributes['title'];
		$entryPackage->slug = $attributes['slug'];

		if (is_numeric($attributes['postDate']))
		{
			$dateTime = new DateTime();
			$dateTime->setTimestamp($attributes['postDate']);
			$entryPackage->postDate = $dateTime;
		}
		else
		{
			$entryPackage->postDate = $attributes['postDate'];
		}
		/* BLOCKSPRO ONLY */

		if (is_numeric($attributes['expiryDate']))
		{
			$dateTime = new DateTime();
			$dateTime->setTimestamp($attributes['expiryDate']);
			$entryPackage->expiryDate = $dateTime;
		}
		else
		{
			$entryPackage->expiryDate = $attributes['expiryDate'];
		}
		/* end BLOCKSPRO ONLY */

		$entryPackage->blocks = array();
		$contentRecord = $this->_getEntryContentRecord($entryPackage);

		/* BLOCKS ONLY */
		$blockPackages = $this->getEntryBlocks();
		/* end BLOCKS ONLY */
		/* BLOCKSPRO ONLY */
		$blockPackages = $this->getEntryBlocksBySectionId($entryPackage->sectionId);
		/* end BLOCKSPRO ONLY */
		foreach ($blockPackages as $blockPackage)
		{
			$handle = $blockPackage->handle;
			$entryPackage->blocks[$handle] = $contentRecord->$handle;
		}

		return $entryPackage;
	}

	/**
	 * Mass-populates entry packages.
	 *
	 * @param array  $data
	 * @param string $index
	 * @return array
	 */
	public function populateEntryPackages($data, $index = 'id')
	{
		$entryPackages = array();

		foreach ($data as $attributes)
		{
			$entryPackage = $this->populateEntryPackage($attributes);
			$entryPackages[$entryPackage->$index] = $entryPackage;
		}

		return $entryPackages;
	}

	/**
	 * Populates an entry with draft data.
	 *
	 * @param EntryPackage $entryPackage
	 */
	public function populateEntryDraftData(EntryPackage $entryPackage)
	{
		$draftRecord = EntryDraftRecord::model()->findByAttributes(array(
			'entryId'  => $entryPackage->id,
			/* BLOCKSPRO ONLY */
			'language' => ($entryPackage->language ? $entryPackage->language : blx()->language),
			/* end BLOCKSPRO ONLY */
		));

		if ($draftRecord)
		{
			$entryPackage->draftId    = $draftRecord->id;
			$entryPackage->title      = (isset($draftRecord->data['title']) ? $draftRecord->data['title'] : null);
			$entryPackage->slug       = (isset($draftRecord->data['slug']) ? $draftRecord->data['slug'] : null);
			$entryPackage->postDate   = (isset($draftRecord->data['postDate']) ? DateTime::createFromFormat(DateTime::W3C_DATE, $draftRecord->data['postDate']) : null);
			/* BLOCKSPRO ONLY */
			$entryPackage->expiryDate = (isset($draftRecord->data['expiryDate']) ? DateTime::createFromFormat(DateTime::W3C_DATE, $draftRecord->data['expiryDate']) : null);
			/* end BLOCKSPRO ONLY */
			$entryPackage->blocks     = (isset($draftRecord->data['blocks']) ? $draftRecord->data['blocks'] : null);
		}
	}

	/**
	 * Gets entries.
	 *
	 * @param EntryParams|null $params
	 * @return array
	 */
	public function getEntries(EntryParams $params = null)
	{
		if (!$params)
		{
			$params = new EntryParams();
		}

		$query = blx()->db->createCommand()
			->select('e.*, t.title')
			->from('entries e')
			->join('entrytitles t', 'e.id = t.entryId');

		$this->_applyEntryConditions($query, $params);

		if ($params->order)
		{
			$query->order($params->order);
		}

		if ($params->offset)
		{
			$query->offset($params->offset);
		}

		if ($params->limit)
		{
			$query->limit($params->limit);
		}

		$result = $query->queryAll();
		return $this->populateEntryPackages($result);
	}

	/**
	 * Gets an entry.
	 *
	 * @param EntryParams|null $params
	 * @return EntryPackage|null
	 */
	public function getEntry(EntryParams $params = null)
	{
		if (!$params)
		{
			$params = new EntryParams();
		}

		$query = blx()->db->createCommand()
			->from('entries e')
			->join('entrytitles t', 'e.id = t.entryId');

		$this->_applyEntryConditions($query, $params);

		$result = $query->queryRow();
		if ($result)
		{
			return $this->populateEntryPackage($result);
		}
	}

	/**
	 * Gets the total number of entries.
	 *
	 * @param EntryParams|null $params
	 * @return int
	 */
	public function getTotalEntries(EntryParams $params = null)
	{
		if (!$params)
		{
			$params = new EntryParams();
		}

		$query = blx()->db->createCommand()
			->select('count(e.id)')
			->from('entries e')
			->join('entrytitles t', 'e.id = t.entryId');

		$this->_applyEntryConditions($query, $params);

		return (int) $query->queryScalar();
	}

	/**
	 * Applies WHERE conditions to a DbCommand query for entries.
	 *
	 * @access private
	 * @param DbCommand $query
	 * @param array $params
	 */
	private function _applyEntryConditions($query, $params)
	{
		$whereConditions = array();
		$whereParams = array();

		if ($params->id)
		{
			$whereConditions[] = DbHelper::parseParam('e.id', $params->id, $whereParams);
		}

		if ($params->slug)
		{
			$whereConditions[] = DbHelper::parseParam('e.handle', $params->slug, $whereParams);
		}

		if ($params->archived)
		{
			$whereConditions[] = 'e.archived = 1';
		}
		else if ($params->status && $params->status != '*')
		{
			$statusCondition = $this->_getEntryStatusCondition($params->status);
			if ($statusCondition)
			{
				$whereConditions[] = $statusCondition;
			}
		}

		/* BLOCKSPRO ONLY */
		if ($params->sectionId)
		{
			$whereConditions[] = DbHelper::parseParam('e.sectionId', $params->sectionId, $whereParams);
		}

		if ($params->section)
		{
			$query->join('sections s', 'e.sectionId = s.id');
			$whereConditions[] = DbHelper::parseParam('s.handle', $params->section, $whereParams);
		}

		if (!$params->language)
		{
			$params->language = blx()->language;
		}

		$whereConditions[] = 't.language = "'.$params->language.'"';
		/* end BLOCKSPRO ONLY */

		if ($whereConditions)
		{
			array_unshift($whereConditions, 'and');
			$query->where($whereConditions, $whereParams);
		}
	}

	/**
	 * Returns the entry status conditions.
	 *
	 * @access private
	 * @param $statusParam
	 */
	private function _getEntryStatusCondition($statusParam)
	{
		$statusConditions = array();

		$statuses = ArrayHelper::stringToArray($statusParam);
		foreach ($statuses as $status)
		{
			$status = strtolower($status);

			$currentTime = DateTimeHelper::currentTime();

			switch ($status)
			{
				case 'live':
				{
					/* BLOCKS ONLY */
					$statusConditions[] = 'e.postDate <= '.$currentTime;
					/* end BLOCKS ONLY */
					/* BLOCKSPRO ONLY */
					$statusConditions[] = array('and',
						'e.postDate <= '.$currentTime,
						array('or', 'e.expiryDate is null', 'e.expiryDate > '.$currentTime)
					);
					/* end BLOCKSPRO ONLY */
					break;
				}
				case 'pending':
				{
					$statusConditions[] = 'e.postDate > '.$currentTime;
					break;
				}
				/* BLOCKSPRO ONLY */
				case 'expired':
				{
					$statusConditions[] = array('and',
						'e.expiryDate is not null',
						'e.expiryDate <= '.$currentTime
					);
					break;
				}
				/* end BLOCKSPRO ONLY */
				case 'draft':
				{
					$statusConditions[] = 'e.postDate is null';
				}
			}
		}

		if ($statusConditions)
		{
			if (count($statusConditions) == 1)
			{
				return $statusConditions[0];
			}
			else
			{
				array_unshift($conditions, 'or');
				return $statusConditions;
			}
		}
	}

	/**
	 * Saves an entry.
	 *
	 * @param EntryPackage $entryPackage
	 * @return bool
	 */
	public function saveEntry(EntryPackage $entryPackage)
	{
		$entryRecord = $this->_getEntryRecord($entryPackage);
		$titleRecord = $this->_getEntryTitleRecord($entryPackage);
		$contentRecord = $this->_getEntryContentRecord($entryPackage);

		$entryRecord->slug = $entryPackage->slug;
		$titleRecord->title = $entryPackage->title;

		// Save the post date if it wasn't set already
		if ($entryPackage->postDate)
		{
			$entryRecord->postDate = $entryPackage->postDate;
		}
		else
		{
			$entryRecord->postDate = new DateTime();
		}

		/* BLOCKSPRO ONLY */
		$entryRecord->expiryDate = $entryPackage->expiryDate;
		/* end BLOCKSPRO ONLY */

 		// Populate the blocks' content
		/* BLOCKS ONLY */
		$blockPackages = $this->getEntryBlocks();
		/* end BLOCKS ONLY */
		/* BLOCKSPRO ONLY */
		$blockPackages = $this->getEntryBlocksBySectionId($entryPackage->sectionId);
		/* end BLOCKSPRO ONLY */

		foreach ($blockPackages as $blockPackage)
		{
			$handle = $blockPackage->handle;

			if (isset($entryPackage->blocks[$handle]))
			{
				$contentRecord->$handle = $entryPackage->blocks[$handle];
			}
			else
			{
				$contentRecord->$handle = null;
			}
		}

		$entryValidates = $entryRecord->validate();
		$titleValidates = $titleRecord->validate();
		$contentValidates = $contentRecord->validate();

		if ($entryValidates && $titleValidates && $contentValidates)
		{
			$entryRecord->save(false);

			// Save the post date on the package if we just made it up
			if (!$entryPackage->postDate)
			{
				$entryPackage->postDate = $entryRecord->postDate;
			}

			// Now that we have an entry ID, save it on the package & models
			if (!$entryPackage->id)
			{
				$entryPackage->id = $entryRecord->id;
				$titleRecord->entryId = $entryRecord->id;
				$contentRecord->entryId = $entryRecord->id;
			}

			$titleRecord->save(false);
			$contentRecord->save(false);

			return true;
		}
		else
		{
			$entryPackage->errors = array_merge($entryRecord->getErrors(), $titleRecord->getErrors());
			$entryPackage->blockErrors = $contentRecord->getErrors();

			return false;
		}
	}

	/**
	 * Saves an entry draft.
	 *
	 * @param EntryDraftPackage $draftPackage
	 * @return bool
	 */
	public function saveEntryDraft(EntryDraftPackage $draftPackage)
	{
		$entryRecord = $this->_getEntryRecord($draftPackage);

		// If it's a new entry, make sure it saves first
		if ($entryRecord->isNewRecord())
		{
			$titleRecord = $this->_getEntryTitleRecord($draftPackage);

			$entryRecord->slug = $draftPackage->slug;
			$titleRecord->title = $draftPackage->title;

			$entryValidates = $entryRecord->validate();
			$titleValidates = $titleRecord->validate();

			if ($entryValidates && $titleValidates)
			{
				$entryRecord->save(false);

				$draftPackage->id = $entryRecord->id;
				$titleRecord->entryId = $entryRecord->id;

				$titleRecord->save(false);
			}
			else
			{
				$draftPackage->errors = array_merge($entryRecord->getErrors(), $titleRecord->getErrors());

				return false;
			}
		}

		$draftRecord = $this->_getEntryDraftRecord($draftPackage);

		$draftRecord->data = array(
			'title'      => $draftPackage->title,
			'slug'       => $draftPackage->slug,
			'postDate'   => (!empty($draftPackage->postDate) ? $draftPackage->postDate->getTimestamp() : null),
			/* BLOCKSPRO ONLY */
			'expiryDate' => (!empty($draftPackage->expiryDate) ? $draftPackage->expiryDate->getTimestamp() : null),
			/* end BLOCKSPRO ONLY */
			'blocks'     => $draftPackage->blocks
		);

		$draftRecord->save(false);

		if (!$draftPackage->draftId)
		{
			$draftPackage->draftId = $draftRecord->id;
		}

		return true;
	}

	/**
	 * Deletes an entry draft by its ID.
	 *
	 * @param int $draftId
	 * @return bool
	 */
	public function deleteEntryDraftById($draftId)
	{
		$draftRecord = $this->_getEntryDraftRecordById($draftId);
		$draftRecord->delete();
		return true;
	}

	/**
	 * Gets an entry record or creates a new one.
	 *
	 * @access private
	 * @param EntryPackage $entryPackage
	 * @return EntryRecord
	 */
	private function _getEntryRecord(EntryPackage $entryPackage)
	{
		if ($entryPackage->id)
		{
			$entryRecord = EntryRecord::model()->findById($entryPackage->id);

			if (!$entryRecord)
			{
				throw new Exception(Blocks::t('No entry exists with the ID “{id}”', array('id' => $entryPackage->id)));
			}
		}
		else
		{
			$entryRecord = new EntryRecord();
			/* BLOCKSPRO ONLY */

			$entryRecord->authorId = $entryPackage->authorId;
			$entryRecord->sectionId = $entryPackage->sectionId;
			/* end BLOCKSPRO ONLY */
		}

		return $entryRecord;
	}

	/**
	 * Gets an entry's title record or creates a new one.
	 *
	 * @access private
	 * @param EntryPackage $entryPackage
	 * @return EntryTitleRecord
	 */
	private function _getEntryTitleRecord(EntryPackage $entryPackage)
	{
		/* BLOCKSPRO ONLY */
		if (!$entryPackage->language)
			$entryPackage->language = blx()->language;

		/* end BLOCKSPRO ONLY */
		if ($entryPackage->id)
		{
			$titleRecord = EntryTitleRecord::model()->findByAttributes(array(
				'entryId' => $entryPackage->id,
				/* BLOCKSPRO ONLY */
				'language' => $entryPackage->language,
				/* end BLOCKSPRO ONLY */
			));
		}

		if (empty($titleRecord))
		{
			$titleRecord = new EntryTitleRecord();
			$titleRecord->entryId = $entryPackage->id;
			/* BLOCKSPRO ONLY */
			$titleRecord->language = $entryPackage->language;
			/* end BLOCKSPRO ONLY */
		}

		return $titleRecord;
	}

	/**
	 * Gets an entry's content record or creates a new one.
	 *
	 * @access private
	 * @param EntryPackage $entryPackage
	 * @return EntryContentRecord
	 */
	private function _getEntryContentRecord(EntryPackage $entryPackage)
	{
		/* BLOCKSPRO ONLY */
		if (!$entryPackage->language)
		{
			$entryPackage->language = blx()->language;
		}

		$sectionPackage = $this->getSectionById($entryPackage->sectionId);
		if (!$sectionPackage)
		{
			$this->_noSectionExists($entryPackage->sectionId);
		}
		/* end BLOCKSPRO ONLY */

		if ($entryPackage->id)
		{
			/* BLOCKS ONLY */
			$contentRecord = EntryContentRecord::model()->findByAttributes(array(
				'entryId' => $entryPackage->id
			));
			/* end BLOCKS ONLY */
			/* BLOCKSPRO ONLY */
			$contentRecord = EntryContentRecord::model($sectionPackage)->findByAttributes(array(
				'entryId'  => $entryPackage->id,
				'language' => $entryPackage->language
			));
			/* end BLOCKSPRO ONLY */
		}

		if (empty($contentRecord))
		{
			/* BLOCKS ONLY */
			$contentRecord = new EntryContentRecord();
			/* end BLOCKS ONLY */
			/* BLOCKSPRO ONLY */
			$contentRecord = new EntryContentRecord($sectionPackage);
			$contentRecord->language = $entryPackage->language;
			/* end BLOCKSPRO ONLY */
			$contentRecord->entryId = $entryPackage->id;
		}

		return $contentRecord;
	}

	/**
	 * Gets an entry draft record or creates a new one.
	 *
	 * @access private
	 * @param EntryDraftPackage $draftPackage
	 * @return EntryDraftRecord
	 */
	private function _getEntryDraftRecord(EntryDraftPackage $draftPackage)
	{
		if ($draftPackage->draftId)
		{
			$draftRecord = $this->_getEntryDraftRecordById($draftPackage->id);
		}
		else
		{
			$draftRecord = new EntryDraftRecord();
			$draftRecord->entryId = $draftPackage->id;
			/* BLOCKSPRO ONLY */
			$draftRecord->authorId = $draftPackage->authorId;
			$draftRecord->language = ($draftPackage->language ? $draftPackage->language : blx()->language);
			/* end BLOCKSPRO ONLY */
		}

		return $draftRecord;
	}

	/**
	 * Gets an entry draft record by its ID.
	 *
	 * @param int $draftId
	 * @return EntryDraftRecord
	 */
	private function _getEntryDraftRecordById($draftId)
	{
		$draftRecord = EntryDraftRecord::model()->findById($draftId);

		if ($draftRecord)
		{
			return $draftRecord;
		}
		else
		{
			throw new Exception(Blocks::t('No entry draft exists with the ID “{id}”', array('id' => $draftId)));
		}
	}






	// -------------------------------------------
	//  Old stuff (to de deleted...)
	// -------------------------------------------

	/**
	 * Creates a new entry
	 *
	 * @param int   $sectionId
	 * @param mixed $parentId
	 * @param mixed $authorId
	 * @param mixed $title
	 * @throws \Exception
	 * @return Entry
	 */
	public function createEntry($sectionId, $parentId = null, $authorId = null, $title = null)
	{
		// Start a transaction
		$transaction = blx()->db->beginTransaction();

		try
		{
			$section = $this->_getSectionRecordById($sectionId);

			// Create the entry
			$entry = new EntryRecord();
			$entry->sectionId = $sectionId;
			$entry->authorId = ($authorId ? $authorId : blx()->accounts->getCurrentUser()->id);
			$entry->parentId = $parentId;
			$entry->save();

			// Create a content row for it
			$content = new EntryContentRecord($section);
			$content->entryId = $entry->id;
			$content->language = blx()->language;
			$content->title = $title;
			$content->save();

			// Commit the transaction and return the entry
			$transaction->commit();
			return $entry;
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}
	}

	/**
	 * Saves an entry's slug
	 *
	 * @param Entry  $entry
	 * @param string $slug
	 * @throws \CDbException
	 */
	public function saveEntrySlug($entry, $slug)
	{
		// Clean it up
		$slug = implode('-', preg_split('/[^a-z0-9]+/', preg_replace('/^[^a-z]+/', '', preg_replace('/[^a-z0-9]+$/', '', $slug))));

		$testSlug = '';

		// Make it unique and save it
		for ($i = 0; true; $i++)
		{
			try
			{
				$testSlug = $slug;
				if ($i != 0)
					$testSlug .= '-'.$i;

				blx()->db->createCommand()->update('entries', array('slug' => $testSlug), array('id'=>$entry->id));

				break;
			}
			catch (\CDbException $e)
			{
				if (isset($e->errorInfo[0]) && $e->errorInfo[0] == 23000)
					continue;
				else
					throw $e;
			}
		}

		// Save it on the entry
		$entry->slug = $testSlug;
		$entry->uri = $this->getEntryUri($entry);
	}

	/**
	 * Saves changes to an entry's content.
	 *
	 * @param mixed  $entry      An Entry record or an entry ID.
	 * @param array  $newContent The new entry content.
	 * @param null   $language
	 * @throws \Exception
	 * @throws Exception
	 * @return bool Whether it was a success.
	 */
	public function saveEntryContent($entry, $newContent, $language = null)
	{
		if (is_numeric($entry))
		{
			$entry = $this->getEntryById($entry);
			if (!$entry)
				throw new Exception(Blocks::t('No entry exists with the ID “{id}”.', array('id' => $entry->id)));
		}

		if (!$language)
			$language = blx()->language;

		$content = $entry->getContent($language);

		foreach ($newContent as $handle => $value)
		{
			$content->setValue($handle, $value);
		}

		// Start a transaction
		$transaction = blx()->db->beginTransaction();
		try
		{
			// Validate the content
			if (!$content->validate())
				return false;

			// Save it
			$content->save(false);

			// Create a new entry version
			$this->createEntryVersion($entry, $newContent, null, $language);

			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}

		return true;
	}

	/**
	 * Returns the full URI for an entry
	 *
	 * @param $entry
	 * @return mixed
	 */
	public function getEntryUri($entry)
	{
		if ($entry->slug)
		{
			$urlFormat = $entry->section->urlFormat;
			$uri = str_replace('{slug}', $entry->slug, $urlFormat);
			return $uri;
		}
		else
			return null;
	}

	/**
	 * @param $sectionId
	 * @return mixed
	 */
	public function getEntriesBySectionId($sectionId)
	{
		$entries = EntryRecord::model()->findAllByAttributes(array(
			'sectionId' => $sectionId,
		));
		return $entries;
	}

	/**
	 * @param $entryId
	 * @return mixed
	 */
	public function doesEntryHaveSubEntries($entryId)
	{
		$exists = EntryRecord::model()->exists(
			'parentId=:parentId',
			array(':parentId' => $entryId)
		);
		return $exists;
	}

	/**
	 * @param $entryId
	 * @return mixed
	 */
	public function getEntryDrafts($entryId)
	{
		$drafts = blx()->db->createCommand()
			->from('entryversions')
			->where(array('entryId' => $entryId, 'draft' => true))
			->order('dateCreated DESC')
			->queryAll();

		return EntryVersionRecord::model()->populateRecords($drafts);
	}

	/**
	 * @param $entryId
	 * @return mixed
	 */
	public function getEntryVersionsByEntryId($entryId)
	{
		$versions = EntryVersionRecord::model()->findAllByAttributes(array(
			'entryId' => $entryId,
		));
		return $versions;
	}

	/**
	 * @param $versionId
	 * @return mixed
	 */
	public function getVersionById($versionId)
	{
		$version = EntryVersionRecord::model()->findById($versionId);
		return $version;
	}

	/**
	 * Creates a new entry version
	 *
	 * @param mixed  $entry    The Entry record, or an entry ID.
	 * @param array  $content  The content to be saved with the version.
	 * @param string $name     The name of the version.
	 * @param string $language The language the content is in.
	 * @param bool   $draft    Whether this is a draft. Defaults to false.
	 * @throws \CDbException|\Exception
	 * @throws Exception
	 * @return EntryVersion The new version record.
	 */
	public function createEntryVersion($entry, $content = null, $name = null, $language = null, $draft = false)
	{
		if (is_numeric($entry))
		{
			$entry = $this->getEntryById($entry);
			if (!$entry)
				throw new Exception(Blocks::t('No entry exists with the ID “{id}”.', array('id' => $entry->id)));
		}

		if (!$language)
			$language = blx()->language;

		$version = new EntryVersionRecord();
		$version->entryId  = $entry->id;
		$version->authorId = blx()->accounts->getCurrentUser()->id;
		$version->language  = $language;
		$version->draft = $draft;
		$version->name = $name;

		if ($content)
			$version->setChanges($content);

		// Start a transaction
		$transaction = blx()->db->beginTransaction();

		try
		{
			if ($version->draft)
				$num = $entry->latestDraft + 1;
			else
				$num = $entry->latestVersion + 1;

			for ($num; true; $num++)
			{
				try
				{
					$version->num = $num;
					$version->save();
					break;
				}
				catch (\CDbException $e)
				{
					if (isset($e->errorInfo[0]) && $e->errorInfo[0] == 23000)
						continue;
					else
						throw $e;
				}
			}

			// Update the entry
			if ($version->draft)
				$entry->latestDraft = $version->num;
			else
				$entry->latestVersion = $version->num;
			$entry->save();

			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}

		return $version;
	}

	/**
	 * Creates a new entry draft
	 *
	 * @param mixed  $entry    The Entry record, or an entry ID.
	 * @param array  $content  The content to be saved with the draft.
	 * @param string $name     The name of the draft.
	 * @param string $language The language the content is in.
	 * @return EntryVersion The new draft record.
	 */
	public function createEntryDraft($entry, $content = null, $name = null, $language = null)
	{
		return $this->createEntryVersion($entry, $content, $name, $language, true);
	}

	/**
	 * @param $draftId
	 * @return mixed
	 */
	public function getDraftById($draftId)
	{
		$draft = EntryVersionRecord::model()->findByAttributes(array(
			'id'    => $draftId,
			'draft' => true
		));

		return $draft;
	}

	/**
	 * @param int $entryId
	 * @param int $draftNum
	 * @return mixed
	 */
	public function getDraftByNum($entryId, $draftNum)
	{
		$draft = EntryVersionRecord::model()->findByAttributes(array(
			'entryId' => $entryId,
			'draft'   => true,
			'num'     => $draftNum
		));

		return $draft;
	}

	/**
	 * @param int $entryId
	 * @return mixed
	 */
	public function getLatestDraft($entryId)
	{
		$draft = blx()->db->createCommand()
			->from('entryversions')
			->where(array('and', 'entryId'=>$entryId, 'draft=1'))
			->order('num DESC')
			->queryRow();

		return EntryVersionRecord::model()->populateRecord($draft);
	}

	/**
	 * Saves draft content
	 *
	 * @param EntryVersion $draft
	 * @param array $newChanges
	 * @return bool
	 */
	public function saveDraftContent($draft, $newChanges)
	{
		$oldChanges = $draft->getChanges();
		$changes = array_merge($oldChanges, $newChanges);
		$draft->setChanges($changes);
		$draft->save();
		return true;
	}

	/**
	 * Publishes an entry draft
	 *
	 * @param EntryVersion $draft
	 * @throws \Exception
	 * @return bool
	 */
	public function publishEntryDraft($draft)
	{
		// Start a transaction
		$transaction = blx()->db->beginTransaction();
		try
		{
			// Save the entry content
			if ($this->saveEntryContent($draft->entry, $draft->getChanges()))
			{
				// Delete the draft
				blx()->content->deleteEntryDraft($draft->id);

				$transaction->commit();
				return true;
			}
			else
				return false;
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}
	}

	/**
	 * Deletes an entry draft
	 *
	 * @param int $draftId
	 */
	public function deleteEntryDraft($draftId)
	{
		blx()->db->createCommand()->delete('entryversions', array(
			'id'    => $draftId,
			'draft' => true
		));
	}

}
