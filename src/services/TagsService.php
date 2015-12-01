<?php
namespace Craft;

/**
 * Class TagsService
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.services
 * @since     1.1
 */
class TagsService extends BaseApplicationComponent
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_allTagGroupIds;

	/**
	 * @var
	 */
	private $_tagGroupsById;

	/**
	 * @var bool
	 */
	private $_fetchedAllTagGroups = false;

	// Public Methods
	// =========================================================================

	// Tag groups
	// -------------------------------------------------------------------------

	/**
	 * Returns all of the group IDs.
	 *
	 * @return array
	 */
	public function getAllTagGroupIds()
	{
		if (!isset($this->_allTagGroupIds))
		{
			if ($this->_fetchedAllTagGroups)
			{
				$this->_allTagGroupIds = array_keys($this->_tagGroupsById);
			}
			else
			{
				$this->_allTagGroupIds = craft()->db->createCommand()
					->select('id')
					->from('taggroups')
					->queryColumn();
			}
		}

		return $this->_allTagGroupIds;
	}

	/**
	 * Returns all tag groups.
	 *
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getAllTagGroups($indexBy = null)
	{
		if (!$this->_fetchedAllTagGroups)
		{
			$tagGroupRecords = TagGroupRecord::model()->ordered()->findAll();
			$this->_tagGroupsById = TagGroupModel::populateModels($tagGroupRecords, 'id');
			$this->_fetchedAllTagGroups = true;
		}

		if ($indexBy == 'id')
		{
			return $this->_tagGroupsById;
		}
		else if (!$indexBy)
		{
			return array_values($this->_tagGroupsById);
		}
		else
		{
			$tagGroups = array();

			foreach ($this->_tagGroupsById as $group)
			{
				$tagGroups[$group->$indexBy] = $group;
			}

			return $tagGroups;
		}
	}

	/**
	 * Gets the total number of tag groups.
	 *
	 * @return int
	 */
	public function getTotalTagGroups()
	{
		return count($this->getAllTagGroupIds());
	}

	/**
	 * Returns a group by its ID.
	 *
	 * @param int $groupId
	 *
	 * @return TagGroupModel|null
	 */
	public function getTagGroupById($groupId)
	{
		if (!isset($this->_tagGroupsById) || !array_key_exists($groupId, $this->_tagGroupsById))
		{
			$groupRecord = TagGroupRecord::model()->findById($groupId);

			if ($groupRecord)
			{
				$this->_tagGroupsById[$groupId] = TagGroupModel::populateModel($groupRecord);
			}
			else
			{
				$this->_tagGroupsById[$groupId] = null;
			}
		}

		return $this->_tagGroupsById[$groupId];
	}

	/**
	 * Gets a group by its handle.
	 *
	 * @param string $groupHandle
	 *
	 * @return TagGroupModel|null
	 */
	public function getTagGroupByHandle($groupHandle)
	{
		$groupRecord = TagGroupRecord::model()->findByAttributes(array(
			'handle' => $groupHandle
		));

		if ($groupRecord)
		{
			return TagGroupModel::populateModel($groupRecord);
		}
	}

	/**
	 * Saves a tag group.
	 *
	 * @param TagGroupModel $tagGroup
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function saveTagGroup(TagGroupModel $tagGroup)
	{
		if ($tagGroup->id)
		{
			$tagGroupRecord = TagGroupRecord::model()->findById($tagGroup->id);

			if (!$tagGroupRecord)
			{
				throw new Exception(Craft::t('No tag group exists with the ID “{id}”.', array('id' => $tagGroup->id)));
			}

			$oldTagGroup = TagGroupModel::populateModel($tagGroupRecord);
			$isNewTagGroup = false;
		}
		else
		{
			$tagGroupRecord = new TagGroupRecord();
			$isNewTagGroup = true;
		}

		$tagGroupRecord->name       = $tagGroup->name;
		$tagGroupRecord->handle     = $tagGroup->handle;

		$tagGroupRecord->validate();
		$tagGroup->addErrors($tagGroupRecord->getErrors());

		if (!$tagGroup->hasErrors())
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
			try
			{
				if (!$isNewTagGroup && $oldTagGroup->fieldLayoutId)
				{
					// Drop the old field layout
					craft()->fields->deleteLayoutById($oldTagGroup->fieldLayoutId);
				}

				// Save the new one
				$fieldLayout = $tagGroup->getFieldLayout();
				craft()->fields->saveLayout($fieldLayout);

				// Update the tag group record/model with the new layout ID
				$tagGroup->fieldLayoutId = $fieldLayout->id;
				$tagGroupRecord->fieldLayoutId = $fieldLayout->id;

				// Save it!
				$tagGroupRecord->save(false);

				// Now that we have a tag group ID, save it on the model
				if (!$tagGroup->id)
				{
					$tagGroup->id = $tagGroupRecord->id;
				}

				// Might as well update our cache of the tag group while we have it.
				$this->_tagGroupsById[$tagGroup->id] = $tagGroup;

				if ($transaction !== null)
				{
					$transaction->commit();
				}
			}
			catch (\Exception $e)
			{
				if ($transaction !== null)
				{
					$transaction->rollback();
				}

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
	 * Deletes a tag group by its ID.
	 *
	 * @param int $tagGroupId
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function deleteTagGroupById($tagGroupId)
	{
		if (!$tagGroupId)
		{
			return false;
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// Delete the field layout
			$fieldLayoutId = craft()->db->createCommand()
				->select('fieldLayoutId')
				->from('taggroups')
				->where(array('id' => $tagGroupId))
				->queryScalar();

			if ($fieldLayoutId)
			{
				craft()->fields->deleteLayoutById($fieldLayoutId);
			}

			// Grab the tag ids so we can clean the elements table.
			$tagIds = craft()->db->createCommand()
				->select('id')
				->from('tags')
				->where(array('groupId' => $tagGroupId))
				->queryColumn();

			craft()->elements->deleteElementById($tagIds);

			$affectedRows = craft()->db->createCommand()->delete('taggroups', array('id' => $tagGroupId));

			if ($transaction !== null)
			{
				$transaction->commit();
			}

			return (bool) $affectedRows;
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}
	}

	// Tags
	// -------------------------------------------------------------------------

	/**
	 * Returns a tag by its ID.
	 *
	 * @param int         $tagId
	 * @param string|null $localeId
	 *
	 * @return TagModel|null
	 */
	public function getTagById($tagId, $localeId)
	{
		return craft()->elements->getElementById($tagId, ElementType::Tag, $localeId);
	}

	/**
	 * Saves a tag.
	 *
	 * @param TagModel $tag
	 *
	 * @throws Exception|\Exception
	 * @return bool
	 */
	public function saveTag(TagModel $tag)
	{
		$isNewTag = !$tag->id;

		// Tag data
		if (!$isNewTag)
		{
			$tagRecord = TagRecord::model()->findById($tag->id);

			if (!$tagRecord)
			{
				throw new Exception(Craft::t('No tag exists with the ID “{id}”.', array('id' => $tag->id)));
			}
		}
		else
		{
			$tagRecord = new TagRecord();
		}

		$tagRecord->groupId = $tag->groupId;

		$tagRecord->validate();
		$tag->addErrors($tagRecord->getErrors());

		if ($tag->hasErrors())
		{
			return false;
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		try
		{
			// Fire an 'onBeforeSaveTag' event
			$event = new Event($this, array(
				'tag'      => $tag,
				'isNewTag' => $isNewTag
			));

			$this->onBeforeSaveTag($event);

			// Is the event giving us the go-ahead?
			if ($event->performAction)
			{
				$success = craft()->elements->saveElement($tag, false);

				// If it didn't work, rollback the transaction in case something changed in onBeforeSaveTag
				if (!$success)
				{
					if ($transaction !== null)
					{
						$transaction->rollback();
					}

					return false;
				}

				// Now that we have an element ID, save it on the other stuff
				if ($isNewTag)
				{
					$tagRecord->id = $tag->id;
				}

				$tagRecord->save(false);
			}
			else
			{
				$success = false;
			}

			// Commit the transaction regardless of whether we saved the tag, in case something changed
			// in onBeforeSaveTag
			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}

		if ($success)
		{
			// Fire an 'onSaveTag' event
			$this->onSaveTag(new Event($this, array(
				'tag'      => $tag,
				'isNewTag' => $isNewTag
			)));

			if ($this->hasEventHandler('onSaveTagContent'))
			{
				// Fire an 'onSaveTagContent' event (deprecated)
				$this->onSaveTagContent(new Event($this, array(
					'tag' => $tag
				)));
			}
		}

		return $success;
	}

	// Events
	// -------------------------------------------------------------------------

	/**
	 * Fires an 'onBeforeSaveTag' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeSaveTag(Event $event)
	{
		$this->raiseEvent('onBeforeSaveTag', $event);
	}

	/**
	 * Fires an 'onSaveTag' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onSaveTag(Event $event)
	{
		$this->raiseEvent('onSaveTag', $event);
	}

	/**
	 * Fires an 'onSaveTagContent' event.
	 *
	 * @param Event $event
	 *
	 * @deprecated Deprecated in 2.0. Use {@link onSaveTag() `tags.onSaveTag`} instead.
	 * @return null
	 */
	public function onSaveTagContent(Event $event)
	{
		craft()->deprecator->log('TagsService::onSaveTagContent()', 'The tags.onSaveTagContent event has been deprecated. Use tags.onSaveTag instead.');
		$this->raiseEvent('onSaveTagContent', $event);
	}
}
