<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\db\Query;
use craft\app\errors\CategoryGroupNotFoundException;
use craft\app\errors\CategoryNotFoundException;
use craft\app\events\CategoryEvent;
use craft\app\elements\Category;
use craft\app\events\CategoryGroupEvent;
use craft\app\models\CategoryGroup;
use craft\app\models\CategoryGroupLocale;
use craft\app\models\FieldLayout;
use craft\app\models\Structure;
use craft\app\records\Category as CategoryRecord;
use craft\app\records\CategoryGroup as CategoryGroupRecord;
use craft\app\records\CategoryGroupLocale as CategoryGroupLocaleRecord;
use yii\base\Component;

/**
 * Class Categories service.
 *
 * An instance of the Categories service is globally accessible in Craft via [[Application::categories `Craft::$app->getCategories()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Categories extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event CategoryEvent The event that is triggered before a category is saved.
     *
     * You may set [[CategoryEvent::isValid]] to `false` to prevent the category from getting saved.
     */
    const EVENT_BEFORE_SAVE_CATEGORY = 'beforeSaveCategory';

    /**
     * @event CategoryEvent The event that is triggered after a category is saved.
     */
    const EVENT_AFTER_SAVE_CATEGORY = 'afterSaveCategory';

    /**
     * @event CategoryEvent The event that is triggered before a category is deleted.
     */
    const EVENT_BEFORE_DELETE_CATEGORY = 'beforeDeleteCategory';

    /**
     * @event CategoryEvent The event that is triggered after a category is deleted.
     */
    const EVENT_AFTER_DELETE_CATEGORY = 'afterDeleteCategory';

    /**
     * @event CategoryGroupEvent The event that is triggered before a category group is saved.
     */
    const EVENT_BEFORE_SAVE_GROUP = 'beforeSaveGroup';

    /**
     * @event CategoryGroupEvent The event that is triggered after a category group is saved.
     */
    const EVENT_AFTER_SAVE_GROUP = 'afterSaveGroup';

    /**
     * @event CategoryGroupEvent The event that is triggered before a category group is deleted.
     */
    const EVENT_BEFORE_DELETE_GROUP = 'beforeDeleteGroup';

    /**
     * @event CategoryGroupEvent The event that is triggered after a category group is deleted.
     */
    const EVENT_AFTER_DELETE_GROUP = 'afterDeleteGroup';

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
     * @return integer[]
     */
    public function getAllGroupIds()
    {
        if (!isset($this->_allGroupIds)) {
            if ($this->_fetchedAllCategoryGroups) {
                $this->_allGroupIds = array_keys($this->_categoryGroupsById);
            } else {
                $this->_allGroupIds = (new Query())
                    ->select('id')
                    ->from('{{%categorygroups}}')
                    ->column();
            }
        }

        return $this->_allGroupIds;
    }

    /**
     * Returns all of the category group IDs that are editable by the current user.
     *
     * @return integer[]
     */
    public function getEditableGroupIds()
    {
        if (!isset($this->_editableGroupIds)) {
            $this->_editableGroupIds = [];

            foreach ($this->getAllGroupIds() as $groupId) {
                if (Craft::$app->getUser()->checkPermission('editCategories:'.$groupId)) {
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
     * @return CategoryGroup[]
     */
    public function getAllGroups($indexBy = null)
    {
        if (!$this->_fetchedAllCategoryGroups) {
            $groupRecords = CategoryGroupRecord::find()
                ->orderBy('name asc')
                ->with('structure')
                ->all();

            if (!isset($this->_categoryGroupsById)) {
                $this->_categoryGroupsById = [];
            }

            foreach ($groupRecords as $groupRecord) {
                $this->_categoryGroupsById[$groupRecord->id] = $this->_createCategoryGroupFromRecord($groupRecord);
            }

            $this->_fetchedAllCategoryGroups = true;
        }

        if ($indexBy == 'id') {
            return $this->_categoryGroupsById;
        }

        if (!$indexBy) {
            return array_values($this->_categoryGroupsById);
        }

        $groups = [];

        foreach ($this->_categoryGroupsById as $group) {
            $groups[$group->$indexBy] = $group;
        }

        return $groups;
    }

    /**
     * Returns all editable groups.
     *
     * @param string|null $indexBy
     *
     * @return CategoryGroup[]
     */
    public function getEditableGroups($indexBy = null)
    {
        $editableGroupIds = $this->getEditableGroupIds();
        $editableGroups = [];

        foreach ($this->getAllGroups() as $group) {
            if (in_array($group->id, $editableGroupIds)) {
                if ($indexBy) {
                    $editableGroups[$group->$indexBy] = $group;
                } else {
                    $editableGroups[] = $group;
                }
            }
        }

        return $editableGroups;
    }

    /**
     * Gets the total number of category groups.
     *
     * @return integer
     */
    public function getTotalGroups()
    {
        return count($this->getAllGroupIds());
    }

    /**
     * Returns a group by its ID.
     *
     * @param integer $groupId
     *
     * @return CategoryGroup|null
     */
    public function getGroupById($groupId)
    {
        if (!isset($this->_categoryGroupsById) || !array_key_exists($groupId,
                $this->_categoryGroupsById)
        ) {
            $groupRecord = CategoryGroupRecord::find()
                ->where(['id' => $groupId])
                ->with('structure')
                ->one();

            if ($groupRecord) {
                $this->_categoryGroupsById[$groupId] = $this->_createCategoryGroupFromRecord($groupRecord);
            } else {
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
     * @return CategoryGroup|null
     */
    public function getGroupByHandle($groupHandle)
    {
        $groupRecord = CategoryGroupRecord::findOne([
            'handle' => $groupHandle
        ]);

        if ($groupRecord) {
            $group = $this->_createCategoryGroupFromRecord($groupRecord);
            $this->_categoryGroupsById[$group->id] = $group;

            return $group;
        }

        return null;
    }

    /**
     * Returns a group's locales.
     *
     * @param integer     $groupId
     * @param string|null $indexBy
     *
     * @return CategoryGroupLocale[]
     */
    public function getGroupLocales($groupId, $indexBy = null)
    {
        $groupLocales = CategoryGroupLocaleRecord::find()
            ->where(['groupId' => $groupId])
            ->indexBy($indexBy)
            ->all();

        foreach ($groupLocales as $key => $value) {
            $groupLocales[$key] = CategoryGroupLocale::create($value);
        }

        return $groupLocales;
    }

    /**
     * Saves a category group.
     *
     * @param CategoryGroup $group
     *
     * @return boolean Whether the category group was saved successfully
     * @throws CategoryGroupNotFoundException if $group has an invalid ID
     * @throws \Exception if reasons
     */
    public function saveGroup(CategoryGroup $group)
    {
        if ($group->id) {
            $groupRecord = CategoryGroupRecord::findOne($group->id);

            if (!$groupRecord) {

                throw new CategoryGroupNotFoundException("No category group exists with the ID '{$group->id}'");
            }

            /** @var CategoryGroup $oldCategoryGroup */
            $oldCategoryGroup = CategoryGroup::create($groupRecord);
            $isNewCategoryGroup = false;
        } else {
            $groupRecord = new CategoryGroupRecord();
            $isNewCategoryGroup = true;
        }

        // If they've set maxLevels to 0 (don't ask why), then pretend like there are none.
        if ($group->maxLevels == 0) {
            $group->maxLevels = null;
        }

        $groupRecord->name = $group->name;
        $groupRecord->handle = $group->handle;
        $groupRecord->hasUrls = (bool)$group->hasUrls;

        if ($group->hasUrls) {
            $groupRecord->template = $group->template;
        } else {
            $groupRecord->template = $group->template = null;
        }

        // Make sure that all of the URL formats are set properly
        $groupLocales = $group->getLocales();

        foreach ($groupLocales as $localeId => $groupLocale) {
            if ($group->hasUrls) {
                $urlFormatAttributes = ['urlFormat'];
                $groupLocale->urlFormatIsRequired = true;

                if ($group->maxLevels == 1) {
                    $groupLocale->nestedUrlFormat = null;
                } else {
                    $urlFormatAttributes[] = 'nestedUrlFormat';
                    $groupLocale->nestedUrlFormatIsRequired = true;
                }

                foreach ($urlFormatAttributes as $attribute) {
                    if (!$groupLocale->validate([$attribute])) {
                        $group->addError($attribute.'-'.$localeId, $groupLocale->getFirstError($attribute));
                    }
                }
            } else {
                $groupLocale->urlFormat = null;
                $groupLocale->nestedUrlFormat = null;
            }
        }

        // Validate!
        $groupRecord->validate();
        $group->addErrors($groupRecord->getErrors());

        if (!$group->hasErrors()) {

            // Fire a 'beforeSaveGroup' event
            $event = new CategoryGroupEvent([
                'categoryGroup' => $group
            ]);

            $this->trigger(self::EVENT_BEFORE_SAVE_GROUP, $event);

            // Make sure the event is giving us the go ahead
            if (!$event->isValid) {
                return false;
            }

            $transaction = Craft::$app->getDb()->beginTransaction();

            try {
                // Create/update the structure

                if ($isNewCategoryGroup) {
                    $structure = new Structure();
                } else {
                    /** @noinspection PhpUndefinedVariableInspection */
                    $structure = Craft::$app->getStructures()->getStructureById($oldCategoryGroup->structureId);
                }

                $structure->maxLevels = $group->maxLevels;
                Craft::$app->getStructures()->saveStructure($structure);
                $groupRecord->structureId = $structure->id;
                $group->structureId = $structure->id;

                // Is there a new field layout?
                /** @var FieldLayout $fieldLayout */
                $fieldLayout = $group->getFieldLayout();

                if (!$fieldLayout->id) {
                    // Delete the old one
                    /** @noinspection PhpUndefinedVariableInspection */
                    if (!$isNewCategoryGroup && $oldCategoryGroup->fieldLayoutId) {
                        Craft::$app->getFields()->deleteLayoutById($oldCategoryGroup->fieldLayoutId);
                    }

                    // Save the new one
                    Craft::$app->getFields()->saveLayout($fieldLayout);

                    // Update the category group record/model with the new layout ID
                    $groupRecord->fieldLayoutId = $fieldLayout->id;
                    $group->fieldLayoutId = $fieldLayout->id;
                }

                // Save the category group
                $groupRecord->save(false);

                // Now that we have a category group ID, save it on the model
                if (!$group->id) {
                    $group->id = $groupRecord->id;
                }

                // Might as well update our cache of the category group while we have it.
                $this->_categoryGroupsById[$group->id] = $group;

                // Update the categorygroups_i18n table
                $newLocaleData = [];

                if (!$isNewCategoryGroup) {
                    // Get the old category group locales
                    $oldLocales = CategoryGroupLocaleRecord::find()
                        ->where(['groupId' => $group->id])
                        ->indexBy('locale')
                        ->all();

                    foreach ($oldLocales as $key => $value) {
                        $oldLocales[$key] = CategoryGroupLocale::create($value);
                    }

                    $changedLocaleIds = [];
                }

                foreach ($groupLocales as $localeId => $locale) {
                    // Was this already selected?
                    if (!$isNewCategoryGroup && isset($oldLocales[$localeId])) {
                        $oldLocale = $oldLocales[$localeId];

                        // Has the URL format changed?
                        if ($locale->urlFormat != $oldLocale->urlFormat || $locale->nestedUrlFormat != $oldLocale->nestedUrlFormat) {
                            Craft::$app->getDb()->createCommand()
                                ->update(
                                    '{{%categorygroups_i18n}}',
                                    [
                                        'urlFormat' => $locale->urlFormat,
                                        'nestedUrlFormat' => $locale->nestedUrlFormat
                                    ],
                                    [
                                        'id' => $oldLocale->id
                                    ])
                                ->execute();

                            $changedLocaleIds[] = $localeId;
                        }
                    } else {
                        $newLocaleData[] = [
                            $group->id,
                            $localeId,
                            $locale->urlFormat,
                            $locale->nestedUrlFormat
                        ];
                    }
                }

                // Insert the new locales
                Craft::$app->getDb()->createCommand()
                    ->batchInsert(
                        '{{%categorygroups_i18n}}',
                        ['groupId', 'locale', 'urlFormat', 'nestedUrlFormat'],
                        $newLocaleData)
                    ->execute();

                if (!$isNewCategoryGroup) {
                    // Drop any locales that are no longer being used, as well as the associated category/element
                    // locale rows
                    /** @noinspection PhpUndefinedVariableInspection */
                    $droppedLocaleIds = array_diff(array_keys($oldLocales), array_keys($groupLocales));

                    if ($droppedLocaleIds) {
                        Craft::$app->getDb()->createCommand()
                            ->delete(
                                '{{%categorygroups_i18n}}',
                                ['in', 'locale', $droppedLocaleIds])
                            ->execute();
                    }
                }

                // Finally, deal with the existing categories...

                if (!$isNewCategoryGroup) {
                    // Get all of the category IDs in this group
                    $categoryIds = Category::find()
                        ->groupId($group->id)
                        ->status(null)
                        ->limit(null)
                        ->ids();

                    // Should we be deleting
                    /** @noinspection PhpUndefinedVariableInspection */
                    if ($categoryIds && $droppedLocaleIds) {
                        Craft::$app->getDb()->createCommand()
                            ->delete(
                                '{{%elements_i18n}}',
                                [
                                    'and',
                                    ['in', 'elementId', $categoryIds],
                                    ['in', 'locale', $droppedLocaleIds]
                                ])
                            ->execute();
                        Craft::$app->getDb()->createCommand()
                            ->delete(
                                '{{%content}}',
                                [
                                    'and',
                                    ['in', 'elementId', $categoryIds],
                                    ['in', 'locale', $droppedLocaleIds]
                                ])
                            ->execute();
                    }

                    // Are there any locales left?
                    if ($groupLocales) {
                        // Drop the old category URIs if the group no longer has URLs
                        /** @noinspection PhpUndefinedVariableInspection */
                        if (!$group->hasUrls && $oldCategoryGroup->hasUrls) {
                            Craft::$app->getDb()->createCommand()
                                ->update(
                                    '{{%elements_i18n}}',
                                    ['uri' => null],
                                    ['in', 'elementId', $categoryIds])
                                ->execute();
                        } else /** @noinspection PhpUndefinedVariableInspection */
                            if ($changedLocaleIds) {
                                foreach ($categoryIds as $categoryId) {
                                    Craft::$app->getConfig()->maxPowerCaptain();

                                    // Loop through each of the changed locales and update all of the categories’ slugs and
                                    // URIs
                                    foreach ($changedLocaleIds as $localeId) {
                                        $category = Category::find()
                                            ->id($categoryId)
                                            ->locale($localeId)
                                            ->status(null)
                                            ->one();

                                        if ($category) {
                                            Craft::$app->getElements()->updateElementSlugAndUri($category, false, false);
                                        }
                                    }
                                }
                            }
                    }
                }

                $transaction->commit();
            } catch (\Exception $e) {
                $transaction->rollBack();

                throw $e;
            }

            // Fire an 'afterSaveGroup' event
            $this->trigger(self::EVENT_AFTER_SAVE_GROUP,
                new CategoryGroupEvent([
                    'categoryGroup' => $group
                ]));

            return true;
        }

        return false;
    }

    /**
     * Deletes a category group by its ID.
     *
     * @param integer $groupId
     *
     * @return boolean Whether the category group was deleted successfully
     * @throws \Exception if reasons
     */
    public function deleteGroupById($groupId)
    {
        if (!$groupId) {
            return false;
        }

        $group = $this->getGroupById($groupId);

        if (!$group) {
            return false;
        }

        // Fire a 'beforeDeleteGroup' event
        $event = new CategoryGroupEvent([
            'categoryGroup' => $group
        ]);

        $this->trigger(self::EVENT_BEFORE_DELETE_GROUP, $event);

        // Make sure the event is giving us the go ahead
        if (!$event->isValid) {
            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Delete the field layout
            $fieldLayoutId = (new Query())
                ->select('fieldLayoutId')
                ->from('{{%categorygroups}}')
                ->where(['id' => $groupId])
                ->scalar();

            if ($fieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($fieldLayoutId);
            }

            // Grab the category ids so we can clean the elements table.
            $categoryIds = (new Query())
                ->select('id')
                ->from('{{%categories}}')
                ->where(['groupId' => $groupId])
                ->column();

            Craft::$app->getElements()->deleteElementById($categoryIds);

            Craft::$app->getDb()->createCommand()
                ->delete(
                    '{{%categorygroups}}',
                    ['id' => $groupId])
                ->execute();

            $transaction->commit();

            // Fire an 'afterDeleteGroup' event
            $this->trigger(self::EVENT_AFTER_DELETE_GROUP,
                new CategoryGroupEvent([
                    'categoryGroup' => $group
                ]));

            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }
    }

    /**
     * Returns whether a group’s categories have URLs, and if the group’s template path is valid.
     *
     * @param CategoryGroup $group
     *
     * @return boolean
     */
    public function isGroupTemplateValid(CategoryGroup $group)
    {
        if ($group->hasUrls) {
            // Set Craft to the site template mode
            $view = Craft::$app->getView();
            $oldTemplateMode = $view->getTemplateMode();
            $view->setTemplateMode($view::TEMPLATE_MODE_SITE);

            // Does the template exist?
            $templateExists = Craft::$app->getView()->doesTemplateExist($group->template);

            // Restore the original template mode
            $view->setTemplateMode($oldTemplateMode);

            if ($templateExists) {
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
     * @param integer      $categoryId
     * @param integer|null $localeId
     *
     * @return Category|null
     */
    public function getCategoryById($categoryId, $localeId = null)
    {
        return Craft::$app->getElements()->getElementById($categoryId, Category::className(), $localeId);
    }

    /**
     * Saves a category.
     *
     * @param Category $category
     *
     * @return boolean Whether the category was saved successfully
     * @throws CategoryNotFoundException if $category has an invalid $id or invalid $newParentID
     * @throws \Exception if reasons
     */
    public function saveCategory(Category $category)
    {
        $isNewCategory = !$category->id;

        $hasNewParent = $this->_checkForNewParent($category);

        if ($hasNewParent) {
            if ($category->newParentId) {
                $parentCategory = $this->getCategoryById($category->newParentId, $category->locale);

                if (!$parentCategory) {
                    throw new CategoryNotFoundException("No category exists with the ID '{$category->newParentId}'");
                }
            } else {
                $parentCategory = null;
            }

            $category->setParent($parentCategory);
        }

        // Category data
        if (!$isNewCategory) {
            $categoryRecord = CategoryRecord::findOne($category->id);

            if (!$categoryRecord) {
                throw new CategoryNotFoundException("No category exists with the ID '{$category->id}'");
            }
        } else {
            $categoryRecord = new CategoryRecord();
        }

        $categoryRecord->groupId = $category->groupId;

        $categoryRecord->validate();
        $category->addErrors($categoryRecord->getErrors());

        if ($category->hasErrors()) {
            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Fire a 'beforeSaveCategory' event
            $event = new CategoryEvent([
                'category' => $category
            ]);

            $this->trigger(self::EVENT_BEFORE_SAVE_CATEGORY, $event);

            // Is the event giving us the go-ahead?
            if ($event->isValid) {
                $success = Craft::$app->getElements()->saveElement($category);

                // If it didn't work, rollback the transaction in case something changed in onBeforeSaveCategory
                if (!$success) {
                    $transaction->rollBack();

                    return false;
                }

                // Now that we have an element ID, save it on the other stuff
                if ($isNewCategory) {
                    $categoryRecord->id = $category->id;
                }

                $categoryRecord->save(false);

                // Has the parent changed?
                if ($hasNewParent) {
                    if (!$category->newParentId) {
                        Craft::$app->getStructures()->appendToRoot($category->getGroup()->structureId, $category);
                    } else {
                        /** @noinspection PhpUndefinedVariableInspection */
                        Craft::$app->getStructures()->append($category->getGroup()->structureId, $category, $parentCategory);
                    }
                }

                // Update the category's descendants, who may be using this category's URI in their own URIs
                Craft::$app->getElements()->updateDescendantSlugsAndUris($category, true, true);
            } else {
                $success = false;
            }

            // Commit the transaction regardless of whether we saved the category, in case something changed
            // in onBeforeSaveCategory
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        if ($success) {
            // Fire an 'afterSaveCategory' event
            $this->trigger(self::EVENT_AFTER_SAVE_CATEGORY,
                new CategoryEvent([
                    'category' => $category
                ]));
        }

        return $success;
    }

    /**
     * Deletes a category(s).
     *
     * @param Category|Category[] $categories
     *
     * @return boolean Whether the category was deleted successfully
     * @throws \Exception if reasons
     */
    public function deleteCategory($categories)
    {
        if (!$categories) {
            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            if (!is_array($categories)) {
                $categories = [$categories];
            }

            $success = $this->_deleteCategories($categories, true);

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        if ($success) {
            foreach ($categories as $category) {
                // Fire an 'afterDeleteCategory' event
                $this->trigger(self::EVENT_AFTER_DELETE_CATEGORY,
                    new CategoryEvent([
                        'category' => $category
                    ]));
            }

            return true;
        }

        return false;
    }

    /**
     * Deletes an category(s) by its ID.
     *
     * @param integer|integer[] $categoryId
     *
     * @return boolean
     */
    public function deleteCategoryById($categoryId)
    {
        if (!$categoryId) {
            return false;
        }

        $categories = Category::find()
            ->id($categoryId)
            ->limit(null)
            ->status(null)
            ->localeEnabled(false)
            ->all();

        if ($categories) {
            return $this->deleteCategory($categories);
        }

        return false;
    }

    /**
     * Updates a list of category IDs, filling in any gaps in the family tree.
     *
     * @param integer[] $ids The original list of category IDs
     *
     * @return integer[] The list of category IDs with all the gaps filled in.
     */
    public function fillGapsInCategoryIds($ids)
    {
        $completeIds = [];

        if ($ids) {
            // Make sure that for each selected category, all of its parents are also selected.
            $categories = Category::find()
                ->id($ids)
                ->status(null)
                ->localeEnabled(false)
                ->limit(null)
                ->all();

            $prevCategory = null;

            foreach ($categories as $i => $category) {
                // Did we just skip any categories?
                if ($category->level != 1 && (
                        ($i == 0) ||
                        (!$category->isSiblingOf($prevCategory) && !$category->isChildOf($prevCategory))
                    )
                ) {
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

    // Private Methods
    // =========================================================================

    /**
     * Creates a CategoryGroup with attributes from a CategoryGroupRecord.
     *
     * @param CategoryGroupRecord|null $groupRecord
     *
     * @return CategoryGroup|null
     */
    private function _createCategoryGroupFromRecord($groupRecord)
    {
        if (!$groupRecord) {
            return null;
        }

        $group = CategoryGroup::create($groupRecord);

        if ($groupRecord->structure) {
            $group->maxLevels = $groupRecord->structure->maxLevels;
        }

        return $group;
    }

    /**
     * Checks if an category was submitted with a new parent category selected.
     *
     * @param Category $category
     *
     * @return boolean
     */
    private function _checkForNewParent(Category $category)
    {
        // Is it a brand new category?
        if (!$category->id) {
            return true;
        }

        // Was a new parent ID actually submitted?
        if ($category->newParentId === null) {
            return false;
        }

        // Is it set to the top level now, but it hadn't been before?
        if ($category->newParentId === '' && $category->level != 1) {
            return true;
        }

        // Is it set to be under a parent now, but didn't have one before?
        if ($category->newParentId !== '' && $category->level == 1) {
            return true;
        }

        // Is the newParentId set to a different category ID than its previous parent?
        $oldParentId = Category::find()
            ->ancestorOf($category)
            ->ancestorDist(1)
            ->status(null)
            ->locale($category->locale)
            ->localeEnabled(null)
            ->select('id')
            ->scalar();

        if ($category->newParentId != $oldParentId) {
            return true;
        }

        // Must be set to the same one then
        return false;
    }

    /**
     * Deletes categories, and their descendants.
     *
     * @param Category[] $categories
     * @param boolean    $deleteDescendants
     *
     * @return boolean
     */
    private function _deleteCategories($categories, $deleteDescendants = true)
    {
        $categoryIds = [];

        foreach ($categories as $category) {
            if ($deleteDescendants) {
                // Delete the descendants in reverse order, so structures don't get wonky
                $descendants = $category->getDescendants()->status(null)->localeEnabled(false)->orderBy('lft desc')->all();
                $this->_deleteCategories($descendants, false);
            }

            // Fire a 'beforeDeleteCategory' event
            $this->trigger(self::EVENT_BEFORE_DELETE_CATEGORY,
                new CategoryEvent([
                    'category' => $category
                ]));

            $categoryIds[] = $category->id;
        }

        // Delete 'em
        return Craft::$app->getElements()->deleteElementById($categoryIds);
    }
}
