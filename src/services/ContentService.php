<?php
namespace Blocks;

/**
 *
 */
class ContentService extends \CApplicationComponent
{
	/* Sections */

	/**
	 * The default parameters for getSections() and getTotalSections().
	 *
	 * @access private
	 * @static
	 */
	private static $_defaultSectionParams = array(
		'parent_id' => null,
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
		return Section::model()->populateRecords($result);
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
		$whereConditions = array('and');
		$whereParams = array();

		if (!empty($params['id']))
			$whereConditions[] = DatabaseHelper::parseParam('id', $params['id'], $whereParams);

		$whereConditions[] = DatabaseHelper::parseParam('parent_id', $params['parent_id'], $whereParams);

		if (!empty($params['handle']))
			$whereConditions[] = DatabaseHelper::parseParam('handle', $params['handle'], $whereParams);

		if (!empty($params['has_urls']))
			$whereConditions[] = DatabaseHelper::parseParam('has_urls', $params['has_urls'], $whereParams);

		$query->where($whereConditions, $whereParams);
	}

	/**
	 * Gets a section by its ID.
	 *
	 * @param int $id
	 * @return Section
	 */
	public function getSectionById($id)
	{
		return Section::model()->findById($id);
	}

	/**
	 * Gets a section by its handle.
	 *
	 * @param string $handle
	 * @return Section
	 */
	public function getSectionByHandle($handle)
	{
		return Section::model()->findByAttributes(array(
			'handle' => $handle
		));
	}

	/**
	 * Saves a section.
	 *
	 * @param array $sectionSettings
	 * @param int $sectionId
	 * @return Section
	 */
	public function saveSection($sectionSettings, $sectionId = null)
	{
		if ($sectionId)
		{
			$section = $this->getSectionById($sectionId);

			if (!$section)
				throw new Exception(Blocks::t('No section exists with the ID “{sectionId}”', array('sectionId' => $sectionId)));

			$isNewSection = false;
			$oldContentTable = $section->getContentTableName();
			$oldUrlFormat = $section->url_format;
		}
		else
		{
			$section = new Section();
			$isNewSection = true;
		}

		$section->name        = $sectionSettings['name'];
		$section->handle      = $sectionSettings['handle'];
		$section->has_urls    = (isset($sectionSettings['has_urls']) ? (bool)$sectionSettings['has_urls'] : false);
		$section->url_format  = (isset($sectionSettings['url_format']) ? $sectionSettings['url_format'] : null);
		$section->template    = (isset($sectionSettings['template']) ? $sectionSettings['template'] : null);

		// Start a transaction
		$transaction = blx()->db->beginTransaction();
		try
		{
			if ($section->save())
			{
				// Get the section's content table name
				$contentTable = $section->getContentTableName();

				if ($isNewSection)
				{
					// Create the content table
					$section->createContentTable();
				}
				else
				{
					// Rename the content table if the handle changed
					if ($contentTable != $oldContentTable)
						blx()->db->createCommand()->renameTable($oldContentTable, $contentTable);

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
			// Create the entry
			$entry = new Entry();
			$entry->section_id = $sectionId;
			$entry->author_id = ($authorId ? $authorId : blx()->accounts->getCurrentUser()->id);
			$entry->parent_id = $parentId;
			$entry->save();

			// Create a content row for it
			$table = $entry->section->getContentTableName();
			blx()->db->createCommand()->insert($table, array(
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
				throw new Exception(Blocks::t('No entry exists with the ID “{entryId}”.', array('entryId' => $entry->id)));
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
	 * @param $entry
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
		$drafts = blx()->db->createCommand()
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
				throw new Exception(Blocks::t('No entry exists with the ID “{entryId}”.', array('entryId' => $entry->id)));
		}

		if (!$language)
			$language = blx()->language;

		$version = new EntryVersion();
		$version->entry_id  = $entry->id;
		$version->author_id = blx()->accounts->getCurrentUser()->id;
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
		$draft = blx()->db->createCommand()
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
