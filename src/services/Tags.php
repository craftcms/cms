<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\db\Query;
use craft\elements\Tag;
use craft\errors\TagGroupNotFoundException;
use craft\events\TagGroupEvent;
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
     * @event TagGroupEvent The event that is triggered after a tag group is deleted.
     */
    const EVENT_AFTER_DELETE_GROUP = 'afterDeleteGroup';

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
    public function getAllTagGroupIds(): array
    {
        if ($this->_allTagGroupIds !== null) {
            return $this->_allTagGroupIds;
        }

        if ($this->_fetchedAllTagGroups) {
            return $this->_allTagGroupIds = array_keys($this->_tagGroupsById);
        }

        return $this->_allTagGroupIds = (new Query())
            ->select(['id'])
            ->from(['{{%taggroups}}'])
            ->column();
    }

    /**
     * Returns all tag groups.
     *
     * @return TagGroup[]
     */
    public function getAllTagGroups(): array
    {
        if (!$this->_fetchedAllTagGroups) {
            $this->_tagGroupsById = TagGroupRecord::find()
                ->orderBy(['name' => SORT_ASC])
                ->indexBy('id')
                ->all();

            foreach ($this->_tagGroupsById as $key => $value) {
                $this->_tagGroupsById[$key] = new TagGroup($value->toArray([
                    'id',
                    'name',
                    'handle',
                    'fieldLayoutId',
                ]));
            }

            $this->_fetchedAllTagGroups = true;
        }

        return array_values($this->_tagGroupsById);
    }

    /**
     * Gets the total number of tag groups.
     *
     * @return int
     */
    public function getTotalTagGroups(): int
    {
        return count($this->getAllTagGroupIds());
    }

    /**
     * Returns a group by its ID.
     *
     * @param int $groupId
     * @return TagGroup|null
     */
    public function getTagGroupById(int $groupId)
    {
        if ($this->_tagGroupsById !== null && array_key_exists($groupId, $this->_tagGroupsById)) {
            return $this->_tagGroupsById[$groupId];
        }

        if ($this->_fetchedAllTagGroups) {
            return null;
        }

        $result = $this->_createTagGroupsQuery()
            ->where(['id' => $groupId])
            ->one();

        return $this->_tagGroupsById[$groupId] = $result ? new TagGroup($result) : null;
    }

    /**
     * Gets a group by its handle.
     *
     * @param string $groupHandle
     * @return TagGroup|null
     */
    public function getTagGroupByHandle(string $groupHandle)
    {
        $result = $this->_createTagGroupsQuery()
            ->where(['handle' => $groupHandle])
            ->one();

        return $result ? new TagGroup($result) : null;
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

        if (!$isNewTagGroup) {
            $tagGroupRecord = TagGroupRecord::findOne($tagGroup->id);

            if (!$tagGroupRecord) {
                throw new TagGroupNotFoundException("No tag group exists with the ID '{$tagGroup->id}'");
            }
        } else {
            $tagGroupRecord = new TagGroupRecord();
        }

        $tagGroupRecord->name = $tagGroup->name;
        $tagGroupRecord->handle = $tagGroup->handle;

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Save the field layout
            $fieldLayout = $tagGroup->getFieldLayout();
            Craft::$app->getFields()->saveLayout($fieldLayout);
            $tagGroup->fieldLayoutId = $fieldLayout->id;
            $tagGroupRecord->fieldLayoutId = $fieldLayout->id;

            // Save it!
            $tagGroupRecord->save(false);

            // Now that we have a tag group ID, save it on the model
            if (!$tagGroup->id) {
                $tagGroup->id = $tagGroupRecord->id;
            }

            // Might as well update our cache of the tag group while we have it.
            $this->_tagGroupsById[$tagGroup->id] = $tagGroup;

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterSaveGroup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_GROUP)) {
            $this->trigger(self::EVENT_AFTER_SAVE_GROUP, new TagGroupEvent([
                'tagGroup' => $tagGroup,
                'isNew' => $isNewTagGroup,
            ]));
        }

        return true;
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
     * @param TagGroup $group The tag group
     * @return bool Whether the tag group was deleted successfully
     * @throws \Throwable if reasons
     */
    public function deleteTagGroup(TagGroup $group): bool
    {
        // Fire a 'beforeDeleteGroup' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_GROUP)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_GROUP, new TagGroupEvent([
                'tagGroup' => $group
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Delete the field layout
            $fieldLayoutId = (new Query())
                ->select(['fieldLayoutId'])
                ->from(['{{%taggroups}}'])
                ->where(['id' => $group->id])
                ->scalar();

            if ($fieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($fieldLayoutId);
            }

            // Delete the tags
            $tags = Tag::find()
                ->anyStatus()
                ->groupId($group->id)
                ->all();

            foreach ($tags as $tag) {
                Craft::$app->getElements()->deleteElement($tag);
            }

            Craft::$app->getDb()->createCommand()
                ->delete('{{%taggroups}}', ['id' => $group->id])
                ->execute();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterSaveGroup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_GROUP)) {
            $this->trigger(self::EVENT_AFTER_DELETE_GROUP, new TagGroupEvent([
                'tagGroup' => $group
            ]));
        }

        return true;
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

    /**
     * @return Query
     */
    private function _createTagGroupsQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'fieldLayoutId',
            ])
            ->from(['{{%taggroups}}']);
    }
}
