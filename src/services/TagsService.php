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

	// Tag sets

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
				craft()->fields->saveLayout($fieldLayout, false);

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
	 * @throws \Exception
	 * @return bool
	 */
	public function deleteTagSetById($tagSetId)
	{
		if (!$tagSetId)
		{
			return false;
		}

		$transaction = craft()->db->beginTransaction();
		try
		{
			// Delete the field layout
			$fieldLayoutId = craft()->db->createCommand()
				->select('fieldLayoutId')
				->from('tagsets')
				->where(array('id' => $tagSetId))
				->queryScalar();

			if ($fieldLayoutId)
			{
				craft()->fields->deleteLayoutById($fieldLayoutId);
			}

			// Grab the tag ids so we can clean the elements table.
			$tagIds = craft()->db->createCommand()
				->select('id')
				->from('tags')
				->where(array('setId' => $tagSetId))
				->queryColumn();

			craft()->elements->deleteElementById($tagIds);

			$affectedRows = craft()->db->createCommand()->delete('tagsets', array('id' => $tagSetId));

			$transaction->commit();

			return (bool) $affectedRows;
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}
	}

	// Tags

	/**
	 * Saves a tag.
	 *
	 * @param TagModel $tag
	 * @throws Exception
	 * @return bool
	 */
	public function saveTag(TagModel $tag)
	{
		$isNewTag = !$tag->id;

		// Tag data
		if (!$isNewTag)
		{
			$tagRecord = TagRecord::model()->with('element')->findById($tag->id);

			if (!$tagRecord)
			{
				throw new Exception(Craft::t('No tag exists with the ID “{id}”', array('id' => $tag->id)));
			}

			$elementRecord = $tagRecord->element;

			// If tag->setId is null and there is an tagRecord setId, we assume this is a front-end edit.
			if ($tag->setId === null && $tagRecord->setId)
			{
				$tag->setId = $tagRecord->setId;
			}
		}
		else
		{
			$tagRecord = new TagRecord();

			$elementRecord = new ElementRecord();
			$elementRecord->type = ElementType::Tag;
		}

		$tagRecord->setId = $tag->setId;
		$tagRecord->name = $tag->name;

		$tagRecord->validate();
		$tag->addErrors($tagRecord->getErrors());

		$elementRecord->validate();
		$tag->addErrors($elementRecord->getErrors());

		if (!$tag->hasErrors())
		{
			// Save the element record first
			$elementRecord->save(false);

			// Now that we have an element ID, save it on the other stuff
			if (!$tag->id)
			{
				$tag->id = $elementRecord->id;
				$tagRecord->id = $tag->id;
			}

			$tagRecord->save(false);

			// Update the search index
			craft()->search->indexElementAttributes($tag, $tag->locale);

			// Fire an 'onSaveTag' event
			$this->onSaveTag(new Event($this, array(
				'tag'      => $tag,
				'isNewTag' => $isNewTag
			)));

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Returns a tag by its ID.
	 *
	 * @param $tagId
	 * @return TagModel|null
	 */
	public function getTagById($tagId)
	{
		return $this->findTag(array(
			'id' => $tagId
		));
	}

	/**
	 * Finds the first tag that matches the given criteria.
	 *
	 * @param mixed $criteria
	 * @return TagModel|null
	 */
	public function findTag($criteria = null)
	{
		if (!($criteria instanceof ElementCriteriaModel))
		{
			$criteria = craft()->elements->getCriteria(ElementType::Tag, $criteria);
		}
		return $criteria->first();
	}

	/**
	 * Saves a tag's content.
	 *
	 * @param TagModel $tag
	 * @return bool
	 */
	public function saveTagContent(TagModel $tag)
	{
		// TODO: translation support
		$fieldLayout = craft()->fields->getLayoutByType(ElementType::Tag);
		if (craft()->content->saveElementContent($tag, $fieldLayout))
		{
			// Fire an 'onSaveTagContent' event
			$this->onSaveTagContent(new Event($this, array(
				'tag' => $tag
			)));

			return true;
		}
		else
		{
			return false;
		}
	}

	// Events

	/**
	 * Fires an 'onSaveTag' event.
	 *
	 * @param Event $event
	 */
	public function onSaveTag(Event $event)
	{
		$this->raiseEvent('onSaveTag', $event);
	}

	/**
	 * Fires an 'onSaveTagContent' event.
	 *
	 * @param Event $event
	 */
	public function onSaveTagContent(Event $event)
	{
		$this->raiseEvent('onSaveTagContent', $event);
	}
}
