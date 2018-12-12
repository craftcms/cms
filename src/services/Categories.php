<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\db\Query;
use craft\elements\Category;
use craft\elements\db\CategoryQuery;
use craft\errors\CategoryGroupNotFoundException;
use craft\events\CategoryGroupEvent;
use craft\helpers\App;
use craft\models\CategoryGroup;
use craft\models\CategoryGroup_SiteSettings;
use craft\models\FieldLayout;
use craft\models\Structure;
use craft\records\CategoryGroup as CategoryGroupRecord;
use craft\records\CategoryGroup_SiteSettings as CategoryGroup_SiteSettingsRecord;
use yii\base\Component;
use yii\base\Exception;

/**
 * Categories service.
 * An instance of the Categories service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getCategories()|`Craft::$app->categories`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Categories extends Component
{
    // Constants
    // =========================================================================

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
     * @var int[]|null
     */
    private $_allGroupIds;

    /**
     * @var int[]|null
     */
    private $_editableGroupIds;

    /**
     * @var CategoryGroup[]|null
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
     * @return int[]
     */
    public function getAllGroupIds(): array
    {
        if ($this->_allGroupIds !== null) {
            return $this->_allGroupIds;
        }

        if ($this->_fetchedAllCategoryGroups) {
            return $this->_allGroupIds = array_keys(array_filter($this->_categoryGroupsById));
        }

        return $this->_allGroupIds = (new Query())
            ->select(['id'])
            ->from(['{{%categorygroups}}'])
            ->column();
    }

    /**
     * Returns all of the category group IDs that are editable by the current user.
     *
     * @return int[]
     */
    public function getEditableGroupIds(): array
    {
        if ($this->_editableGroupIds !== null) {
            return $this->_editableGroupIds;
        }

        $this->_editableGroupIds = [];

        foreach ($this->getAllGroupIds() as $groupId) {
            if (Craft::$app->getUser()->checkPermission('editCategories:' . $groupId)) {
                $this->_editableGroupIds[] = $groupId;
            }
        }

        return $this->_editableGroupIds;
    }

    /**
     * Returns all category groups.
     *
     * @return CategoryGroup[]
     */
    public function getAllGroups(): array
    {
        if ($this->_fetchedAllCategoryGroups) {
            return array_values(array_filter($this->_categoryGroupsById));
        }

        $this->_categoryGroupsById = [];

        /** @var CategoryGroupRecord[] $groupRecords */
        $groupRecords = CategoryGroupRecord::find()
            ->orderBy(['name' => SORT_ASC])
            ->with('structure')
            ->all();

        foreach ($groupRecords as $groupRecord) {
            $this->_categoryGroupsById[$groupRecord->id] = $this->_createCategoryGroupFromRecord($groupRecord);
        }

        $this->_fetchedAllCategoryGroups = true;

        return array_values($this->_categoryGroupsById);
    }

    /**
     * Returns all editable groups.
     *
     * @return CategoryGroup[]
     */
    public function getEditableGroups(): array
    {
        $editableGroupIds = $this->getEditableGroupIds();
        $editableGroups = [];

        foreach ($this->getAllGroups() as $group) {
            if (in_array($group->id, $editableGroupIds, false)) {
                $editableGroups[] = $group;
            }
        }

        return $editableGroups;
    }

    /**
     * Gets the total number of category groups.
     *
     * @return int
     */
    public function getTotalGroups(): int
    {
        return count($this->getAllGroupIds());
    }

    /**
     * Returns a group by its ID.
     *
     * @param int $groupId
     * @return CategoryGroup|null
     */
    public function getGroupById(int $groupId)
    {
        if ($this->_categoryGroupsById !== null && array_key_exists($groupId, $this->_categoryGroupsById)) {
            return $this->_categoryGroupsById[$groupId];
        }

        if ($this->_fetchedAllCategoryGroups) {
            return null;
        }

        $groupRecord = CategoryGroupRecord::find()
            ->where(['id' => $groupId])
            ->with('structure')
            ->one();

        if ($groupRecord === null) {
            return $this->_categoryGroupsById[$groupId] = null;
        }

        /** @var CategoryGroupRecord $groupRecord */
        return $this->_categoryGroupsById[$groupId] = $this->_createCategoryGroupFromRecord($groupRecord);
    }

    /**
     * Returns a group by its handle.
     *
     * @param string $groupHandle
     * @return CategoryGroup|null
     */
    public function getGroupByHandle(string $groupHandle)
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
     * Returns a group's site settings.
     *
     * @param int $groupId
     * @return CategoryGroup_SiteSettings[]
     */
    public function getGroupSiteSettings(int $groupId): array
    {
        $results = CategoryGroup_SiteSettingsRecord::find()
            ->where(['groupId' => $groupId])
            ->all();
        $siteSettings = [];

        foreach ($results as $result) {
            $siteSettings[] = new CategoryGroup_SiteSettings($result->toArray([
                'id',
                'groupId',
                'siteId',
                'hasUrls',
                'uriFormat',
                'template',
            ]));
        }

        return $siteSettings;
    }

    /**
     * Saves a category group.
     *
     * @param CategoryGroup $group The category group to be saved
     * @param bool $runValidation Whether the category group should be validated
     * @return bool Whether the category group was saved successfully
     * @throws CategoryGroupNotFoundException if $group has an invalid ID
     * @throws \Throwable if reasons
     */
    public function saveGroup(CategoryGroup $group, bool $runValidation = true): bool
    {
        $isNewCategoryGroup = !$group->id;

        // Fire a 'beforeSaveGroup' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_GROUP)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_GROUP, new CategoryGroupEvent([
                'categoryGroup' => $group,
                'isNew' => $isNewCategoryGroup,
            ]));
        }

        if ($runValidation && !$group->validate()) {
            Craft::info('Category group not saved due to validation error.', __METHOD__);
            return false;
        }

        if (!$isNewCategoryGroup) {
            $groupRecord = CategoryGroupRecord::find()
                ->where(['id' => $group->id])
                ->one();

            if (!$groupRecord) {
                throw new CategoryGroupNotFoundException("No category group exists with the ID '{$group->id}'");
            }

            $oldCategoryGroup = new CategoryGroup($groupRecord->toArray([
                'id',
                'structureId',
                'fieldLayoutId',
                'name',
                'handle',
            ]));
        } else {
            $groupRecord = new CategoryGroupRecord();
        }

        // If they've set maxLevels to 0 (don't ask why), then pretend like there are none.
        if ((int)$group->maxLevels === 0) {
            $group->maxLevels = null;
        }

        $groupRecord->name = $group->name;
        $groupRecord->handle = $group->handle;

        // Get the site settings
        $allSiteSettings = $group->getSiteSettings();

        // Make sure they're all there
        foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
            if (!isset($allSiteSettings[$siteId])) {
                throw new Exception('Tried to save a category group that is missing site settings');
            }
        }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

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

            // Save the field layout
            /** @var FieldLayout $fieldLayout */
            $fieldLayout = $group->getFieldLayout();
            Craft::$app->getFields()->saveLayout($fieldLayout);
            $groupRecord->fieldLayoutId = $fieldLayout->id;
            $group->fieldLayoutId = $fieldLayout->id;

            // Save the category group
            $groupRecord->save(false);

            // Now that we have a category group ID, save it on the model
            if (!$group->id) {
                $group->id = $groupRecord->id;
            }

            // Might as well update our cache of the category group while we have it.
            $this->_categoryGroupsById[$group->id] = $group;

            // Update the site settings
            // -----------------------------------------------------------------

            $sitesNowWithoutUrls = [];
            $sitesWithNewUriFormats = [];

            if (!$isNewCategoryGroup) {
                // Get the old category group site settings
                $allOldSiteSettingsRecords = CategoryGroup_SiteSettingsRecord::find()
                    ->where(['groupId' => $group->id])
                    ->indexBy('siteId')
                    ->all();
            }

            foreach ($allSiteSettings as $siteId => $siteSettings) {
                // Was this already selected?
                if (!$isNewCategoryGroup && isset($allOldSiteSettingsRecords[$siteId])) {
                    $siteSettingsRecord = $allOldSiteSettingsRecords[$siteId];
                } else {
                    $siteSettingsRecord = new CategoryGroup_SiteSettingsRecord();
                    $siteSettingsRecord->groupId = $group->id;
                    $siteSettingsRecord->siteId = $siteId;
                }

                if ($siteSettingsRecord->hasUrls = $siteSettings->hasUrls) {
                    $siteSettingsRecord->uriFormat = $siteSettings->uriFormat;
                    $siteSettingsRecord->template = $siteSettings->template;
                } else {
                    $siteSettingsRecord->uriFormat = $siteSettings->uriFormat = null;
                    $siteSettingsRecord->template = $siteSettings->template = null;
                }

                if (!$siteSettingsRecord->getIsNewRecord()) {
                    // Did it used to have URLs, but not anymore?
                    if ($siteSettingsRecord->isAttributeChanged('hasUrls', false) && !$siteSettings->hasUrls) {
                        $sitesNowWithoutUrls[] = $siteId;
                    }

                    // Does it have URLs, and has its URI format changed?
                    if ($siteSettings->hasUrls && $siteSettingsRecord->isAttributeChanged('uriFormat', false)) {
                        $sitesWithNewUriFormats[] = $siteId;
                    }
                }

                $siteSettingsRecord->save(false);

                // Set the ID on the model
                $siteSettings->id = $siteSettingsRecord->id;
            }

            if (!$isNewCategoryGroup) {
                // Drop any site settings that are no longer being used, as well as the associated category/element
                // site rows
                $siteIds = array_keys($allSiteSettings);

                /** @noinspection PhpUndefinedVariableInspection */
                foreach ($allOldSiteSettingsRecords as $siteId => $siteSettingsRecord) {
                    if (!in_array($siteId, $siteIds, false)) {
                        $siteSettingsRecord->delete();
                    }
                }
            }

            // Finally, deal with the existing categories...
            // -----------------------------------------------------------------

            if (!$isNewCategoryGroup) {
                // Get all of the category IDs in this group
                $categoryIds = Category::find()
                    ->groupId($group->id)
                    ->anyStatus()
                    ->ids();

                // Are there any sites left?
                if (!empty($allSiteSettings)) {
                    // Drop the old category URIs for any site settings that don't have URLs
                    if (!empty($sitesNowWithoutUrls)) {
                        $db->createCommand()
                            ->update(
                                '{{%elements_sites}}',
                                ['uri' => null],
                                [
                                    'elementId' => $categoryIds,
                                    'siteId' => $sitesNowWithoutUrls,
                                ])
                            ->execute();
                    } else if (!empty($sitesWithNewUriFormats)) {
                        foreach ($categoryIds as $categoryId) {
                            App::maxPowerCaptain();

                            // Loop through each of the changed sites and update all of the categories’ slugs and
                            // URIs
                            foreach ($sitesWithNewUriFormats as $siteId) {
                                $category = Category::find()
                                    ->id($categoryId)
                                    ->siteId($siteId)
                                    ->anyStatus()
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
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterSaveGroup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_GROUP)) {
            $this->trigger(self::EVENT_AFTER_SAVE_GROUP, new CategoryGroupEvent([
                'categoryGroup' => $group,
                'isNew' => $isNewCategoryGroup,
            ]));
        }

        return true;
    }

    /**
     * Deletes a category group by its ID.
     *
     * @param int $groupId The category group's ID
     * @return bool Whether the category group was deleted successfully
     * @throws \Throwable if reasons
     */
    public function deleteGroupById(int $groupId): bool
    {
        if (!$groupId) {
            return false;
        }

        $group = $this->getGroupById($groupId);

        if (!$group) {
            return false;
        }

        return $this->deleteGroup($group);
    }

    /**
     * Deletes a category group.
     *
     * @param CategoryGroup $group The category group
     * @return bool Whether the category group was deleted successfully
     */
    public function deleteGroup(CategoryGroup $group): bool
    {
        // Fire a 'beforeDeleteGroup' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_GROUP)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_GROUP, new CategoryGroupEvent([
                'categoryGroup' => $group
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Delete the field layout
            $fieldLayoutId = (new Query())
                ->select(['fieldLayoutId'])
                ->from(['{{%categorygroups}}'])
                ->where(['id' => $group->id])
                ->scalar();

            if ($fieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($fieldLayoutId);
            }

            // Delete the categories
            $categories = Category::find()
                ->anyStatus()
                ->groupId($group->id)
                ->all();

            foreach ($categories as $category) {
                Craft::$app->getElements()->deleteElement($category);
            }

            Craft::$app->getDb()->createCommand()
                ->delete(
                    '{{%categorygroups}}',
                    ['id' => $group->id])
                ->execute();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterDeleteGroup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_GROUP)) {
            $this->trigger(self::EVENT_AFTER_DELETE_GROUP, new CategoryGroupEvent([
                'categoryGroup' => $group
            ]));
        }

        return true;
    }

    /**
     * Returns whether a group’s categories have URLs for the given site ID, and if the group’s template path is valid.
     *
     * @param CategoryGroup $group
     * @param int $siteId
     * @return bool
     */
    public function isGroupTemplateValid(CategoryGroup $group, int $siteId): bool
    {
        $categoryGroupSiteSettings = $group->getSiteSettings();

        if (isset($categoryGroupSiteSettings[$siteId]) && $categoryGroupSiteSettings[$siteId]->hasUrls) {
            // Set Craft to the site template mode
            $view = Craft::$app->getView();
            $oldTemplateMode = $view->getTemplateMode();
            $view->setTemplateMode($view::TEMPLATE_MODE_SITE);

            // Does the template exist?
            $templateExists = Craft::$app->getView()->doesTemplateExist((string)$categoryGroupSiteSettings[$siteId]->template);

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
     * @param int $categoryId
     * @param int|null $siteId
     * @return Category|null
     */
    public function getCategoryById(int $categoryId, int $siteId = null)
    {
        if (!$categoryId) {
            return null;
        }

        // Get the structure ID
        $structureId = (new Query())
            ->select(['categorygroups.structureId'])
            ->from(['{{%categories}} categories'])
            ->innerJoin('{{%categorygroups}} categorygroups', '[[categorygroups.id]] = [[categories.groupId]]')
            ->where(['categories.id' => $categoryId])
            ->scalar();

        // All categories are part of a structure
        if (!$structureId) {
            return null;
        }

        $query = Category::find();
        $query->id($categoryId);
        $query->structureId($structureId);
        $query->siteId($siteId);
        $query->anyStatus();
        return $query->one();
    }

    /**
     * Patches an array of categories, filling in any gaps in the tree.
     *
     * @param Category[] $categories
     */
    public function fillGapsInCategories(array &$categories)
    {
        /** @var Category|null $prevCategory */
        $prevCategory = null;
        $patchedCategories = [];

        foreach ($categories as $i => $category) {
            // Did we just skip any categories?
            if ($category->level != 1 && (
                    ($i == 0) ||
                    (!$category->isSiblingOf($prevCategory) && !$category->isChildOf($prevCategory))
                )
            ) {
                // Merge in any missing ancestors
                /** @var CategoryQuery $ancestorQuery */
                $ancestorQuery = $category->getAncestors()
                    ->anyStatus();

                if ($prevCategory) {
                    $ancestorQuery->andWhere(['>', 'structureelements.lft', $prevCategory->lft]);
                }

                foreach ($ancestorQuery->all() as $ancestor) {
                    $patchedCategories[] = $ancestor;
                }
            }

            $patchedCategories[] = $category;
            $prevCategory = $category;
        }

        $categories = $patchedCategories;
    }

    /**
     * Filters an array of categories down to only <= X branches.
     *
     * @param Category[] $categories
     * @param int $branchLimit
     */
    public function applyBranchLimitToCategories(array &$categories, int $branchLimit)
    {
        $branchCount = 0;
        $prevCategory = null;

        foreach ($categories as $i => $category) {
            // Is this a new branch?
            if ($prevCategory === null || !$category->isDescendantOf($prevCategory)) {
                $branchCount++;

                // Have we gone over?
                if ($branchCount > $branchLimit) {
                    array_splice($categories, $i);
                    break;
                }
            }

            $prevCategory = $category;
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Creates a CategoryGroup with attributes from a CategoryGroupRecord.
     *
     * @param CategoryGroupRecord|null $groupRecord
     * @return CategoryGroup|null
     */
    private function _createCategoryGroupFromRecord(CategoryGroupRecord $groupRecord = null)
    {
        if (!$groupRecord) {
            return null;
        }

        $group = new CategoryGroup($groupRecord->toArray([
            'id',
            'structureId',
            'fieldLayoutId',
            'name',
            'handle',
        ]));

        if ($groupRecord->structure) {
            $group->maxLevels = $groupRecord->structure->maxLevels;
        }

        return $group;
    }
}
