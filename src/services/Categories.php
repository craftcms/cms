<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\services;

use Craft;
use craft\db\Query;
use craft\elements\Category;
use craft\errors\CategoryGroupNotFoundException;
use craft\events\CategoryGroupEvent;
use craft\models\CategoryGroup;
use craft\models\CategoryGroup_SiteSettings;
use craft\models\FieldLayout;
use craft\models\Structure;
use craft\records\CategoryGroup as CategoryGroupRecord;
use craft\records\CategoryGroup_SiteSettings as CategoryGroup_SiteSettingsRecord;
use yii\base\Component;
use yii\base\Exception;

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
     * @var integer[]
     */
    private $_allGroupIds;

    /**
     * @var integer[]
     */
    private $_editableGroupIds;

    /**
     * @var CategoryGroup[]
     */
    private $_categoryGroupsById;

    /**
     * @var boolean
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
        if ($this->_allGroupIds !== null) {
            return $this->_allGroupIds;
        }

        if ($this->_fetchedAllCategoryGroups) {
            return $this->_allGroupIds = array_keys($this->_categoryGroupsById);
        }

        return $this->_allGroupIds = (new Query())
            ->select(['id'])
            ->from(['{{%categorygroups}}'])
            ->column();
    }

    /**
     * Returns all of the category group IDs that are editable by the current user.
     *
     * @return integer[]
     */
    public function getEditableGroupIds()
    {
        if ($this->_editableGroupIds !== null) {
            return $this->_editableGroupIds;
        }

        $this->_editableGroupIds = [];

        foreach ($this->getAllGroupIds() as $groupId) {
            if (Craft::$app->getUser()->checkPermission('editCategories:'.$groupId)) {
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
    public function getAllGroups()
    {
        if ($this->_fetchedAllCategoryGroups) {
            return array_values($this->_categoryGroupsById);
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
    public function getEditableGroups()
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
     * Returns a group's site settings.
     *
     * @param integer $groupId
     *
     * @return CategoryGroup_SiteSettings[]
     */
    public function getGroupSiteSettings($groupId)
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
     * @param CategoryGroup $group         The category group to be saved
     * @param boolean       $runValidation Whether the category group should be validated
     *
     * @return boolean Whether the category group was saved successfully
     * @throws CategoryGroupNotFoundException if $group has an invalid ID
     * @throws \Exception if reasons
     */
    public function saveGroup(CategoryGroup $group, $runValidation = true)
    {
        if ($runValidation && !$group->validate()) {
            Craft::info('Category group not saved due to validation error.', __METHOD__);

            return false;
        }

        $isNewCategoryGroup = !$group->id;

        // Fire a 'beforeSaveGroup' event
        $this->trigger(self::EVENT_BEFORE_SAVE_GROUP, new CategoryGroupEvent([
            'categoryGroup' => $group,
            'isNew' => $isNewCategoryGroup,
        ]));

        if (!$isNewCategoryGroup) {
            $groupRecord = CategoryGroupRecord::findOne($group->id);

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
        if ($group->maxLevels == 0) {
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

                $siteSettingsRecord->hasUrls = $siteSettings->hasUrls;
                $siteSettingsRecord->uriFormat = $siteSettings->uriFormat;
                $siteSettingsRecord->template = $siteSettings->template;

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
                    ->status(null)
                    ->limit(null)
                    ->ids();

                // Are there any sites left?
                if (!empty($allSiteSettings)) {
                    // Drop the old category URIs for any site settings that don't have URLs
                    if (!empty($sitesNowWithoutUrls)) {
                        $db->createCommand()
                            ->update(
                                '{{%elements_i18n}}',
                                ['uri' => null],
                                [
                                    'elementId' => $categoryIds,
                                    'siteId' => $sitesNowWithoutUrls,
                                ])
                            ->execute();
                    } else if (!empty($sitesWithNewUriFormats)) {
                        foreach ($categoryIds as $categoryId) {
                            Craft::$app->getConfig()->maxPowerCaptain();

                            // Loop through each of the changed sites and update all of the categories’ slugs and
                            // URIs
                            foreach ($sitesWithNewUriFormats as $siteId) {
                                $category = Category::find()
                                    ->id($categoryId)
                                    ->siteId($siteId)
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
        $this->trigger(self::EVENT_AFTER_SAVE_GROUP, new CategoryGroupEvent([
            'categoryGroup' => $group,
            'isNew' => $isNewCategoryGroup,
        ]));

        return true;
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
        $this->trigger(self::EVENT_BEFORE_DELETE_GROUP, new CategoryGroupEvent([
            'categoryGroup' => $group
        ]));

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Delete the field layout
            $fieldLayoutId = (new Query())
                ->select(['fieldLayoutId'])
                ->from(['{{%categorygroups}}'])
                ->where(['id' => $groupId])
                ->scalar();

            if ($fieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($fieldLayoutId);
            }

            // Delete the categories
            $categories = Category::find()
                ->status(null)
                ->enabledForSite(false)
                ->groupId($groupId)
                ->all();

            foreach ($categories as $category) {
                Craft::$app->getElements()->deleteElement($category);
            }

            Craft::$app->getDb()->createCommand()
                ->delete(
                    '{{%categorygroups}}',
                    ['id' => $groupId])
                ->execute();

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterDeleteGroup' event
        $this->trigger(self::EVENT_AFTER_DELETE_GROUP, new CategoryGroupEvent([
            'categoryGroup' => $group
        ]));

        return true;
    }

    /**
     * Returns whether a group’s categories have URLs for the given site ID, and if the group’s template path is valid.
     *
     * @param CategoryGroup $group
     * @param integer       $siteId
     *
     * @return boolean
     */
    public function isGroupTemplateValid(CategoryGroup $group, $siteId)
    {
        $categoryGroupSiteSettings = $group->getSiteSettings();

        if (isset($categoryGroupSiteSettings[$siteId]) && $categoryGroupSiteSettings[$siteId]->hasUrls) {
            // Set Craft to the site template mode
            $view = Craft::$app->getView();
            $oldTemplateMode = $view->getTemplateMode();
            $view->setTemplateMode($view::TEMPLATE_MODE_SITE);

            // Does the template exist?
            $templateExists = Craft::$app->getView()->doesTemplateExist($categoryGroupSiteSettings[$siteId]->template);

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
     * @param integer|null $siteId
     *
     * @return Category|null
     */
    public function getCategoryById($categoryId, $siteId = null)
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
        $query->status(null);
        $query->enabledForSite(false);

        return $query->one();
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

        if (!empty($ids)) {
            // Make sure that for each selected category, all of its parents are also selected.
            $categoryQuery = Category::find();
            $categoryQuery->id($ids);
            $categoryQuery->status(null);
            $categoryQuery->enabledForSite(false);
            $categoryQuery->limit(null);
            $categories = $categoryQuery->all();

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
                    /** @noinspection SlowArrayOperationsInLoopInspection */
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
