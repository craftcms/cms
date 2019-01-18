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
use craft\elements\Tag;
use craft\errors\TagGroupNotFoundException;
use craft\events\ConfigEvent;
use craft\events\FieldEvent;
use craft\events\TagGroupEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\models\TagGroup;
use craft\records\TagGroup as TagGroupRecord;
use yii\base\Component;

/**
 * Tags service.
 * An instance of the Tags service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getTags()|`Craft::$app->tags`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Tags extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event TagGroupEvent The event that is triggered before a tag group is saved.
     */
    const EVENT_BEFORE_SAVE_GROUP = 'beforeSaveGroup';

    /**
     * @event TagGroupEvent The event that is triggered after a tag group is saved.
     */
    const EVENT_AFTER_SAVE_GROUP = 'afterSaveGroup';

    /**
     * @event TagGroupEvent The event that is triggered before a tag group is deleted.
     */
    const EVENT_BEFORE_DELETE_GROUP = 'beforeDeleteGroup';

    /**
     * @event TagGroupEvent The event that is triggered before a tag group delete is applied to the database.
     */
    const EVENT_BEFORE_APPLY_GROUP_DELETE = 'beforeApplyGroupDelete';

    /**
     * @event TagGroupEvent The event that is triggered after a tag group is deleted.
     */
    const EVENT_AFTER_DELETE_GROUP = 'afterDeleteGroup';

    const CONFIG_TAGGROUP_KEY = 'tagGroups';

    // Properties
    // =========================================================================

    /**
     * @var TagGroup[]
     */
    private $_tagGroups;

    // Public Methods
    // =========================================================================

    // Tag groups
    // -------------------------------------------------------------------------

    /**
     * Returns all of the group IDs.
     *
     * @return array
     */
    public function getAllTagGroupIds(): array
    {
        return ArrayHelper::getColumn($this->getAllTagGroups(), 'id');
    }

    /**
     * Returns all tag groups.
     *
     * @return TagGroup[]
     */
    public function getAllTagGroups(): array
    {
        if ($this->_tagGroups !== null) {
            return $this->_tagGroups;
        }

        $this->_tagGroups = [];
        $records = TagGroupRecord::find()
            ->orderBy(['name' => SORT_ASC])
            ->all();

        foreach ($records as $record) {
            $this->_tagGroups[] = new TagGroup($record->toArray([
                'id',
                'name',
                'handle',
                'fieldLayoutId',
                'uid',
            ]));
        }

        return $this->_tagGroups;
    }

    /**
     * Gets the total number of tag groups.
     *
     * @return int
     */
    public function getTotalTagGroups(): int
    {
        return count($this->getAllTagGroups());
    }

    /**
     * Returns a group by its ID.
     *
     * @param int $groupId
     * @return TagGroup|null
     */
    public function getTagGroupById(int $groupId)
    {
        return ArrayHelper::firstWhere($this->getAllTagGroups(), 'id', $groupId);
    }

    /**
     * Returns a group by its UID.
     *
     * @param string $groupUid
     * @return TagGroup|null
     */
    public function getTagGroupByUid(string $groupUid)
    {
        return ArrayHelper::firstWhere($this->getAllTagGroups(), 'uid', $groupUid, true);
    }


    /**
     * Gets a group by its handle.
     *
     * @param string $groupHandle
     * @return TagGroup|null
     */
    public function getTagGroupByHandle(string $groupHandle)
    {
        return ArrayHelper::firstWhere($this->getAllTagGroups(), 'handle', $groupHandle, true);
    }

    /**
     * Saves a tag group.
     *
     * @param TagGroup $tagGroup The tag group to be saved
     * @param bool $runValidation Whether the tag group should be validated
     * @return bool Whether the tag group was saved successfully
     * @throws TagGroupNotFoundException if $tagGroup->id is invalid
     * @throws \Throwable if reasons
     */
    public function saveTagGroup(TagGroup $tagGroup, bool $runValidation = true): bool
    {
        $isNewTagGroup = !$tagGroup->id;

        // Fire a 'beforeSaveGroup' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_GROUP)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_GROUP, new TagGroupEvent([
                'tagGroup' => $tagGroup,
                'isNew' => $isNewTagGroup
            ]));
        }

        if ($runValidation && !$tagGroup->validate()) {
            Craft::info('Tag group not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNewTagGroup) {
            $tagGroup->uid = StringHelper::UUID();
        } else if (!$tagGroup->uid) {
            $tagGroup->uid = Db::uidById(Table::TAGGROUPS, $tagGroup->id);
        }

        $projectConfig = Craft::$app->getProjectConfig();
        $configData = [
            'name' => $tagGroup->name,
            'handle' => $tagGroup->handle,
        ];

        $fieldLayout = $tagGroup->getFieldLayout();
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

        $configPath = self::CONFIG_TAGGROUP_KEY . '.' . $tagGroup->uid;
        $projectConfig->set($configPath, $configData);

        if ($isNewTagGroup) {
            $tagGroup->id = Db::idByUid(Table::TAGGROUPS, $tagGroup->uid);
        }

        return true;
    }

    /**
     * Handle tag group change
     *
     * @param ConfigEvent $event
     */
    public function handleChangedTagGroup(ConfigEvent $event)
    {
        $tagGroupUid = $event->tokenMatches[0];
        $data = $event->newValue;

        // Make sure fields are processed
        ProjectConfigHelper::ensureAllFieldsProcessed();

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $tagGroupRecord = $this->_getTagGroupRecord($tagGroupUid, true);
            $isNewTagGroup = $tagGroupRecord->getIsNewRecord();

            $tagGroupRecord->name = $data['name'];
            $tagGroupRecord->handle = $data['handle'];
            $tagGroupRecord->uid = $tagGroupUid;

            if (!empty($data['fieldLayouts'])) {
                // Save the field layout
                $layout = FieldLayout::createFromConfig(reset($data['fieldLayouts']));
                $layout->id = $tagGroupRecord->fieldLayoutId;
                $layout->type = Tag::class;
                $layout->uid = key($data['fieldLayouts']);
                Craft::$app->getFields()->saveLayout($layout);
                $tagGroupRecord->fieldLayoutId = $layout->id;
            } else if ($tagGroupRecord->fieldLayoutId) {
                // Delete the field layout
                Craft::$app->getFields()->deleteLayoutById($tagGroupRecord->fieldLayoutId);
                $tagGroupRecord->fieldLayoutId = null;
            }

            // Save the tag group
            if ($wasTrashed = (bool)$tagGroupRecord->dateDeleted) {
                $tagGroupRecord->restore();
            } else {
                $tagGroupRecord->save(false);
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_tagGroups = null;

        if ($wasTrashed) {
            // Restore the tags that were deleted with the group
            $tags = Tag::find()
                ->groupId($tagGroupRecord->id)
                ->trashed()
                ->andWhere(['tags.deletedWithGroup' => true])
                ->all();
            Craft::$app->getElements()->restoreElements($tags);
        }

        // Fire an 'afterSaveGroup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_GROUP)) {
            $this->trigger(self::EVENT_AFTER_SAVE_GROUP, new TagGroupEvent([
                'tagGroup' => $this->getTagGroupById($tagGroupRecord->id),
                'isNew' => $isNewTagGroup,
            ]));
        }
    }

    /**
     * Deletes a tag group by its ID.
     *
     * @param int $groupId The tag group's ID
     * @return bool Whether the tag group was deleted successfully
     * @throws \Throwable if reasons
     */
    public function deleteTagGroupById(int $groupId): bool
    {
        if (!$groupId) {
            return false;
        }

        $group = $this->getTagGroupById($groupId);

        if (!$group) {
            return false;
        }

        return $this->deleteTagGroup($group);
    }

    /**
     * Deletes a tag group.
     *
     * @param TagGroup $tagGroup The tag group
     * @return bool Whether the tag group was deleted successfully
     * @throws \Throwable if reasons
     */
    public function deleteTagGroup(TagGroup $tagGroup): bool
    {
        if (!$tagGroup) {
            return false;
        }

        // Fire a 'beforeDeleteGroup' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_GROUP)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_GROUP, new TagGroupEvent([
                'tagGroup' => $tagGroup
            ]));
        }

        Craft::$app->getProjectConfig()->remove(self::CONFIG_TAGGROUP_KEY . '.' . $tagGroup->uid);
        return true;
    }

    /**
     * Handle Tag group getting deleted
     *
     * @param ConfigEvent $event
     */
    public function handleDeletedTagGroup(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];
        $tagGroupRecord = $this->_getTagGroupRecord($uid);

        if (!$tagGroupRecord->id) {
            return;
        }

        /** @var TagGroup $tagGroup */
        $tagGroup = $this->getTagGroupById($tagGroupRecord->id);

        // Fire a 'beforeApplyGroupDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_GROUP_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_GROUP_DELETE, new TagGroupEvent([
                'tagGroup' => $tagGroup,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Delete the tags
            $tags = Tag::find()
                ->anyStatus()
                ->groupId($tagGroupRecord->id)
                ->all();
            $elementsService = Craft::$app->getElements();

            foreach ($tags as $tag) {
                $tag->deletedWithGroup = true;
                $elementsService->deleteElement($tag);
            }

            // Delete the field layout
            if ($tagGroupRecord->fieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($tagGroupRecord->fieldLayoutId);
            }

            // Delete the tag group
            Craft::$app->getDb()->createCommand()
                ->softDelete(Table::TAGGROUPS, ['id' => $tagGroupRecord->id])
                ->execute();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_tagGroups = null;

        // Fire an 'afterDeleteGroup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_GROUP)) {
            $this->trigger(self::EVENT_AFTER_DELETE_GROUP, new TagGroupEvent([
                'tagGroup' => $tagGroup
            ]));
        }
    }


    /**
     * Prune a deleted field from tag group layouts.
     *
     * @param FieldEvent $event
     */
    public function pruneDeletedField(FieldEvent $event)
    {
        /** @var Field $field */
        $field = $event->field;
        $fieldUid = $field->uid;

        $projectConfig = Craft::$app->getProjectConfig();
        $tagGroups = $projectConfig->get(self::CONFIG_TAGGROUP_KEY);

        // Loop through the tag groups and prune the UID from field layouts.
        if (is_array($tagGroups)) {
            foreach ($tagGroups as $tagGroupUid => $tagGroup) {
                if (!empty($tagGroup['fieldLayouts'])) {
                    foreach ($tagGroup['fieldLayouts'] as $layoutUid => $layout) {
                        if (!empty($layout['tabs'])) {
                            foreach ($layout['tabs'] as $tabUid => $tab) {
                                $projectConfig->remove(self::CONFIG_TAGGROUP_KEY . '.' . $tagGroupUid . '.fieldLayouts.' . $layoutUid . '.tabs.' . $tabUid . '.fields.' . $fieldUid);
                            }
                        }
                    }
                }
            }
        }
    }

    // Tags
    // -------------------------------------------------------------------------

    /**
     * Returns a tag by its ID.
     *
     * @param int $tagId
     * @param int|null $siteId
     * @return Tag|null
     */
    public function getTagById(int $tagId, int $siteId = null)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getElements()->getElementById($tagId, Tag::class, $siteId);
    }

    // Private methods
    // =========================================================================

    /**
     * Gets a tag group's record by uid.
     *
     * @param string $uid
     * @param bool $withTrashed Whether to include trashed tag groups in search
     * @return TagGroupRecord
     */
    private function _getTagGroupRecord(string $uid, bool $withTrashed = false): TagGroupRecord
    {
        $query = $withTrashed ? TagGroupRecord::findWithTrashed() : TagGroupRecord::find();
        $query->andWhere(['uid' => $uid]);
        return $query->one() ?? new TagGroupRecord();
    }
}
