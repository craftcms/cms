<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fs;

use Craft;
use craft\base\Fs;
use craft\base\LocalFsInterface;
use craft\errors\FsException;
use craft\errors\FsObjectNotFoundException;
use craft\helpers\App;
use craft\helpers\FileHelper;
use craft\helpers\Path;
use craft\helpers\StringHelper;
use craft\models\FsListing;
use DirectoryIterator;
use FilesystemIterator;
use Generator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use yii\validators\InlineValidator;

/**
 * Local represents a local filesystem.
 *
 * @property-read mixed $settingsHtml
 * @property-read string $rootPath
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Local extends Fs implements LocalFsInterface
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
            self::VISIBILITY_HIDDEN => 0700,
        ],
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
    public ?string $path = null;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // Config normalization
        if (isset($config['path'])) {
            $config['path'] = rtrim(str_replace('\\', '/', $config['path']), '/');
            if ($config['path'] === '') {
                unset($config['path']);
            }
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

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
        $rules[] = [['path'], 'validatePath'];
        return $rules;
    }

    /**
     * @param string $attribute
     * @param array|null $params
     * @param InlineValidator $validator
     * @return void
     * @since 4.4.6
     */
    public function validatePath(string $attribute, ?array $params, InlineValidator $validator): void
    {
        // Make sure it’s not within any of the system directories
        $path = FileHelper::absolutePath($this->getRootPath(), '/');

        $systemDirs = Craft::$app->getPath()->getSystemPaths();

        foreach ($systemDirs as $dir) {
            $dir = FileHelper::absolutePath($dir, '/');
            if (str_starts_with("$path/", "$dir/")) {
                $validator->addError($this, $attribute, Craft::t('app', 'Local volumes cannot be located within system directories.'));
                break;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('_components/fs/Local/settings.twig',
            [
                'volume' => $this,
            ]);
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew): void
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
        $path = FileHelper::normalizePath(App::parseEnv($this->path));
        // Pass it through realpath() in case the path is symlinked
        return realpath($path) ?: $path;
    }

    /**
     * @inheritdoc
     */
    public function getFileList(string $directory = '', bool $recursive = true): Generator
    {
        $targetDir = $this->prefixPath($directory);
        try {
            $iterator = $recursive ? $this->getRecursiveIterator($targetDir) : new DirectoryIterator($targetDir);
        } catch (\UnexpectedValueException $e) {
            Craft::$app->getErrorHandler()->logException($e);
            return;
        }

        /** @var DirectoryIterator $listing */
        foreach ($iterator as $listing) {
            if ($listing->isDir() && $listing->isDot()) {
                continue;
            }

            $filePath = FileHelper::normalizePath(StringHelper::removeLeft($listing->getRealPath(), $this->prefixPath()), '/');
            $dirname = pathinfo($filePath, PATHINFO_DIRNAME);
            $basename = $listing->getFilename();

            yield new FsListing([
                'dirname' => $dirname,
                'basename' => $basename,
                'type' => $listing->isDir() ? 'dir' : 'file',
                'dateModified' => filemtime($listing->getRealPath()),
                'fileSize' => !$listing->isDir() ? filesize($listing->getRealPath()) : null,
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
            throw new FsException("Unable to get file size for “{$uri}”");
        }

        return $fileSize;
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
            throw new FsException("Unable to get date modified for “{$uri}”");
        }

        return $dateModified;
    }

    /**
     * @inheritdoc
     */
    public function writeFileFromStream(string $path, $stream, array $config = []): void
    {
        $this->createDirectory(pathinfo($path, PATHINFO_DIRNAME));
        $fullPath = $this->prefixPath($path);

        $targetStream = @fopen($fullPath, 'w+b');

        if (!@stream_copy_to_stream($stream, $targetStream)) {
            throw new FsException("Unable to copy stream to `$fullPath`");
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
    public function read(string $path): string
    {
        $stream = $this->getFileStream($path);
        $contents = stream_get_contents($stream);
        fclose($stream);

        return $contents;
    }

    /**
     * @inheritdoc
     */
    public function write(string $path, string $contents, array $config = []): void
    {
        $stream = tmpfile();
        fwrite($stream, $contents);
        rewind($stream);

        $this->writeFileFromStream($path, $stream, $config);
    }

    /**
     * @inheritdoc
     */
    public function fileExists(string $path): bool
    {
        try {
            return file_exists($this->prefixPath($path));
        } catch (FsException $exception) {
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
        $this->createDirectory(pathinfo($newPath, PATHINFO_DIRNAME));
        @rename($this->prefixPath($path), $this->prefixPath($newPath));
    }

    /**
     * @inheritdoc
     */
    public function copyFile(string $path, string $newPath): void
    {
        $this->createDirectory(pathinfo($newPath, PATHINFO_DIRNAME));
        @copy($this->prefixPath($path), $this->prefixPath($newPath));
    }

    /**
     * @inheritdoc
     */
    public function getFileStream(string $uriPath)
    {
        $path = $this->prefixPath($uriPath);
        $file = @fopen($path, 'rb');
        if (!$file) {
            throw new FsObjectNotFoundException("Unable to open $path.");
        }
        return $file;
    }

    /**
     * @inheritdoc
     */
    public function directoryExists(string $path): bool
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
        if (!is_dir($this->prefixPath($path))) {
            throw new FsObjectNotFoundException('No folder exists at path: ' . $path);
        }

        $components = explode("/", $this->prefixPath($path));
        array_pop($components);
        $components[] = $newName;
        $newPath = implode("/", $components);

        @rename($this->prefixPath($path), $newPath);
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
     * @throws FsException if path is not contained.
     */
    protected function prefixPath(string $path = ''): string
    {
        if (!Path::ensurePathIsContained($path)) {
            throw new FsException("The path `$path` is not contained.");
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
