<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Field;
use craft\base\MemoizableArray;
use craft\base\VolumeInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Asset;
use craft\errors\MissingComponentException;
use craft\events\ConfigEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\VolumeEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\records\Volume as AssetVolumeRecord;
use craft\records\VolumeFolder;
use craft\volumes\Local;
use craft\volumes\MissingVolume;
use yii\base\Component;
use yii\base\UnknownPropertyException;

/**
 * Volumes service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getVolumes()|`Craft::$app->volumes()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Volumes extends Component
{
    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering volume types.
     *
     * Volume types must implement [[VolumeInterface]]. [[Volume]] provides a base implementation.
     *
     * See [Volume Types](https://craftcms.com/docs/3.x/extend/volume-types.html) for documentation on creating volume types.
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
     * @since 3.1.0
     */
    const EVENT_BEFORE_APPLY_VOLUME_DELETE = 'beforeApplyVolumeDelete';

    /**
     * @event VolumeEvent The event that is triggered after a Asset volume is deleted.
     */
    const EVENT_AFTER_DELETE_VOLUME = 'afterDeleteVolume';

    const CONFIG_VOLUME_KEY = 'volumes';

    /**
     * @var MemoizableArray<VolumeInterface>|null
     * @see _volumes()
     */
    private $_volumes;

    /**
     * @var array|null Volume setting overrides
     */
    private $_overrides;

    /**
     * Serializer
     *
     * @since 3.5.14
     */
    public function __serialize()
    {
        $vars = get_object_vars($this);
        unset($vars['_volumes']);
        return $vars;
    }

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
            Local::class,
        ];

        $event = new RegisterComponentTypesEvent([
            'types' => $volumeTypes,
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
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return $this->getAllVolumes();
        }

        $userSession = Craft::$app->getUser();
        return ArrayHelper::where($this->getAllVolumes(), function(VolumeInterface $volume) use ($userSession) {
            return $userSession->checkPermission('viewVolume:' . $volume->uid);
        }, true, true, false);
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
        return $this->_volumes()->where('hasUrls')->all();
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
     * Returns a memoizable array of all volumes.
     *
     * @return MemoizableArray<VolumeInterface>
     */
    private function _volumes(): MemoizableArray
    {
        if ($this->_volumes === null) {
            $volumes = [];
            foreach ($this->_createVolumeQuery()->all() as $result) {
                $volumes[] = $this->createVolume($result);
            }
            $this->_volumes = new MemoizableArray($volumes);
        }

        return $this->_volumes;
    }

    /**
     * Returns all volumes.
     *
     * @return VolumeInterface[]
     */
    public function getAllVolumes(): array
    {
        return $this->_volumes()->all();
    }

    /**
     * Returns a volume by its ID.
     *
     * @param int $volumeId
     * @return VolumeInterface|null
     */
    public function getVolumeById(int $volumeId)
    {
        return $this->_volumes()->firstWhere('id', $volumeId);
    }

    /**
     * Returns a volume by its UID.
     *
     * @param string $volumeUid
     * @return VolumeInterface|null
     */
    public function getVolumeByUid(string $volumeUid)
    {
        return $this->_volumes()->firstWhere('uid', $volumeUid, true);
    }

    /**
     * Returns a volume by its handle.
     *
     * @param string $handle
     * @return VolumeInterface|null
     */
    public function getVolumeByHandle(string $handle)
    {
        return $this->_volumes()->firstWhere('handle', $handle, true);
    }

    /**
     * Returns the config for the given volume.
     *
     * @param VolumeInterface $volume
     * @return array
     * @since 3.5.0
     */
    public function createVolumeConfig(VolumeInterface $volume): array
    {
        $config = [
            'name' => $volume->name,
            'handle' => $volume->handle,
            'type' => get_class($volume),
            'hasUrls' => (bool)$volume->hasUrls,
            'url' => $volume->url,
            'titleTranslationMethod' => $volume->titleTranslationMethod,
            'titleTranslationKeyFormat' => $volume->titleTranslationKeyFormat ?: null,
            'settings' => ProjectConfigHelper::packAssociativeArrays($volume->getSettings()),
            'sortOrder' => (int)$volume->sortOrder,
        ];

        if (
            ($fieldLayout = $volume->getFieldLayout()) &&
            ($fieldLayoutConfig = $fieldLayout->getConfig())
        ) {
            if (!$fieldLayout->uid) {
                $fieldLayout->uid = $fieldLayout->id ? Db::uidById(Table::FIELDLAYOUTS, $fieldLayout->id) : StringHelper::UUID();
            }
            $config['fieldLayouts'] = [
                $fieldLayout->uid => $fieldLayoutConfig,
            ];
        }

        return $config;
    }

    /**
     * Creates or updates a volume.
     *
     * ---
     *
     * ```php
     * use craft\volumes\Local;
     *
     * $volume = new Local([
     *     'name' => 'Content Images',
     *     'handle' => 'contentImages',
     *     'hasUrls' => true,
     *     'url' => '$CONTENT_IMAGES_URL',
     *     'path' => '$CONTENT_IMAGES_PATH',
     * ]);
     *
     * if (!Craft::$app->volumes->saveVolume(($volume))) {
     *     throw new Exception('Couldn’t save volume.');
     * }
     * ```
     *
     * @param VolumeInterface $volume the volume to be saved.
     * @param bool $runValidation Whether the volume should be validated
     * @return bool Whether the volume was saved successfully
     * @throws \Throwable
     */
    public function saveVolume(VolumeInterface $volume, bool $runValidation = true): bool
    {
        $isNewVolume = $volume->getIsNew();

        // Fire a 'beforeSaveVolume' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_VOLUME)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_VOLUME, new VolumeEvent([
                'volume' => $volume,
                'isNew' => $isNewVolume,
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
        } elseif (!$volume->uid) {
            $volume->uid = Db::uidById(Table::VOLUMES, $volume->id);
        }

        $configPath = self::CONFIG_VOLUME_KEY . '.' . $volume->uid;
        $configData = $this->createVolumeConfig($volume);
        Craft::$app->getProjectConfig()->set($configPath, $configData, "Save the “{$volume->handle}” volume");

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
            $volumeRecord->titleTranslationMethod = $data['titleTranslationMethod'] ?? Field::TRANSLATION_METHOD_SITE;
            $volumeRecord->titleTranslationKeyFormat = $data['titleTranslationKeyFormat'] ?? null;
            $volumeRecord->settings = ProjectConfigHelper::unpackAssociativeArrays($data['settings']);
            $volumeRecord->uid = $volumeUid;

            if (!empty($data['fieldLayouts'])) {
                // Save the field layout
                $layout = FieldLayout::createFromConfig(reset($data['fieldLayouts']));
                $layout->id = $volumeRecord->fieldLayoutId;
                $layout->type = Asset::class;
                $layout->uid = key($data['fieldLayouts']);
                Craft::$app->getFields()->saveLayout($layout, false);
                $volumeRecord->fieldLayoutId = $layout->id;
            } elseif ($volumeRecord->fieldLayoutId) {
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
                'parentId' => ':empty:',
            ]);

            if ($rootFolder === null) {
                $rootFolderRecord = new VolumeFolder([
                    'volumeId' => $volumeRecord->id,
                    'parentId' => null,
                    'path' => '',
                    'name' => $volumeRecord->name,
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
                'isNew' => $isNewVolume,
            ]));
        }

        // Invalidate asset caches
        Craft::$app->getElements()->invalidateCachesForElementType(Asset::class);
    }

    /**
     * Reorders asset volumes.
     *
     * @param array $volumeIds
     * @return bool
     * @throws \Throwable
     */
    public function reorderVolumes(array $volumeIds): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        $uidsByIds = Db::uidsByIds(Table::VOLUMES, $volumeIds);

        foreach ($volumeIds as $volumeOrder => $volumeId) {
            if (!empty($uidsByIds[$volumeId])) {
                $volumeUid = $uidsByIds[$volumeId];
                $projectConfig->set(self::CONFIG_VOLUME_KEY . '.' . $volumeUid . '.sortOrder', $volumeOrder + 1, "Reorder volumes");
            }
        }

        return true;
    }

    /**
     * Returns any custom volume config values.
     *
     * @param string $handle The volume handle
     * @return array|null
     * @deprecated in 3.5.8. [Environment variables](https://craftcms.com/docs/3.x/config/#environmental-configuration) or [dependency injection](https://craftcms.com/knowledge-base/using-local-volumes-for-development)
     * should be used instead.
     */
    public function getVolumeOverrides(string $handle)
    {
        if ($this->_overrides === null) {
            $this->_overrides = Craft::$app->getConfig()->getConfigFromFile('volumes');
            if (!empty($this->_overrides)) {
                Craft::$app->getDeprecator()->log('volumes.php', 'Support for overriding volume configs in `config/volumes.php` has been ' .
                    'deprecated. [Environment variables](https://craftcms.com/docs/3.x/config/#environmental-configuration) or ' .
                    '[dependency injection](https://craftcms.com/knowledge-base/using-local-volumes-for-development) should be used instead.');
            }
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

        // JSON-decode the settings now so we don't have to do it twice in the event we need to remove the `path`
        if (isset($config['settings']) && is_string($config['settings'])) {
            $config['settings'] = Json::decode($config['settings']);
        }

        // Are they overriding any settings?
        if (!empty($config['handle']) && ($override = $this->getVolumeOverrides($config['handle'])) !== null) {
            // Save a reference to the original config in case the volume type is missing
            $originalConfig = $config;

            // Merge in the DB settings first, then the config file overrides
            $config = array_merge(ComponentHelper::mergeSettings($config), $override);
        }

        try {
            $volume = ComponentHelper::createComponent($config, VolumeInterface::class);
        } catch (UnknownPropertyException $e) {
            // Special case for Local volumes that are being converted to something else
            // https://github.com/craftcms/cms/issues/5277
            if (
                isset($originalConfig) &&
                $originalConfig['type'] === Local::class &&
                isset($originalConfig['settings']['path'])
            ) {
                unset($originalConfig['settings']['path']);
                return $this->createVolume($originalConfig);
            }
            throw $e;
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
        $folder = VolumeFolder::findOne(
            [
                'name' => $volume->name,
                'volumeId' => $volume->id,
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
     * @return bool
     * @throws \Throwable
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
     * @return bool
     * @throws \Throwable
     */
    public function deleteVolume(VolumeInterface $volume): bool
    {
        // Fire a 'beforeDeleteVolume' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_VOLUME)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_VOLUME, new VolumeEvent([
                'volume' => $volume,
            ]));
        }

        if (!$volume->beforeDelete()) {
            return false;
        }

        Craft::$app->getProjectConfig()->remove(self::CONFIG_VOLUME_KEY . '.' . $volume->uid, "Delete the “{$volume->handle}” volume");
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

        if ($volumeRecord->getIsNewRecord()) {
            return;
        }

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
                'volume' => $volume,
            ]));
        }

        // Invalidate asset caches
        Craft::$app->getElements()->invalidateCachesForElementType(Asset::class);
    }

    /**
     * @deprecated in 3.7.51. Unused fields will be pruned automatically as field layouts are resaved.
     */
    public function pruneDeletedField()
    {
    }

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
                'uid',
            ])
            ->from([Table::VOLUMES])
            ->orderBy(['sortOrder' => SORT_ASC]);

        // todo: remove schema version conditions after next beakpoint
        $schemaVersion = Craft::$app->getInstalledSchemaVersion();
        if (version_compare($schemaVersion, '3.1.19', '>=')) {
            $query->where(['dateDeleted' => null]);
        }
        if (version_compare($schemaVersion, '3.6.0', '>=')) {
            $query->addSelect([
                'titleTranslationMethod',
                'titleTranslationKeyFormat',
            ]);
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
