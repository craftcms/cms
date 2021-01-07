<?php

namespace craft\volumes;

use Craft;
use craft\base\LocalVolumeInterface;
use craft\base\Volume;
use craft\errors\VolumeException;
use craft\helpers\FileHelper;
use craft\helpers\Path;
use craft\helpers\StringHelper;
use craft\models\VolumeListing;
use craft\models\VolumeListingMetadata;
use DirectoryIterator;
use FilesystemIterator;
use Generator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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
class Local extends Volume implements LocalVolumeInterface
{
    /**
     * @var int Default file mode when writing new files
     */
    private int $fileMode = 0644;

    /**
     * @var int Default directory mode when creating new directories
     */
    private int $dirMode = 0644;

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

        $generalConfig = Craft::$app->getConfig()->getGeneral();

        $this->fileMode = $generalConfig->defaultFileMode ?: $this->fileMode;
        $this->dirMode = $generalConfig->defaultDirMode ?: $this->dirMode;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['path'], 'required'];
        return $rules;
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
    public function getFileList(string $directory = '', bool $recursive = true): Generator
    {
        $targetDir = $this->prefixPath($directory);
        $iterator = $recursive ? $this->getRecursiveIterator($targetDir) : new DirectoryIterator($targetDir);

        /** @var DirectoryIterator $listing */
        foreach ($iterator as $listing) {
            if ($listing->isDir() && $listing->isDot()) {
                continue;
            }

            $filePath = StringHelper::removeLeft($listing->getRealPath(), $this->prefixPath());

            yield new VolumeListing([
                'path' => pathinfo($filePath, PATHINFO_DIRNAME),
                'filename' => $listing->getFilename(),
                'type' => $listing->isDir() ? 'dir' : 'file',
                'volume' => $this
            ]);
        }
    }

    /**
     * @inheritdoc
     */
    public function getFileMetadata(string $uri): VolumeListingMetadata
    {
        $targetPath = $this->prefixPath($uri);
        clearstatcache();

        $lastModified = @filemtime($targetPath);
        $filesize = is_dir($targetPath) ? null : @filesize($targetPath);
        $mimeType = FileHelper::getMimeType($targetPath);

        return new VolumeListingMetadata(compact('lastModified', 'filesize', 'mimeType'));
    }

    /**
     * @inheritdoc
     */
    public function writeFileFromStream(string $path, $stream, array $config): void
    {
        $this->ensureDirectory($path);
        $fullPath = $this->prefixPath($path);

        $targetStream = @fopen($fullPath, 'w+b');

        if (!@stream_copy_to_stream($stream, $targetStream)) {
            throw new VolumeException("Unable to copy stream to `$fullPath`");
        }

        fclose($targetStream);
        @chmod($fullPath, $this->fileMode);
    }

    /**
     * @inheritdoc
     */
    public function fileExists(string $path): bool
    {
        return file_exists($this->prefixPath($path));
    }

    /**
     * @inheritdoc
     */
    public function deleteFile(string $path): void
    {
        if (!$this->fileExists($path)) {
            return;
        }

        if (!unlink($this->prefixPath($path))) {
            Craft::warning("Tried to delete `$path`, but could not.");
        }
    }

    /**
     * @inheritdoc
     */
    public function renameFile(string $path, string $newPath): void
    {
        $this->ensureDirectoryOnVolume($newPath);
        @rename($this->prefixPath($path), $this->prefixPath($newPath));
    }

    /**
     * @inheritdoc
     */
    public function copyFile(string $path, string $newPath): void
    {
        $this->ensureDirectoryOnVolume($newPath);
        @copy($this->prefixPath($path), $this->prefixPath($newPath));
    }

    /**
     * @inheritdoc
     */
    public function getFileStream(string $uriPath)
    {
        return @fopen($this->prefixPath($uriPath), 'rb');
    }

    /**
     * @inheritdoc
     */
    public function folderExists(string $path): bool
    {
        return is_dir($this->prefixPath($path));
    }

    /**
     * @inheritdoc
     */
    public function createDir(string $path): void
    {
        FileHelper::createDirectory($this->prefixPath($path), $this->dirMode, true);
    }

    /**
     * @inheritdoc
     */
    public function deleteDir(string $path): void
    {
        FileHelper::removeDirectory($this->prefixPath($path));
    }

    /**
     * @inheritdoc
     */
    public function renameDir(string $path, string $newName): void
    {
        if (is_dir($this->prefixPath($path))) {
            @rename($path, $this->prefixPath($path));
        }
    }

    /**
     * Create the recursive iterator for traversing file system.
     *
     * @param string $targetDir
     * @return RecursiveIteratorIterator
     */
    protected function getRecursiveIterator(string $targetDir): RecursiveIteratorIterator
    {
        $directoryMode = FilesystemIterator::FOLLOW_SYMLINKS | FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_SELF;
        $recursiveIteratorMode = RecursiveIteratorIterator::SELF_FIRST;

        $recursiveDirectoryIterator = new RecursiveDirectoryIterator($targetDir, $directoryMode);
        return new RecursiveIteratorIterator($recursiveDirectoryIterator, $recursiveIteratorMode);
    }

    /**
     * Prefix the path with the root path.
     *
     * @return string
     * @throws VolumeException if path is not contained.
     */
    protected function prefixPath(string $path = ''): string
    {
        if (!Path::ensurePathIsContained($path)) {
            throw new VolumeException("The path `$path` is not contained.");
        }

        return $this->getRootPath() . DIRECTORY_SEPARATOR . $path;
    }

    /**
     * Makes sure a directory exists at a given volume path.
     *
     * @param $path
     * @throws VolumeException
     * @throws \yii\base\Exception
     */
    protected function ensureDirectoryOnVolume($path): void
    {
        FileHelper::createDirectory(pathinfo($this->prefixPath($path), PATHINFO_DIRNAME), $this->dirMode, true);
    }
}
