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
	 * @return \Blocks\Section
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
							b()->db->createCommand()->addColumn($table, $block->handle, $columnType);
						}
						else
						{
							// Rename the column if the block has a new handle
							if ($block->handle != $originalBlock->handle)
								b()->db->createCommand()->renameColumn($table, $originalBlock->handle, $block->handle);

							// Update the column's type
							b()->db->createCommand()->alterColumn($table, $block->handle, $columnType);
						}
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
					b()->db->createCommand()->delete('sectionblocks', 'block_id=:id', array(':id' => $blockId));
					b()->db->createCommand()->delete('blocksettings', 'block_id=:id', array(':id' => $blockId));
					b()->db->createCommand()->delete('blocks',        'id=:id',       array(':id' => $blockId));
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
	 * @param $sectionId
	 * @param $authorId
	 * @param null $parentId
	 * @return Entry
	 */
	public function createEntry($sectionId, $authorId, $parentId = null, $title = null)
	{
		$entry = new Entry;
		$entry->section_id = $sectionId;
		$entry->author_id = $authorId;
		$entry->parent_id = $parentId;
		$entry->save();
		return $entry;
	}

	/**
	 * Saves an entry's slug
	 */
	public function saveEntrySlug($entry, $slug)
	{
		// Clean it up
		$slug = implode('-', preg_split('/[^a-z0-9]+/', preg_replace('/^[^a-z]+/', '', preg_replace('/[^a-z0-9]+$/', '', $slug))));

		// Make it unique and save it
		for ($i = 0; true; $i++)
		{
			try
			{
				$testSlug = $slug;
				if ($i != 0)
					$testSlug .= '-'.$i;

				b()->db->createCommand()->update('entries', array('slug' => $testSlug), 'id=:id', array(':id' => $entry->id));

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
		{
			$entry = new Entry;
			$entry->title = new EntryTitle;
		}

		return $entry;
	}

	/**
	 * @param $entryId
	 * @return mixed
	 */
	public function getEntryById($entryId)
	{
		$entry = Entry::model()->with('title')->findById($entryId);
		return $entry;
	}

	/**
	 * @param $sectionId
	 * @return mixed
	 */
	public function getEntriesBySectionId($sectionId)
	{
		$entries = Entry::model()->with('title')->findAllByAttributes(array(
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
			->where('s.site_id=:siteId', array(':siteId' => $siteId))
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
		$version = EntryVersions::model()->findByAttributes(array(
			'id' => $versionId,
		));
		return $version;
	}

	/**
	 * Creates a new draft
	 * @param int $entryId
	 * @param string $name
	 * @return Draft
	 */
	public function createDraft($entryId, $name = 'Untitled')
	{
		$draft = new Draft;
		$draft->entry_id = $entryId;
		$draft->author_id = b()->users->current->id;
		$draft->language = b()->sites->currentSite->language;
		$draft->name = $name;
		$draft->save();
		return $draft;
	}
	
	/**
	 * @param $entryId
	 * @return mixed
	 */
	public function getDraftById($draftId)
	{
		$draft = Draft::model()->findById($draftId);
		return $draft;
	}

	/**
	 * Returns the latest draft for an entry, if one exists
	 * @param int $entryId
	 * @return mixed The latest draft or null
	 */
	public function getLatestDraft($entryId)
	{
		$draft = Draft::model()->find(array(
			'condition' => 'entry_id = :entryId',
			'params' => array(':entryId' => $entryId),
			'order' => 'date_created DESC'
		));
		return $draft;
	}
}
