<?php

namespace craft\volumes;

use Craft;
use craft\base\FlysystemVolume;
use craft\base\LocalVolumeInterface;
use craft\errors\VolumeException;
use craft\errors\VolumeObjectExistsException;
use craft\errors\VolumeObjectNotFoundException;
use craft\helpers\FileHelper;
use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use yii\validators\InlineValidator;

/**
 * The local volume class. Handles the implementation of the local filesystem as a volume in
 * Craft.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license http://craftcms.com/license Craft License Agreement
 * @see http://craftcms.com
 * @package craft.app.volumes
 * @since 3.0.0
 */
class Local extends FlysystemVolume implements LocalVolumeInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Local Folder');
    }

    /**
     * @var string|null Path to the root of this sources local folder.
     */
    public $path;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->path !== null) {
            $this->path = str_replace('\\', '/', $this->path);
        }
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['path'], 'required'];
        $rules[] = [['path'], 'validatePath'];
        return $rules;
    }

    /**
     * @param string $attribute
     * @param array|null $params
     * @param InlineValidator $validator
     * @param string $path
     * @return void
     * @since 3.6.7
     */
    public function validatePath(string $attribute, ?array $params, InlineValidator $validator, string $path): void
    {
        if ($created = !file_exists($path)) {
            FileHelper::createDirectory($path);
        }

        $path = realpath($this->getRootPath());

        if ($path === false) {
            return;
        }

        // Make sure it’s not within any of the system directories
        $pathService = Craft::$app->getPath();
        $systemDirs = [
            Craft::getAlias('@contentMigrations'),
            Craft::getAlias('@lib'),
            $pathService->getComposerBackupsPath(false),
            $pathService->getConfigBackupPath(false),
            $pathService->getConfigDeltaPath(false),
            $pathService->getConfigPath(),
            $pathService->getDbBackupPath(false),
            $pathService->getLogPath(false),
            $pathService->getRebrandPath(false),
            $pathService->getRuntimePath(false),
            $pathService->getSiteTemplatesPath(),
            $pathService->getSiteTranslationsPath(),
            $pathService->getTestsPath(),
            $pathService->getVendorPath(),
        ];

        foreach ($systemDirs as $dir) {
            $dir = realpath($dir);
            if ($dir !== false && strpos($path . DIRECTORY_SEPARATOR, $dir . DIRECTORY_SEPARATOR) === 0) {
                $validator->addError($this, $attribute, Craft::t('app', 'Local volumes cannot be located within system directories.'));
                if ($created) {
                    FileHelper::removeDirectory($path);
                }
                break;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('_components/volumes/Local/settings',
            [
                'volume' => $this,
            ]);
    }

    /**
     * @inheritdoc
     * @since 3.4.0
     */
    public function afterSave(bool $isNew)
    {
        // If the folder doesn't exist yet, create it with a .gitignore file
        $path = $this->getRootPath();
        if (!is_dir($path)) {
            FileHelper::createDirectory($path);
            FileHelper::writeGitignoreFile($path);
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function getRootPath(): string
    {
        return FileHelper::normalizePath(Craft::parseEnv($this->path));
    }

    /**
     * @inheritdoc
     */
    public function renameDir(string $path, string $newName)
    {
        $parentDir = dirname($path);
        $newPath = ($parentDir && $parentDir !== '.' ? $parentDir . '/' : '') . $newName;

        try {
            if (!$this->filesystem()->rename($path, $newPath)) {
                throw new VolumeException('Couldn’t rename ' . $path);
            }
        } catch (FileExistsException $exception) {
            throw new VolumeObjectExistsException($exception->getMessage());
        } catch (FileNotFoundException $exception) {
            throw new VolumeObjectNotFoundException(Craft::t('app', 'Folder was not found while attempting to rename {path}!', ['path' => $path]));
        }
    }

    /**
     * @inheritdoc
     * @return LocalAdapter
     */
    protected function createAdapter(): LocalAdapter
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        return new LocalAdapter($this->getRootPath(), LOCK_EX, LocalAdapter::DISALLOW_LINKS, [
            'file' => [
                'public' => $generalConfig->defaultFileMode ?: 0644
            ],
            'dir' => [
                'public' => $generalConfig->defaultDirMode ?: 0755
            ],
        ]);
    }
}
