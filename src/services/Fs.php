<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\FsInterface;
use craft\base\MemoizableArray;
use craft\errors\MissingComponentException;
use craft\events\RegisterComponentTypesEvent;
use craft\fs\Local;
use craft\fs\MissingFs;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use Throwable;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * Filesystems service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getfs()|`Craft::$app->fs`]].
 *
 * @property-read FsInterface[] $allFilesystems All filesystems
 * @property-read string[] $allFilesystemTypes All registered filesystem types
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Fs extends Component
{
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
     * Returns the config for the given filesystem.
     *
     * @param FsInterface $fs
     * @return array
     */
    public function createFilesystemConfig(FsInterface $fs): array
    {
        return [
            'name' => $fs->name,
            'type' => get_class($fs),
            'hasUrls' => $fs->hasUrls,
            'url' => $fs->url,
            'settings' => ProjectConfigHelper::packAssociativeArrays($fs->getSettings()),
        ];
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
            $configs = Craft::$app->getProjectConfig()->get(ProjectConfig::PATH_FS) ?? [];
            $filesystems = array_map(function(string $handle, array $config) {
                $config['handle'] = $handle;
                $config['settings'] = ProjectConfigHelper::unpackAssociativeArrays($config['settings']);
                return $this->createFilesystem($config);
            }, array_keys($configs), $configs);
            $this->_filesystems = new MemoizableArray($filesystems);
        }

        return $this->_filesystems;
    }

    /**
     * Returns all filesystems.
     *
     * @return FsInterface[]
     */
    public function getAllFilesystems(): array
    {
        return $this->_filesystems()->all();
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
     * @param bool $runValidation Whether the filesystem should be validated
     * @return bool Whether the filesystem was saved successfully
     * @throws \Throwable
     */
    public function saveFilesystem(FsInterface $fs, bool $runValidation = true): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $configPath = sprintf('%s.%s', ProjectConfig::PATH_FS, $fs->handle);
        $isNewFs = $projectConfig->get($configPath) !== null;

        if (!$fs->beforeSave($isNewFs)) {
            return false;
        }

        if ($runValidation && !$fs->validate()) {
            Craft::info('Filesystem not saved due to validation error.', __METHOD__);
            return false;
        }

        $configData = $this->createFilesystemConfig($fs);
        $projectConfig->set($configPath, $configData, "Save the “{$fs->handle}” filesystem");

        return true;
    }

    /**
     * Creates a filesystem from a given config.
     *
     * @param mixed $config The filesystem’s class name, or its config, with a `type` value and optionally a `settings` value
     * @return FsInterface The filesystem
     */
    public function createFilesystem($config): FsInterface
    {
        try {
            return ComponentHelper::createComponent($config, FsInterface::class);
        } catch (MissingComponentException | InvalidConfigException $e) {
            $config['errorMessage'] = $e->getMessage();
            $config['expectedType'] = $config['type'];
            unset($config['type']);
            return new MissingFs($config);
        }
    }

    /**
     * Removes a filesystem.
     *
     * @param FsInterface $fs The filesystem to remove
     * @return bool
     * @throws Throwable
     */
    public function removeFilesystem(FsInterface $fs): bool
    {
        if (!$fs->beforeDelete()) {
            return false;
        }

        Craft::$app->getProjectConfig()->remove(sprintf('%s.%s', ProjectConfig::PATH_FS, $fs->handle), "Remove the “{$fs->handle}” filesystem");

        return true;
    }
}
