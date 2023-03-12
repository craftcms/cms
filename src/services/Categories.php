<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Category;
use craft\errors\CategoryGroupNotFoundException;
use craft\events\CategoryGroupEvent;
use craft\events\ConfigEvent;
use craft\events\DeleteSiteEvent;
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
use craft\web\View;
use DateTime;
use Throwable;
use yii\base\Component;
use yii\base\Exception;

/**
 * Categories service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getCategories()|`Craft::$app->categories`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Categories extends Component
{
    /**
     * @event CategoryGroupEvent The event that is triggered before a category group is saved.
     */
    public const EVENT_BEFORE_SAVE_GROUP = 'beforeSaveGroup';

    /**
     * @event CategoryGroupEvent The event that is triggered after a category group is saved.
     */
    public const EVENT_AFTER_SAVE_GROUP = 'afterSaveGroup';

    /**
     * @event CategoryGroupEvent The event that is triggered before a category group is deleted.
     */
    public const EVENT_BEFORE_DELETE_GROUP = 'beforeDeleteGroup';

    /**
     * @event CategoryGroupEvent The event that is triggered before a category group delete is applied to the database.
     * @since 3.1.0
     */
    public const EVENT_BEFORE_APPLY_GROUP_DELETE = 'beforeApplyGroupDelete';

    /**
     * @event CategoryGroupEvent The event that is triggered after a category group is deleted.
     */
    public const EVENT_AFTER_DELETE_GROUP = 'afterDeleteGroup';

    /**
     * @var MemoizableArray<CategoryGroup>|null
     * @see _groups()
     */
    private ?MemoizableArray $_groups = null;

    /**
     * Serializer
     */
    public function __serialize()
    {
        $vars = get_object_vars($this);
        unset($vars['_groups']);
        return $vars;
    }

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
     * Returns a memoizable array of all category groups.
     *
     * @return MemoizableArray<CategoryGroup>
     */
    private function _groups(): MemoizableArray
    {
        if (!isset($this->_groups)) {
            $groups = [];

            /** @var CategoryGroupRecord[] $groupRecords */
            $groupRecords = CategoryGroupRecord::find()
                ->orderBy(['name' => SORT_ASC])
                ->with('structure')
                ->all();

            foreach ($groupRecords as $groupRecord) {
                $groups[] = $this->_createCategoryGroupFromRecord($groupRecord);
            }

            $this->_groups = new MemoizableArray($groups);
        }

        return $this->_groups;
    }

    /**
     * Returns all category groups.
     *
     * @return CategoryGroup[]
     */
    public function getAllGroups(): array
    {
        return $this->_groups()->all();
    }

    /**
     * Returns all editable groups.
     *
     * @return CategoryGroup[]
     */
    public function getEditableGroups(): array
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return $this->getAllGroups();
        }

        $user = Craft::$app->getUser()->getIdentity();

        if (!$user) {
            return [];
        }

        return ArrayHelper::where($this->getAllGroups(), function(CategoryGroup $group) use ($user) {
            return $user->can("viewCategories:$group->uid");
        }, true, true, false);
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
    public function getGroupById(int $groupId): ?CategoryGroup
    {
        return $this->_groups()->firstWhere('id', $groupId);
    }

    /**
     * Returns a group by its UID.
     *
     * @param string $uid
     * @return CategoryGroup|null
     * @since 3.1.0
     */
    public function getGroupByUid(string $uid): ?CategoryGroup
    {
        return $this->_groups()->firstWhere('uid', $uid, true);
    }

    /**
     * Returns a group by its handle.
     *
     * @param string $groupHandle
     * @param bool $withTrashed
     * @return CategoryGroup|null
     */
    public function getGroupByHandle(string $groupHandle, bool $withTrashed = false): ?CategoryGroup
    {
        /** @var CategoryGroup|null $group */
        $group = $this->_groups()->firstWhere('handle', $groupHandle, true);

        if (!$group && $withTrashed) {
            /** @var CategoryGroupRecord|null $record */
            $record = CategoryGroupRecord::findWithTrashed()
                ->andWhere(['handle' => $groupHandle])
                ->one();
            if ($record) {
                $group = $this->_createCategoryGroupFromRecord($record);
            }
        }

        return $group;
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
     * @throws Throwable if reasons
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
        }

        // If they've set maxLevels to 0 (don't ask why), then pretend like there are none.
        if ((int)$group->maxLevels === 0) {
            $group->maxLevels = null;
        }

        // Make sure the group isn't missing any site settings
        $allSiteSettings = $group->getSiteSettings();
        foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
            if (!isset($allSiteSettings[$siteId])) {
                throw new Exception('Tried to save a category group that is missing site settings');
            }
        }

        $configPath = ProjectConfig::PATH_CATEGORY_GROUPS . '.' . $group->uid;
        $configData = $group->getConfig();
        Craft::$app->getProjectConfig()->set($configPath, $configData, "Save category group “{$group->handle}”");

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
    public function handleChangedCategoryGroup(ConfigEvent $event): void
    {
        $categoryGroupUid = $event->tokenMatches[0];
        $data = $event->newValue;

        // Make sure fields and sites are processed
        ProjectConfigHelper::ensureAllSitesProcessed();
        ProjectConfigHelper::ensureAllFieldsProcessed();

        $transaction = Craft::$app->getDb()->beginTransaction();

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
            $groupRecord->defaultPlacement = $data['defaultPlacement'] ?? CategoryGroup::DEFAULT_PLACEMENT_END;

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
                Craft::$app->getFields()->saveLayout($layout, false);
                $groupRecord->fieldLayoutId = $layout->id;
            } elseif ($groupRecord->fieldLayoutId) {
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
                /** @var CategoryGroup_SiteSettingsRecord[] $allOldSiteSettingsRecords */
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
                    ->status(null)
                    ->ids();

                // Are there any sites left?
                if (!empty($siteData)) {
                    // Drop the old category URIs for any site settings that don't have URLs
                    if (!empty($sitesNowWithoutUrls)) {
                        Db::update(Table::ELEMENTS_SITES, [
                            'uri' => null,
                        ], [
                            'elementId' => $categoryIds,
                            'siteId' => $sitesNowWithoutUrls,
                        ]);
                    } elseif (!empty($sitesWithNewUriFormats)) {
                        foreach ($categoryIds as $categoryId) {
                            App::maxPowerCaptain();

                            // Loop through each of the changed sites and update all of the categories’ slugs and URIs
                            foreach ($sitesWithNewUriFormats as $siteId) {
                                /** @var Category|null $category */
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
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_groups = null;

        if ($wasTrashed) {
            // Restore the categories that were deleted with the group
            /** @var Category[] $categories */
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

        // Invalidate category caches
        Craft::$app->getElements()->invalidateCachesForElementType(Category::class);
    }

    /**
     * Deletes a category group by its ID.
     *
     * @param int $groupId The category group's ID
     * @return bool Whether the category group was deleted successfully
     * @throws Throwable if reasons
     * @since 3.0.12
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
                'categoryGroup' => $group,
            ]));
        }

        Craft::$app->getProjectConfig()->remove(ProjectConfig::PATH_CATEGORY_GROUPS . '.' . $group->uid, "Delete category group “{$group->handle}”");
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

        if (!isset($categoryGroupSiteSettings[$siteId]) || !$categoryGroupSiteSettings[$siteId]->hasUrls) {
            return false;
        }

        $template = (string)$categoryGroupSiteSettings[$siteId]->template;
        return Craft::$app->getView()->doesTemplateExist($template, View::TEMPLATE_MODE_SITE);
    }

    /**
     * Handle Category group getting deleted
     *
     * @param ConfigEvent $event
     */
    public function handleDeletedCategoryGroup(ConfigEvent $event): void
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
            $elementsTable = Table::ELEMENTS;
            $categoriesTable = Table::CATEGORIES;
            $now = Db::prepareDateForDb(new DateTime());
            $db = Craft::$app->getDb();

            $conditionSql = <<<SQL
[[categories.groupId]] = $group->id AND
[[categories.id]] = [[elements.id]] AND
[[elements.canonicalId]] IS NULL AND
[[elements.revisionId]] IS NULL AND
[[elements.dateDeleted]] IS NULL
SQL;

            if ($db->getIsMysql()) {
                $db->createCommand(<<<SQL
UPDATE $elementsTable [[elements]], $categoriesTable [[categories]] 
SET [[elements.dateDeleted]] = '$now',
  [[categories.deletedWithGroup]] = 1
WHERE $conditionSql
SQL)->execute();
            } else {
                // Not possible to update two tables simultaneously with Postgres
                $db->createCommand(<<<SQL
UPDATE $categoriesTable [[categories]]
SET [[deletedWithGroup]] = TRUE
FROM $elementsTable [[elements]]
WHERE $conditionSql
SQL)->execute();
                $db->createCommand(<<<SQL
UPDATE $elementsTable
SET [[dateDeleted]] = '$now'
FROM $categoriesTable [[categories]]
WHERE $conditionSql
SQL)->execute();
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
        } catch (Throwable $e) {
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

        // Invalidate category caches
        Craft::$app->getElements()->invalidateCachesForElementType(Category::class);
    }

    /**
     * @deprecated in 4.0.5. Unused fields will be pruned automatically as field layouts are resaved.
     */
    public function pruneDeletedField(): void
    {
    }

    /**
     * Prune a deleted site from category group site settings.
     *
     * @param DeleteSiteEvent $event
     */
    public function pruneDeletedSite(DeleteSiteEvent $event): void
    {
        $siteUid = $event->site->uid;

        $projectConfig = Craft::$app->getProjectConfig();
        $categoryGroups = $projectConfig->get(ProjectConfig::PATH_CATEGORY_GROUPS);

        // Loop through the category groups and prune the UID from field layouts.
        if (is_array($categoryGroups)) {
            foreach ($categoryGroups as $categoryGroupUid => $categoryGroup) {
                $projectConfig->remove(ProjectConfig::PATH_CATEGORY_GROUPS . '.' . $categoryGroupUid . '.siteSettings.' . $siteUid, 'Prune deleted site settings');
            }
        }
    }

    // Categories
    // -------------------------------------------------------------------------

    /**
     * Returns a category by its ID.
     *
     * @param int $categoryId
     * @param int|int[]|string|null $siteId
     * @param array $criteria
     * @return Category|null
     */
    public function getCategoryById(int $categoryId, mixed $siteId = null, array $criteria = []): ?Category
    {
        if (!$categoryId) {
            return null;
        }

        // Get the structure ID
        if (!isset($criteria['structureId'])) {
            $criteria['structureId'] = (new Query())
                ->select(['categorygroups.structureId'])
                ->from(['categories' => Table::CATEGORIES])
                ->innerJoin(['categorygroups' => Table::CATEGORYGROUPS], '[[categorygroups.id]] = [[categories.groupId]]')
                ->where(['categories.id' => $categoryId])
                ->scalar();
        }

        // All categories are part of a structure
        if (!$criteria['structureId']) {
            return null;
        }

        return Craft::$app->getElements()->getElementById($categoryId, Category::class, $siteId, $criteria);
    }

    /**
     * Patches an array of categories, filling in any gaps in the tree.
     *
     * @param Category[] $categories
     * @deprecated in 3.6.0. Use [[\craft\services\Structures::fillGapsInElements()]] instead.
     */
    public function fillGapsInCategories(array &$categories): void
    {
        Craft::$app->getStructures()->fillGapsInElements($categories);
    }

    /**
     * Filters an array of categories down to only <= X branches.
     *
     * @param Category[] $categories
     * @param int $branchLimit
     * @deprecated in 3.6.0. Use [[\craft\services\Structures::applyBranchLimitToElements()]] instead.
     */
    public function applyBranchLimitToCategories(array &$categories, int $branchLimit): void
    {
        Craft::$app->getStructures()->applyBranchLimitToElements($categories, $branchLimit);
    }

    /**
     * Creates a CategoryGroup with attributes from a CategoryGroupRecord.
     *
     * @param CategoryGroupRecord|null $groupRecord
     * @return CategoryGroup|null
     */
    private function _createCategoryGroupFromRecord(?CategoryGroupRecord $groupRecord = null): ?CategoryGroup
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
            'defaultPlacement',
            'dateDeleted',
            'uid',
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
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        /** @var CategoryGroupRecord */
        return $query->one() ?? new CategoryGroupRecord();
    }
}
