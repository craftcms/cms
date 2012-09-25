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
	public function populateSection($attributes)
	{
		if ($attributes instanceof SectionRecord)
		{
			$attributes = $attributes->getAttributes();
		}

		$section = new SectionPackage();

		$section->id = $attributes['id'];
		$section->name = $attributes['name'];
		$section->handle = $attributes['handle'];
		$section->hasUrls = $attributes['hasUrls'];
		$section->urlFormat = $attributes['urlFormat'];
		$section->template = $attributes['template'];

		return $section;
	}

	/**
	 * Mass-populates section packages.
	 *
	 * @param array  $data
	 * @param string $index
	 * @return array
	 */
	public function populateSections($data, $index = 'id')
	{
		$sectionPackages = array();

		foreach ($data as $attributes)
		{
			$section = $this->populateSection($attributes);
			$sectionPackages[$section->$index] = $section;
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
		return $this->populateSections($result);
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
			return $this->populateSection($result);
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
			return $this->populateSection($sectionRecord);
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
			return $this->populateSection($sectionRecord);
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
	 * @param SectionPackage $section
	 * @throws \Exception
	 * @return bool
	 */
	public function saveSection(SectionPackage $section)
	{
		$sectionRecord = $this->_getSectionRecordById($section->id);

		$isNewSection = $sectionRecord->isNewRecord();
		if (!$isNewSection)
		{
			$oldUrlFormat = $sectionRecord->urlFormat;
			$oldSection = $this->populateSection($sectionRecord);
			$oldContentTable = EntryContentRecord::getTableNameForSection($oldSection);
		}

		$sectionRecord->name      = $section->name;
		$sectionRecord->handle    = $section->handle;
		$sectionRecord->hasUrls   = $section->hasUrls;
		$sectionRecord->urlFormat = $section->urlFormat;
		$sectionRecord->template  = $section->template;

		if ($sectionRecord->validate())
		{
			$transaction = blx()->db->beginTransaction();
			try
			{
				$sectionRecord->save(false);

				// Now that we have a section ID, save it on the package
				if (!$section->id)
				{
					$section->id = $sectionRecord->id;
				}

				if ($isNewSection)
				{
					// Create the content table
					$contentRecord = new EntryContentRecord($section, 'install');
					$contentRecord->createTable();
					$contentRecord->addForeignKeys();
				}
				else
				{
					// Rename the content table if the handle changed
					$newContentTable = EntryContentRecord::getTableNameForSection($section);
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
			$section = $this->populateSection($sectionRecord);
			$contentRecord = new EntryContentRecord($section);
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
	//  Entries
	// -------------------------------------------

	/**
	 * Populates an entry package.
	 *
	 * @param array|EntryRecord $attributes
	 * @return EntryPackage
	 */
	public function populateEntry($attributes)
	{
		if ($attributes instanceof EntryRecord)
		{
			$attributes = $attributes->getAttributes();
		}

		$entry = new EntryPackage();

		$entry->id = $attributes['id'];
		/* BLOCKSPRO ONLY */
		$entry->authorId = $attributes['authorId'];
		$entry->sectionId = $attributes['sectionId'];
		/* end BLOCKSPRO ONLY */
		$entry->title = $attributes['title'];
		$entry->slug = $attributes['slug'];

		if (is_numeric($attributes['postDate']))
		{
			$dateTime = new DateTime();
			$dateTime->setTimestamp($attributes['postDate']);
			$entry->postDate = $dateTime;
		}
		else
		{
			$entry->postDate = $attributes['postDate'];
		}
		/* BLOCKSPRO ONLY */

		if (is_numeric($attributes['expiryDate']))
		{
			$dateTime = new DateTime();
			$dateTime->setTimestamp($attributes['expiryDate']);
			$entry->expiryDate = $dateTime;
		}
		else
		{
			$entry->expiryDate = $attributes['expiryDate'];
		}
		/* end BLOCKSPRO ONLY */

		$entry->blocks = array();
		$contentRecord = $this->_getEntryContentRecord($entry);

		/* BLOCKS ONLY */
		$blockPackages = blx()->entryBlocks->getAllBlocks();
		/* end BLOCKS ONLY */
		/* BLOCKSPRO ONLY */
		$blockPackages = blx()->entryBlocks->getBlocksBySectionId($entry->sectionId);
		/* end BLOCKSPRO ONLY */
		foreach ($blockPackages as $block)
		{
			$name = 'block'.$block->id;
			$handle = $block->handle;

			$entry->blocks[$name] = $contentRecord->$handle;
		}

		return $entry;
	}

	/**
	 * Mass-populates entry packages.
	 *
	 * @param array  $data
	 * @param string $index
	 * @return array
	 */
	public function populateEntries($data, $index = 'id')
	{
		$entryPackages = array();

		foreach ($data as $attributes)
		{
			$entry = $this->populateEntry($attributes);
			$entryPackages[$entry->$index] = $entry;
		}

		return $entryPackages;
	}

	/**
	 * Populates an entry with draft data.
	 *
	 * @param EntryPackage $entry
	 */
	public function populateEntryDraftData(EntryPackage $entry)
	{
		$draftRecord = EntryDraftRecord::model()->findByAttributes(array(
			'entryId'  => $entry->id,
			/* BLOCKSPRO ONLY */
			'language' => ($entry->language ? $entry->language : blx()->language),
			/* end BLOCKSPRO ONLY */
		));

		if ($draftRecord)
		{
			$entry->draftId    = $draftRecord->id;
			$entry->title      = (isset($draftRecord->data['title']) ? $draftRecord->data['title'] : null);
			$entry->slug       = (isset($draftRecord->data['slug']) ? $draftRecord->data['slug'] : null);
			$entry->postDate   = (isset($draftRecord->data['postDate']) ? DateTime::createFromFormat(DateTime::W3C_DATE, $draftRecord->data['postDate']) : null);
			/* BLOCKSPRO ONLY */
			$entry->expiryDate = (isset($draftRecord->data['expiryDate']) ? DateTime::createFromFormat(DateTime::W3C_DATE, $draftRecord->data['expiryDate']) : null);
			/* end BLOCKSPRO ONLY */
			$entry->blocks     = (isset($draftRecord->data['blocks']) ? $draftRecord->data['blocks'] : null);
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
		return $this->populateEntries($result);
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
			return $this->populateEntry($result);
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
	 * @param EntryPackage $entry
	 * @return bool
	 */
	public function saveEntry(EntryPackage $entry)
	{
		$entryRecord = $this->_getEntryRecord($entry);
		$titleRecord = $this->_getEntryTitleRecord($entry);
		$contentRecord = $this->_getEntryContentRecord($entry);

		$entryRecord->slug = $entry->slug;
		$titleRecord->title = $entry->title;

		// Save the post date if it wasn't set already
		if ($entry->postDate)
		{
			$entryRecord->postDate = $entry->postDate;
		}
		else
		{
			$entryRecord->postDate = new DateTime();
		}

		/* BLOCKSPRO ONLY */
		$entryRecord->expiryDate = $entry->expiryDate;
		/* end BLOCKSPRO ONLY */

 		// Populate the blocks' content
		/* BLOCKS ONLY */
		$blockPackages = blx()->entryBlocks->getAllBlocks();
		/* end BLOCKS ONLY */
		/* BLOCKSPRO ONLY */
		$blockPackages = blx()->entryBlocks->getBlocksBySectionId($entry->sectionId);
		/* end BLOCKSPRO ONLY */

		foreach ($blockPackages as $block)
		{
			$handle = $block->handle;
			$name = 'block'.$block->id;

			if (isset($entry->blocks[$name]))
			{
				$contentRecord->$handle = $entry->blocks[$name];
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
			if (!$entry->postDate)
			{
				$entry->postDate = $entryRecord->postDate;
			}

			// Now that we have an entry ID, save it on the package & models
			if (!$entry->id)
			{
				$entry->id = $entryRecord->id;
				$titleRecord->entryId = $entryRecord->id;
				$contentRecord->entryId = $entryRecord->id;
			}

			$titleRecord->save(false);
			$contentRecord->save(false);

			return true;
		}
		else
		{
			$entry->errors = array_merge($entryRecord->getErrors(), $titleRecord->getErrors());
			$entry->blockErrors = $contentRecord->getErrors();

			return false;
		}
	}

	/**
	 * Saves an entry draft.
	 *
	 * @param EntryDraftPackage $draft
	 * @return bool
	 */
	public function saveEntryDraft(EntryDraftPackage $draft)
	{
		$entryRecord = $this->_getEntryRecord($draft);

		// If it's a new entry, make sure it saves first
		if ($entryRecord->isNewRecord())
		{
			$titleRecord = $this->_getEntryTitleRecord($draft);

			$entryRecord->slug = $draft->slug;
			$titleRecord->title = $draft->title;

			$entryValidates = $entryRecord->validate();
			$titleValidates = $titleRecord->validate();

			if ($entryValidates && $titleValidates)
			{
				$entryRecord->save(false);

				$draft->id = $entryRecord->id;
				$titleRecord->entryId = $entryRecord->id;

				$titleRecord->save(false);
			}
			else
			{
				$draft->errors = array_merge($entryRecord->getErrors(), $titleRecord->getErrors());

				return false;
			}
		}

		$draftRecord = $this->_getEntryDraftRecord($draft);

		$draftRecord->data = array(
			'title'      => $draft->title,
			'slug'       => $draft->slug,
			'postDate'   => (!empty($draft->postDate) ? $draft->postDate->getTimestamp() : null),
			/* BLOCKSPRO ONLY */
			'expiryDate' => (!empty($draft->expiryDate) ? $draft->expiryDate->getTimestamp() : null),
			/* end BLOCKSPRO ONLY */
			'blocks'     => $draft->blocks
		);

		$draftRecord->save(false);

		if (!$draft->draftId)
		{
			$draft->draftId = $draftRecord->id;
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
	 * @param EntryPackage $entry
	 * @return EntryRecord
	 */
	private function _getEntryRecord(EntryPackage $entry)
	{
		if ($entry->id)
		{
			$entryRecord = EntryRecord::model()->findById($entry->id);

			if (!$entryRecord)
			{
				throw new Exception(Blocks::t('No entry exists with the ID “{id}”', array('id' => $entry->id)));
			}
		}
		else
		{
			$entryRecord = new EntryRecord();
			/* BLOCKSPRO ONLY */

			$entryRecord->authorId = $entry->authorId;
			$entryRecord->sectionId = $entry->sectionId;
			/* end BLOCKSPRO ONLY */
		}

		return $entryRecord;
	}

	/**
	 * Gets an entry's title record or creates a new one.
	 *
	 * @access private
	 * @param EntryPackage $entry
	 * @return EntryTitleRecord
	 */
	private function _getEntryTitleRecord(EntryPackage $entry)
	{
		/* BLOCKSPRO ONLY */
		if (!$entry->language)
			$entry->language = blx()->language;

		/* end BLOCKSPRO ONLY */
		if ($entry->id)
		{
			$titleRecord = EntryTitleRecord::model()->findByAttributes(array(
				'entryId' => $entry->id,
				/* BLOCKSPRO ONLY */
				'language' => $entry->language,
				/* end BLOCKSPRO ONLY */
			));
		}

		if (empty($titleRecord))
		{
			$titleRecord = new EntryTitleRecord();
			$titleRecord->entryId = $entry->id;
			/* BLOCKSPRO ONLY */
			$titleRecord->language = $entry->language;
			/* end BLOCKSPRO ONLY */
		}

		return $titleRecord;
	}

	/**
	 * Gets an entry's content record or creates a new one.
	 *
	 * @access private
	 * @param EntryPackage $entry
	 * @return EntryContentRecord
	 */
	private function _getEntryContentRecord(EntryPackage $entry)
	{
		/* BLOCKSPRO ONLY */
		if (!$entry->language)
		{
			$entry->language = blx()->language;
		}

		$section = $this->getSectionById($entry->sectionId);
		if (!$section)
		{
			$this->_noSectionExists($entry->sectionId);
		}
		/* end BLOCKSPRO ONLY */

		if ($entry->id)
		{
			/* BLOCKS ONLY */
			$contentRecord = EntryContentRecord::model()->findByAttributes(array(
				'entryId' => $entry->id
			));
			/* end BLOCKS ONLY */
			/* BLOCKSPRO ONLY */
			$contentRecord = EntryContentRecord::model($section)->findByAttributes(array(
				'entryId'  => $entry->id,
				'language' => $entry->language
			));
			/* end BLOCKSPRO ONLY */
		}

		if (empty($contentRecord))
		{
			/* BLOCKS ONLY */
			$contentRecord = new EntryContentRecord();
			/* end BLOCKS ONLY */
			/* BLOCKSPRO ONLY */
			$contentRecord = new EntryContentRecord($section);
			$contentRecord->language = $entry->language;
			/* end BLOCKSPRO ONLY */
			$contentRecord->entryId = $entry->id;
		}

		return $contentRecord;
	}

	/**
	 * Gets an entry draft record or creates a new one.
	 *
	 * @access private
	 * @param EntryDraftPackage $draft
	 * @return EntryDraftRecord
	 */
	private function _getEntryDraftRecord(EntryDraftPackage $draft)
	{
		if ($draft->draftId)
		{
			$draftRecord = $this->_getEntryDraftRecordById($draft->draftId);
		}
		else
		{
			$draftRecord = new EntryDraftRecord();
			$draftRecord->entryId = $draft->id;
			/* BLOCKSPRO ONLY */
			$draftRecord->authorId = $draft->authorId;
			$draftRecord->language = ($draft->language ? $draft->language : blx()->language);
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
