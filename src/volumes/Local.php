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
 *
 * @property-read mixed $settingsHtml
 * @property-read string $rootPath
 */
class Local extends Volume implements LocalVolumeInterface
{
    public const VISIBILITY_FILE = 'file';
    public const VISIBILITY_DIR = 'dir';

    /**
     * @var int[][] Visibility map
     */
    protected array $visibilityMap = [
        self::VISIBILITY_FILE => [
            self::VISIBILITY_DEFAULT => 0644,
            self::VISIBILITY_PUBLIC => 0644,
            self::VISIBILITY_HIDDEN => 0600,
        ],
        self::VISIBILITY_DIR => [
            self::VISIBILITY_DEFAULT => 0775,
            self::VISIBILITY_PUBLIC => 0775,
            self::VISIBILITY_HIDDEN => 0700
        ]
    ];

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

        if ($generalConfig->defaultFileMode) {
            $this->visibilityMap[self::VISIBILITY_FILE][self::VISIBILITY_DEFAULT] = $generalConfig->defaultFileMode;
        }

        if ($generalConfig->defaultFileMode) {
            $this->visibilityMap[self::VISIBILITY_DIR][self::VISIBILITY_DEFAULT] = $generalConfig->defaultDirMode;
        }
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
    public function getFileSize(string $uri): int
    {
        $targetPath = $this->prefixPath($uri);
        clearstatcache();
        $fileSize = is_file($targetPath) ? filesize($targetPath) : false;

        if ($fileSize === false) {
            throw new VolumeException("Unable to get file size for “{$uri}”");
        }
    }

    /**
     * @inheritdoc
     */
    public function getDateModified(string $uri): int
    {
        $targetPath = $this->prefixPath($uri);
        clearstatcache();
        $dateModified = filemtime($targetPath);

        if ($dateModified === false) {
            throw new VolumeException("Unable to get date modified for “{$uri}”");
        }
    }

    /**
     * @inheritdoc
     */
    public function writeFileFromStream(string $path, $stream, array $config = []): void
    {
        $this->createDirectory(pathinfo($path, PATHINFO_DIRNAME), []);
        $fullPath = $this->prefixPath($path);

        $targetStream = @fopen($fullPath, 'w+b');

        if (!@stream_copy_to_stream($stream, $targetStream)) {
            throw new VolumeException("Unable to copy stream to `$fullPath`");
        }

        fclose($targetStream);

        $visibility = $this->resolveVisibility(self::VISIBILITY_FILE, $config);

        if ($visibility) {
            @chmod($fullPath, $visibility);
        }
    }

    /**
     * @inheritdoc
     */
    public function fileExists(string $path): bool
    {
        try {
            return file_exists($this->prefixPath($path));
        } catch (VolumeException $exception) {
            Craft::$app->getErrorHandler()->logException($exception);
        }

        return false;
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
        $this->createDirectory($newPath);
        @rename($this->prefixPath($path), $this->prefixPath($newPath));
    }

    /**
     * @inheritdoc
     */
    public function copyFile(string $path, string $newPath): void
    {
        $this->createDirectory($newPath);
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
    public function createDirectory(string $path, array $config = []): void
    {
        $dirPath = StringHelper::removeRight($this->prefixPath($path), '.');
        FileHelper::createDirectory($dirPath, $this->resolveVisibility(self::VISIBILITY_DIR, $config), true);
    }

    /**
     * @inheritdoc
     */
    public function deleteDirectory(string $path): void
    {
        FileHelper::removeDirectory($this->prefixPath($path));
    }

    /**
     * @inheritdoc
     */
    public function renameDirectory(string $path, string $newName): void
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
     * @param string $path
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
     * Resolve visibility by a config array and type.
     *
     * @param string $type
     * @param array $config
     * @return int
     */
    protected function resolveVisibility(string $type, array $config = []): int
    {
        if (empty($config[self::CONFIG_VISIBILITY])) {
            return $this->visibilityMap[$type][self::VISIBILITY_DEFAULT];
        }

        return $this->visibilityMap[$type][$config[self::CONFIG_VISIBILITY]];
    }
}
