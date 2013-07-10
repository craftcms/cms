<?php
namespace Craft;

/**
 *
 */
class TagsService extends BaseApplicationComponent
{
	private $_allTagSetIds;
	private $_tagSetsById;
	private $_fetchedAllTagSets = false;


	/**
	 * Returns all of the set IDs.
	 *
	 * @return array
	 */
	public function getAllTagSetIds()
	{
		if (!isset($this->_allTagSetIds))
		{
			if ($this->_fetchedAllTagSets)
			{
				$this->_allTagSetIds = array_keys($this->_tagSetsById);
			}
			else
			{
				$this->_allTagSetIds = craft()->db->createCommand()
					->select('id')
					->from('tagsets')
					->queryColumn();
			}
		}

		return $this->_allTagSetIds;
	}

	/**
	 * Returns all tag sets.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getAllTagSets($indexBy = null)
	{
		if (!$this->_fetchedAllTagSets)
		{
			$tagSetRecords = TagSetRecord::model()->ordered()->findAll();
			$this->_tagSetsById = TagSetModel::populateModels($tagSetRecords, 'id');
			$this->_fetchedAllTagSets = true;
		}

		if ($indexBy == 'id')
		{
			return $this->_tagSetsById;
		}
		else if (!$indexBy)
		{
			return array_values($this->_tagSetsById);
		}
		else
		{
			$tagSets = array();

			foreach ($this->_tagSetsById as $set)
			{
				$tagSets[$set->$indexBy] = $set;
			}

			return $tagSets;
		}
	}

	/**
	 * Gets the total number of tag sets.
	 *
	 * @return int
	 */
	public function getTotalTagSets()
	{
		return count($this->getAllTagSetIds());
	}

	/**
	 * Returns a set by its ID.
	 *
	 * @param $setId
	 * @return TagSetModel|null
	 */
	public function getTagSetById($setId)
	{
		if (!isset($this->_tagSetsById) || !array_key_exists($setId, $this->_tagSetsById))
		{
			$setRecord = TagSetRecord::model()->findById($setId);

			if ($setRecord)
			{
				$this->_tagSetsById[$setId] = TagSetModel::populateModel($setRecord);
			}
			else
			{
				$this->_tagSetsById[$setId] = null;
			}
		}

		return $this->_tagSetsById[$setId];
	}

	/**
	 * Gets a set by its handle.
	 *
	 * @param string $setHandle
	 * @return TagSetModel|null
	 */
	public function getTagSetByHandle($setHandle)
	{
		$setRecord = TagSetRecord::model()->findByAttributes(array(
			'handle' => $setHandle
		));

		if ($setRecord)
		{
			return TagSetModel::populateModel($setRecord);
		}
	}

	/**
	 * Saves a tag set.
	 *
	 * @param TagSetModel $tagSet
	 * @throws \Exception
	 * @return bool
	 */
	public function saveTagSet(TagSetModel $tagSet)
	{
		if ($tagSet->id)
		{
			$tagSetRecord = TagSetRecord::model()->findById($tagSet->id);

			if (!$tagSetRecord)
			{
				throw new Exception(Craft::t('No tag set exists with the ID “{id}”', array('id' => $tagSet->id)));
			}

			$oldTagSet = TagSetModel::populateModel($tagSetRecord);
			$isNewTagSet = false;
		}
		else
		{
			$tagSetRecord = new TagSetRecord();
			$isNewTagSet = true;
		}

		$tagSetRecord->name       = $tagSet->name;
		$tagSetRecord->handle     = $tagSet->handle;

		$tagSetRecord->validate();
		$tagSet->addErrors($tagSetRecord->getErrors());

		if (!$tagSet->hasErrors())
		{
			$transaction = craft()->db->beginTransaction();
			try
			{
				if (!$isNewTagSet && $oldTagSet->fieldLayoutId)
				{
					// Drop the old field layout
					craft()->fields->deleteLayoutById($oldTagSet->fieldLayoutId);
				}

				// Save the new one
				$fieldLayout = $tagSet->getFieldLayout();
				craft()->fields->saveLayout($fieldLayout);

				// Update the tag set record/model with the new layout ID
				$tagSet->fieldLayoutId = $fieldLayout->id;
				$tagSetRecord->fieldLayoutId = $fieldLayout->id;

				// Save it!
				$tagSetRecord->save(false);

				// Now that we have a tag set ID, save it on the model
				if (!$tagSet->id)
				{
					$tagSet->id = $tagSetRecord->id;
				}

				// Might as well update our cache of the tag set while we have it.
				$this->_tagSetsById[$tagSet->id] = $tagSet;

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
	 * Deletes a tag set by its ID.
	 *
	 * @param int $tagSetId
	 * @return bool
	*/
	public function deleteTagSetById($tagSetId)
	{
		$affectedRows = craft()->db->createCommand()->delete('tagsets', array('id' => $tagSetId));
		return (bool) $affectedRows;
	}
}
