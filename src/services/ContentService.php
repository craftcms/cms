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
			'site_id' => b()->sites->currentSite->id,
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
	 * Saves a section
	 *
	 * @param            $sectionSettings
	 * @param array|null $blocksData
	 * @param null       $sectionId
	 * @return Section
	 */
	public function saveSection($sectionSettings, $blocksData = null, $sectionId = null)
	{
		$section = $this->getSection($sectionId);
		$isNewSection = $section->isNewRecord;

		$section->name = $sectionSettings['name'];
		$section->handle = $sectionSettings['handle'];
		$section->max_entries = $sectionSettings['max_entries'];
		$section->sortable = $sectionSettings['sortable'];
		$section->has_urls = $sectionSettings['has_urls'];
		$section->url_format = $sectionSettings['url_format'];
		$section->template = $sectionSettings['template'];
		$section->site_id = b()->sites->currentSite->id;

		// Try saving the section
		$sectionSaved = $section->save();

		// Get the section's content table name
		$table = $section->getContentTableName();

		// Create the blocks
		$blocks = array();

		if (isset($blocksData['order']))
		{
			$lastColumn = 'title';

			foreach ($blocksData['order'] as $order => $blockId)
			{
				$blockData = $blocksData[$blockId];

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

				$block->name = $blockData['name'];
				$block->handle = $blockData['handle'];
				$block->class = $blockData['class'];
				$block->instructions = $blockData['instructions'];
				$block->required = (isset($blockData['required']) && $blockData['required'] == 'y');
				$block->sort_order = ($order+1);

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
							b()->db->createCommand()->addColumnAfter($table, $block->handle, $columnType, $lastColumn);
						}
						else
						{
							// Alter the column
							b()->db->createCommand()->alterColumn($table, $originalBlock->handle, $columnType, $block->handle, $lastColumn);
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
		if (isset($blocksData['delete']))
		{
			foreach ($blocksData['delete'] as $blockId)
			{
				// Start a transaction
				$transaction = b()->db->beginTransaction();
				try
				{
					$block = b()->blocks->getBlockById($blockId);
					b()->db->createCommand()->delete('sectionblocks',       array('block_id'=>$blockId));
					b()->db->createCommand()->delete('blocksettings',       array('block_id'=>$blockId));
					b()->db->createCommand()->delete('entryversioncontent', array('block_id'=>$blockId));
					b()->db->createCommand()->delete('blocks',              array('id'=>$blockId));
					b()->db->createCommand()->dropColumn($table, $block->handle);
					$transaction->commit();
				}
				catch (\Exception $e)
				{
					$transaction->rollBack();
					throw $e;
				}
			}
		}

		$section->blocks = $blocks;

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
				'title'    => ($title ? $title : 'Untitled')
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
	}

	/**
	 * Returns an Entry instance, whether it already exists based on an ID, or is new
	 * @param int $entryId The Entry ID if it exists
	 * @return Section
	 */
	public function getEntry($entryId = null)
	{
		if ($entryId)
			$entry = $this->getEntryById($entryId);

		if (empty($entry))
			$entry = new Entry;

		return $entry;
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

	public function getEntryDrafts($entryId)
	{
		$drafts = b()->db->createCommand()
			->from('entryversions')
			->where(array('and', 'entry_id'=>$entryId, 'draft=1'))
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
		$versions = EntryVersions::model()->findAllByAttributes(array(
			'entry_id' => $entryId,
		));
		return $versions;
	}

	/**
	 * @param $versionId
	 * @return mixed
	 */
	public function getEntryVersionById($versionId)
	{
		$version = EntryVersions::model()->findById($versionId);
		return $version;
	}

	/**
	 * Creates a new draft
	 * @param int $entryId
	 * @param string $language
	 * @param mixed $name
	 * @return EntryVersion The new draft record
	 */
	public function createDraft($entryId, $language = null, $name = null)
	{
		$entry = $this->getEntryById($entryId);

		if (!$entry)
			throw new Exception('No entry exists with the id '.$entryId);

		$draft = new EntryVersion;
		$draft->entry_id = $entryId;
		$draft->author_id = b()->users->current->id;
		$draft->language = ($language ? $language : b()->sites->currentSite->language);
		$draft->draft = true;
		$draft->name = ($name ? $name : 'Untitled');

		// Start a transaction
		$transaction = b()->db->beginTransaction();
		try
		{
			for ($num = $entry->latest_draft+1; true; $num++)
			{
				try
				{
					$draft->num = $num;
					$draft->save();
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
			$entry->latest_draft = $draft->num;
			$entry->save();

			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}

		return $draft;
	}

	/**
	 * @param $draftId
	 * @return mixed
	 */
	public function getDraftById($draftId)
	{
		$draft = EntryVersion::model()->findById($draftId);
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
	 * @param int $draftId
	 * @param array $content
	 */
	public function saveDraftContent($draftId, $content)
	{
		$blocks = b()->db->createCommand()
			->select('id, handle')
			->from('blocks')
			->where(array('in', 'handle', array_keys($content)))
			->queryAll();

		if ($blocks)
		{
			$blockIds = array();
			foreach ($blocks as $block)
			{
				$blockIds[] = $block['id'];
			}

			// Start a transaction
			$transaction = b()->db->beginTransaction();
			try
			{
				$insertVals = array();

				// Check for previous data on this draft
				$draftContent = b()->db->createCommand()
					->select('id, block_id')
					->from('entryversioncontent')
					->where(array('and', 'version_id'=>$draftId, array('in', 'block_id', $blockIds)))
					->queryAll();

				$draftContentIds = array();
				foreach ($draftContent as $row)
				{
					$draftContentIds[$row['block_id']] = $row['id'];
				}

				// Update existing rows and get ready to insert new ones
				foreach ($blocks as $block)
				{
					$val = $content[$block['handle']];

					if (isset($draftContentIds[$block['id']]))
						b()->db->createCommand()->update('entryversioncontent', array('value' => $val), array('id'=>$draftContentIds[$block['id']]));
					else
						$insertVals[] = array($draftId, $block['id'], $val);
				}

				// Insert new rows
				if ($insertVals)
				{
					$columns = array('version_id', 'block_id', 'value');
					b()->db->createCommand()->insertAll('entryversioncontent', $columns, $insertVals);
				}

				$transaction->commit();
			}
			catch (\Exception $e)
			{
				$transaction->rollBack();
				throw $e;
			}
		}
	}

	/**
	 * Publishes a draft
	 * @param int $draftId
	 * @return
	 */
	public function publishDraft($draftId)
	{
		$draft = EntryVersion::model()->with('entry.section', 'content.block')->findById($draftId);

		if (!$draft)
			throw new Exception('No draft exists with the id '.$draftId);

		$entry = $draft->entry;

		// Start a transaction
		$transaction = b()->db->beginTransaction();
		try
		{
			// Figure out what's changed
			$content = array();

			$draftContent = b()->db->createCommand()
				->select('c.title, b.handle, c.value')
				->from('entryversioncontent c')
				->leftJoin('blocks b', 'b.id=c.block_id')
				->where(array('c.version_id'=>$draftId))
				->queryAll();

			foreach ($draftContent as $row)
			{
				if ($row['title'])
					$content['title'] = $row['value'];
				else if ($row['handle'])
					$content[$row['handle']] = $row['value'];
			}

			// Update the entry content
			if ($content)
			{
				$table = $entry->section->getContentTableName();

				// Does a content row already exist for this entry & language?
				$contentId = b()->db->createCommand()
					->select('id')
					->from($table)
					->where(array('and', 'entry_id'=>$entry->id, 'language'=>$draft->language))
					->queryRow();

				if ($contentId)
					b()->db->createCommand()->update($table, $content, array('id'=>$contentId['id']));
				else
				{
					$content['entry_id'] = $entry->id;
					$content['langugae'] = $draft->language;

					if (empty($content['title']))
						$content['title'] = 'Untitled';

					b()->db->createCommand()->insert($table, $content);
				}
			}

			// Transform the draft into a version, with the next highest num
			$draft->draft = false;
			for ($num = $entry->latest_version+1; true; $num++)
			{
				try
				{
					$draft->num = $num;
					$draft->save();
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
			$entry->latest_version = $draft->num;
			if (!$entry->publish_date)
				$entry->publish_date = DateTimeHelper::currentTime();
			$entry->save();

			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}

		return $draft;
	}
}
