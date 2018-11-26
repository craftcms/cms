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
use craft\elements\Tag;
use craft\errors\TagGroupNotFoundException;
use craft\events\ConfigEvent;
use craft\events\FieldEvent;
use craft\events\TagGroupEvent;
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
     * @var
     */
    private $_allTagGroupIds;

    /**
     * @var
     */
    private $_tagGroupsById;

    /**
     * @var
     */
    private $_tagGroupsByUid = [];

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
                $tagGroup = new TagGroup($value->toArray([
                    'id',
                    'name',
                    'handle',
                    'fieldLayoutId',
                    'uid'
                ]));
                $this->_tagGroupsById[$tagGroup->id] = $tagGroup;
                $this->_tagGroupsByUid[$tagGroup->uid] = $tagGroup;
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
     * Returns a group by its UID.
     *
     * @param string $groupUid
     * @return TagGroup|null
     */
    public function getTagGroupByUid(string $groupUid)
    {
        if ($this->_tagGroupsByUid !== null && array_key_exists($groupUid, $this->_tagGroupsByUid)) {
            return $this->_tagGroupsByUid[$groupUid];
        }

        if ($this->_fetchedAllTagGroups) {
            return null;
        }

        $result = $this->_createTagGroupsQuery()
            ->where(['uid' => $groupUid])
            ->one();

        return $this->_tagGroupsByUid[$groupUid] = $result ? new TagGroup($result) : null;
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

        if ($isNewTagGroup) {
            $tagGroup->uid = StringHelper::UUID();
        } else if (!$tagGroup->uid) {
            $tagGroup->uid = Db::uidById('{{%taggroups}}', $tagGroup->id);
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
                $layoutUid = Db::uidById('{{%fieldlayouts}}', $fieldLayout->id);
            }

            $configData['fieldLayouts'] = [
                $layoutUid => $fieldLayoutConfig
            ];
        }

        $configPath = self::CONFIG_TAGGROUP_KEY . '.' . $tagGroup->uid;
        $projectConfig->set($configPath, $configData);

        if ($isNewTagGroup) {
            $tagGroup->id = Db::idByUid('{{%taggroups}}', $tagGroup->uid);
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
            $tagGroupRecord = $this->_getTagGroupRecord($tagGroupUid);
            $isNewTagGroup = $tagGroupRecord->getIsNewRecord();

            $tagGroupRecord->name = $data['name'];
            $tagGroupRecord->handle = $data['handle'];
            $tagGroupRecord->uid = $tagGroupUid;

            if (!empty($data['fieldLayouts'])) {
                $fields = Craft::$app->getFields();

                // Delete the field layout
                if ($tagGroupRecord->fieldLayoutId) {
                    $fields->deleteLayoutById($tagGroupRecord->fieldLayoutId);
                }

                //Create the new layout
                $layout = FieldLayout::createFromConfig(reset($data['fieldLayouts']));
                $layout->type = Tag::class;
                $layout->uid = key($data['fieldLayouts']);
                $fields->saveLayout($layout);
                $tagGroupRecord->fieldLayoutId = $layout->id;
            } else {
                $tagGroupRecord->fieldLayoutId = null;
            }

            // Save the volume
            $tagGroupRecord->save(false);

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        unset(
            $this->_allTagGroupIds,
            $this->_tagGroupsById[$tagGroupRecord->id],
            $this->_tagGroupsByUid[$tagGroupRecord->uid]
        );
        $this->_fetchedAllTagGroups = false;

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
            // Delete the field layout
            $fieldLayoutId = (new Query())
                ->select(['fieldLayoutId'])
                ->from(['{{%taggroups}}'])
                ->where(['id' => $tagGroupRecord->id])
                ->scalar();

            if ($fieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($fieldLayoutId);
            }

            // Delete the tags
            $tags = Tag::find()
                ->anyStatus()
                ->groupId($tagGroupRecord->id)
                ->all();

            foreach ($tags as $tag) {
                Craft::$app->getElements()->deleteElement($tag);
            }

            Craft::$app->getDb()->createCommand()
                ->delete('{{%taggroups}}', ['id' => $tagGroupRecord->id])
                ->execute();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        unset(
            $this->_allTagGroupIds,
            $this->_tagGroupsById[$tagGroupRecord->id],
            $this->_tagGroupsByUid[$tagGroupRecord->uid]
        );

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

        $fieldPruned = false;
        $projectConfig = Craft::$app->getProjectConfig();
        $tagGroups = $projectConfig->get(self::CONFIG_TAGGROUP_KEY);

        // Loop through the tag groups and see if the UID exists in the field layouts.
        if (is_array($tagGroups)) {
            foreach ($tagGroups as &$tagGroup) {
                if (!empty($tagGroup['fieldLayouts'])) {
                    foreach ($tagGroup['fieldLayouts'] as &$layout) {
                        if (!empty($layout['tabs'])) {
                            foreach ($layout['tabs'] as &$tab) {
                                if (!empty($tab['fields'])) {
                                    // Remove the straggler.
                                    if (array_key_exists($fieldUid, $tab['fields'])) {
                                        unset($tab['fields'][$fieldUid]);
                                        $fieldPruned = true;
                                        // If last field, just remove field layouts entry altogether.
                                        if (empty($tab['fields'])) {
                                            unset($tagGroup['fieldLayouts']);
                                            break 2;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($fieldPruned) {
            $projectConfig->set(self::CONFIG_TAGGROUP_KEY, $tagGroups, true);
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
                'uid'
            ])
            ->from(['{{%taggroups}}']);
    }

    /**
     * Gets a tag group's record by uid.
     *
     * @param string $uid
     * @return TagGroupRecord
     */
    private function _getTagGroupRecord(string $uid): TagGroupRecord
    {
        return TagGroupRecord::findOne(['uid' => $uid]) ?? new TagGroupRecord();
    }
}
