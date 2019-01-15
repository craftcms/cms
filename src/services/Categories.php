<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Field;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Category;
use craft\elements\db\CategoryQuery;
use craft\errors\CategoryGroupNotFoundException;
use craft\events\CategoryGroupEvent;
use craft\events\ConfigEvent;
use craft\events\DeleteSiteEvent;
use craft\events\FieldEvent;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
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
     * @event CategoryGroupEvent The event that is triggered before a category group delete is applied to the database.
     */
    const EVENT_BEFORE_APPLY_GROUP_DELETE = 'beforeApplyGroupDelete';

    /**
     * @event CategoryGroupEvent The event that is triggered after a category group is deleted.
     */
    const EVENT_AFTER_DELETE_GROUP = 'afterDeleteGroup';

    const CONFIG_CATEGORYROUP_KEY = 'categoryGroups';

    // Properties
    // =========================================================================

    /**
     * @var CategoryGroup[]
     */
    private $_groups;

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
        return ArrayHelper::getColumn($this->getAllGroups(), 'id');
    }

    /**
     * Returns all of the category group IDs that are editable by the current user.
     *
     * @return int[]
     */
    public function getEditableGroupIds(): array
    {
        return ArrayHelper::getColumn($this->getEditableGroups(), 'id');
    }

    /**
     * Returns all category groups.
     *
     * @return CategoryGroup[]
     */
    public function getAllGroups(): array
    {
        if ($this->_groups !== null) {
            return $this->_groups;
        }

        $this->_groups = [];

        /** @var CategoryGroupRecord[] $groupRecords */
        $groupRecords = CategoryGroupRecord::find()
            ->orderBy(['name' => SORT_ASC])
            ->with('structure')
            ->all();

        foreach ($groupRecords as $groupRecord) {
            $this->_groups[] = $this->_createCategoryGroupFromRecord($groupRecord);
        }

        return $this->_groups;
    }

    /**
     * Returns all editable groups.
     *
     * @return CategoryGroup[]
     */
    public function getEditableGroups(): array
    {
        $userSession = Craft::$app->getUser();
        return ArrayHelper::filterByValue($this->getAllGroups(), function(CategoryGroup $group) use ($userSession) {
            return $userSession->checkPermission('editCategories:' . $group->uid);
        });
    }

    /**
     * Gets the total number of category groups.
     *
     * @return int
     */
    public function getTotalGroups(): int
    {
        return count($this->getAllGroups());
    }

    /**
     * Returns a group by its ID.
     *
     * @param int $groupId
     * @return CategoryGroup|null
     */
    public function getGroupById(int $groupId)
    {
        return ArrayHelper::firstWhere($this->getAllGroups(), 'id', $groupId);
    }

    /**
     * Returns a group by its UID.
     *
     * @param string $uid
     * @return CategoryGroup|null
     */
    public function getGroupByUid(string $uid)
    {
        return ArrayHelper::firstWhere($this->getAllGroups(), 'uid', $uid, true);
    }

    /**
     * Returns a group by its handle.
     *
     * @param string $groupHandle
     * @return CategoryGroup|null
     */
    public function getGroupByHandle(string $groupHandle)
    {
        return ArrayHelper::firstWhere($this->getAllGroups(), 'handle', $groupHandle, true);
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

        if ($isNewCategoryGroup) {
            $group->uid = StringHelper::UUID();
            $structureUid = StringHelper::UUID();
        } else {
            $existingGroupRecord = CategoryGroupRecord::find()
                ->where(['id' => $group->id])
                ->one();

            if (!$existingGroupRecord) {
                throw new CategoryGroupNotFoundException("No category group exists with the ID '{$group->id}'");
            }

            $group->uid = $existingGroupRecord->uid;
            $structureUid = Db::uidById(Table::STRUCTURES, $existingGroupRecord->structureId);
        }

        // If they've set maxLevels to 0 (don't ask why), then pretend like there are none.
        if ((int)$group->maxLevels === 0) {
            $group->maxLevels = null;
        }

        $projectConfig = Craft::$app->getProjectConfig();
        $configData = [
            'name' => $group->name,
            'handle' => $group->handle,
            'structure' => [
                'uid' => $structureUid,
                'maxLevels' => $group->maxLevels,
            ],
            'siteSettings' => []
        ];

        $fieldLayout = $group->getFieldLayout();
        $fieldLayoutConfig = $fieldLayout->getConfig();

        if ($fieldLayoutConfig) {
            if (empty($fieldLayout->id)) {
                $layoutUid = StringHelper::UUID();
                $fieldLayout->uid = $layoutUid;
            } else {
                $layoutUid = Db::uidById(Table::FIELDLAYOUTS, $fieldLayout->id);
            }

            $configData['fieldLayouts'] = [
                $layoutUid => $fieldLayoutConfig
            ];
        }

        // Get the site settings
        $allSiteSettings = $group->getSiteSettings();

        // Make sure they're all there
        foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
            if (!isset($allSiteSettings[$siteId])) {
                throw new Exception('Tried to save a category group that is missing site settings');
            }
        }

        foreach ($allSiteSettings as $siteId => $settings) {
            $siteUid = Db::uidById(Table::SITES, $siteId);
            $configData['siteSettings'][$siteUid] = [
                'hasUrls' => $settings['hasUrls'],
                'uriFormat' => $settings['uriFormat'],
                'template' => $settings['template'],
            ];
        }

        $configPath = self::CONFIG_CATEGORYROUP_KEY . '.' . $group->uid;
        $projectConfig->set($configPath, $configData);

        if ($isNewCategoryGroup) {
            $group->id = Db::idByUid(Table::CATEGORYGROUPS, $group->uid);
        }

        return true;
    }

    /**
     * Handle category group change
     *
     * @param ConfigEvent $event
     */
    public function handleChangedCategoryGroup(ConfigEvent $event)
    {
        $categoryGroupUid = $event->tokenMatches[0];
        $data = $event->newValue;

        // Make sure fields and sites are processed
        ProjectConfigHelper::ensureAllSitesProcessed();
        ProjectConfigHelper::ensureAllFieldsProcessed();

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            $structureData = $data['structure'];
            $siteData = $data['siteSettings'];
            $structureUid = $structureData['uid'];

            // Basic data
            $groupRecord = $this->_getCategoryGroupRecord($categoryGroupUid, true);
            $isNewCategoryGroup = $groupRecord->getIsNewRecord();

            $groupRecord->name = $data['name'];
            $groupRecord->handle = $data['handle'];
            $groupRecord->uid = $categoryGroupUid;

            // Structure
            $structuresService = Craft::$app->getStructures();
            $structure = $structuresService->getStructureByUid($structureUid, true) ?? new Structure(['uid' => $structureUid]);
            $structure->maxLevels = $structureData['maxLevels'];
            $structuresService->saveStructure($structure);

            $groupRecord->structureId = $structure->id;

            // Save the field layout
            if (!empty($data['fieldLayouts'])) {
                // Save the field layout
                $layout = FieldLayout::createFromConfig(reset($data['fieldLayouts']));
                $layout->id = $groupRecord->fieldLayoutId;
                $layout->type = Category::class;
                $layout->uid = key($data['fieldLayouts']);
                Craft::$app->getFields()->saveLayout($layout);
                $groupRecord->fieldLayoutId = $layout->id;
            } else if ($groupRecord->fieldLayoutId) {
                // Delete the field layout
                Craft::$app->getFields()->deleteLayoutById($groupRecord->fieldLayoutId);
                $groupRecord->fieldLayoutId = null;
            }

            // Save the category group
            if ($wasTrashed = (bool)$groupRecord->dateDeleted) {
                $groupRecord->restore();
            } else {
                $groupRecord->save(false);
            }

            // Update the site settings
            // -----------------------------------------------------------------

            $sitesNowWithoutUrls = [];
            $sitesWithNewUriFormats = [];

            if (!$isNewCategoryGroup) {
                // Get the old category group site settings
                $allOldSiteSettingsRecords = CategoryGroup_SiteSettingsRecord::find()
                    ->where(['groupId' => $groupRecord->id])
                    ->indexBy('siteId')
                    ->all();
            }

            $siteIdMap = Db::idsByUids(Table::SITES, array_keys($siteData));

            foreach ($siteData as $siteUid => $siteSettings) {
                $siteId = $siteIdMap[$siteUid];

                // Was this already selected?
                if (!$isNewCategoryGroup && isset($allOldSiteSettingsRecords[$siteId])) {
                    $siteSettingsRecord = $allOldSiteSettingsRecords[$siteId];
                } else {
                    $siteSettingsRecord = new CategoryGroup_SiteSettingsRecord();
                    $siteSettingsRecord->groupId = $groupRecord->id;
                    $siteSettingsRecord->siteId = $siteId;
                }

                if ($siteSettingsRecord->hasUrls = $siteSettings['hasUrls']) {
                    $siteSettingsRecord->uriFormat = $siteSettings['uriFormat'];
                    $siteSettingsRecord->template = $siteSettings['template'];
                } else {
                    $siteSettingsRecord->uriFormat = null;
                    $siteSettingsRecord->template = null;
                }

                if (!$siteSettingsRecord->getIsNewRecord()) {
                    // Did it used to have URLs, but not anymore?
                    if ($siteSettingsRecord->isAttributeChanged('hasUrls', false) && !$siteSettings['hasUrls']) {
                        $sitesNowWithoutUrls[] = $siteId;
                    }

                    // Does it have URLs, and has its URI format changed?
                    if ($siteSettings['hasUrls'] && $siteSettingsRecord->isAttributeChanged('uriFormat', false)) {
                        $sitesWithNewUriFormats[] = $siteId;
                    }
                }

                $siteSettingsRecord->save(false);
            }

            if (!$isNewCategoryGroup) {
                // Drop any site settings that are no longer being used, as well as the associated category/element
                // site rows
                $affectedSiteUids = array_keys($siteData);

                /** @noinspection PhpUndefinedVariableInspection */
                foreach ($allOldSiteSettingsRecords as $siteId => $siteSettingsRecord) {
                    $siteUid = array_search($siteId, $siteIdMap, false);
                    if (!in_array($siteUid, $affectedSiteUids, false)) {
                        $siteSettingsRecord->delete();
                    }
                }
            }

            // Finally, deal with the existing categories...
            // -----------------------------------------------------------------

            if (!$isNewCategoryGroup) {
                // Get all of the category IDs in this group
                $categoryIds = Category::find()
                    ->groupId($groupRecord->id)
                    ->anyStatus()
                    ->ids();

                // Are there any sites left?
                if (!empty($siteData)) {
                    // Drop the old category URIs for any site settings that don't have URLs
                    if (!empty($sitesNowWithoutUrls)) {
                        $db->createCommand()
                            ->update(
                                Table::ELEMENTS_SITES,
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

        // Clear caches
        $this->_groups = null;

        if ($wasTrashed) {
            // Restore the categories that were deleted with the group
            $categories = Category::find()
                ->groupId($groupRecord->id)
                ->trashed()
                ->andWhere(['categories.deletedWithGroup' => true])
                ->all();
            Craft::$app->getElements()->restoreElements($categories);
        }

        // Fire an 'afterSaveGroup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_GROUP)) {
            $this->trigger(self::EVENT_AFTER_SAVE_GROUP, new CategoryGroupEvent([
                'categoryGroup' => $this->getGroupById($groupRecord->id),
                'isNew' => $isNewCategoryGroup,
            ]));
        }
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

        Craft::$app->getProjectConfig()->remove(self::CONFIG_CATEGORYROUP_KEY . '.' . $group->uid);
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

    /**
     * Handle Category group getting deleted
     *
     * @param ConfigEvent $event
     */
    public function handleDeletedCategoryGroup(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];
        $categoryGroupRecord = $this->_getCategoryGroupRecord($uid);

        if (!$categoryGroupRecord->id) {
            return;
        }

        /** @var CategoryGroup $group */
        $group = $this->getGroupById($categoryGroupRecord->id);

        // Fire a 'beforeApplyGroupDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_GROUP_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_GROUP_DELETE, new CategoryGroupEvent([
                'categoryGroup' => $group,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Delete the categories
            $categories = Category::find()
                ->anyStatus()
                ->groupId($categoryGroupRecord->id)
                ->all();
            $elementsService = Craft::$app->getElements();

            foreach ($categories as $category) {
                $category->deletedWithGroup = true;
                $elementsService->deleteElement($category);
            }

            // Delete the structure
            Craft::$app->getStructures()->deleteStructureById($categoryGroupRecord->structureId);

            // Delete the field layout
            if ($categoryGroupRecord->fieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($categoryGroupRecord->fieldLayoutId);
            }

            // Delete the category group
            Craft::$app->getDb()->createCommand()
                ->softDelete(Table::CATEGORYGROUPS, ['id' => $categoryGroupRecord->id])
                ->execute();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_groups = null;

        // Fire an 'afterDeleteGroup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_GROUP)) {
            $this->trigger(self::EVENT_AFTER_DELETE_GROUP, new CategoryGroupEvent([
                'categoryGroup' => $group,
            ]));
        }
    }

    /**
     * Prune a deleted field from category group layouts.
     *
     * @param FieldEvent $event
     */
    public function pruneDeletedField(FieldEvent $event)
    {
        /** @var Field $field */
        $field = $event->field;
        $fieldUid = $field->uid;

        $projectConfig = Craft::$app->getProjectConfig();
        $categoryGroups = $projectConfig->get(self::CONFIG_CATEGORYROUP_KEY);

        // Loop through the category groups and prune the UID from field layouts.
        if (is_array($categoryGroups)) {
            foreach ($categoryGroups as $categoryGroupUid => $categoryGroup) {
                if (!empty($categoryGroup['fieldLayouts'])) {
                    foreach ($categoryGroup['fieldLayouts'] as $layoutUid => $layout) {
                        if (!empty($layout['tabs'])) {
                            foreach ($layout['tabs'] as $tabUid => $tab) {
                                $projectConfig->remove(self::CONFIG_CATEGORYROUP_KEY . '.' . $categoryGroupUid . '.fieldLayouts.' . $layoutUid . '.tabs.' . $tabUid . '.fields.' . $fieldUid);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Prune a deleted site from category group site settings.
     *
     * @param DeleteSiteEvent $event
     */
    public function pruneDeletedSite(DeleteSiteEvent $event)
    {
        $siteUid = $event->site->uid;

        $projectConfig = Craft::$app->getProjectConfig();
        $categoryGroups = $projectConfig->get(self::CONFIG_CATEGORYROUP_KEY);

        // Loop through the category groups and prune the UID from field layouts.
        if (is_array($categoryGroups)) {
            foreach ($categoryGroups as $categoryGroupUid => $categoryGroup) {
                $projectConfig->remove(self::CONFIG_CATEGORYROUP_KEY . '.' . $categoryGroupUid . '.siteSettings.' . $siteUid);
            }
        }
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
            'uid'
        ]));

        if ($groupRecord->structure) {
            $group->maxLevels = $groupRecord->structure->maxLevels;
        }

        return $group;
    }

    /**
     * Gets a category group's record by uid.
     *
     * @param string $uid
     * @param bool $withTrashed Whether to include trashed category groups in search
     * @return CategoryGroupRecord
     */
    private function _getCategoryGroupRecord(string $uid, bool $withTrashed = false): CategoryGroupRecord
    {
        $query = $withTrashed ? CategoryGroupRecord::findWithTrashed() : CategoryGroupRecord::find();
        $query->andWhere(['uid' => $uid]);
        return $query->one() ?? new CategoryGroupRecord();
    }
}
