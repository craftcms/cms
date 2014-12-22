<?php
namespace Craft;

/**
 * Class CategoriesService
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.services
 * @since     2.0
 */
class CategoriesService extends BaseApplicationComponent
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_allGroupIds;

	/**
	 * @var
	 */
	private $_editableGroupIds;

	/**
	 * @var
	 */
	private $_categoryGroupsById;

	/**
	 * @var bool
	 */
	private $_fetchedAllCategoryGroups = false;

	// Public Methods
	// =========================================================================

	// Category groups
	// -------------------------------------------------------------------------

	/**
	 * Returns all of the group IDs.
	 *
	 * @return array
	 */
	public function getAllGroupIds()
	{
		if (!isset($this->_allGroupIds))
		{
			if ($this->_fetchedAllCategoryGroups)
			{
				$this->_allGroupIds = array_keys($this->_categoryGroupsById);
			}
			else
			{
				$this->_allGroupIds = craft()->db->createCommand()
					->select('id')
					->from('categorygroups')
					->queryColumn();
			}
		}

		return $this->_allGroupIds;
	}

	/**
	 * Returns all of the category group IDs that are editable by the current user.
	 *
	 * @return array
	 */
	public function getEditableGroupIds()
	{
		if (!isset($this->_editableGroupIds))
		{
			$this->_editableGroupIds = array();

			foreach ($this->getAllGroupIds() as $groupId)
			{
				if (craft()->userSession->checkPermission('editCategories:'.$groupId))
				{
					$this->_editableGroupIds[] = $groupId;
				}
			}
		}

		return $this->_editableGroupIds;
	}

	/**
	 * Returns all category groups.
	 *
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getAllGroups($indexBy = null)
	{
		if (!$this->_fetchedAllCategoryGroups)
		{
			$groupRecords = CategoryGroupRecord::model()->with('structure')->ordered()->findAll();

			if (!isset($this->_categoryGroupsById))
			{
				$this->_categoryGroupsById = array();
			}

			foreach ($groupRecords as $groupRecord)
			{
				$this->_categoryGroupsById[$groupRecord->id] = $this->_populateCategoryGroupFromRecord($groupRecord);
			}

			$this->_fetchedAllCategoryGroups = true;
		}

		if ($indexBy == 'id')
		{
			return $this->_categoryGroupsById;
		}
		else if (!$indexBy)
		{
			return array_values($this->_categoryGroupsById);
		}
		else
		{
			$groups = array();

			foreach ($this->_categoryGroupsById as $group)
			{
				$groups[$group->$indexBy] = $group;
			}

			return $groups;
		}
	}

	/**
	 * Returns all editable groups.
	 *
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getEditableGroups($indexBy = null)
	{
		$editableGroupIds = $this->getEditableGroupIds();
		$editableGroups = array();

		foreach ($this->getAllGroups() as $group)
		{
			if (in_array($group->id, $editableGroupIds))
			{
				if ($indexBy)
				{
					$editableGroups[$group->$indexBy] = $group;
				}
				else
				{
					$editableGroups[] = $group;
				}
			}
		}

		return $editableGroups;
	}

	/**
	 * Gets the total number of category groups.
	 *
	 * @return int
	 */
	public function getTotalGroups()
	{
		return count($this->getAllGroupIds());
	}

	/**
	 * Returns a group by its ID.
	 *
	 * @param int $groupId
	 *
	 * @return CategoryGroupModel|null
	 */
	public function getGroupById($groupId)
	{
		if (!isset($this->_categoryGroupsById) || !array_key_exists($groupId, $this->_categoryGroupsById))
		{
			$groupRecord = CategoryGroupRecord::model()->with('structure')->findById($groupId);

			if ($groupRecord)
			{
				$this->_categoryGroupsById[$groupId] = $this->_populateCategoryGroupFromRecord($groupRecord);
			}
			else
			{
				$this->_categoryGroupsById[$groupId] = null;
			}
		}

		return $this->_categoryGroupsById[$groupId];
	}

	/**
	 * Returns a group by its handle.
	 *
	 * @param string $groupHandle
	 *
	 * @return CategoryGroupModel|null
	 */
	public function getGroupByHandle($groupHandle)
	{
		$groupRecord = CategoryGroupRecord::model()->findByAttributes(array(
			'handle' => $groupHandle
		));

		if ($groupRecord)
		{
			$group = $this->_populateCategoryGroupFromRecord($groupRecord);
			$this->_categoryGroupsById[$group->id] = $group;
			return $group;
		}
	}

	/**
	 * Returns a group's locales.
	 *
	 * @param int         $groupId
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getGroupLocales($groupId, $indexBy = null)
	{
		$records = CategoryGroupLocaleRecord::model()->findAllByAttributes(array(
			'groupId' => $groupId
		));

		return CategoryGroupLocaleModel::populateModels($records, $indexBy);
	}

	/**
	 * Saves a category group.
	 *
	 * @param CategoryGroupModel $group
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function saveGroup(CategoryGroupModel $group)
	{
		if ($group->id)
		{
			$groupRecord = CategoryGroupRecord::model()->findById($group->id);

			if (!$groupRecord)
			{
				throw new Exception(Craft::t('No category group exists with the ID “{id}”.', array('id' => $group->id)));
			}

			$oldCategoryGroup = CategoryGroupModel::populateModel($groupRecord);
			$isNewCategoryGroup = false;
		}
		else
		{
			$groupRecord = new CategoryGroupRecord();
			$isNewCategoryGroup = true;
		}

		$groupRecord->name    = $group->name;
		$groupRecord->handle  = $group->handle;
		$groupRecord->hasUrls = $group->hasUrls;

		if ($group->hasUrls)
		{
			$groupRecord->template = $group->template;
		}
		else
		{
			$groupRecord->template = $group->template = null;
		}

		// Make sure that all of the URL formats are set properly
		$groupLocales = $group->getLocales();

		foreach ($groupLocales as $localeId => $groupLocale)
		{
			if ($group->hasUrls)
			{
				$urlFormatAttributes = array('urlFormat');
				$groupLocale->urlFormatIsRequired = true;

				if ($group->maxLevels == 1)
				{
					$groupLocale->nestedUrlFormat = null;
				}
				else
				{
					$urlFormatAttributes[] = 'nestedUrlFormat';
					$groupLocale->nestedUrlFormatIsRequired = true;
				}

				foreach ($urlFormatAttributes as $attribute)
				{
					if (!$groupLocale->validate(array($attribute)))
					{
						$group->addError($attribute.'-'.$localeId, $groupLocale->getError($attribute));
					}
				}
			}
			else
			{
				$groupLocale->urlFormat = null;
				$groupLocale->nestedUrlFormat = null;
			}
		}

		// Validate!
		$groupRecord->validate();
		$group->addErrors($groupRecord->getErrors());

		if (!$group->hasErrors())
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
			try
			{
				// Create/update the structure

				if ($isNewCategoryGroup)
				{
					$structure = new StructureModel();
				}
				else
				{
					$structure = craft()->structures->getStructureById($oldCategoryGroup->structureId);
				}

				$structure->maxLevels = $group->maxLevels;
				craft()->structures->saveStructure($structure);
				$groupRecord->structureId = $structure->id;
				$group->structureId = $structure->id;

				// Create and set the field layout

				if (!$isNewCategoryGroup && $oldCategoryGroup->fieldLayoutId)
				{
					craft()->fields->deleteLayoutById($oldCategoryGroup->fieldLayoutId);
				}

				$fieldLayout = $group->getFieldLayout();
				craft()->fields->saveLayout($fieldLayout);
				$groupRecord->fieldLayoutId = $fieldLayout->id;
				$group->fieldLayoutId = $fieldLayout->id;

				// Save the category group
				$groupRecord->save(false);

				// Now that we have a category group ID, save it on the model
				if (!$group->id)
				{
					$group->id = $groupRecord->id;
				}

				// Might as well update our cache of the category group while we have it.
				$this->_categoryGroupsById[$group->id] = $group;

				// Update the categorygroups_i18n table
				$newLocaleData = array();

				if (!$isNewCategoryGroup)
				{
					// Get the old category group locales
					$oldLocaleRecords = CategoryGroupLocaleRecord::model()->findAllByAttributes(array(
						'groupId' => $group->id
					));
					$oldLocales = CategoryGroupLocaleModel::populateModels($oldLocaleRecords, 'locale');

					$changedLocaleIds = array();
				}

				foreach ($groupLocales as $localeId => $locale)
				{
					// Was this already selected?
					if (!$isNewCategoryGroup && isset($oldLocales[$localeId]))
					{
						$oldLocale = $oldLocales[$localeId];

						// Has the URL format changed?
						if ($locale->urlFormat != $oldLocale->urlFormat || $locale->nestedUrlFormat != $oldLocale->nestedUrlFormat)
						{
							craft()->db->createCommand()->update('categorygroups_i18n', array(
								'urlFormat'       => $locale->urlFormat,
								'nestedUrlFormat' => $locale->nestedUrlFormat
							), array(
								'id' => $oldLocale->id
							));

							$changedLocaleIds[] = $localeId;
						}
					}
					else
					{
						$newLocaleData[] = array($group->id, $localeId, $locale->urlFormat, $locale->nestedUrlFormat);
					}
				}

				// Insert the new locales
				craft()->db->createCommand()->insertAll('categorygroups_i18n',
					array('groupId', 'locale', 'urlFormat', 'nestedUrlFormat'),
					$newLocaleData
				);

				if (!$isNewCategoryGroup)
				{
					// Drop any locales that are no longer being used, as well as the associated category/element
					// locale rows

					$droppedLocaleIds = array_diff(array_keys($oldLocales), array_keys($groupLocales));

					if ($droppedLocaleIds)
					{
						craft()->db->createCommand()->delete('categorygroups_i18n', array('in', 'locale', $droppedLocaleIds));
					}
				}

				// Finally, deal with the existing categories...

				if (!$isNewCategoryGroup)
				{
					// Get all of the category IDs in this group
					$criteria = craft()->elements->getCriteria(ElementType::Category);
					$criteria->groupId = $group->id;
					$criteria->status = null;
					$criteria->limit = null;
					$categoryIds = $criteria->ids();

					// Should we be deleting
					if ($categoryIds && $droppedLocaleIds)
					{
						craft()->db->createCommand()->delete('elements_i18n', array('and', array('in', 'elementId', $categoryIds), array('in', 'locale', $droppedLocaleIds)));
						craft()->db->createCommand()->delete('content', array('and', array('in', 'elementId', $categoryIds), array('in', 'locale', $droppedLocaleIds)));
					}

					// Are there any locales left?
					if ($groupLocales)
					{
						// Drop the old category URIs if the group no longer has URLs
						if (!$group->hasUrls && $oldCategoryGroup->hasUrls)
						{
							craft()->db->createCommand()->update('elements_i18n',
								array('uri' => null),
								array('in', 'elementId', $categoryIds)
							);
						}
						else if ($changedLocaleIds)
						{
							foreach ($categoryIds as $categoryId)
							{
								craft()->config->maxPowerCaptain();

								// Loop through each of the changed locales and update all of the categories’ slugs and
								// URIs
								foreach ($changedLocaleIds as $localeId)
								{
									$criteria = craft()->elements->getCriteria(ElementType::Category);
									$criteria->id = $categoryId;
									$criteria->locale = $localeId;
									$criteria->status = null;
									$category = $criteria->first();

									// todo: replace the getContent()->id check with 'strictLocale' param once it's added
									if ($category && $category->getContent()->id)
									{
										craft()->elements->updateElementSlugAndUri($category, false, false);
									}
								}
							}
						}
					}
				}

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
	 * Deletes a category group by its ID.
	 *
	 * @param int $groupId
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function deleteGroupById($groupId)
	{
		if (!$groupId)
		{
			return false;
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// Delete the field layout
			$fieldLayoutId = craft()->db->createCommand()
				->select('fieldLayoutId')
				->from('categorygroups')
				->where(array('id' => $groupId))
				->queryScalar();

			if ($fieldLayoutId)
			{
				craft()->fields->deleteLayoutById($fieldLayoutId);
			}

			// Grab the category ids so we can clean the elements table.
			$categoryIds = craft()->db->createCommand()
				->select('id')
				->from('categories')
				->where(array('groupId' => $groupId))
				->queryColumn();

			craft()->elements->deleteElementById($categoryIds);

			$affectedRows = craft()->db->createCommand()->delete('categorygroups', array('id' => $groupId));

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

	/**
	 * Returns whether a group’s categories have URLs, and if the group’s template path is valid.
	 *
	 * @param CategoryGroupModel $group
	 *
	 * @return bool
	 */
	public function isGroupTemplateValid(CategoryGroupModel $group)
	{
		if ($group->hasUrls)
		{
			// Set Craft to the site template path
			$oldTemplatesPath = craft()->path->getTemplatesPath();
			craft()->path->setTemplatesPath(craft()->path->getSiteTemplatesPath());

			// Does the template exist?
			$templateExists = craft()->templates->doesTemplateExist($group->template);

			// Restore the original template path
			craft()->path->setTemplatesPath($oldTemplatesPath);

			if ($templateExists)
			{
				return true;
			}
		}

		return false;
	}

	// Categories
	// -------------------------------------------------------------------------

	/**
	 * Returns a category by its ID.
	 *
	 * @param int      $categoryId
	 * @param int|null $localeId
	 *
	 * @return CategoryModel|null
	 */
	public function getCategoryById($categoryId, $localeId = null)
	{
		return craft()->elements->getElementById($categoryId, ElementType::Category, $localeId);
	}

	/**
	 * Saves a category.
	 *
	 * @param CategoryModel $category
	 *
	 * @throws Exception|\Exception
	 * @return bool
	 */
	public function saveCategory(CategoryModel $category)
	{
		$isNewCategory = !$category->id;

		$hasNewParent = $this->_checkForNewParent($category);

		if ($hasNewParent)
		{
			if ($category->newParentId)
			{
				$parentCategory = $this->getCategoryById($category->newParentId, $category->locale);

				if (!$parentCategory)
				{
					throw new Exception(Craft::t('No category exists with the ID “{id}”.', array('id' => $category->newParentId)));
				}
			}
			else
			{
				$parentCategory = null;
			}

			$category->setParent($parentCategory);
		}

		// Category data
		if (!$isNewCategory)
		{
			$categoryRecord = CategoryRecord::model()->findById($category->id);

			if (!$categoryRecord)
			{
				throw new Exception(Craft::t('No category exists with the ID “{id}”.', array('id' => $category->id)));
			}
		}
		else
		{
			$categoryRecord = new CategoryRecord();
		}

		$categoryRecord->groupId = $category->groupId;

		$categoryRecord->validate();
		$category->addErrors($categoryRecord->getErrors());

		if ($category->hasErrors())
		{
			return false;
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		try
		{
			// Fire an 'onBeforeSaveCategory' event
			$event = new Event($this, array(
				'category'      => $category,
				'isNewCategory' => $isNewCategory
			));

			$this->onBeforeSaveCategory($event);

			// Is the event giving us the go-ahead?
			if ($event->performAction)
			{
				$success = craft()->elements->saveElement($category);

				// If it didn't work, rollback the transaction in case something changed in onBeforeSaveCategory
				if (!$success)
				{
					if ($transaction !== null)
					{
						$transaction->rollback();
					}

					return false;
				}

				// Now that we have an element ID, save it on the other stuff
				if ($isNewCategory)
				{
					$categoryRecord->id = $category->id;
				}

				$categoryRecord->save(false);

				// Has the parent changed?
				if ($hasNewParent)
				{
					if (!$category->newParentId)
					{
						craft()->structures->appendToRoot($category->getGroup()->structureId, $category);
					}
					else
					{
						craft()->structures->append($category->getGroup()->structureId, $category, $parentCategory);
					}
				}

				// Update the category's descendants, who may be using this category's URI in their own URIs
				craft()->elements->updateDescendantSlugsAndUris($category);
			}
			else
			{
				$success = false;
			}

			// Commit the transaction regardless of whether we saved the category, in case something changed
			// in onBeforeSaveCategory
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
			// Fire an 'onSaveCategory' event
			$this->onSaveCategory(new Event($this, array(
				'category'      => $category,
				'isNewCategory' => $isNewCategory
			)));
		}

		return $success;
	}

	/**
	 * Deletes a category(s).
	 *
	 * @param CategoryModel|array $categories
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function deleteCategory($categories)
	{
		if (!$categories)
		{
			return false;
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			if (!is_array($categories))
			{
				$categories = array($categories);
			}

			$success = $this->_deleteCategories($categories, true);

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
			foreach ($categories as $category)
			{
				// Fire an 'onDeleteCategory' event
				$this->onDeleteCategory(new Event($this, array(
					'category' => $category
				)));
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Deletes an category(s) by its ID.
	 *
	 * @param int|array $categoryId
	 *
	 * @return bool
	 */
	public function deleteCategoryById($categoryId)
	{
		if (!$categoryId)
		{
			return false;
		}

		$criteria = craft()->elements->getCriteria(ElementType::Category);
		$criteria->id = $categoryId;
		$criteria->limit = null;
		$criteria->status = null;
		$criteria->localeEnabled = false;
		$categories = $criteria->find();

		if ($categories)
		{
			return $this->deleteCategory($categories);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Updates a list of category IDs, filling in any gaps in the family tree.
	 *
	 * @param array $ids The original list of category IDs
	 *
	 * @return array The list of category IDs with all the gaps filled in.
	 */
	public function fillGapsInCategoryIds($ids)
	{
		$completeIds = array();

		if ($ids)
		{
			// Make sure that for each selected category, all of its parents are also selected.
			$criteria = craft()->elements->getCriteria(ElementType::Category);
			$criteria->id = $ids;
			$criteria->status = null;
			$criteria->localeEnabled = false;
			$criteria->limit = null;
			$categories = $criteria->find();

			$prevCategory = null;

			foreach ($categories as $i => $category)
			{
				// Did we just skip any categories?
				if ($category->level != 1 && (
					($i == 0) ||
					(!$category->isSiblingOf($prevCategory) && !$category->isChildOf($prevCategory))
				))
				{
					// Merge in all of the entry's ancestors
					$ancestorIds = $category->getAncestors()->ids();
					$completeIds = array_merge($completeIds, $ancestorIds);
				}

				$completeIds[] = $category->id;
				$prevCategory = $category;
			}
		}

		return $completeIds;
	}

	// Events
	// -------------------------------------------------------------------------

	/**
	 * Fires an 'onBeforeSaveCategory' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeSaveCategory(Event $event)
	{
		$this->raiseEvent('onBeforeSaveCategory', $event);
	}

	/**
	 * Fires an 'onSaveCategory' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onSaveCategory(Event $event)
	{
		$this->raiseEvent('onSaveCategory', $event);
	}

	/**
	 * Fires an 'onBeforeDeleteCategory' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeDeleteCategory(Event $event)
	{
		$this->raiseEvent('onBeforeDeleteCategory', $event);
	}

	/**
	 * Fires an 'onDeleteCategory' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onDeleteCategory(Event $event)
	{
		$this->raiseEvent('onDeleteCategory', $event);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Populates a CategoryGroupModel with attributes from a CategoryGroupRecord.
	 *
	 * @param CategoryGroupRecord|null
	 *
	 * @return CategoryGroupModel|null
	 */
	private function _populateCategoryGroupFromRecord($groupRecord)
	{
		if (!$groupRecord)
		{
			return null;
		}

		$group = CategoryGroupModel::populateModel($groupRecord);

		if ($groupRecord->structure)
		{
			$group->maxLevels = $groupRecord->structure->maxLevels;
		}

		return $group;
	}

	/**
	 * Checks if an category was submitted with a new parent category selected.
	 *
	 * @param CategoryModel $category
	 *
	 * @return bool
	 */
	private function _checkForNewParent(CategoryModel $category)
	{
		// Is it a brand new category?
		if (!$category->id)
		{
			return true;
		}

		// Was a new parent ID actually submitted?
		if ($category->newParentId === null)
		{
			return false;
		}

		// Is it set to the top level now, but it hadn't been before?
		if ($category->newParentId === '' && $category->level != 1)
		{
			return true;
		}

		// Is it set to be under a parent now, but didn't have one before?
		if ($category->newParentId !== '' && $category->level == 1)
		{
			return true;
		}

		// Is the newParentId set to a different category ID than its previous parent?
		$criteria = craft()->elements->getCriteria(ElementType::Category);
		$criteria->ancestorOf = $category;
		$criteria->ancestorDist = 1;
		$criteria->status = null;
		$criteria->localeEnabled = null;

		$oldParent = $criteria->first();
		$oldParentId = ($oldParent ? $oldParent->id : '');

		if ($category->newParentId != $oldParentId)
		{
			return true;
		}

		// Must be set to the same one then
		return false;
	}

	/**
	 * Deletes categories, and their descendants.
	 *
	 * @param array $categories
	 * @param bool  $deleteDescendants
	 *
	 * @return bool
	 */
	private function _deleteCategories($categories, $deleteDescendants = true)
	{
		$categoryIds = array();

		foreach ($categories as $category)
		{
			if ($deleteDescendants)
			{
				// Delete the descendants in reverse order, so structures don't get wonky
				$descendants = $category->getDescendants()->status(null)->localeEnabled(false)->order('lft desc')->find();
				$this->_deleteCategories($descendants, false);
			}

			// Fire an 'onBeforeDeleteCategory' event
			$this->onBeforeDeleteCategory(new Event($this, array(
				'category' => $category
			)));

			$categoryIds[] = $category->id;
		}

		// Delete 'em
		return craft()->elements->deleteElementById($categoryIds);
	}
}
