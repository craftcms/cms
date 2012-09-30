<?php
namespace Blocks;

/**
 *
 */
class EntriesService extends BaseApplicationComponent
{
	/**
	 * Populates an entry model.
	 *
	 * @param array|EntryRecord $attributes
	 * @return EntryModel
	 */
	public function populateEntry($attributes)
	{
		if ($attributes instanceof EntryRecord)
		{
			$attributes = $attributes->getAttributes();
		}

		$entry = new EntryModel();

		$entry->id = $attributes['id'];
		$entry->title = $attributes['title'];
		$entry->slug = $attributes['slug'];
		$entry->postDate = $this->_getDate($attributes['postDate']);
		$entry->expiryDate = $this->_getDate($attributes['expiryDate']);
		$entry->enabled = $attributes['enabled'];

		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			$entry->authorId = $attributes['authorId'];
		}

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$entry->sectionId = $attributes['sectionId'];
		}

		// Set the block content
		$entry->blocks = array();

		$contentRecord = $this->_getEntryContentRecord($entry);

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$blocks = blx()->sectionBlocks->getBlocksBySectionId($entry->sectionId);
		}
		else
		{
			$blocks = blx()->entryBlocks->getAllBlocks();
		}

		$blockValues = array();

		foreach ($blocks as $block)
		{
			$name = 'block'.$block->id;
			$handle = $block->handle;

			$blockValues[$name] = $contentRecord->$handle;
		}

		$entry->blocks = $blockValues;

		return $entry;
	}

	/**
	 * Gets a DateTime object from an entry date attribute
	 *
	 * @param mixed $dateAttribute
	 * @param bool|null $required
	 * @return DateTime|null
	 */
	private function _getDate($dateAttribute, $required = false)
	{
		if ($dateAttribute instanceof \DateTime)
		{
			return $dateAttribute;
		}
		else if (is_numeric($dateAttribute))
		{
			$dateTime = new DateTime();
			$dateTime->setTimestamp($dateAttribute);
			return $dateTime;
		}
		else if ($required)
		{
			return new DateTime();
		}
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

		if (Blocks::hasPackage(BlocksPackage::Language))
		{
			if (!$params->language)
			{
				$params->language = blx()->language;
			}

			$whereConditions[] = 't.language = "'.$params->language.'"';
		}

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
		$contentRecord = $this->_getEntryContentRecord($entry);

		// Has the slug changed?
		if ($entryRecord->isNewRecord() || $entry->slug != $entryRecord->slug)
		{
			$this->generateEntrySlug($entry);
		}

		$entryRecord->slug = $entry->slug;
		$titleRecord->title = $entry->title;
		$entryRecord->postDate = $this->_getDate($entry->postDate, true);
		$entryRecord->expiryDate = $this->_getDate($entry->expiryDate);
		$entryRecord->enabled = $entry->enabled;

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$blocks = blx()->sectionBlocks->getBlocksBySectionId($entry->sectionId);
		}
		else
		{
			$blocks = blx()->entryBlocks->getAllBlocks();
		}

		foreach ($blocks as $block)
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

			$titleRecord->save(false);
			$contentRecord->save(false);

			return true;
		}
		else
		{
			$entry->addErrors(array_merge($entryRecord->getErrors(), $titleRecord->getErrors()));
			$entry->addBlockErrors($contentRecord->getErrors());

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
	private function _getEntryRecord(EntryModel $entry)
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

			if (Blocks::hasPackage(BlocksPackage::Users))
			{
				$entryRecord->authorId = $entry->authorId;
			}

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
		if (Blocks::hasPackage(BlocksPackage::Language))
		{
			if (!$entry->language)
			{
				$entry->language = blx()->language;
			}
		}


		if ($entry->id)
		{
			$attributes['entryId'] = $entry->id;

			if (Blocks::hasPackage(BlocksPackage::Language))
			{
				$attributes['language'] = $entry->language;
			}

			$titleRecord = EntryTitleRecord::model()->findByAttributes($attributes);
		}

		if (empty($titleRecord))
		{
			$titleRecord = new EntryTitleRecord();
			$titleRecord->entryId = $entry->id;

			if (Blocks::hasPackage(BlocksPackage::Language))
			{
				$titleRecord->language = $entry->language;
			}
		}

		return $titleRecord;
	}

	/**
	 * Gets an entry's content record or creates a new one.
	 *
	 * @access private
	 * @param EntryModel $entry
	 * @return EntryContentRecord
	 */
	private function _getEntryContentRecord(EntryModel $entry)
	{
		if (Blocks::hasPackage(BlocksPackage::Language))
		{
			if (!$entry->language)
			{
				$entry->language = blx()->language;
			}
		}

		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$section = blx()->sections->getSectionById($entry->sectionId);

			if (!$section)
			{
				throw new Exception(Blocks::t('No section exists with the ID “{id}”', array('id' => $sectionId)));
			}
		}

		if ($entry->id)
		{
			$attributes['entryId'] = $entry->id;

			if (Blocks::hasPackage(BlocksPackage::Language))
			{
				$attributes['language'] = $entry->language;
			}

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

			if (Blocks::hasPackage(BlocksPackage::Language))
			{
				$contentRecord->language = $entry->language;
			}

			$contentRecord->entryId = $entry->id;
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

			if (Blocks::hasPackage(BlocksPackage::PublishPro))
			{
				$where['sectionId'] = $entry->sectionId;
			}

			for ($i = 0; true; $i++)
			{
				$testSlug = $slug;
				if ($i != 0)
				{
					$testSlug .= '-'.$i;
				}

				$where['slug'] = $testSlug;

				$totalEntries = blx()->db->createCommand()
					->select('count(e.id)')
					->from('entries e')
					->where($where)
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
