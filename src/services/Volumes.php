<?php

namespace craft\services;

use Craft;
use craft\base\Field;
use craft\base\Volume;
use craft\base\VolumeInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Asset;
use craft\errors\MissingComponentException;
use craft\events\ConfigEvent;
use craft\events\FieldEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\VolumeEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\Db;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\records\Volume as AssetVolumeRecord;
use craft\records\VolumeFolder;
use craft\volumes\Local;
use craft\volumes\MissingVolume;
use yii\base\Component;

/**
 * Class AssetVolumesService
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license http://craftcms.com/license Craft License Agreement
 * @see http://craftcms.com
 * @package craft.app.services
 * @since 3.0
 */
class Volumes extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering volume types.
     *
     * Volume types must implement [[VolumeInterface]]. [[Volume]] provides a base implementation.
     *
     * See [Volume Types](https://docs.craftcms.com/v3/volume-types.html) for documentation on creating volume types.
     * ---
     * ```php
     * use craft\events\RegisterComponentTypesEvent;
     * use craft\services\Volumes;
     * use yii\base\Event;
     *
     * Event::on(Volumes::class,
     *     Volumes::EVENT_REGISTER_VOLUME_TYPES,
     *     function(RegisterComponentTypesEvent $event) {
     *         $event->types[] = MyVolumeType::class;
     *     }
     * );
     * ```
     */
    const EVENT_REGISTER_VOLUME_TYPES = 'registerVolumeTypes';

    /**
     * @event VolumeEvent The event that is triggered before an Asset volume is saved.
     */
    const EVENT_BEFORE_SAVE_VOLUME = 'beforeSaveVolume';

    /**
     * @event VolumeEvent The event that is triggered after an Asset volume is saved.
     */
    const EVENT_AFTER_SAVE_VOLUME = 'afterSaveVolume';

    /**
     * @event VolumeEvent The event that is triggered before an Asset volume is deleted.
     */
    const EVENT_BEFORE_DELETE_VOLUME = 'beforeDeleteVolume';

    /**
     * @event VolumeEvent The event that is triggered before a volume delete is applied to the database.
     */
    const EVENT_BEFORE_APPLY_VOLUME_DELETE = 'beforeApplyVolumeDelete';

    /**
     * @event VolumeEvent The event that is triggered after a Asset volume is deleted.
     */
    const EVENT_AFTER_DELETE_VOLUME = 'afterDeleteVolume';

    const CONFIG_VOLUME_KEY = 'volumes';

    // Properties
    // =========================================================================

    /**
     * @var VolumeInterface[]
     */
    private $_volumes;

    /**
     * @var array|null Volume setting overrides
     */
    private $_overrides;

    // Public Methods
    // =========================================================================

    // Volumes
    // -------------------------------------------------------------------------

    /**
     * Returns all registered volume types.
     *
     * @return string[]
     */
    public function getAllVolumeTypes(): array
    {
        $volumeTypes = [
            Local::class
        ];

        $event = new RegisterComponentTypesEvent([
            'types' => $volumeTypes
        ]);

        $this->trigger(self::EVENT_REGISTER_VOLUME_TYPES, $event);

        return $event->types;
    }

    /**
     * Returns all of the volume IDs.
     *
     * @return int[]
     */
    public function getAllVolumeIds(): array
    {
        return ArrayHelper::getColumn($this->getAllVolumes(), 'id', false);
    }

    /**
     * Returns all volume IDs that are viewable by the current user.
     *
     * @return array
     */
    public function getViewableVolumeIds(): array
    {
        return ArrayHelper::getColumn($this->getViewableVolumes(), 'id', false);
    }

    /**
     * Returns all volumes that are viewable by the current user.
     *
     * @return VolumeInterface[]
     */
    public function getViewableVolumes(): array
    {
        $userSession = Craft::$app->getUser();
        return ArrayHelper::filterByValue($this->getAllVolumes(), function(VolumeInterface $volume) use ($userSession) {
            /** @var Volume $volume */
            return $userSession->checkPermission('viewVolume:' . $volume->uid);
        });
    }

    /**
     * Returns all volume IDs that have public URLs.
     *
     * @return int[]
     */
    public function getPublicVolumeIds(): array
    {
        return ArrayHelper::getColumn($this->getPublicVolumes(), 'id', false);
    }

    /**
     * Returns all volumes that have public URLs.
     *
     * @return VolumeInterface[]
     */
    public function getPublicVolumes(): array
    {
        return ArrayHelper::filterByValue($this->getAllVolumes(), 'hasUrls');
    }

    /**
     * Returns the total number of volumes.
     *
     * @return int
     */
    public function getTotalVolumes(): int
    {
        return count($this->getAllVolumes());
    }

    /**
     * Returns the total number of volumes that are viewable by the current user.
     *
     * @return int
     */
    public function getTotalViewableVolumes(): int
    {
        return count($this->getViewableVolumes());
    }

    /**
     * Returns all volumes.
     *
     * @return VolumeInterface[]
     */
    public function getAllVolumes(): array
    {
        if ($this->_volumes !== null) {
            return $this->_volumes;
        }

        $this->_volumes = [];
        $results = $this->_createVolumeQuery()
            ->all();

        foreach ($results as $result) {
            $this->_volumes[] = $this->createVolume($result);
        }

        return $this->_volumes;
    }

    /**
     * Returns a volume by its ID.
     *
     * @param int $volumeId
     * @return VolumeInterface|null
     */
    public function getVolumeById(int $volumeId)
    {
        return ArrayHelper::firstWhere($this->getAllVolumes(), 'id', $volumeId);
    }

    /**
     * Returns a volume by its UID.
     *
     * @param string $volumeUid
     * @return VolumeInterface|null
     */
    public function getVolumeByUid(string $volumeUid)
    {
        return ArrayHelper::firstWhere($this->getAllVolumes(), 'uid', $volumeUid);
    }

    /**
     * Returns a volume by its handle.
     *
     * @param string $handle
     * @return VolumeInterface|null
     */
    public function getVolumeByHandle(string $handle)
    {
        return ArrayHelper::firstWhere($this->getAllVolumes(), 'handle', $handle, true);
    }

    /**
     * Saves an asset volume.
     *
     * @param VolumeInterface $volume the volume to be saved.
     * @param bool $runValidation Whether the volume should be validated
     * @return bool Whether the field was saved successfully
     * @throws \Throwable
     */
    public function saveVolume(VolumeInterface $volume, bool $runValidation = true): bool
    {
        /** @var Volume $volume */
        $isNewVolume = $volume->getIsNew();

        // Fire a 'beforeSaveVolume' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_VOLUME)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_VOLUME, new VolumeEvent([
                'volume' => $volume,
                'isNew' => $isNewVolume
            ]));
        }

        if (!$volume->beforeSave($isNewVolume)) {
            return false;
        }

        if ($runValidation && !$volume->validate()) {
            Craft::info('Volume not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNewVolume) {
            $volume->uid = StringHelper::UUID();
            $volume->sortOrder = (new Query())
                    ->from([Table::VOLUMES])
                    ->max('[[sortOrder]]') + 1;
        } else if (!$volume->uid) {
            $volume->uid = Db::uidById(Table::VOLUMES, $volume->id);
        }

        $projectConfig = Craft::$app->getProjectConfig();

        $configData = [
            'name' => $volume->name,
            'handle' => $volume->handle,
            'type' => \get_class($volume),
            'hasUrls' => $volume->hasUrls,
            'url' => $volume->url,
            'settings' => $volume->getSettings(),
            'sortOrder' => $volume->sortOrder,
        ];

        $fieldLayout = $volume->getFieldLayout();
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


        $configPath = self::CONFIG_VOLUME_KEY . '.' . $volume->uid;
        $projectConfig->set($configPath, $configData);

        if ($isNewVolume) {
            $volume->id = Db::idByUid(Table::VOLUMES, $volume->uid);
        }

        return true;
    }

    /**
     * Handle volume change
     *
     * @param ConfigEvent $event
     */
    public function handleChangedVolume(ConfigEvent $event)
    {
        $volumeUid = $event->tokenMatches[0];
        $data = $event->newValue;

        // Make sure fields are processed
        ProjectConfigHelper::ensureAllFieldsProcessed();

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $volumeRecord = $this->_getVolumeRecord($volumeUid, true);
            $isNewVolume = $volumeRecord->getIsNewRecord();

            $volumeRecord->name = $data['name'];
            $volumeRecord->handle = $data['handle'];
            $volumeRecord->type = $data['type'];
            $volumeRecord->hasUrls = $data['hasUrls'];
            $volumeRecord->sortOrder = $data['sortOrder'];
            $volumeRecord->url = !empty($data['url']) ? $data['url'] : null;
            $volumeRecord->settings = $data['settings'];
            $volumeRecord->uid = $volumeUid;

            if (!empty($data['fieldLayouts'])) {
                // Save the field layout
                $layout = FieldLayout::createFromConfig(reset($data['fieldLayouts']));
                $layout->id = $volumeRecord->fieldLayoutId;
                $layout->type = Asset::class;
                $layout->uid = key($data['fieldLayouts']);
                Craft::$app->getFields()->saveLayout($layout);
                $volumeRecord->fieldLayoutId = $layout->id;
            } else if ($volumeRecord->fieldLayoutId) {
                // Delete the field layout
                Craft::$app->getFields()->deleteLayoutById($volumeRecord->fieldLayoutId);
                $volumeRecord->fieldLayoutId = null;
            }

            // Save the volume
            if ($wasTrashed = (bool)$volumeRecord->dateDeleted) {
                $volumeRecord->restore();
            } else {
                $volumeRecord->save(false);
            }

            $assetsService = Craft::$app->getAssets();
            $rootFolder = $assetsService->findFolder([
                'volumeId' => $volumeRecord->id,
                'parentId' => ':empty:'
            ]);

            if ($rootFolder === null) {
                $rootFolderRecord = new VolumeFolder([
                    'volumeId' => $volumeRecord->id,
                    'parentId' => null,
                    'path' => '',
                    'name' => $volumeRecord->name
                ]);

                $rootFolderRecord->save();
            } else {
                $rootFolder->name = $volumeRecord->name;
                $assetsService->storeFolderRecord($rootFolder);
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_volumes = null;

        /** @var Volume $volume */
        $volume = $this->getVolumeById($volumeRecord->id);
        $volume->afterSave($isNewVolume);

        if ($wasTrashed) {
            // Restore the assets that were deleted with the volume
            $assets = Asset::find()
                ->volumeId($volumeRecord->id)
                ->trashed()
                ->andWhere(['assets.deletedWithVolume' => true])
                ->all();
            Craft::$app->getElements()->restoreElements($assets);
        }

        // Fire an 'afterSaveVolume' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_VOLUME)) {
            $this->trigger(self::EVENT_AFTER_SAVE_VOLUME, new VolumeEvent([
                'volume' => $this->getVolumeById($volumeRecord->id),
                'isNew' => $isNewVolume
            ]));
        }
    }

    /**
     * Reorders asset volumes.
     *
     * @param array $volumeIds
     * @throws \Throwable
     * @return bool
     */
    public function reorderVolumes(array $volumeIds): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        $uidsByIds = Db::uidsByIds(Table::VOLUMES, $volumeIds);

        foreach ($volumeIds as $volumeOrder => $volumeId) {
            if (!empty($uidsByIds[$volumeId])) {
                $volumeUid = $uidsByIds[$volumeId];
                $projectConfig->set(self::CONFIG_VOLUME_KEY . '.' . $volumeUid . '.sortOrder', $volumeOrder + 1);
            }
        }

        return true;
    }

    /**
     * Returns any custom volume config values.
     *
     * @param string $handle The volume handle
     * @return array|null
     */
    public function getVolumeOverrides(string $handle)
    {
        if ($this->_overrides === null) {
            $this->_overrides = Craft::$app->getConfig()->getConfigFromFile('volumes');
        }

        return $this->_overrides[$handle] ?? null;
    }

    /**
     * Creates an asset volume with a given config.
     *
     * @param mixed $config The asset volume’s class name, or its config, with a `type` value and optionally a `settings` value
     * @return VolumeInterface The asset volume
     */
    public function createVolume($config): VolumeInterface
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        // Are they overriding any settings?
        if (!empty($config['handle']) && ($override = $this->getVolumeOverrides($config['handle'])) !== null) {
            // Save a reference to the original config in case the volume type is missing
            $originalConfig = $config;

            // Merge in the DB settings first, then the config file overrides
            $config = array_merge(ComponentHelper::mergeSettings($config), $override);
        }

        try {
            /** @var Volume $volume */
            $volume = ComponentHelper::createComponent($config, VolumeInterface::class);
        } catch (MissingComponentException $e) {
            // Revert to the original config if it was overridden
            $config = $originalConfig ?? $config;

            $config['errorMessage'] = $e->getMessage();
            $config['expectedType'] = $config['type'];
            unset($config['type']);

            $volume = new MissingVolume($config);
        }

        return $volume;
    }

    /**
     * Ensures a top level folder exists that matches the model.
     *
     * @param VolumeInterface $volume
     * @return int
     */
    public function ensureTopFolder(VolumeInterface $volume): int
    {
        /** @var Volume $volume */
        $folder = VolumeFolder::findOne(
            [
                'name' => $volume->name,
                'volumeId' => $volume->id
            ]
        );

        if (empty($folder)) {
            $folder = new VolumeFolder();
            $folder->volumeId = $volume->id;
            $folder->parentId = null;
            $folder->name = $volume->name;
            $folder->path = '';
            $folder->save();
        }

        return $folder->id;
    }

    /**
     * Deletes an asset volume by its ID.
     *
     * @param int $volumeId
     * @throws \Throwable
     * @return bool
     */
    public function deleteVolumeById(int $volumeId): bool
    {
        $volume = $this->getVolumeById($volumeId);

        if (!$volume) {
            return false;
        }

        return $this->deleteVolume($volume);
    }

    /**
     * Deletes an asset volume.
     *
     * @param VolumeInterface $volume The volume to delete
     * @throws \Throwable
     * @return bool
     */
    public function deleteVolume(VolumeInterface $volume): bool
    {
        /** @var Volume $volume */
        // Fire a 'beforeDeleteVolume' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_VOLUME)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_VOLUME, new VolumeEvent([
                'volume' => $volume
            ]));
        }

        if (!$volume->beforeDelete()) {
            return false;
        }

        Craft::$app->getProjectConfig()->remove(self::CONFIG_VOLUME_KEY . '.' . $volume->uid);
        return true;
    }

    /**
     * Handle volume getting deleted
     *
     * @param ConfigEvent $event
     */
    public function handleDeletedVolume(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];
        $volumeRecord = $this->_getVolumeRecord($uid);

        if (!$volumeRecord) {
            return;
        }

        /** @var Volume $volume */
        $volume = $this->getVolumeById($volumeRecord->id);

        // Fire a 'beforeApplyVolumeDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_VOLUME_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_VOLUME_DELETE, new VolumeEvent([
                'volume' => $volume,
            ]));
        }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            $volume->beforeApplyDelete();

            // Delete the assets
            $assets = Asset::find()
                ->anyStatus()
                ->volumeId($volumeRecord->id)
                ->all();
            $elementsService = Craft::$app->getElements();

            foreach ($assets as $asset) {
                $asset->deletedWithVolume = true;
                $asset->keepFileOnDelete = true;
                $elementsService->deleteElement($asset);
            }

            // Delete the field layout
            if ($volumeRecord->fieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($volumeRecord->fieldLayoutId);
            }

            // Delete the volume
            $db->createCommand()
                ->softDelete(Table::VOLUMES, ['id' => $volumeRecord->id])
                ->execute();

            $volume->afterDelete();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_volumes = null;

        // Fire an 'afterDeleteVolume' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_VOLUME)) {
            $this->trigger(self::EVENT_AFTER_DELETE_VOLUME, new VolumeEvent([
                'volume' => $volume
            ]));
        }
    }

    /**
     * Prune a deleted field from volume layouts.
     *
     * @param FieldEvent $event
     */
    public function pruneDeletedField(FieldEvent $event)
    {
        /** @var Field $field */
        $field = $event->field;
        $fieldUid = $field->uid;

        $projectConfig = Craft::$app->getProjectConfig();
        $volumes = $projectConfig->get(self::CONFIG_VOLUME_KEY);

        // Loop through the volumes and prune the UID from field layouts.
        if (is_array($volumes)) {
            foreach ($volumes as $volumeUid => $volume) {
                if (!empty($volume['fieldLayouts'])) {
                    foreach ($volume['fieldLayouts'] as $layoutUid => $layout) {
                        if (!empty($layout['tabs'])) {
                            foreach ($layout['tabs'] as $tabUid => $tab) {
                                $projectConfig->remove(self::CONFIG_VOLUME_KEY . '.' . $volumeUid . '.fieldLayouts.' . $layoutUid . '.tabs.' . $tabUid . '.fields.' . $fieldUid);
                            }
                        }
                    }
                }
            }
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a DbCommand object prepped for retrieving volumes.
     *
     * @return Query
     */
    private function _createVolumeQuery(): Query
    {
        $query = (new Query())
            ->select([
                'id',
                'dateCreated',
                'dateUpdated',
                'name',
                'handle',
                'hasUrls',
                'url',
                'sortOrder',
                'fieldLayoutId',
                'type',
                'settings',
                'uid'
            ])
            ->from([Table::VOLUMES])
            ->orderBy(['sortOrder' => SORT_ASC]);

        // todo: remove schema version condition after next beakpoint
        $schemaVersion = Craft::$app->getProjectConfig()->get('system.schemaVersion');
        if (version_compare($schemaVersion, '3.1.19', '>=')) {
            $query->where(['dateDeleted' => null]);
        }

        return $query;
    }

    /**
     * Gets a volume's record by uid.
     *
     * @param string $uid
     * @param bool $withTrashed Whether to include trashed volumes in search
     * @return AssetVolumeRecord
     */
    private function _getVolumeRecord(string $uid, bool $withTrashed = false): AssetVolumeRecord
    {
        $query = $withTrashed ? AssetVolumeRecord::findWithTrashed() : AssetVolumeRecord::find();
        $query->andWhere(['uid' => $uid]);
        return $query->one() ?? new AssetVolumeRecord();
    }
}
