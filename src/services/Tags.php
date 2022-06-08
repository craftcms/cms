<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Table;
use craft\elements\Tag;
use craft\errors\TagGroupNotFoundException;
use craft\events\ConfigEvent;
use craft\events\TagGroupEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\models\TagGroup;
use craft\records\TagGroup as TagGroupRecord;
use Throwable;
use yii\base\Component;

/**
 * Tags service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getTags()|`Craft::$app->tags`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Tags extends Component
{
    /**
     * @event TagGroupEvent The event that is triggered before a tag group is saved.
     */
    public const EVENT_BEFORE_SAVE_GROUP = 'beforeSaveGroup';

    /**
     * @event TagGroupEvent The event that is triggered after a tag group is saved.
     */
    public const EVENT_AFTER_SAVE_GROUP = 'afterSaveGroup';

    /**
     * @event TagGroupEvent The event that is triggered before a tag group is deleted.
     */
    public const EVENT_BEFORE_DELETE_GROUP = 'beforeDeleteGroup';

    /**
     * @event TagGroupEvent The event that is triggered before a tag group delete is applied to the database.
     * @since 3.1.0
     */
    public const EVENT_BEFORE_APPLY_GROUP_DELETE = 'beforeApplyGroupDelete';

    /**
     * @event TagGroupEvent The event that is triggered after a tag group is deleted.
     */
    public const EVENT_AFTER_DELETE_GROUP = 'afterDeleteGroup';

    /**
     * @var MemoizableArray<TagGroup>|null
     * @see _tagGroups()
     */
    private ?MemoizableArray $_tagGroups = null;

    /**
     * Serializer
     *
     * @since 3.5.14
     */
    public function __serialize()
    {
        $vars = get_object_vars($this);
        unset($vars['_tagGroups']);
        return $vars;
    }

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
     * Returns a memoizable array of all tag groups.
     *
     * @return MemoizableArray<TagGroup>
     */
    private function _tagGroups(): MemoizableArray
    {
        if (!isset($this->_tagGroups)) {
            $groups = [];
            $records = TagGroupRecord::find()
                ->orderBy(['name' => SORT_ASC])
                ->all();

            foreach ($records as $record) {
                $groups[] = new TagGroup($record->toArray([
                    'id',
                    'name',
                    'handle',
                    'fieldLayoutId',
                    'uid',
                ]));
            }

            $this->_tagGroups = new MemoizableArray($groups);
        }

        return $this->_tagGroups;
    }

    /**
     * Returns all tag groups.
     *
     * @return TagGroup[]
     */
    public function getAllTagGroups(): array
    {
        return $this->_tagGroups()->all();
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
    public function getTagGroupById(int $groupId): ?TagGroup
    {
        return $this->_tagGroups()->firstWhere('id', $groupId);
    }

    /**
     * Returns a group by its UID.
     *
     * @param string $groupUid
     * @return TagGroup|null
     */
    public function getTagGroupByUid(string $groupUid): ?TagGroup
    {
        return $this->_tagGroups()->firstWhere('uid', $groupUid, true);
    }


    /**
     * Gets a group by its handle.
     *
     * @param string $groupHandle
     * @return TagGroup|null
     */
    public function getTagGroupByHandle(string $groupHandle): ?TagGroup
    {
        return $this->_tagGroups()->firstWhere('handle', $groupHandle, true);
    }

    /**
     * Saves a tag group.
     *
     * @param TagGroup $tagGroup The tag group to be saved
     * @param bool $runValidation Whether the tag group should be validated
     * @return bool Whether the tag group was saved successfully
     * @throws TagGroupNotFoundException if $tagGroup->id is invalid
     * @throws Throwable if reasons
     */
    public function saveTagGroup(TagGroup $tagGroup, bool $runValidation = true): bool
    {
        $isNewTagGroup = !$tagGroup->id;

        // Fire a 'beforeSaveGroup' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_GROUP)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_GROUP, new TagGroupEvent([
                'tagGroup' => $tagGroup,
                'isNew' => $isNewTagGroup,
            ]));
        }

        if ($runValidation && !$tagGroup->validate()) {
            Craft::info('Tag group not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNewTagGroup) {
            $tagGroup->uid = StringHelper::UUID();
        } elseif (!$tagGroup->uid) {
            $tagGroup->uid = Db::uidById(Table::TAGGROUPS, $tagGroup->id);
        }

        $configPath = ProjectConfig::PATH_TAG_GROUPS . '.' . $tagGroup->uid;
        $configData = $tagGroup->getConfig();
        Craft::$app->getProjectConfig()->set($configPath, $configData, "Save the “{$tagGroup->handle}” tag group");

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
    public function handleChangedTagGroup(ConfigEvent $event): void
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
                Craft::$app->getFields()->saveLayout($layout, false);
                $tagGroupRecord->fieldLayoutId = $layout->id;
            } elseif ($tagGroupRecord->fieldLayoutId) {
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
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_tagGroups = null;

        if ($wasTrashed) {
            // Restore the tags that were deleted with the group
            /** @var Tag[] $tags */
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

        // Invalidate tag caches
        Craft::$app->getElements()->invalidateCachesForElementType(Tag::class);
    }

    /**
     * Deletes a tag group by its ID.
     *
     * @param int $groupId The tag group's ID
     * @return bool Whether the tag group was deleted successfully
     * @throws Throwable if reasons
     * @since 3.0.12
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
     * @throws Throwable if reasons
     */
    public function deleteTagGroup(TagGroup $tagGroup): bool
    {
        // Fire a 'beforeDeleteGroup' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_GROUP)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_GROUP, new TagGroupEvent([
                'tagGroup' => $tagGroup,
            ]));
        }

        Craft::$app->getProjectConfig()->remove(ProjectConfig::PATH_TAG_GROUPS . '.' . $tagGroup->uid, "Delete the “{$tagGroup->handle}” tag group");
        return true;
    }

    /**
     * Handle Tag group getting deleted
     *
     * @param ConfigEvent $event
     */
    public function handleDeletedTagGroup(ConfigEvent $event): void
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
            /** @var Tag[] $tags */
            $tags = Tag::find()
                ->groupId($tagGroupRecord->id)
                ->status(null)
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
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_tagGroups = null;

        // Fire an 'afterDeleteGroup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_GROUP)) {
            $this->trigger(self::EVENT_AFTER_DELETE_GROUP, new TagGroupEvent([
                'tagGroup' => $tagGroup,
            ]));
        }

        // Invalidate tag caches
        Craft::$app->getElements()->invalidateCachesForElementType(Tag::class);
    }

    /**
     * @deprecated in 4.0.5. Unused fields will be pruned automatically as field layouts are resaved.
     */
    public function pruneDeletedField(): void
    {
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
    public function getTagById(int $tagId, ?int $siteId = null): ?Tag
    {
        return Craft::$app->getElements()->getElementById($tagId, Tag::class, $siteId);
    }

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
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        /** @var TagGroupRecord */
        return $query->one() ?? new TagGroupRecord();
    }
}
