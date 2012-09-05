<?php
namespace Blocks;

/**
 *
 */
class ContentService extends \CApplicationComponent
{
	/* BLOCKSPRO ONLY */

	/* Sections */

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
			$whereConditions[] = DbHelper::parseParam('id', $params['id'], $whereParams);

		$whereConditions[] = DbHelper::parseParam('parentId', $params['parentId'], $whereParams);

		if (!empty($params['handle']))
			$whereConditions[] = DbHelper::parseParam('handle', $params['handle'], $whereParams);

		if (!empty($params['hasUrls']))
			$whereConditions[] = DbHelper::parseParam('hasUrls', $params['hasUrls'], $whereParams);

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
	 * Gets a section or creates a new one.
	 *
	 * @access private
	 * @param int $sectionId
	 * @return Section
	 */
	private function _getSection($sectionId = null)
	{
		if ($sectionId)
		{
			$section = $this->getSectionById($sectionId);

			// This is serious business.
			if (!$section)
				$this->_noSectionExists($sectionId);
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
		$section = $this->_getSection($sectionId);

		$isNewSection = $section->getIsNewRecord();
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
	 */
	public function deleteSection($sectionId)
	{
		$section = SectionRecord::model()->with('blocks')->findById($sectionId);
		if (!$section)
			$this->_noSectionExists($sectionId);

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

	/* Entry blocks s */

	/* BLOCKS ONLY */

	/**
	 * Returns all entry blocks.
	 *
	 * @return array
	 */
	public function getEntryBlocks()
	{
		return EntryBlockRecord::model()->ordered()->findAll();
	}

	/* end BLOCKS ONLY */

	/**
	 * Gets an entry block by its ID.
	 *
	 * @param int $id
	 * @return EntryBlock
	 */
	public function getEntryBlockById($id)
	{
		return EntryBlockRecord::model()->findById($id);
	}

	/**
	 * Gets an entry block or creates a new one.
	 *
	 * @access private
	 * @param int $blockId
	 * @return EntryBlock
	 */
	private function _getEntryBlock($blockId = null)
	{
		if ($blockId)
		{
			$block = $this->getEntryBlockById($blockId);

			// This is serious business.
			if (!$block)
				$this->_noEntryBlockExists($blockId);
		}
		else
		{
			$block = new EntryBlockRecord();
		}

		return $block;
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
	 * @param array $settings
	 * @param int $blockId
	 * @return EntryBlock
	 */
	public function saveEntryBlock($settings, $blockId = null)
	{
	/* end BLOCKS ONLY */
	/* BLOCKSPRO ONLY */

	/**
	 * Saves an entry block.
	 *
	 * @param int $sectionId
	 * @param array $settings
	 * @param int $blockId
	 * @return EntryBlock
	 */
	public function saveEntryBlock($sectionId, $settings, $blockId = null)
	{
		$section = $this->_getSection($sectionId);
	/* end BLOCKSPRO ONLY */
		$record = $this->_getEntryBlock($blockId);

		$isNewRecord = $record->getIsNewRecord();
		if (!$isNewRecord)
			$oldHandle = $record->handle;

		/* BLOCKSPRO ONLY */
		$record->sectionId   = $section->id;
		/* end BLOCKSPRO ONLY */
		$record->name         = $settings['name'];
		$record->handle        = $settings['handle'];
		$record->instructions  = (!empty($settings['instructions']) ? $settings['instructions'] : null);
		$record->required      = !empty($settings['required']);
		$record->translatable  = !empty($settings['translatable']);
		$record->class         = $settings['class'];
		$record->blockSettings = (!empty($settings['blockSettings']) ? $settings['blockSettings'] : null);

		if ($isNewRecord)
		{
			$maxSortOrder = blx()->db->createCommand()
				->select('max(sortOrder)')
				->from('entryblocks')
				->queryScalar();

			$record->sortOrder = $maxSortOrder + 1;
		}

		if ($record->validate())
		{
			// Start a transaction
			$transaction = blx()->db->beginTransaction();

			try
			{
				$record->save(false);

				/* BLOCKS ONLY */
				$content = new EntryContentRecord();
				$contentTable = $content->getTableName();
				/* end BLOCKS ONLY */
				/* BLOCKSPRO ONLY */
				$contentTable = EntryContentRecord::getTableNameForSection($section);
				/* end BLOCKSPRO ONLY */

				$block = blx()->blocks->getBlockByClass($record->class);
				$block->setSettings($record->blockSettings);
				$columnType = DbHelper::generateColumnDefinition($block->getColumn());

				if ($isNewRecord)
				{
					blx()->db->createCommand()->addColumn($contentTable, $record->handle, $columnType);
				}
				else
				{
					blx()->db->createCommand()->alterColumn($contentTable, $oldHandle, $columnType, $record->handle);
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

		return $record;
	}

	/**
	 * Deletes an entry block.
	 *
	 * @param int $blockId
	 */
	public function deleteEntryBlock($blockId)
	{
		/* BLOCKS ONLY */
		$block = $this->_getEntryBlock($blockId);
		$content = new EntryContentRecord();
		$contentTable = $content->getTableName();
		/* end BLOCKS ONLY */
		/* BLOCKSPRO ONLY */
		$block = EntryBlockRecord::model()->with('section')->findById($blockId);
		if (!$block)
			$this->_noEntryBlockExists($blockId);

		$contentTable = EntryContentRecord::getTableNameForSection($block->section);
		/* end BLOCKSPRO ONLY */

		$transaction = blx()->db->beginTransaction();
		try
		{
			$block->delete();
			blx()->db->createCommand()->dropColumn($contentTable, $block->handle);
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
	 */
	public function reorderEntryBlocks($blockIds)
	{
		/* BLOCKS ONLY */
		$content = new EntryContentRecord();
		$contentTable = $content->getTableName();
		/* end BLOCKS ONLY */

		$lastColumn = 'title';

		$transaction = blx()->db->beginTransaction();
		try
		{
			foreach ($blockIds as $blockOrder => $blockId)
			{
				// Update the sortOrder in entryblocks
				/* BLOCKS ONLY */
				$record = $this->_getEntryBlock($blockId);
				/* end BLOCKS ONLY */
				/* BLOCKSPRO ONLY */
				$record = EntryBlockRecord::model()->with('section')->findById($blockId);
				if (!$record)
					$this->_noEntryBlockExists($blockId);
				/* end BLOCKSPRO ONLY */
				$record->sortOrder = $blockOrder+1;
				$record->save();

				// Update the column order in the content table
				$block = blx()->blocks->getBlockByClass($record->class);
				$block->setSettings($record->blockSettings);
				/* BLOCKSPRO ONLY */
				$contentTable = EntryContentRecord::getTableNameForSection($record->section);
				/* end BLOCKSPRO ONLY */
				$columnType = DbHelper::generateColumnDefinition($block->getColumn());
				blx()->db->createCommand()->alterColumn($contentTable, $record->handle, $columnType, null, $lastColumn);
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

	/* Entries */

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
			$section = $this->_getSection($sectionId);

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
	 * @param $entryId
	 * @return mixed
	 */
	public function getEntryById($entryId)
	{
		$entry = EntryRecord::model()->findById($entryId);
		return $entry;
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
