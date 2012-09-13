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
	 * The default parameters for getSections() and getTotalSections().
	 *
	 * @access private
	 * @static
	 */
	private static $_defaultSectionParams = array(
		'parentId' => null,
		'order' => 'name asc',
	);

	/**
	 * Gets sections.
	 *
	 * @param array $params
	 * @return array
	 */
	public function getSections($params = array())
	{
		$params = array_merge(static::$_defaultSectionParams, $params);
		$query = blx()->db->createCommand()
			->from('sections');

		$this->_applySectionConditions($query, $params);

		if (!empty($params['order']))
			$query->order($params['order']);

		if (!empty($params['offset']))
			$query->offset($params['offset']);

		if (!empty($params['limit']))
			$query->limit($params['limit']);

		$result = $query->queryAll();
		return SectionRecord::model()->populateRecords($result);
	}

	/**
	 * Gets the total number of sections.
	 *
	 * @param array $params
	 * @return int
	 */
	public function getTotalSections($params = array())
	{
		$params = array_merge(static::$_defaultUserParams, $params);
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

		if (!empty($params['id']))
		{
			$whereConditions[] = DbHelper::parseParam('id', $params['id'], $whereParams);
		}

		$whereConditions[] = DbHelper::parseParam('parentId', $params['parentId'], $whereParams);

		if (!empty($params['handle']))
		{
			$whereConditions[] = DbHelper::parseParam('handle', $params['handle'], $whereParams);
		}

		if (!empty($params['hasUrls']))
		{
			$whereConditions[] = DbHelper::parseParam('hasUrls', $params['hasUrls'], $whereParams);
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
	 * @param int $id
	 * @return Section
	 */
	public function getSectionById($id)
	{
		return SectionRecord::model()->findById($id);
	}

	/**
	 * Gets a section by its handle.
	 *
	 * @param string $handle
	 * @return Section
	 */
	public function getSectionByHandle($handle)
	{
		return SectionRecord::model()->findByAttributes(array(
			'handle' => $handle
		));
	}

	/**
	 * Gets a section record or creates a new one.
	 *
	 * @access private
	 * @param int $sectionId
	 * @return Section
	 */
	private function _getSectionRecord($sectionId = null)
	{
		if ($sectionId)
		{
			$section = $this->getSectionById($sectionId);

			// This is serious business.
			if (!$section)
			{
				$this->_noSectionExists($sectionId);
			}
		}
		else
		{
			$section = new SectionRecord();
		}

		return $section;
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
	 * @param array $settings
	 * @param int   $sectionId
	 *
	 * @throws \Exception
	 * @return Section
	 */
	public function saveSection($settings, $sectionId = null)
	{
		$section = $this->_getSectionRecord($sectionId);

		$isNewSection = $section->isNewRecord();
		if (!$isNewSection)
		{
			$oldUrlFormat = $section->urlFormat;
			$oldContentTable = EntryContentRecord::getTableNameForSection($section);
		}

		$section->name      = $settings['name'];
		$section->handle    = $settings['handle'];
		$section->hasUrls   = !empty($settings['hasUrls']);
		$section->urlFormat = (!empty($settings['urlFormat']) ? $settings['urlFormat'] : null);
		$section->template  = (!empty($settings['template']) ? $settings['template'] : null);

		// Start a transaction
		$transaction = blx()->db->beginTransaction();
		try
		{
			if ($section->save())
			{
				if ($isNewSection)
				{
					// Create the content table
					$content = new EntryContentRecord($section);
					$content->createTable();
					$content->addForeignKeys();
				}
				else
				{
					// Rename the content table if the handle changed
					$newContentTable = EntryContentRecord::getTableNameForSection($section);
					if ($newContentTable != $oldContentTable)
						blx()->db->createCommand()->renameTable($oldContentTable, $newContentTable);

					// Update the entry URIs if the URL format changed
					if ($section->urlFormat != $oldUrlFormat)
					{
						foreach ($section->entries as $entry)
						{
							$entry->uri = $this->getEntryUri($entry);
							$entry->save();
						}
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

		return $section;
	}

	/**
	 * Deletes a section.
	 *
	 * @param int $sectionId
	 * @throws \Exception
	 * @return void
	*/
	public function deleteSection($sectionId)
	{
		$section = SectionRecord::model()->with('blocks')->findById($sectionId);
		if (!$section)
		{
			$this->_noSectionExists($sectionId);
		}

		$transaction = blx()->db->beginTransaction();
		try
		{
			// Delete the entry blocks
			foreach ($section->blocks as $block)
			{
				$block->delete();
			}

			// Delete the content table
			$content = new EntryContentRecord($section);
			$content->dropForeignKeys();
			$content->dropTable();

			// Delete the section
			$section->delete();

			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}
	}

	/* end BLOCKSPRO ONLY */
	// -------------------------------------------
	//  Entry Blocks
	// -------------------------------------------

	/* BLOCKS ONLY */
	/**
	 * Returns all entry blocks.
	 *
	 * @return array
	 */
	public function getEntryBlocks()
	{
		$records = EntryBlockRecord::model()->ordered()->findAll();
		return blx()->blocks->populateBlocks($records);
	}
	/* end BLOCKS ONLY */
	/* BLOCKSPRO ONLY */
	/**
	 * Returns all entry blocks for a given section
	 *
	 * @param int $sectionId
	 * @return array
	 */
	public function getEntryBlocksBySectionId($sectionId)
	{
		$records = EntryBlockRecord::model()->ordered()->findAllByAttributes(array(
			'sectionId' => $sectionId
		));
		return blx()->blocks->populateBlocks($records);
	}
	/* end BLOCKSPRO ONLY */

	/**
	 * Gets an entry block by its ID.
	 *
	 * @param int $id
	 * @return BaseBlock
	 */
	public function getEntryBlockById($id)
	{
		$record = EntryBlockRecord::model()->findById($id);
		if ($record)
		{
			return blx()->blocks->populateBlock($record);
		}
	}

	/**
	 * Gets an entry block or creates a new one.
	 *
	 * @access private
	 * @param int $blockId
	 * @return EntryBlockRecord
	 */
	private function _getEntryBlockRecord($blockId = null)
	{
		if ($blockId)
		{
			$record = EntryBlockRecord::model()->findById($blockId);

			// This is serious business.
			if (!$record)
			{
				$this->_noEntryBlockExists($blockId);
			}
		}
		else
		{
			$record = new EntryBlockRecord();
		}

		return $record;
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

	/* BLOCKS ONLY */
	/**
	 * Saves an entry block.
	 *
	 * @param array    $settings
	 * @param int|null $blockId
	 * @throws \Exception
	 * @return EntryBlock
	 */
	public function saveEntryBlock($settings, $blockId = null)
	{
	/* end BLOCKS ONLY */
	/* BLOCKSPRO ONLY */
	/**
	 * Saves an entry block.
	 *
	 * @param int      $sectionId
	 * @param array    $settings
	 * @param int|null $blockId
	 * @throws \Exception
	 * @return BaseBlock
	 */
	public function saveEntryBlock($sectionId, $settings, $blockId = null)
	{
		$section = $this->_getSectionRecord($sectionId);
	/* end BLOCKSPRO ONLY */
		$record = $this->_getEntryBlockRecord($blockId);

		$isNewRecord = $record->isNewRecord();
		if (!$isNewRecord)
		{
			$oldHandle = $record->handle;
		}

		/* BLOCKSPRO ONLY */
		$record->sectionId     = $section->id;
		/* end BLOCKSPRO ONLY */
		$record->name          = $settings['name'];
		$record->handle        = $settings['handle'];
		$record->instructions  = (!empty($settings['instructions']) ? $settings['instructions'] : null);
		/* BLOCKSPRO ONLY */
		$record->required      = !empty($settings['required']);
		$record->translatable  = !empty($settings['translatable']);
		/* end BLOCKSPRO ONLY */
		$record->class         = $settings['class'];
		$record->settings      = null;

		$block = blx()->blocks->populateBlock($record);
		$blockSettings = (!empty($settings['blockSettings']) ? $settings['blockSettings'] : null);
		$block->setSettings($blockSettings);

		$recordValidates = $record->validate();
		$settingsValidate = $block->getSettings()->validate();

		if ($recordValidates && $settingsValidate)
		{
			// Set the record settings now that the block has had a chance to tweak them
			$record->settings = $block->getSettings()->getAttributes();

			if ($isNewRecord)
			{
				$maxSortOrder = blx()->db->createCommand()
					->select('max(sortOrder)')
					->from('entryblocks')
					->queryScalar();

				$record->sortOrder = $maxSortOrder + 1;
			}

			$transaction = blx()->db->beginTransaction();
			try
			{
				$record->save(false);

				// Create/alter the content table column
				/* BLOCKS ONLY */
				$content = new EntryContentRecord();
				$contentTable = $content->getTableName();
				/* end BLOCKS ONLY */
				/* BLOCKSPRO ONLY */
				$contentTable = EntryContentRecord::getTableNameForSection($section);
				/* end BLOCKSPRO ONLY */

				$column = ModelHelper::normalizeAttributeConfig($block->defineContentAttribute());

				if ($isNewRecord)
				{
					blx()->db->createCommand()->addColumn($contentTable, $record->handle, $column);
				}
				else
				{
					blx()->db->createCommand()->alterColumn($contentTable, $oldHandle, $column, $record->handle);
				}

				$transaction->commit();
			}
			catch (\Exception $e)
			{
				$transaction->rollBack();
				throw $e;
			}
		}

		return $block;
	}

	/**
	 * Deletes an entry block.
	 *
	 * @param int $blockId
	 * @throws \Exception
	 */
	public function deleteEntryBlock($blockId)
	{
		/* BLOCKS ONLY */
		$record = $this->_getEntryBlockRecord($blockId);
		$content = new EntryContentRecord();
		$contentTable = $content->getTableName();
		/* end BLOCKS ONLY */
		/* BLOCKSPRO ONLY */
		$record = EntryBlockRecord::model()->with('section')->findById($blockId);
		if (!$record)
		{
			$this->_noEntryBlockExists($blockId);
		}

		$contentTable = EntryContentRecord::getTableNameForSection($record->section);
		/* end BLOCKSPRO ONLY */

		$transaction = blx()->db->beginTransaction();
		try
		{
			$record->delete();
			blx()->db->createCommand()->dropColumn($contentTable, $record->handle);
			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}
	}

	/**
	 * Reorders entry blocks.
	 *
	 * @param array $blockIds
	 * @throws \Exception
	 */
	public function reorderEntryBlocks($blockIds)
	{
		/* BLOCKS ONLY */
		$content = new EntryContentRecord();
		$contentTable = $content->getTableName();
		/* end BLOCKS ONLY */

		$lastColumn = 'id';

		$transaction = blx()->db->beginTransaction();
		try
		{
			foreach ($blockIds as $blockOrder => $blockId)
			{
				// Update the sortOrder in entryblocks
				/* BLOCKS ONLY */
				$record = $this->_getEntryBlockRecord($blockId);
				/* end BLOCKS ONLY */
				/* BLOCKSPRO ONLY */
				$record = EntryBlockRecord::model()->with('section')->findById($blockId);
				if (!$record)
					$this->_noEntryBlockExists($blockId);
				/* end BLOCKSPRO ONLY */
				$record->sortOrder = $blockOrder+1;
				$record->save();

				// Update the column order in the content table
				$block = blx()->blocks->populateBlock($record);
				/* BLOCKSPRO ONLY */
				$contentTable = EntryContentRecord::getTableNameForSection($record->section);
				/* end BLOCKSPRO ONLY */
				blx()->db->createCommand()->alterColumn($contentTable, $record->handle, $block->defineContentAttribute(), null, $lastColumn);
				$lastColumn = $record->handle;
			}

			// Commit the transaction
			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}
	}

	// -------------------------------------------
	//  Entries
	// -------------------------------------------

	/**
	 * Populates an EntryModel from an entry row.
	 *
	 * @param array $row
	 * @return EntryModel
	 */
	public function populateEntry($row)
	{
		$entry = new EntryModel();
		$entry->id = $row['id'];
		/* BLOCKSPRO ONLY */
		$entry->authorId = $row['authorId'];
		$entry->sectionId = $row['sectionId'];
		/* end BLOCKSPRO ONLY */
		$entry->title = $row['title'];
		$entry->slug = $row['slug'];

		$entry->blocks = array();
		$contentRecord = $this->_getEntryContentRecord($entry);

		/* BLOCKS ONLY */
		$blocks = $this->getEntryBlocks();
		/* end BLOCKS ONLY */
		/* BLOCKSPRO ONLY */
		$blocks = $this->getEntryBlocksBySectionId($entry->sectionId);
		/* end BLOCKSPRO ONLY */
		foreach ($blocks as $block)
		{
			$handle = $block->record->handle;
			$entry->blocks[$handle] = $contentRecord->$handle;
		}

		return $entry;
	}

	/**
	 * Mass-populates EntryModel's from a list of entry rows.
	 *
	 * @param array $rows
	 * @return array
	 */
	public function populateEntries($rows)
	{
		$entries = array();

		foreach ($rows as $row)
		{
			$entries[] = $this->populateEntry($row);
		}

		return $entries;
	}

	/**
	 * Gets entries.
	 *
	 * @param EntryParams|null $params
	 * @return array
	 */
	public function getEntries(EntryParams $params = null)
	{
		$query = blx()->db->createCommand()
			->select('e.*, t.title')
			->from('entries e')
			->join('entrytitles t', 't.entryId=e.id');

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
	 * Gets the total number of entries.
	 *
	 * @param EntryParams $params
	 * @return int
	 */
	public function getTotalEntries(EntryParams $params = null)
	{
		$query = blx()->db->createCommand()
			->select('count(e.id)')
			->from('entries e')
			->join('entrytitles t', 't.entryId=e.id');

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

		/* BLOCKSPRO ONLY */
		$whereConditions[] = DbHelper::parseParam('t.language', $params->language, $whereParams);
		/* end BLOCKSPRO ONLY */

		if ($whereConditions)
		{
			array_unshift($whereConditions, 'and');
			$query->where($whereConditions, $whereParams);
		}
	}

	/**
	 * Gets an entry by its ID.
	 *
	 * @param int $id
	 * @return EntryModel
	 */
	public function getEntryById($entryId)
	{
		$query = blx()->db->createCommand()
			->select('e.*, t.title')
			->from('entries e')
			->join('entrytitles t', 't.entryId=e.id')
			->where(array('e.id' => $entryId))
			->limit(1);

		$result = $query->queryRow();
		return $this->populateEntry($result);
	}

	/**
	 * Saves an entry.
	 *
	 * @param EntryModel $entry
	 * @return bool
	 */
	public function saveEntry(EntryModel $entry)
	{
		$entryRecord = $this->_getEntryRecord($entry);
		$titleRecord = $this->_getEntryTitleRecord($entry);
		$contentRecord = $this->_getEntryContentRecord($entry);

		/* BLOCKSPRO ONLY */
		if ($entryRecord->isNewRecord())
		{
			$entryRecord->authorId = $entry->authorId;
			$entryRecord->sectionId = $entry->sectionId;
		}

		/* end BLOCKSPRO ONLY */
		$entryRecord->slug = $entry->slug;
		$titleRecord->title = $entry->title;

 		// Populate the blocks' content
		/* BLOCKS ONLY */
		$blocks = $this->getEntryBlocks();
		/* end BLOCKS ONLY */
		/* BLOCKSPRO ONLY */
		$blocks = $this->getEntryBlocksBySectionId($entry->sectionId);
		/* end BLOCKSPRO ONLY */

		foreach ($blocks as $block)
		{
			$handle = $block->record->handle;

			if (isset($entry->blocks[$handle]))
			{
				$contentRecord->$handle = $entry->blocks[$handle];
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

			// Now that we have an entry ID, save it on the models
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
			$entry->errors = array_merge(
				$entryRecord->getErrors(),
				$titleRecord->getErrors(),
				$contentRecord->getErrors()
			);

			return false;
		}
	}

	/**
	 * Gets an entry record or creates a new one.
	 *
	 * @access private
	 * @param EntryModel $entry
	 * @return EntryRecord
	 */
	private function _getEntryRecord($entry)
	{
		if ($entry->id)
		{
			$record = EntryRecord::model()->findById($entry->id);

			// This is serious business.
			if (!$record)
			{
				throw new Exception(Blocks::t('No entry exists with the ID “{id}”', array('id' => $entry->id)));
			}
		}
		else
		{
			$record = new EntryRecord();
		}

		return $record;
	}

	/**
	 * Gets an entry's title record or creates a new one.
	 *
	 * @access private
	 * @param EntryModel $entry
	 * @return EntryTitleRecord
	 */
	private function _getEntryTitleRecord($entry)
	{
		/* BLOCKSPRO ONLY */
		if (!$entry->language)
			$entry->language = blx()->language;

		/* end BLOCKSPRO ONLY */
		if ($entry->id)
		{
			$record = EntryTitleRecord::model()->findByAttributes(array(
				'entryId' => $entry->id,
				/* BLOCKSPRO ONLY */
				'language' => $entry->language,
				/* end BLOCKSPRO ONLY */
			));
		}

		if (empty($record))
		{
			$record = new EntryTitleRecord();
			$record->entryId = $entry->id;
			/* BLOCKSPRO ONLY */
			$record->language = $entry->language;
			/* end BLOCKSPRO ONLY */
		}

		return $record;
	}

	/**
	 * Gets an entry's content record or creates a new one.
	 *
	 * @access private
	 * @param EntryModel $entry
	 * @return EntryContentRecord
	 */
	private function _getEntryContentRecord($entry)
	{
		/* BLOCKSPRO ONLY */
		if (!$entry->language)
		{
			$entry->language = blx()->language;
		}

		// We have to get the content manually, since there's no way to tell EntryContentRecord
		// which section to use from EntryContentRecord::model()->findByAttributes()

		$sectionRecord = $this->_getSectionRecord($entry->sectionId);
		$contentRecord = new EntryContentRecord($sectionRecord);

		if ($entry->id)
		{
			$contentRow = blx()->db->createCommand()
				->from($contentRecord->getTableName())
				->where(array('entryId' => $entry->id, 'language' => $entry->language))
				->queryRow();

			if ($contentRow)
			{
				$contentRecord->populateRecord($contentRow);
			}
		}

		if (empty($contentRow))
		{
			$contentRecord->entryId = $entry->id;
			$contentRecord->language = $entry->language;
		}

		/* end BLOCKSPRO ONLY */
		/* BLOCKS ONLY */
		if ($entry->id)
		{
			$contentRecord = EntryContentRecord::model()->findByAttributes(array(
				'entryId' => $entry->id,
			));
		}

		if (empty($contentRecord))
		{
			$contentRecord = new EntryContentRecord();
			$contentRecord->entryId = $entry->id;
		}

		/* end BLOCKS ONLY */
		return $contentRecord;
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
			$section = $this->_getSectionRecord($sectionId);

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
