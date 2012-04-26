<?php
namespace Blocks;

/**
 *
 */
class ContentService extends Component
{
	/* Sections */

	/**
	 * Get all sections for the current site
	 * @return array
	 */
	public function getSections()
	{
		$sections = Section::model()->findAllByAttributes(array(
			'site_id' => b()->sites->current->id,
			'parent_id' => null
		));

		return $sections;
	}

	/**
	 * Get the sub sections of another section
	 * @param int $parentId The ID of the parent section
	 * @return array
	 */
	public function getSubSections($parentId)
	{
		return Section::model()->findAllByAttributes(array(
			'parent_id' => $parentId
		));
	}

	/**
	 * Returns a Section instance, whether it already exists based on an ID, or is new
	 * @param int $sectionId The Section ID if it exists
	 * @return Section
	 */
	public function getSection($sectionId = null)
	{
		if ($sectionId)
			$section = $this->getSectionById($sectionId);

		if (empty($section))
			$section = new Section;

		return $section;
	}

	/**
	 * Get a specific section by ID
	 * @param int $sectionId The ID of the section to get
	 * @return Section
	 */
	public function getSectionById($sectionId)
	{
		return Section::model()->findById($sectionId);
	}

	/**
	 * @param $siteId
	 * @param $handle
	 * @return mixed
	 */
	public function getSectionBySiteIdHandle($siteId, $handle)
	{
		$section = Section::model()->findByAttributes(array(
			'handle' => $handle,
			'site_id' => $siteId,
		));

		return $section;
	}

	/**
	 * @param $siteId
	 * @param $handles
	 * @return mixed
	 */
	public function getSectionsBySiteIdHandles($siteId, $handles)
	{
		$sections = Section::model()->findAllByAttributes(array(
			'handle' => $handles,
			'site_id' => $siteId,
		));

		return $sections;
	}

	/**
	 * Saves a section.
	 * @param array $sectionSettings
	 * @param int   $sectionId The site ID, if saving an existing site.
	 * @return Section
	 */
	public function saveSection($sectionSettings, $sectionId = null)
	{
		if ($sectionId)
		{
			$section = $this->getSectionById($sectionId);
			if (!$section)
				throw new Exception('No section exists with the ID '.$sectionId);
			$isNewSection = false;
			$oldContentTable = $section->getContentTableName();
			$oldUrlFormat = $section->url_format;
		}
		else
		{
			$section = new Section;
			$isNewSection = true;
		}

		$section->name        = $sectionSettings['name'];
		$section->handle      = $sectionSettings['handle'];
		$section->max_entries = (isset($sectionSettings['max_entries']) && $sectionSettings['max_entries'] > 0 ? (int)$sectionSettings['max_entries'] : null);
		$section->sortable    = (isset($sectionSettings['sortable']) ? (bool)$sectionSettings['sortable'] : false);
		$section->has_urls    = (isset($sectionSettings['has_urls']) ? (bool)$sectionSettings['has_urls'] : false);
		$section->url_format  = (isset($sectionSettings['url_format']) ? $sectionSettings['url_format'] : null);
		$section->template    = (isset($sectionSettings['template']) ? $sectionSettings['template'] : null);
		$section->site_id     = b()->sites->current->id;

		// Start a transaction
		$transaction = b()->db->beginTransaction();
		try
		{
			// Try saving the section
			$sectionSaved = $section->save();

			// Get the section's content table name
			$contentTable = $section->getContentTableName();

			if ($sectionSaved)
			{
				if ($isNewSection)
				{
					// Create the content table
					$section->createContentTable();
				}
				else
				{
					// Rename the content table if the handle changed
					if ($contentTable != $oldContentTable)
						b()->db->createCommand()->renameTable($oldContentTable, $contentTable);

					// Update the entry URIs if the URL format changed
					if ($section->url_format != $oldUrlFormat)
					{
						foreach ($section->entries as $entry)
						{
							$entry->uri = $this->getEntryUri($entry);
							$entry->save();
						}
					}
				}
			}

			// Create the blocks
			$blocks = array();

			if (isset($sectionSettings['blocks']))
			{
				if (isset($sectionSettings['blocks']['order']))
					$blockIds = $sectionSettings['blocks']['order'];
				else
				{
					$blockIds = array_keys($sectionSettings['blocks']);
					if (($deleteIndex = array_search('delete', $blockIds)) !== false)
						array_splice($blockIds, $deleteIndex, 1);
					if (($tempIndex = array_search('BLOCK_ID', $blockIds)) !== false)
						array_splice($blockIds, $tempIndex, 1);
				}

				$lastColumn = 'title';

				foreach ($blockIds as $order => $blockId)
				{
					$blockData = $sectionSettings['blocks'][$blockId];

					$block = b()->blocks->getBlockByClass($blockData['class']);
					$isNewBlock = true;

					if (strncmp($blockId, 'new', 3) != 0)
					{
						$originalBlock = b()->blocks->getBlockById($blockId);
						if ($originalBlock)
						{
							$isNewBlock = false;
							$block->isNewRecord = false;
							$block->id = $blockId;
							$block->setPrimaryKey($blockId);
						}
					}

					$block->name         = $blockData['name'];
					$block->handle       = $blockData['handle'];
					$block->class        = $blockData['class'];
					$block->instructions = (isset($blockData['instructions']) ? $blockData['instructions'] : null);
					$block->required     = (isset($blockData['required']) ? (bool)$blockData['required'] : false);
					$block->sort_order   = ($order+1);

					// Only save it if the section saved
					if ($sectionSaved)
					{
						if ($block->save())
						{
							// Attach it to the section
							try
							{
								b()->db->createCommand()->insert('sectionblocks', array(
									'section_id' => $section->id,
									'block_id'   => $block->id,
								));
							}
							catch (\CDbException $e)
							{
								// Only allow a Duplicate Key exception (the section is already tied to the block)
								if (!isset($e->errorInfo[0]) || $e->errorInfo[0] != 23000)
									throw $e;
							}

							// Save the settings
							if (!isset($blockData['settings']))
								$blockData['settings'] = array();
							$block->settings = $blockData['settings'];

							// Add or modify the block's content column
							$columnType = DatabaseHelper::generateColumnDefinition($block->columnType);

							if ($isNewBlock)
							{
								// Add the new column
								b()->db->createCommand()->addColumnAfter($contentTable, $block->handle, $columnType, $lastColumn);
							}
							else
							{
								// Alter the column
								b()->db->createCommand()->alterColumn($contentTable, $originalBlock->handle, $columnType, $block->handle, $lastColumn);
							}

							// Remember this column name for the next block
							$lastColumn = $block->handle;
						}
					}

					// Keep the "newX" ID around for the templates
					if ($block->isNewRecord)
						$block->id = $blockId;

					$blocks[] = $block;
				}
			}

			// Any deleted blocks?
			if (isset($sectionSettings['blocks']['delete']))
			{
				foreach ($sectionSettings['blocks']['delete'] as $blockId)
				{
					$block = b()->blocks->getBlockById($blockId);
					b()->db->createCommand()->delete('sectionblocks',       array('block_id'=>$blockId));
					b()->db->createCommand()->delete('blocksettings',       array('block_id'=>$blockId));
					b()->db->createCommand()->delete('blocks',              array('id'=>$blockId));
					b()->db->createCommand()->dropColumn($contentTable, $block->handle);
				}
			}

			$section->blocks = $blocks;

			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}

		return $section;
	}

	/* Entries */

	/**
	 * Creates a new entry
	 * @param int $sectionId
	 * @param mixed $parentId
	 * @param mixed $authorId
	 * @param mixed $title
	 * @return Entry
	 */
	public function createEntry($sectionId, $parentId = null, $authorId = null, $title = null)
	{
		// Start a transaction
		$transaction = b()->db->beginTransaction();

		try
		{
			// Create the entry
			$entry = new Entry;
			$entry->section_id = $sectionId;
			$entry->author_id = ($authorId ? $authorId : b()->users->current->id);
			$entry->parent_id = $parentId;
			$entry->save();

			// Create a content row for it
			$table = $entry->section->getContentTableName();
			b()->db->createCommand()->insert($table, array(
				'entry_id' => $entry->id,
				'language' => $entry->section->site->language,
				'title'    => $title
			));

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
	 * @param Entry $entry
	 * @param string $slug
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

				b()->db->createCommand()->update('entries', array('slug' => $testSlug), array('id'=>$entry->id));

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
	 * @param mixed  $entry      An Entry record or an entry ID.
	 * @param array  $newContent The new entry content.
	 * @param string $langugae   The language of the content.
	 * @return bool Whether it was a success.
	 */
	public function saveEntryContent($entry, $newContent, $language = null)
	{
		if (is_numeric($entry))
		{
			$entry = $this->getEntryById($entry);
			if (!$entry)
				throw new Exception('No entry exists with the ID '.$entry->id);
		}

		if (!$language)
			$language = b()->sites->current->language;

		$content = $entry->getContent($language);

		foreach ($newContent as $handle => $value)
		{
			$content->setValue($handle, $value);
		}

		// Start a transaction
		$transaction = b()->db->beginTransaction();
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
	 * @return mixed
	 */
	public function getEntryUri($entry)
	{
		if ($entry->slug)
		{
			$urlFormat = $entry->section->url_format;
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
		$entry = Entry::model()->findById($entryId);
		return $entry;
	}

	/**
	 * @param $sectionId
	 * @return mixed
	 */
	public function getEntriesBySectionId($sectionId)
	{
		$entries = Entry::model()->findAllByAttributes(array(
			'section_id' => $sectionId,
		));
		return $entries;
	}

	/**
	 * @param $siteId
	 * @return array
	 */
	public function getAllEntriesBySiteId($siteId)
	{
		$entries = b()->db->createCommand()
			->select('e.*')
			->from('sections s')
			->join('entries e', 's.id = e.section_id')
			->where(array('s.site_id' => $siteId))
			->queryAll();
		return $entries;
	}

	/**
	 * @param $entryId
	 * @return mixed
	 */
	public function doesEntryHaveSubEntries($entryId)
	{
		$exists = Entry::model()->exists(
			'parent_id=:parentId',
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
		$drafts = b()->db->createCommand()
			->from('entryversions')
			->where(array('entry_id' => $entryId, 'draft' => true))
			->order('date_created DESC')
			->queryAll();

		return EntryVersion::model()->populateRecords($drafts);
	}

	/**
	 * @param $entryId
	 * @return mixed
	 */
	public function getEntryVersionsByEntryId($entryId)
	{
		$versions = EntryVersion::model()->findAllByAttributes(array(
			'entry_id' => $entryId,
		));
		return $versions;
	}

	/**
	 * @param $versionId
	 * @return mixed
	 */
	public function getVersionById($versionId)
	{
		$version = EntryVersion::model()->findById($versionId);
		return $version;
	}

	/**
	 * Creates a new entry version
	 * @param mixed  $entry    The Entry record, or an entry ID.
	 * @param array  $changes  The changes to be saved with the version.
	 * @param string $name     The name of the version.
	 * @param string $language The language the content is in.
	 * @param bool   $draft    Whether this is a draft. Defaults to false.
	 * @return EntryVersion The new version record.
	 */
	public function createEntryVersion($entry, $changes = null, $name = null, $language = null, $draft = false)
	{
		if (is_numeric($entry))
		{
			$entry = $this->getEntryById($entry);
			if (!$entry)
				throw new Exception('No entry exists with the ID '.$entry->id);
		}

		$version = new EntryVersion;
		$version->entry_id  = $entry->id;
		$version->author_id = b()->users->current->id;
		$version->language  = ($language ? $language : b()->sites->current->language);
		$version->draft = $draft;
		$version->name = $name;

		if ($changes)
			$version->setChanges($changes);

		// Start a transaction
		$transaction = b()->db->beginTransaction();

		try
		{
			if ($version->draft)
				$num = $entry->latest_draft + 1;
			else
				$num = $entry->latest_version + 1;

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
				$entry->latest_draft = $version->num;
			else
				$entry->latest_version = $version->num;
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
	 * @param mixed  $entry    The Entry record, or an entry ID.
	 * @param array  $changes  The changes to be saved with the version.
	 * @param string $name     The name of the version.
	 * @param string $language The language the content is in.
	 * @return EntryVersion The new draft record.
	 */
	public function createEntryDraft($entry, $changes = null, $name = null, $language = null)
	{
		return $this->createEntryVersion($entry, $changes, $name, $language, true);
	}

	/**
	 * @param $draftId
	 * @return mixed
	 */
	public function getDraftById($draftId)
	{
		$draft = EntryVersion::model()->findByAttributes(array(
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
		$draft = EntryVersion::model()->findByAttributes(array(
			'entry_id' => $entryId,
			'draft'    => true,
			'num'      => $draftNum
		));
		return $draft;
	}

	/**
	 * @param int $entryId
	 * @return mixed
	 */
	public function getLatestDraft($entryId)
	{
		$draft = b()->db->createCommand()
			->from('entryversions')
			->where(array('and', 'entry_id'=>$entryId, 'draft=1'))
			->order('num DESC')
			->queryRow();
		return EntryVersion::model()->populateRecord($draft);
	}

	/**
	 * Saves draft content
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
	 * @param EntryVersion $draft
	 * @return bool
	 */
	public function publishEntryDraft($draft)
	{
		// Start a transaction
		$transaction = b()->db->beginTransaction();
		try
		{
			// Save the entry content
			if ($this->saveEntryContent($draft->entry, $draft->getChanges()))
			{
				// Delete the draft
				b()->content->deleteEntryDraft($draft->id);

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
	 * @param int $draftId
	 */
	public function deleteEntryDraft($draftId)
	{
		b()->db->createCommand()->delete('entryversions', array(
			'id'    => $draftId,
			'draft' => true
		));
	}

}
