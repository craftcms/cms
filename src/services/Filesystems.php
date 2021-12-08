<?php

namespace craft\services;

use Craft;
use craft\base\FsInterface;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Asset;
use craft\errors\MissingComponentException;
use craft\events\ConfigEvent;
use craft\events\FsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\fs\Local;
use craft\fs\MissingFs;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\records\Filesystem as FilesystemRecord;
use Throwable;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * Class Filesystems
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license http://craftcms.com/license Craft License Agreement
 * @see http://craftcms.com
 * @package craft.app.services
 * @since 4.0.0
 *
 * @property-read FsInterface[] $allFilesystems
 */
class Filesystems extends Component
{
    /**
     * @event FsEvent The event that is triggered before a filesystem is saved.
     */
    public const EVENT_BEFORE_SAVE_FILESYSTEM = 'beforeSaveFilesystem';

    /**
     * @event FsEvent The event that is triggered after a filesystem is saved.
     */
    public const EVENT_AFTER_SAVE_FILESYSTEM = 'afterSaveFilesystem';

    /**
     * @event FsEvent The event that is triggered before a filesystem is deleted.
     */
    public const EVENT_BEFORE_DELETE_FILESYSTEM = 'beforeDeleteFilesystem';

    /**
     * @event FsEvent The event that is triggered after a filesystem is deleted.
     */
    public const EVENT_AFTER_DELETE_FILESYSTEM = 'afterDeleteFilesystem';

    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering filesystem types.
     */
    public const EVENT_REGISTER_FILESYSTEM_TYPES = 'registerFilesystemTypes';

    /**
     * @var MemoizableArray<FsInterface>|null
     * @see _filesystems()
     */
    private ?MemoizableArray $_filesystems = null;

    /**
     * Serializer
     *
     * @since 3.5.14
     */
    public function __serialize(): array
    {
        $vars = get_object_vars($this);
        unset($vars['_filesystems']);
        return $vars;
    }

    // FileSystems

    // -------------------------------------------------------------------------
    /**
     * Returns the config for a given filesystem
     *
     * @param FsInterface $fs
     * @return array
     */
    public function createFilesystemConfig(FsInterface $fs): array
    {
        $config = [
            'name' => $fs->name,
            'handle' => $fs->handle,
            'type' => get_class($fs),
            'settings' => ProjectConfigHelper::packAssociativeArrays($fs->getSettings()),
        ];

        return $config;
    }

    /**
     * Returns all registered filesystem types.
     *
     * @return string[]
     */
    public function getAllFilesystemTypes(): array
    {
        $fsTypes = [
            Local::class,
        ];

        $event = new RegisterComponentTypesEvent([
            'types' => $fsTypes,
        ]);

        $this->trigger(self::EVENT_REGISTER_FILESYSTEM_TYPES, $event);

        return $event->types;
    }

    /**
     * Returns a memoizable array of all filesystems.
     *
     * @return MemoizableArray<FsInterface>
     */
    private function _filesystems(): MemoizableArray
    {
        if (!isset($this->_filesystems)) {
            $filesystems = [];
            foreach ($this->_createFsQuery()->all() as $result) {
                $filesystems[] = $this->createFilesystem($result);
            }
            $this->_filesystems = new MemoizableArray($filesystems);
        }

        return $this->_filesystems;
    }

    /**
     * Returns all volumes.
     *
     * @return FsInterface[]
     */
    public function getAllFilesystems(): array
    {
        return $this->_filesystems()->all();
    }

    /**
     * Returns a filesystem by its ID.
     *
     * @param int $fsId
     * @return FsInterface|null
     */
    public function getFilesystemById(int $fsId): ?FsInterface
    {
        return $this->_filesystems()->firstWhere('id', $fsId);
    }

    /**
     * Returns a filesystem by its handle.
     *
     * @param string $handle
     * @return FsInterface|null
     */
    public function getFilesystemByHandle(string $handle): ?FsInterface
    {
        return $this->_filesystems()->firstWhere('handle', $handle, true);
    }

    /**
     * Creates or updates a filesystem.
     *
     * @param FsInterface $fs the filesystem to be saved.
     * @param bool $runValidation Whether the volume should be validated
     * @return bool Whether the filesystem was saved successfully
     * @throws \Throwable
     */
    public function saveFilesystem(FsInterface $fs, bool $runValidation = true): bool
    {
        $isNewFs = $fs->getIsNew();

        // Fire a 'beforeSaveFilesystem' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_FILESYSTEM)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_FILESYSTEM, new FsEvent([
                'filesystem' => $fs,
                'isNew' => $isNewFs,
            ]));
        }

        if (!$fs->beforeSave($isNewFs)) {
            return false;
        }

        if ($runValidation && !$fs->validate()) {
            Craft::info('Filesystem not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNewFs) {
            $fs->uid = StringHelper::UUID();
        } else if (!$fs->uid) {
            $fs->uid = Db::uidById(Table::FILESYSTEMS, $fs->id);
        }

        $configPath = ProjectConfig::PATH_FILESYSTEMS . '.' . $fs->uid;
        $configData = $this->createFilesystemConfig($fs);
        Craft::$app->getProjectConfig()->set($configPath, $configData, "Save the “{$fs->handle}” filesystem");

        if ($isNewFs) {
            $fs->id = Db::idByUid(Table::FILESYSTEMS, $fs->uid);
        }

        // Fire a 'afterSaveFilesystem' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_FILESYSTEM)) {
            $this->trigger(self::EVENT_AFTER_SAVE_FILESYSTEM, new FsEvent([
                'filesystem' => $fs,
                'isNew' => $isNewFs,
            ]));
        }

        return true;
    }

    /**
     * Handle volume change
     *
     * @param ConfigEvent $event
     */
    public function handleChangeFilesystem(ConfigEvent $event): void
    {
        $fsUid = $event->tokenMatches[0];
        $data = $event->newValue;

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $fsRecord = $this->_getFsRecord($fsUid, true);
            $isNewFs = $fsRecord->getIsNewRecord();

            $fsRecord->name = $data['name'];
            $fsRecord->handle = $data['handle'];
            $fsRecord->type = $data['type'];
            $fsRecord->settings = ProjectConfigHelper::unpackAssociativeArrays($data['settings']);
            $fsRecord->uid = $fsUid;
            $fsRecord->save(false);

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_filesystems = null;

        $fs = $this->getFilesystemByHandle($fsRecord->handle);
        $fs->afterSave($isNewFs);

        // Invalidate asset caches
        Craft::$app->getElements()->invalidateCachesForElementType(Asset::class);
    }

    /**
     * Creates a file system with a given config.
     *
     * @param mixed $config The asset volume’s class name, or its config, with a `type` value and optionally a `settings` value
     * @return FsInterface The asset volume
     */
    public function createFilesystem($config): FsInterface
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        // JSON-decode the settings now so we don't have to do it twice in the event we need to remove the `path`
        if (isset($config['settings']) && is_string($config['settings'])) {
            $config['settings'] = Json::decode($config['settings']);
        }

        try {
            $fs = ComponentHelper::createComponent($config, FsInterface::class);
        } catch (MissingComponentException|InvalidConfigException $e) {
            $config['errorMessage'] = $e->getMessage();
            $config['expectedType'] = $config['type'];
            unset($config['type']);

            $fs = new MissingFs($config);
        }

        $fs->id = (int)$fs->id;
        return $fs;
    }

    /**
     * Deletes an asset volume.
     *
     * @param FsInterface $fs The volume to delete
     * @return bool
     * @throws Throwable
     */
    public function deleteFilesystem(FsInterface $fs): bool
    {
        if (!$fs->beforeDelete()) {
            return false;
        }

        // Fire a 'beforeDeleteFilesystem' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_FILESYSTEM)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_FILESYSTEM, new FsEvent([
                'filesystem' => $fs,
            ]));
        }

        Craft::$app->getProjectConfig()->remove(ProjectConfig::PATH_FILESYSTEMS . '.' . $fs->uid, "Delete the “{$fs->handle}” filesystem");

        return true;
    }

    /**
     * Handle volume getting deleted
     *
     * @param ConfigEvent $event
     */
    public function handleDeletedFilesystem(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $fsRecord = $this->_getFsRecord($uid);

        if ($fsRecord->getIsNewRecord()) {
            return;
        }

        $fs = $this->getFilesystemById($fsRecord->id);

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            // Delete the fs
            $db->createCommand()
                ->delete(Table::FILESYSTEMS, ['id' => $fsRecord->id])
                ->execute();

            $fs->afterDelete();

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_filesystems = null;

        // Fire a 'afterDeleteFilesystem' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_FILESYSTEM)) {
            $this->trigger(self::EVENT_AFTER_DELETE_FILESYSTEM, new FsEvent([
                'filesystem' => $fs,
            ]));
        }

        // Invalidate asset caches
        Craft::$app->getElements()->invalidateCachesForElementType(Asset::class);
    }

    /**
     * Returns a DbCommand object prepped for retrieving filesystems.
     *
     * @return Query
     */
    private function _createFsQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'dateCreated',
                'dateUpdated',
                'name',
                'handle',
                'type',
                'settings',
                'uid',
            ])
            ->from([Table::FILESYSTEMS])
            ->orderBy(['handle' => SORT_ASC]);
    }

    /**
     * Gets a volume's record by uid.
     *
     * @param string $uid
     * @return FilesystemRecord
     */
    private function _getFsRecord(string $uid): FilesystemRecord
    {
        $query = FilesystemRecord::find();
        $query->andWhere(['uid' => $uid]);
        return $query->one() ?? new FilesystemRecord();
    }
}
