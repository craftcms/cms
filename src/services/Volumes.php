<?php

namespace craft\services;

use Craft;
use craft\base\Field;
use craft\base\Volume;
use craft\base\VolumeInterface;
use craft\db\Query;
use craft\elements\Asset;
use craft\errors\MissingComponentException;
use craft\events\FieldEvent;
use craft\events\ParseConfigEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\VolumeEvent;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\Db;
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
     * @event VolumeEvent The event that is triggered after a Asset volume is deleted.
     */
    const EVENT_AFTER_DELETE_VOLUME = 'afterDeleteVolume';

    const CONFIG_VOLUME_KEY = 'volumes';

    // Properties
    // =========================================================================

    /**
     * @var
     */
    private $_allVolumeIds;

    /**
     * @var
     */
    private $_viewableVolumeIds;

    /**
     * @var
     */
    private $_viewableVolumes;

    /**
     * @var
     */
    private $_publicVolumeIds;

    /**
     * @var
     */
    private $_publicVolumes;

    /**
     * @var
     */
    private $_volumesById;

    /**
     * @var
     */
    private $_volumesByHandle;

    /**
     * @var bool
     */
    private $_fetchedAllVolumes = false;

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
     * @return array
     */
    public function getAllVolumeIds(): array
    {
        if ($this->_allVolumeIds !== null) {
            return $this->_allVolumeIds;
        }

        if ($this->_fetchedAllVolumes) {
            return $this->_allVolumeIds = array_keys($this->_volumesById);
        }

        return $this->_allVolumeIds = (new Query())
            ->select(['id'])
            ->from(['{{%volumes}}'])
            ->orderBy('sortOrder asc')
            ->column();
    }

    /**
     * Returns all volume IDs that are viewable by the current user.
     *
     * @return array
     */
    public function getViewableVolumeIds(): array
    {
        if ($this->_viewableVolumeIds !== null) {
            return $this->_viewableVolumeIds;
        }

        $this->_viewableVolumeIds = [];

        foreach ($this->getAllVolumes() as $volume) {
            if (Craft::$app->user->checkPermission('viewVolume:'.$volume->uid)) {
                $this->_viewableVolumeIds[] = $volume->id;
            }
        }

        return $this->_viewableVolumeIds;
    }

    /**
     * Returns all volumes that are viewable by the current user.
     *
     * @return VolumeInterface[]
     */
    public function getViewableVolumes(): array
    {
        if ($this->_viewableVolumes !== null) {
            return $this->_viewableVolumes;
        }

        $this->_viewableVolumes = [];

        foreach ($this->getAllVolumes() as $volume) {
            /** @var Volume $volume */
            if (Craft::$app->user->checkPermission('viewVolume:'.$volume->uid)) {
                $this->_viewableVolumes[] = $volume;
            }
        }

        return $this->_viewableVolumes;
    }

    /**
     * Returns all volume IDs that have public URLs.
     *
     * @return int[]
     */
    public function getPublicVolumeIds(): array
    {
        if ($this->_publicVolumeIds !== null) {
            return $this->_publicVolumeIds;
        }

        $this->_publicVolumeIds = [];

        foreach ($this->getAllVolumes() as $volume) {
            /** @var Volume $volume */
            if ($volume->hasUrls) {
                $this->_publicVolumeIds[] = $volume->id;
            }
        }

        return $this->_publicVolumeIds;
    }

    /**
     * Returns all volumes that have public URLs.
     *
     * @return VolumeInterface[]
     */
    public function getPublicVolumes(): array
    {
        if ($this->_publicVolumes !== null) {
            return $this->_publicVolumes;
        }

        $this->_publicVolumes = [];

        foreach ($this->getAllVolumes() as $volume) {
            /** @var Volume $volume */
            if ($volume->hasUrls) {
                $this->_publicVolumes[] = $volume;
            }
        }

        return $this->_publicVolumes;
    }

    /**
     * Returns the total number of volumes.
     *
     * @return int
     */
    public function getTotalVolumes(): int
    {
        return count($this->getAllVolumeIds());
    }

    /**
     * Returns the total number of volumes that are viewable by the current user.
     *
     * @return int
     */
    public function getTotalViewableVolumes(): int
    {
        return count($this->getViewableVolumeIds());
    }

    /**
     * Returns all volumes.
     *
     * @return VolumeInterface[]
     */
    public function getAllVolumes(): array
    {
        if ($this->_fetchedAllVolumes) {
            return array_values($this->_volumesById);
        }

        $this->_volumesById = [];
        $results = $this->_createVolumeQuery()
            ->all();

        foreach ($results as $result) {
            /** @var Volume $volume */
            $volume = $this->createVolume($result);
            $this->_volumesById[$volume->id] = $volume;
            $this->_volumesByHandle[$volume->handle] = $volume;
        }

        $this->_fetchedAllVolumes = true;

        return array_values($this->_volumesById);
    }

    /**
     * Returns a volume by its ID.
     *
     * @param int $volumeId
     * @return VolumeInterface|null
     */
    public function getVolumeById(int $volumeId)
    {
        if ($this->_volumesById !== null && array_key_exists($volumeId, $this->_volumesById)) {
            return $this->_volumesById[$volumeId];
        }

        if ($this->_fetchedAllVolumes) {
            return null;
        }

        $result = $this->_createVolumeQuery()
            ->where(['id' => $volumeId])
            ->one();

        return $this->_volumesById[$volumeId] = $result ? $this->createVolume($result) : null;
    }

    /**
     * Returns a volume by its UID.
     *
     * @param string $volumeUid
     * @return VolumeInterface|null
     */
    public function getVolumeByUid(string $volumeUid) {
        $result = $this->_createVolumeQuery()
            ->where(['uid' => $volumeUid])
            ->one();

        return $result ? $this->createVolume($result) : null;
    }

    /**
     * Returns a volumn by its handle.
     *
     * @param string $handle
     * @return VolumeInterface|null
     */
    public function getVolumeByHandle(string $handle)
    {
        if ($this->_volumesByHandle !== null && array_key_exists($handle, $this->_volumesByHandle)) {
            return $this->_volumesByHandle[$handle];
        }

        if ($this->_fetchedAllVolumes) {
            return null;
        }

        $result = $this->_createVolumeQuery()
            ->where(['handle' => $handle])
            ->one();

        return $this->_volumesByHandle[$handle] = $result ? $this->createVolume($result) : null;
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
            $volumeUid = StringHelper::UUID();
            $volume->sortOrder = (new Query())
                    ->from(['{{%volumes}}'])
                    ->max('[[sortOrder]]') + 1;
        } else {
            $volumeUid = $volume->uid;
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
                $layoutUid = Db::uidById('{{%fieldlayouts}}', $fieldLayout->id);
            }

            $configData['fieldLayouts'] = [
                $layoutUid => $fieldLayoutConfig
            ];
        }


        $configPath = self::CONFIG_VOLUME_KEY.'.'.$volumeUid;
        $projectConfig->save($configPath, $configData);

        if ($isNewVolume) {
            $volume->id = Db::idByUid('{{%volumes}}', $volumeUid);
        }

        $volume->afterSave($isNewVolume);

        // Update our caches
        $this->_volumesById[$volume->id] = $volume;
        $this->_volumesByHandle[$volume->handle] = $volume;

        if ($this->_viewableVolumeIds !== null && Craft::$app->user->checkPermission('viewVolume:'.$volume->uid)) {
            $this->_viewableVolumeIds[] = $volume->id;
        }

        // Fire an 'afterSaveVolume' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_VOLUME)) {
            $this->trigger(self::EVENT_AFTER_SAVE_VOLUME, new VolumeEvent([
                'volume' => $volume,
                'isNew' => $isNewVolume
            ]));
        }

        return true;
    }

    /**
     * Handle volume change
     *
     * @param ParseConfigEvent $event
     */
    public function handleChangedVolume(ParseConfigEvent $event)
    {
        $path = $event->configPath;

        // Does it match a volume?
        if (preg_match('/'.self::CONFIG_VOLUME_KEY.'\.('.ProjectConfig::UID_PATTERN.')$/i', $path, $matches)) {

            $volumeUid = $matches[1];
            $data = Craft::$app->getProjectConfig()->get($path, true);

            // Make sure fields are processed
            Craft::$app->getProjectConfig()->applyPendingChanges(Fields::CONFIG_FIELDGROUP_KEY);

            $transaction = Craft::$app->getDb()->beginTransaction();
            try {
                $volumeRecord = $this->_getVolumeRecord($volumeUid);

                $volumeRecord->name = $data['name'];
                $volumeRecord->handle = $data['handle'];
                $volumeRecord->type = $data['type'];
                $volumeRecord->hasUrls = $data['hasUrls'];
                $volumeRecord->url = !empty($data['url']) ? $data['url'] : null;
                $volumeRecord->settings = $data['settings'];
                $volumeRecord->uid = $volumeUid;

                if (!empty($data['fieldLayouts'])) {
                    $fields = Craft::$app->getFields();

                    // Delete the field layout
                    $fields->deleteLayoutById($volumeRecord->fieldLayoutId);

                    //Create the new layout
                    $layout = FieldLayout::createFromConfig(reset($data['fieldLayouts']));
                    $layout->type = Asset::class;
                    $layout->uid = key($data['fieldLayouts']);
                    $fields->saveLayout($layout);
                    $volumeRecord->fieldLayoutId = $layout->id;
                } else {
                    $volumeRecord->fieldLayoutId = null;
                }

                // Save the volume
                $volumeRecord->save(false);

                $assets = Craft::$app->getAssets();
                $topFolder = $assets->findFolder([
                    'volumeId' => $volumeRecord->id,
                    'parentId' => ':empty:'
                ]);

                if ($topFolder === null) {
                    $topFolder = new VolumeFolder([
                        'volumeId' => $volumeRecord->id,
                        'parentId' => null,
                        'name' => $volumeRecord->name,
                        'path' => ''
                    ]);
                    $topFolder->save();
                }

                $transaction->commit();
            } catch (\Throwable $e) {
                $transaction->rollBack();

                throw $e;
            }
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
        $transaction = Craft::$app->getDb()->beginTransaction();
        $projectConfig = Craft::$app->getProjectConfig();

        try {
            foreach ($volumeIds as $volumeOrder => $volumeUid) {
                $volumeRecord = $this->_getVolumeRecord($volumeUid);
                $volumeRecord->sortOrder = $volumeOrder + 1;
                $volumeRecord->save();
                $projectConfig->save(self::CONFIG_VOLUME_KEY.'.'.$volumeRecord->uid.'.sortOrder', $volumeOrder + 1, true);
            }

            $transaction->commit();

        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
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

        Craft::$app->getProjectConfig()->save(self::CONFIG_VOLUME_KEY.'.'.$volume->uid, null);

        $volume->afterDelete();

        // Fire an 'afterDeleteVolume' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_VOLUME)) {
            $this->trigger(self::EVENT_AFTER_DELETE_VOLUME, new VolumeEvent([
                'volume' => $volume
            ]));
        }

        return true;
    }

    /**
     * Handle volume getting deleted
     *
     * @param ParseConfigEvent $event
     */
    public function handleDeletedVolume (ParseConfigEvent $event) {
        $path = $event->configPath;

        // Does it match a field group?
        if (preg_match('/'.self::CONFIG_VOLUME_KEY.'\.('.ProjectConfig::UID_PATTERN.')$/i', $path, $matches)) {
            $uid = $matches[1];

            $volume = $this->_getVolumeRecord($uid);

            $db = Craft::$app->getDb();
            $transaction = $db->beginTransaction();

            try {
                // Delete the field layout
                $fieldLayoutId = (new Query())
                    ->select(['fieldLayoutId'])
                    ->from(['{{%volumes}}'])
                    ->where(['id' => $volume->id])
                    ->scalar();

                if ($fieldLayoutId) {
                    Craft::$app->getFields()->deleteLayoutById($fieldLayoutId);
                }

                // Delete the assets
                $assets = Asset::find()
                    ->status(null)
                    ->enabledForSite(false)
                    ->volumeId($volume->id)
                    ->all();

                foreach ($assets as $asset) {
                    $asset->keepFileOnDelete = true;
                    Craft::$app->getElements()->deleteElement($asset);
                }

                // Nuke the asset volume.
                $db->createCommand()
                    ->delete('{{%volumes}}', ['id' => $volume->id])
                    ->execute();

                $transaction->commit();
            } catch (\Throwable $e) {
                $transaction->rollBack();

                throw $e;
            }
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

        $fieldPruned = false;
        $projectConfig = Craft::$app->getProjectConfig();
        $volumes = $projectConfig->get(self::CONFIG_VOLUME_KEY);

        // Loop through the volumes and see if the UID exists in the field layouts.
        foreach ($volumes as &$volume) {
            if (!empty($volume['fieldLayouts'])) {
                foreach ($volume['fieldLayouts'] as &$layout) {
                    if (!empty($layout['tabs'])) {
                        foreach ($layout['tabs'] as &$tab) {
                            if (!empty($tab['fields'])) {
                                // Remove the straggler.
                                if (array_key_exists($fieldUid, $tab['fields'])) {
                                    unset($tab['fields'][$fieldUid]);
                                    $fieldPruned = true;
                                    // If last field, just remove field layouts entry altogether.
                                    if (empty($tab['fields'])) {
                                        unset($volume['fieldLayouts']);
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
            $projectConfig->save(self::CONFIG_VOLUME_KEY, $volumes, true);
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
        return (new Query())
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
            ->from(['{{%volumes}}'])
            ->orderBy(['sortOrder' => SORT_ASC]);
    }

    /**
     * Gets a volume's record by uid.
     *
     * @param string $uid
     * @return AssetVolumeRecord
     */
    private function _getVolumeRecord(string $uid): AssetVolumeRecord
    {
        return AssetVolumeRecord::findOne(['uid' => $uid]) ?? new AssetVolumeRecord();
    }
}
