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
use craft\elements\Category;
use craft\elements\db\CategoryQuery;
use craft\errors\CategoryGroupNotFoundException;
use craft\events\CategoryGroupEvent;
use craft\events\FieldEvent;
use craft\events\ParseConfigEvent;
use craft\helpers\App;
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
     * @event CategoryGroupEvent The event that is triggered after a category group is deleted.
     */
    const EVENT_AFTER_DELETE_GROUP = 'afterDeleteGroup';

    const CONFIG_CATEGORYROUP_KEY = 'categoryGroups';

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
     * @return int[]
     */
    public function getEditableGroupIds(): array
    {
        if ($this->_editableGroupIds !== null) {
            return $this->_editableGroupIds;
        }

        $this->_editableGroupIds = [];

        foreach ($this->getAllGroups() as $group) {
            if (Craft::$app->getUser()->checkPermission('editCategories:'.$group->uid)) {
                $this->_editableGroupIds[] = $group->id;
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

        if ($isNewCategoryGroup) {
            $categoryGroupUid = StringHelper::UUID();
            $structureUid = StringHelper::UUID();
        } else {
            $existingGroupRecord = CategoryGroupRecord::find()
                ->where(['id' => $group->id])
                ->one();

            if (!$existingGroupRecord) {
                throw new CategoryGroupNotFoundException("No category group exists with the ID '{$group->id}'");
            }

            $categoryGroupUid = Db::uidById('{{%categorygroups}}', $group->id);
            $structureUid = Db::uidById('{{%structures}}', $existingGroupRecord->structureId);
        }

        if (!$categoryGroupUid) {
            throw new CategoryGroupNotFoundException("No category group exists with the ID '{$group->id}'");
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
                $layoutUid = Db::uidById('{{%fieldlayouts}}', $fieldLayout->id);
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
            $siteUid = Db::uidById('{{%sites}}', $siteId);
            $configData['siteSettings'][$siteUid] = [
                'hasUrls' => $settings['hasUrls'],
                'uriFormat' => $settings['uriFormat'],
                'template' => $settings['template'],
            ];
        }

        $configPath = self::CONFIG_CATEGORYROUP_KEY.'.'.$categoryGroupUid;
        $projectConfig->save($configPath, $configData);

        if ($isNewCategoryGroup) {
            $group->id = Db::idByUid('{{%categorygroups}}', $categoryGroupUid);
        }

        // Might as well update our cache of the category group while we have it.
        $this->_categoryGroupsById[$group->id] = $group;

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
     * Handle category group change
     *
     * @param ParseConfigEvent $event
     */
    public function handleChangedCategoryGroup(ParseConfigEvent $event)
    {
        $path = $event->configPath;

        // Does it match a category group?
        if (preg_match('/' . self::CONFIG_CATEGORYROUP_KEY . '\.(' . ProjectConfig::UID_PATTERN . ')$/i', $path, $matches)) {

            $categoryGroupUid = $matches[1];
            $data = $event->configData;

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
                $groupRecord = $this->_getCategoryGroupRecord($categoryGroupUid);
                $groupRecord->name = $data['name'];
                $groupRecord->handle = $data['handle'];
                $groupRecord->uid = $categoryGroupUid;

                $isNewRecord = $groupRecord->getIsNewRecord();

                // Structure
                $structure = Craft::$app->getStructures()->getStructureByUid($structureUid) ?? new Structure();
                $structure->maxLevels = $structureData['maxLevels'];
                Craft::$app->getStructures()->saveStructure($structure);

                $groupRecord->structureId = $structure->id;

                // Save the field layout
                if (!empty($data['fieldLayouts'])) {
                    $fields = Craft::$app->getFields();

                    // Delete the field layout
                    if ($groupRecord->fieldLayoutId) {
                        $fields->deleteLayoutById($groupRecord->fieldLayoutId);
                    }

                    //Create the new layout
                    $layout = FieldLayout::createFromConfig(reset($data['fieldLayouts']));
                    $layout->type = Category::class;
                    $layout->uid = key($data['fieldLayouts']);
                    $fields->saveLayout($layout);
                    $groupRecord->fieldLayoutId = $layout->id;
                } else {
                    $groupRecord->fieldLayoutId = null;
                }

                // Save the category group
                $groupRecord->save(false);


                // Update the site settings
                // -----------------------------------------------------------------

                $sitesNowWithoutUrls = [];
                $sitesWithNewUriFormats = [];

                if (!$isNewRecord) {
                    // Get the old category group site settings
                    $allOldSiteSettingsRecords = CategoryGroup_SiteSettingsRecord::find()
                        ->where(['groupId' => $groupRecord->id])
                        ->indexBy('siteId')
                        ->all();
                }

                $siteIdMap = Db::idsByUids('{{%sites}}', array_keys($siteData));

                foreach ($siteData as $siteUid => $siteSettings) {
                    $siteId = $siteIdMap[$siteUid];

                    // Was this already selected?
                    if (!$isNewRecord && isset($allOldSiteSettingsRecords[$siteId])) {
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

                if (!$isNewRecord) {
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

                if (!$isNewRecord) {
                    // Get all of the category IDs in this group
                    $categoryIds = Category::find()
                        ->groupId($groupRecord->id)
                        ->status(null)
                        ->limit(null)
                        ->ids();

                    // Are there any sites left?
                    if (!empty($siteData)) {
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
            } catch (\Throwable $e) {
                $transaction->rollBack();

                throw $e;
            }
        }
    }

    /**
     * Deletes a category group by its ID.
     *
     * @param int $groupId
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

        // Fire a 'beforeDeleteGroup' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_GROUP)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_GROUP, new CategoryGroupEvent([
                'categoryGroup' => $group
            ]));
        }

        Craft::$app->getProjectConfig()->save(self::CONFIG_CATEGORYROUP_KEY.'.'.$group->uid, null);

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

    /**
     * Handle Category group getting deleted
     *
     * @param ParseConfigEvent $event
     */
    public function handleDeletedCategoryGroup (ParseConfigEvent $event) {
        $path = $event->configPath;

        // Does it match a category group?
        if (preg_match('/'.self::CONFIG_CATEGORYROUP_KEY.'\.('.ProjectConfig::UID_PATTERN.')$/i', $path, $matches)) {
            $uid = $matches[1];

            $categoryGroup = $groupRecord = $this->_getCategoryGroupRecord($uid);

            $transaction = Craft::$app->getDb()->beginTransaction();
            try {
                // Delete the field layout
                $fieldLayoutId = (new Query())
                    ->select(['fieldLayoutId'])
                    ->from(['{{%categorygroups}}'])
                    ->where(['id' => $categoryGroup->id])
                    ->scalar();

                if ($fieldLayoutId) {
                    Craft::$app->getFields()->deleteLayoutById($fieldLayoutId);
                }

                // Delete the tags
                $categories = Category::find()
                    ->status(null)
                    ->enabledForSite(false)
                    ->groupId($categoryGroup->id)
                    ->all();

                foreach ($categories as $category) {
                    Craft::$app->getElements()->deleteElement($category);
                }

                Craft::$app->getDb()->createCommand()
                    ->delete('{{%categorygroups}}', ['id' => $categoryGroup->id])
                    ->execute();

                $transaction->commit();
            } catch (\Throwable $e) {
                $transaction->rollBack();

                throw $e;
            }
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

        $fieldPruned = false;
        $projectConfig = Craft::$app->getProjectConfig();
        $categoryGroups = $projectConfig->get(self::CONFIG_CATEGORYROUP_KEY);

        // Loop through the categories and see if the UID exists in the field layouts.
        foreach ($categoryGroups as &$categoryGroup) {
            if (!empty($categoryGroup['fieldLayouts'])) {
                foreach ($categoryGroup['fieldLayouts'] as &$layout) {
                    if (!empty($layout['tabs'])) {
                        foreach ($layout['tabs'] as &$tab) {
                            if (!empty($tab['fields'])) {
                                // Remove the straggler.
                                if (array_key_exists($fieldUid, $tab['fields'])) {
                                    unset($tab['fields'][$fieldUid]);
                                    $fieldPruned = true;
                                    // If last field, just remove field layouts entry altogether.
                                    if (empty($tab['fields'])) {
                                        unset($categoryGroup['fieldLayouts']);
                                        break 2;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($fieldPruned) {
            $projectConfig->save(self::CONFIG_CATEGORYROUP_KEY, $categoryGroups, true);
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
        $query->status(null);
        $query->enabledForSite(false);

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
                    ->status(null)
                    ->enabledForSite(false);

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
     * @return CategoryGroupRecord
     */
    private function _getCategoryGroupRecord(string $uid): CategoryGroupRecord
    {
        return CategoryGroupRecord::findOne(['uid' => $uid]) ?? new CategoryGroupRecord();
    }

}
