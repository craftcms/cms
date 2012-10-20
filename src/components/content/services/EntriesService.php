<?php
namespace Blocks;

/**
 *
 */
class EntriesService extends BaseEntityService
{
	// -------------------------------------------
	//  Entry Blocks
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
	protected $contentRecordClass = 'EntryContentRecord';

	/**
	 * The name of the content table column right before where the block columns should be inserted.
	 *
	 * @access protected
	 * @var string
	 */
	protected $placeBlockColumnsAfter = 'entryId';

	// -------------------------------------------
	//  Entries
	// -------------------------------------------

	/**
	 * Populates an entry model.
	 *
	 * @param array|EntryRecord $attributes
	 * @return EntryModel
	 */
	public function populateEntry($attributes)
	{
		return EntryModel::populateModel($attributes);
	}

	/**
	 * Populates a list of EntryTag models
	 *
	 * @param $attributes
	 * @return array
	 */
	public function populateEntryTags($attributes)
	{
		$entryTags = EntryTagModel::populateModels($attributes);
		return $entryTags;

	}

	/**
	 * Mass-populates entry models.
	 *
	 * @param array  $data
	 * @param string $index
	 * @return array
	 */
	public function populateEntries($data, $index = null)
	{
		$entries = array();

		if (!$index)
		{
			$index = 'id';
		}

		foreach ($data as $attributes)
		{
			$entry = $this->populateEntry($attributes);
			$entries[$entry->$index] = $entry;
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
	 * @return EntryModel|null
	 */
	public function getEntry(EntryParams $params = null)
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
	 * @param           $params
	 * @param array     $params
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
			$whereConditions[] = DbHelper::parseParam('e.slug', $params->slug, $whereParams);
		}

		if ($params->uri)
		{
			$whereConditions[] = DbHelper::parseParam('e.uri', $params->uri, $whereParams);
		}

		if ($params->archived)
		{
			$whereConditions[] = 'e.archived = 1';
		}
		else
		{
			$whereConditions[] = 'e.archived = 0';

			if ($params->status && $params->status != '*')
			{
				$statusCondition = $this->_getEntryStatusCondition($params->status);

				if ($statusCondition)
				{
					$whereConditions[] = $statusCondition;
				}
			}
		}

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			if ($params->sectionId && $params->sectionId != '*')
			{
				$whereConditions[] = DbHelper::parseParam('e.sectionId', $params->sectionId, $whereParams);
			}

			if ($params->section)
			{
				$query->join('sections s', 'e.sectionId = s.id');
				$whereConditions[] = DbHelper::parseParam('s.handle', $params->section, $whereParams);
			}
		}

		$whereConditions[] = 't.language = :language';

		if ($params->language)
		{
			$whereParams[':language'] = $params->language;
		}
		else
		{
			$whereParams[':language'] = blx()->language;
		}

		if (count($whereConditions) == 1)
		{
			$query->where($whereConditions[0], $whereParams);
		}
		else
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
	 * @return array
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
					$statusConditions[] = array('and',
						'e.enabled = 1',
						'e.postDate <= '.$currentTime,
						array('or', 'e.expiryDate is null', 'e.expiryDate > '.$currentTime)
					);
					break;
				}
				case 'pending':
				{
					$statusConditions[] = array('and',
						'e.enabled = 1',
						'e.postDate > '.$currentTime
					);
					break;
				}
				case 'expired':
				{
					$statusConditions[] = array('and',
						'e.enabled = 1',
						'e.expiryDate is not null',
						'e.expiryDate <= '.$currentTime
					);
					break;
				}
				case 'disabled':
				{
					$statusConditions[] = 'e.enabled != 1';
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
	 * @param EntryModel $entry
	 * @return bool
	 */
	public function saveEntry(EntryModel $entry)
	{
		$entryRecord = $this->_getEntryRecord($entry);
		$titleRecord = $this->_getEntryTitleRecord($entry);
		$contentRecord = $this->getEntryContentRecord($entry);

		// Has the slug changed?
		if ($entryRecord->isNewRecord() || $entry->slug != $entryRecord->slug)
		{
			$this->generateEntrySlug($entry);
		}

		$entryRecord->slug = $entry->slug;
		$titleRecord->title = $entry->title;
		$entryRecord->postDate = DateTimeHelper::normalizeDate($entry->postDate, true);
		$entryRecord->expiryDate = DateTimeHelper::normalizeDate($entry->expiryDate);
		$entryRecord->enabled = $entry->enabled;

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$blocks = blx()->sections->getBlocksBySectionId($entry->sectionId);
		}
		else
		{
			$blocks = $this->getAllBlocks();
		}

		$blockTypes = array();

		foreach ($blocks as $block)
		{
			$blockType = blx()->blockTypes->populateBlockType($block);
			$blockType->entity = $entry;

			if ($blockType->defineContentAttribute() !== false)
			{
				$handle = $block->handle;
				$contentRecord->$handle = $blockType->getPostData();
			}

			// Keep the block type instance around for calling onAfterEntitySave()
			$blockTypes[] = $blockType;
		}

		$entryValidates = $entryRecord->validate();
		$titleValidates = $titleRecord->validate();
		$contentValidates = $contentRecord->validate();

		$tagsValidate = true;
		$tagErrors = array();

		if (!empty($entry->tags))
		{
			$entryTagRecords = $this->_processTags($entry, $entryRecord);
			$tagErrors = $this->_validateEntryTagRecords($entryTagRecords);
			$tagsValidate = empty($tagErrors);
		}

		if ($entryValidates && $titleValidates && $contentValidates && $tagsValidate)
		{
			if (Blocks::hasPackage(BlocksPackage::PublishPro))
			{
				// We already had to fetch this in getEntryContentRecord()
				// Would be nice if we could eliminate the extra DB query...
				$section = blx()->sections->getSectionById($entry->sectionId);

				if ($section->hasUrls)
				{
					$entryRecord->uri = str_replace('{slug}', $entry->slug, $section->urlFormat);
				}
			}

			$entryRecord->save(false);

			// Save the post date on the model if we just made it up
			if (!$entry->postDate)
			{
				$entry->postDate = $entryRecord->postDate;
			}

			// Now that we have an entry ID, save it on the model & models
			if (!$entry->id)
			{
				$entry->id = $entryRecord->id;
				$titleRecord->entryId = $entryRecord->id;
				$contentRecord->entryId = $entryRecord->id;
			}

			// Save the title and content records
			$titleRecord->save(false);
			$contentRecord->save(false);

			// If we have any tags to process
			if (!empty($entryTagRecords))
			{
				// Create any of the new tag records first.
				if (isset($entryTagRecords['new']))
				{
					foreach ($entryTagRecords['new'] as $newEntryTagRecord)
					{
						$newEntryTagRecord->save(false);
					}
				}

				// Add any tags to the entry.
				if (isset($entryTagRecords['add']))
				{
					foreach ($entryTagRecords['add'] as $addEntryTagRecord)
					{
						$entryTagEntryRecord = new EntryTagEntryRecord();
						$entryTagEntryRecord->tagId = $addEntryTagRecord->id;
						$entryTagEntryRecord->entryId = $entryRecord->id;
						$entryTagEntryRecord->save(false);

						$this->_updateTagCount($addEntryTagRecord);
					}
				}

				// Process any tags that need to be removed from the entry.
				if (isset($entryTagRecords['delete']))
				{
					foreach ($entryTagRecords['delete'] as $deleteEntryTagRecord)
					{
						EntryTagEntryRecord::model()->deleteAllByAttributes(array(
							'tagId'   => $deleteEntryTagRecord->id,
							'entryId' => $entryRecord->id
						));

						$this->_updateTagCount($deleteEntryTagRecord);
					}
				}
			}

			// Give the block types a chance to do any post-processing
			foreach ($blockTypes as $blockType)
			{
				$blockType->onAfterEntitySave();
			}

			return true;
		}
		else
		{
			$entry->addErrors(array_merge($entryRecord->getErrors(), $titleRecord->getErrors(), $contentRecord->getErrors(), $tagErrors));

			return false;
		}
	}

	/**
	 * Keeps the given $entryTagRecord->count column up-to-date with the number of entries using that tag.
	 *
	 * @param EntryTagRecord $entryTagRecord
	 */
	private function _updateTagCount(EntryTagRecord $entryTagRecord)
	{
		$criteria = new \CDbCriteria();
		$criteria->addCondition('tagId =:tagId');
		$criteria->params[':tagId'] = $entryTagRecord->id;
		$tagCount = EntryTagEntryRecord::model()->count($criteria);

		// If the count is zero, let's delete the entryTagRecord.
		if ($tagCount == 0)
		{
			$entryTagRecord->delete();
		}
		else
		{
			$entryTagRecord->count = $tagCount;
			$entryTagRecord->save(false);
		}
	}

	/**
	 * Checks to see if there are any tag validation errors.  If so, returns them.
	 *
	 * @param $entryTagRecords
	 * @return array
	 */
	private function _validateEntryTagRecords($entryTagRecords)
	{
		$errors = array();

		foreach ($entryTagRecords as $entryTagRecordActions)
		{
			foreach ($entryTagRecordActions as $entryTagRecord)
			{
				if (!$entryTagRecord->validate())
				{
					$errors[] = $entryTagRecord->getErrors();
				}
			}
		}

		return $errors;
	}

	/**
	 * Processes any tags on the EntryModel for the given EntryRecord.  Will generate a list of tags that need to be
	 * added, updated or deleted for an entry.
	 *
	 * @param EntryModel  $entry
	 * @param EntryRecord $entryRecord
	 * @return array
	 */
	private function _processTags(EntryModel $entry, EntryRecord $entryRecord)
	{
		$newEntryTags = explode(',', $entry->tags);
		$entryTagRecords = array();

		// Get the entries' current EntryTags
		$currentEntryTagRecords = $this->_getTagsForEntry($entryRecord);

		// Trim any whitespaces from the new tag names.
		foreach ($newEntryTags as $key => $entryTag)
		{
			$newEntryTags[$key] = trim($entryTag);
		}

		// See if any tags have even changed for this entry.
		if (count($currentEntryTagRecords) == count($newEntryTags))
		{
			$identical = true;

			foreach ($currentEntryTagRecords as $currentEntryTagRecord)
			{
				if (!preg_grep("/{$currentEntryTagRecord->name}/i", $newEntryTags))
				{
					// Something is different.
					$identical = false;
					break;
				}
			}

			if ($identical)
			{
				// Identical, so just return the empty array.
				return $entryTagRecords;
			}
		}

		// Process the new entry tags.
		foreach ($newEntryTags as $newEntryTag)
		{
			foreach ($currentEntryTagRecords as $currentEntryTagRecord)
			{
				// The current entry already has this tag assigned to it... skip.
				if (strtolower($currentEntryTagRecord->name) == strtolower($newEntryTag))
				{
					// Try the next $newEntryTag
					continue 2;
				}
			}

			// If we make it here, then we know the tag is new for this entry because it doesn't exist in $currentEntryTagRecords
			// Make sure the tag exists at all, if not create the record.
			if (($entryTagRecord = $this->_getEntryTagRecordByName($newEntryTag)) == null)
			{
				$entryTagRecord = new EntryTagRecord();
				$entryTagRecord->name = $newEntryTag;
				$entryTagRecord->count = 1;

				// Keep track of the new tag records.
				$entryTagRecords['new'][] = $entryTagRecord;
			}

			// Keep track of the tags we'll need to add to the entry.
			$entryTagRecords['add'][] = $entryTagRecord;
		}

		// Now check for deleted tags from the entry.
		foreach ($currentEntryTagRecords as $currentEntryTagRecord)
		{
			foreach ($newEntryTags as $newEntryTag)
			{
				if (strtolower($currentEntryTagRecord->name) == strtolower($newEntryTag))
				{
					// Try the next $currentEntryTagRecord
					continue 2;
				}
			}

			// If we made it here, then we know the tag was removed from the entry.
			$entryTagRecords['delete'][] = $currentEntryTagRecord;
		}

		return $entryTagRecords;
	}

	/**
	 * Given an entry, will return an array of EntryTagRecords associated with the entry.
	 *
	 * @param EntryRecord $entryRecord
	 * @return array
	 */
	private function _getTagsForEntry(EntryRecord $entryRecord)
	{
		$currentEntryTagRecords = array();

		$entryTagEntries = $entryRecord->entryTagEntries;

		foreach ($entryTagEntries as $entryTagEntry)
		{
			if (($currentEntryTagRecord = $this->_getEntryTagRecordById($entryTagEntry->tagId)) !== null)
			{
				$currentEntryTagRecords[] = $currentEntryTagRecord;
			}
		}

		return $currentEntryTagRecords;
	}

	/**
	 * Returns a list of EntryTagModels for a given entry.
	 * @param $entryId
	 * @return array
	 */
	public function getTagsForEntryById($entryId)
	{
		$entryRecord = EntryRecord::model()->findByPk($entryId);
		$entryTagRecords = $this->_getTagsForEntry($entryRecord);

		$entryTagModels = $this->populateEntryTags($entryTagRecords);
		return $entryTagModels;
	}

	/**
	 * Returns an EntryTagRecord with the given tag name.
	 *
	 * @param $tagName
	 * @return EntryTagRecord
	 */
	private function _getEntryTagRecordByName($tagName)
	{
		$entryTagRecord = EntryTagRecord::model()->findByAttributes(
			array('name' => $tagName)
		);

		return $entryTagRecord;
	}

	/**
	 * Returns an EntryTagRecord with the given ID.
	 *
	 * @param $id
	 * @return EntryTagRecord
	 */
	private function _getEntryTagRecordById($id)
	{
		$entryTagRecord = EntryTagRecord::model()->findByPk($id);
		return $entryTagRecord;
	}

	/**
	 * Gets an entry record or creates a new one.
	 *
	 * @access private
	 * @param EntryModel $entry
	 * @throws Exception
	 * @return EntryRecord
	 */
	private function _getEntryRecord(EntryModel $entry)
	{
		if ($entry->id)
		{
			$entryRecord = EntryRecord::model()->with('entryTagEntries')->findById($entry->id);

			if (!$entryRecord)
			{
				throw new Exception(Blocks::t('No entry exists with the ID “{id}”', array('id' => $entry->id)));
			}
		}
		else
		{
			$entryRecord = new EntryRecord();
			$entryRecord->authorId = $entry->authorId;

			if (Blocks::hasPackage(BlocksPackage::PublishPro))
			{
				$entryRecord->sectionId = $entry->sectionId;
			}
		}

		return $entryRecord;
	}

	/**
	 * Gets an entry's title record or creates a new one.
	 *
	 * @access private
	 * @param EntryModel $entry
	 * @return EntryTitleRecord
	 */
	private function _getEntryTitleRecord(EntryModel $entry)
	{
		if (!$entry->language)
		{
			$entry->language = blx()->language;
		}

		if ($entry->id)
		{
			$titleRecord = EntryTitleRecord::model()->findByAttributes(array(
				'entryId'  => $entry->id,
				'language' => $entry->language,
			));
		}

		if (empty($titleRecord))
		{
			$titleRecord = new EntryTitleRecord();
			$titleRecord->entryId = $entry->id;
			$titleRecord->language = $entry->language;
		}

		return $titleRecord;
	}

	/**
	 * Gets an entry's content record or creates a new one.
	 *
	 * @param EntryModel $entry
	 * @throws Exception
	 * @return EntryContentRecord
	 */
	public function getEntryContentRecord(EntryModel $entry)
	{
		if (!$entry->language)
		{
			$entry->language = blx()->language;
		}

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$section = blx()->sections->getSectionById($entry->sectionId);

			if (!$section)
			{
				throw new Exception(Blocks::t('No section exists with the ID “{id}”', array('id' => $entry->getSection()->id)));
			}
		}

		if ($entry->id)
		{
			$attributes = array(
				'entryId' => $entry->id,
				'language' => $entry->language,
			);

			if (Blocks::hasPackage(BlocksPackage::PublishPro))
			{
				$contentRecord = SectionContentRecord::model($section)->findByAttributes($attributes);
			}
			else
			{
				$contentRecord = EntryContentRecord::model()->findByAttributes($attributes);
			}
		}

		if (empty($contentRecord))
		{
			if (Blocks::hasPackage(BlocksPackage::PublishPro))
			{
				$contentRecord = new SectionContentRecord($section);
			}
			else
			{
				$contentRecord = new EntryContentRecord();
			}

			$contentRecord->entryId = $entry->id;
			$contentRecord->language = $entry->language;
		}

		return $contentRecord;
	}

	/**
	 * Generates an entry slug based on its title.
	 *
	 * @param EntryModel $entry
	 */
	public function generateEntrySlug(EntryModel $entry)
	{
		$slug = ($entry->slug ? $entry->slug : $entry->title);

		// Remove HTML tags
		$slug = preg_replace('/<(.*?)>/', '', $slug);

		// Make it lowercase
		$slug = strtolower($slug);

		// Convert extended ASCII characters to basic ASCII
		$slug = StringHelper::asciiString($slug);

		// Slug must start and end with alphanumeric characters
		$slug = preg_replace('/^[^a-z0-9]+/', '', $slug);
		$slug = preg_replace('/[^a-z0-9]+$/', '', $slug);

		// Get the "words"
		$slug = implode('-', array_filter(preg_split('/[^a-z0-9]+/', $slug)));

		if ($slug)
		{
			// Make it unique
			$testSlug = '';

			$where = 'slug = :slug';
			$params = array();

			if (Blocks::hasPackage(BlocksPackage::PublishPro))
			{
				$where .= ' and sectionId = :sectionId';
				$params[':sectionId'] = $entry->sectionId;
			}

			if ($entry->id)
			{
				$where .= ' and id != :entryId';
				$params[':entryId'] = $entry->id;
			}

			for ($i = 0; true; $i++)
			{
				$testSlug = $slug;
				if ($i != 0)
				{
					$testSlug .= '-'.$i;
				}

				$params[':slug'] = $testSlug;

				$totalEntries = blx()->db->createCommand()
					->select('count(e.id)')
					->from('entries e')
					->where($where, $params)
					->queryScalar();

				if ($totalEntries == 0)
				{
					break;
				}
			}

			$entry->slug = $testSlug;
		}
		else
		{
			$entry->slug = '';
		}
	}
}
