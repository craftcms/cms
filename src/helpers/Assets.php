<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\base\FsInterface;
use craft\base\LocalFsInterface;
use craft\elements\Asset;
use craft\enums\PeriodType;
use craft\errors\FsException;
use craft\events\RegisterAssetFileKindsEvent;
use craft\events\SetAssetFilenameEvent;
use craft\helpers\ImageTransforms as TransformHelper;
use craft\models\VolumeFolder;
use DateTime;
use yii\base\Event;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

/**
 * Class Assets
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Assets
{
    public const INDEX_SKIP_ITEMS_PATTERN = '/.*(Thumbs\.db|__MACOSX|__MACOSX\/|__MACOSX\/.*|\.DS_STORE)$/i';

    /**
     * @event SetElementTableAttributeHtmlEvent The event that is triggered when defining an asset’s filename.
     */
    public const EVENT_SET_FILENAME = 'setFilename';

    /**
     * @event RegisterAssetFileKindsEvent The event that is triggered when registering asset file kinds.
     */
    public const EVENT_REGISTER_FILE_KINDS = 'registerFileKinds';

    /**
     * @var array Supported file kinds
     * @see getFileKinds()
     */
    private static array $_fileKinds;

    /**
     * @var array Allowed file kinds
     * @see getAllowedFileKinds()
     */
    private static array $_allowedFileKinds;

    /**
     * Get a temporary file path.
     *
     * @param string $extension extension to use. "tmp" by default.
     * @return string The temporary file path
     * @throws Exception in case of failure
     */
    public static function tempFilePath(string $extension = 'tmp'): string
    {
        $extension = str_contains($extension, '.') ? pathinfo($extension, PATHINFO_EXTENSION) : $extension;
        $filename = uniqid('assets', true) . '.' . $extension;
        $path = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $filename;

        if (($handle = fopen($path, 'wb')) === false) {
            throw new Exception('Could not create temp file: ' . $path);
        }
        fclose($handle);

        return $path;
    }

    /**
     * Generates a URL for a given Assets file on a filesystem.
     *
     * @param FsInterface $fs
     * @param Asset $asset
     * @param string|null $uri Asset URI to use. Defaults to the filename.
     * @param DateTime|null $dateUpdated last datetime the target of the url was updated, if known
     * @return string
     * @throws InvalidConfigException
     */
    public static function generateUrl(FsInterface $fs, Asset $asset, ?string $uri = null, ?DateTime $dateUpdated = null): string
    {
        $revParams = self::revParams($asset, $dateUpdated);
        $pathParts = explode('/', $asset->folderPath . ($uri ?? $asset->getFilename()));
        $path = implode('/', array_map('rawurlencode', $pathParts));
        return UrlHelper::urlWithParams($fs->getRootUrl() . $path, $revParams);
    }

    /**
     * Revisions the query parameters that should be appended to asset URLs, per the `revAssetUrls` config setting.
     *
     * @param Asset $asset
     * @param DateTime|null $dateUpdated
     * @return array
     * @since 4.0.0
     */
    public static function revParams(Asset $asset, ?DateTime $dateUpdated = null): array
    {
        if (!Craft::$app->getConfig()->getGeneral()->revAssetUrls) {
            return [];
        }

        /** @var DateTime $dateModified */
        $dateModified = max($asset->dateModified, $dateUpdated ?? null);
        $v = $dateModified->getTimestamp();

        if ($asset->getHasFocalPoint()) {
            $fp = $asset->getFocalPoint();
            $v .= ",{$fp['x']},{$fp['y']}";
        }

        return compact('v');
    }

    /**
     * Get appendix for a URL based on its Source caching settings.
     *
     * @param Asset $asset
     * @param DateTime|null $dateUpdated last datetime the target of the url was updated, if known
     * @return string
     * @deprecated in 4.0.0. [[generateUrl()]] should be used instead.
     */
    public static function urlAppendix(Asset $asset, ?DateTime $dateUpdated = null): string
    {
        $revParams = self::revParams($asset, $dateUpdated);
        return $revParams ? sprintf('?%s', UrlHelper::buildQuery($revParams)) : '';
    }

    /**
     * Clean an Asset's filename.
     *
     * @param string $name
     * @param bool $isFilename if set to true (default), will separate extension
     * and clean the filename separately.
     * @param bool $preventPluginModifications if set to true, will prevent plugins from modify
     * @return string
     */
    public static function prepareAssetName(string $name, bool $isFilename = true, bool $preventPluginModifications = false): string
    {
        if ($isFilename) {
            /** @var string $baseName */
            $baseName = pathinfo($name, PATHINFO_FILENAME);
            /** @var string $extension */
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            if ($extension !== '') {
                $extension = '.' . $extension;
            }
        } else {
            $baseName = $name;
            $extension = '';
        }

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $separator = $generalConfig->filenameWordSeparator;

        if (!is_string($separator)) {
            $separator = null;
        }

        $baseNameSanitized = FileHelper::sanitizeFilename($baseName, [
            'asciiOnly' => $generalConfig->convertFilenamesToAscii,
            'separator' => $separator,
        ]);

        // Give developers a chance to do their own sanitation
        if ($isFilename && !$preventPluginModifications) {
            $event = new SetAssetFilenameEvent([
                'filename' => $baseNameSanitized,
                'originalFilename' => $baseName,
                'extension' => $extension,
            ]);
            Event::trigger(self::class, self::EVENT_SET_FILENAME, $event);
            $baseName = $event->filename;
            $extension = $event->extension;
        }

        if ($isFilename && empty($baseName)) {
            $baseName = '-';
        }

        if (!$isFilename) {
            $baseName = $baseNameSanitized;
        }

        // Put them back together, but keep the full filename w/ extension from going over 255 chars
        return substr($baseName, 0, 255 - strlen($extension)) . $extension;
    }

    /**
     * Generates a default asset title based on its filename.
     *
     * @param string $filename The asset's filename
     * @return string
     */
    public static function filename2Title(string $filename): string
    {
        return StringHelper::upperCaseFirst(implode(' ', StringHelper::toWords($filename, false, true)));
    }

    /**
     * Mirror a folder structure on a Volume.
     *
     * @param VolumeFolder $sourceParentFolder Folder who's children folder structure should be mirrored.
     * @param VolumeFolder $destinationFolder The destination folder
     * @param array $targetTreeMap map of relative path => existing folder id
     * @return array map of original folder id => new folder id
     */
    public static function mirrorFolderStructure(VolumeFolder $sourceParentFolder, VolumeFolder $destinationFolder, array $targetTreeMap = []): array
    {
        $assets = Craft::$app->getAssets();
        $sourceTree = $assets->getAllDescendantFolders($sourceParentFolder);
        $previousParent = $sourceParentFolder->getParent();
        $sourcePrefixLength = strlen($previousParent->path);
        $folderIdChanges = [];

        foreach ($sourceTree as $sourceFolder) {
            $relativePath = substr($sourceFolder->path, $sourcePrefixLength);

            // If we have a target tree map, try to see if we should just point to an existing folder.
            if (!empty($targetTreeMap) && isset($targetTreeMap[$relativePath])) {
                $folderIdChanges[$sourceFolder->id] = $targetTreeMap[$relativePath];
            } else {
                $folder = new VolumeFolder();
                $folder->name = $sourceFolder->name;
                $folder->volumeId = $destinationFolder->volumeId;
                $folder->path = ltrim(rtrim($destinationFolder->path, '/') . '/' . $relativePath, '/');

                // Any and all parent folders should be already mirrored
                $folder->parentId = ($folderIdChanges[$sourceFolder->parentId] ?? $destinationFolder->id);
                $assets->createFolder($folder);

                $folderIdChanges[$sourceFolder->id] = $folder->id;
            }
        }

        return $folderIdChanges;
    }

    /**
     * Create an Asset transfer list based on a list of Assets and an array of
     * changing folder ids.
     *
     * @param array $assets List of assets
     * @param array $folderIdChanges A map of folder id changes
     * @return array
     */
    public static function fileTransferList(array $assets, array $folderIdChanges): array
    {
        $fileTransferList = [];

        // Build the transfer list for files
        foreach ($assets as $asset) {
            $newFolderId = $folderIdChanges[$asset->folderId];

            $fileTransferList[] = [
                'assetId' => $asset->id,
                'folderId' => $newFolderId,
                'force' => true,
            ];
        }

        return $fileTransferList;
    }

    /**
     * Get a list of available periods for Cache duration settings.
     *
     * @return array
     */
    public static function periodList(): array
    {
        return [
            PeriodType::Seconds => Craft::t('app', 'Seconds'),
            PeriodType::Minutes => Craft::t('app', 'Minutes'),
            PeriodType::Hours => Craft::t('app', 'Hours'),
            PeriodType::Days => Craft::t('app', 'Days'),
            PeriodType::Months => Craft::t('app', 'Months'),
            PeriodType::Years => Craft::t('app', 'Years'),
        ];
    }

    /**
     * Sorts a folder tree by Volume sort order.
     *
     * @param VolumeFolder[] $tree array passed by reference of the sortable folders.
     */
    public static function sortFolderTree(array &$tree): void
    {
        ArrayHelper::multisort($tree, function($folder) {
            return $folder->getVolume()->sortOrder;
        });
    }

    /**
     * Returns a list of the supported file kinds.
     *
     * @return array The supported file kinds
     */
    public static function getFileKinds(): array
    {
        self::_buildFileKinds();
        return self::$_fileKinds;
    }

    /**
     * Returns a list of file kinds that are allowed to be uploaded.
     *
     * @return array The allowed file kinds
     * @since 3.1.16
     */
    public static function getAllowedFileKinds(): array
    {
        if (isset(self::$_allowedFileKinds)) {
            return self::$_allowedFileKinds;
        }

        self::$_allowedFileKinds = [];
        $allowedExtensions = array_flip(Craft::$app->getConfig()->getGeneral()->allowedFileExtensions);

        foreach (static::getFileKinds() as $kind => $info) {
            foreach ($info['extensions'] as $extension) {
                if (isset($allowedExtensions[$extension])) {
                    self::$_allowedFileKinds[$kind] = $info;
                    continue 2;
                }
            }
        }

        return self::$_allowedFileKinds;
    }

    /**
     * Returns the label of a given file kind.
     *
     * @param string $kind
     * @return string
     */
    public static function getFileKindLabel(string $kind): string
    {
        self::_buildFileKinds();
        return self::$_fileKinds[$kind]['label'] ?? Asset::KIND_UNKNOWN;
    }

    /**
     * Return a file's kind by a file's extension.
     *
     * @param string $file The file name/path
     * @return string The file kind, or "unknown" if unknown.
     */
    public static function getFileKindByExtension(string $file): string
    {
        if (($ext = pathinfo($file, PATHINFO_EXTENSION)) !== '') {
            $ext = strtolower($ext);

            foreach (static::getFileKinds() as $kind => $info) {
                if (in_array($ext, $info['extensions'], true)) {
                    return $kind;
                }
            }
        }

        return Asset::KIND_UNKNOWN;
    }

    /**
     * Parses a file location in the format of `{folder:X}filename.ext` returns the folder ID + filename.
     *
     * @param string $location
     * @return array
     * @throws InvalidArgumentException if the file location is invalid
     */
    public static function parseFileLocation(string $location): array
    {
        if (!preg_match('/^{folder:(\d+)}(.+)$/', $location, $matches)) {
            throw new InvalidArgumentException('Invalid file location format: ' . $location);
        }

        [, $folderId, $filename] = $matches;

        return [(int)$folderId, $filename];
    }

    /**
     * Builds the internal file kinds array, if it hasn't been built already.
     */
    private static function _buildFileKinds(): void
    {
        if (!isset(self::$_fileKinds)) {
            self::$_fileKinds = [
                Asset::KIND_ACCESS => [
                    'label' => 'Access',
                    'extensions' => [
                        'accdb',
                        'accde',
                        'accdr',
                        'accdt',
                        'adp',
                        'mdb',
                    ],
                ],
                Asset::KIND_AUDIO => [
                    'label' => Craft::t('app', 'Audio'),
                    'extensions' => [
                        '3gp',
                        'aac',
                        'act',
                        'aif',
                        'aifc',
                        'aiff',
                        'alac',
                        'amr',
                        'au',
                        'dct',
                        'dss',
                        'dvf',
                        'flac',
                        'gsm',
                        'iklax',
                        'ivs',
                        'm4a',
                        'm4p',
                        'mmf',
                        'mp3',
                        'mpc',
                        'msv',
                        'oga',
                        'ogg',
                        'opus',
                        'ra',
                        'tta',
                        'vox',
                        'wav',
                        'wma',
                        'wv',
                    ],
                ],
                Asset::KIND_CAPTIONS_SUBTITLES => [
                    'label' => Craft::t('app', 'Captions/Subtitles'),
                    'extensions' => [
                        'asc',
                        'cap',
                        'cin',
                        'dfxp',
                        'itt',
                        'lrc',
                        'mcc',
                        'mpsub',
                        'rt',
                        'sami',
                        'sbv',
                        'scc',
                        'smi',
                        'srt',
                        'stl',
                        'sub',
                        'tds',
                        'ttml',
                        'vtt',
                    ],
                ],
                Asset::KIND_COMPRESSED => [
                    'label' => Craft::t('app', 'Compressed'),
                    'extensions' => [
                        '7z',
                        'bz2',
                        'dmg',
                        'gz',
                        'rar',
                        's7z',
                        'tar',
                        'tgz',
                        'zip',
                        'zipx',
                    ],
                ],
                Asset::KIND_EXCEL => [
                    'label' => 'Excel',
                    'extensions' => [
                        'xls',
                        'xlsm',
                        'xlsx',
                        'xltm',
                        'xltx',
                    ],
                ],
                Asset::KIND_HTML => [
                    'label' => 'HTML',
                    'extensions' => [
                        'htm',
                        'html',
                    ],
                ],
                Asset::KIND_ILLUSTRATOR => [
                    'label' => 'Illustrator',
                    'extensions' => [
                        'ai',
                    ],
                ],
                Asset::KIND_IMAGE => [
                    'label' => Craft::t('app', 'Image'),
                    'extensions' => [
                        'avif',
                        'bmp',
                        'gif',
                        'heic',
                        'heif',
                        'jfif',
                        'jp2',
                        'jpe',
                        'jpeg',
                        'jpg',
                        'jpx',
                        'pam',
                        'pfm',
                        'pgm',
                        'png',
                        'pnm',
                        'ppm',
                        'svg',
                        'tif',
                        'tiff',
                        'webp',
                    ],
                ],
                Asset::KIND_JAVASCRIPT => [
                    'label' => 'JavaScript',
                    'extensions' => [
                        'js',
                    ],
                ],
                Asset::KIND_JSON => [
                    'label' => 'JSON',
                    'extensions' => [
                        'json',
                    ],
                ],
                Asset::KIND_PDF => [
                    'label' => 'PDF',
                    'extensions' => [
                        'pdf',
                    ],
                ],
                Asset::KIND_PHOTOSHOP => [
                    'label' => 'Photoshop',
                    'extensions' => [
                        'psb',
                        'psd',
                    ],
                ],
                Asset::KIND_PHP => [
                    'label' => 'PHP',
                    'extensions' => [
                        'php',
                    ],
                ],
                Asset::KIND_POWERPOINT => [
                    'label' => 'PowerPoint',
                    'extensions' => [
                        'potx',
                        'pps',
                        'ppsm',
                        'ppsx',
                        'ppt',
                        'pptm',
                        'pptx',
                    ],
                ],
                Asset::KIND_TEXT => [
                    'label' => Craft::t('app', 'Text'),
                    'extensions' => [
                        'text',
                        'txt',
                    ],
                ],
                Asset::KIND_VIDEO => [
                    'label' => Craft::t('app', 'Video'),
                    'extensions' => [
                        'asf',
                        'asx',
                        'avchd',
                        'avi',
                        'fla',
                        'flv',
                        'hevc',
                        'm1s',
                        'm2s',
                        'm2t',
                        'm2v',
                        'm4v',
                        'mkv',
                        'mng',
                        'mov',
                        'mp2v',
                        'mp4',
                        'mpeg',
                        'mpg',
                        'ogg',
                        'ogv',
                        'qt',
                        'rm',
                        'vob',
                        'webm',
                        'wmv',
                    ],
                ],
                Asset::KIND_WORD => [
                    'label' => 'Word',
                    'extensions' => [
                        'doc',
                        'docm',
                        'docx',
                        'dot',
                        'dotm',
                        'dotx',
                    ],
                ],
                Asset::KIND_XML => [
                    'label' => 'XML',
                    'extensions' => [
                        'xml',
                    ],
                ],
            ];

            // Merge with the extraFileKinds setting
            self::$_fileKinds = ArrayHelper::merge(self::$_fileKinds, Craft::$app->getConfig()->getGeneral()->extraFileKinds);

            // Allow plugins to modify file kinds
            $event = new RegisterAssetFileKindsEvent([
                'fileKinds' => self::$_fileKinds,
            ]);

            Event::trigger(self::class, self::EVENT_REGISTER_FILE_KINDS, $event);
            self::$_fileKinds = $event->fileKinds;

            // Sort by label
            ArrayHelper::multisort(self::$_fileKinds, 'label');
        }
    }

    /**
     * Return an image path to use in Image Editor for an Asset by id and size.
     *
     * @param int $assetId
     * @param int $size
     * @return string|false
     * @throws Exception in case of failure
     */
    public static function getImageEditorSource(int $assetId, int $size): string|false
    {
        $asset = Craft::$app->getAssets()->getAssetById($assetId);

        if (!$asset || !Image::canManipulateAsImage($asset->getExtension())) {
            return false;
        }

        $volume = $asset->getVolume();

        $imagePath = Craft::$app->getPath()->getImageEditorSourcesPath();
        $assetSourcesDirectory = $imagePath . '/' . $assetId;
        $targetSizedPath = $assetSourcesDirectory . '/' . $size;
        $targetFilePath = $targetSizedPath . '/' . $assetId . '.' . $asset->getExtension();
        FileHelper::createDirectory($targetSizedPath);

        // You never know.
        if (is_file($targetFilePath)) {
            return $targetFilePath;
        }

        // Maybe we have larger sources available we can use.
        if (FileHelper::createDirectory($assetSourcesDirectory)) {
            $handle = opendir($assetSourcesDirectory);

            if ($handle === false) {
                throw new Exception("Unable to open directory: $assetSourcesDirectory");
            }

            while (($subDir = readdir($handle)) !== false) {
                if ($subDir === '.' || $subDir === '..') {
                    continue;
                }
                $existingSize = $subDir;
                $existingAsset = $assetSourcesDirectory . DIRECTORY_SEPARATOR . $subDir . '/' . $assetId . '.' . $asset->getExtension();
                if ($existingSize >= $size && is_file($existingAsset)) {
                    Craft::$app->getImages()->loadImage($existingAsset)
                        ->scaleToFit($size, $size, false)
                        ->saveAs($targetFilePath);

                    return $targetFilePath;
                }
            }
            closedir($handle);
        }

        // No existing resources we could use.

        // For remote files, check if maxCachedImageSizes setting would work for us.
        $maxCachedSize = Craft::$app->getConfig()->getGeneral()->maxCachedCloudImageSize;

        if (!$volume->getFs() instanceof LocalFsInterface && $maxCachedSize > $size) {
            // For remote sources we get a transform source, if maxCachedImageSizes is not smaller than that.
            $localSource = TransformHelper::getLocalImageSource($asset);
            Craft::$app->getImages()->loadImage($localSource)->scaleToFit($size, $size, false)->saveAs($targetFilePath);
        } else {
            // For local source or if cached versions are smaller or not allowed, get a copy, size it and delete afterwards
            $localSource = $asset->getCopyOfFile();
            Craft::$app->getImages()->loadImage($localSource)->scaleToFit($size, $size, false)->saveAs($targetFilePath);
            FileHelper::unlink($localSource);
        }

        return $targetFilePath;
    }

    /**
     * Returns the maximum allowed upload size in bytes per all config settings combined.
     *
     * @return int|float
     */
    public static function getMaxUploadSize(): float|int
    {
        $maxUpload = ConfigHelper::sizeInBytes(ini_get('upload_max_filesize'));
        $maxPost = ConfigHelper::sizeInBytes(ini_get('post_max_size'));
        $memoryLimit = ConfigHelper::sizeInBytes(ini_get('memory_limit'));

        $uploadInBytes = min($maxUpload, $maxPost);

        if ($memoryLimit > 0) {
            $uploadInBytes = min($uploadInBytes, $memoryLimit);
        }

        $configLimit = Craft::$app->getConfig()->getGeneral()->maxUploadFileSize;

        if ($configLimit) {
            $uploadInBytes = min($uploadInBytes, $configLimit);
        }

        return $uploadInBytes;
    }

    /**
     * Returns scaled width & height values for a maximum container size.
     *
     * @param int $realWidth
     * @param int $realHeight
     * @param int $maxWidth
     * @param int $maxHeight
     * @return array The scaled width and height
     * @since 3.4.21
     */
    public static function scaledDimensions(int $realWidth, int $realHeight, int $maxWidth, int $maxHeight): array
    {
        // Avoid division by 0 errors
        if ($realWidth === 0 || $realHeight === 0) {
            return [$maxWidth, $maxHeight];
        }

        $realRatio = $realWidth / $realHeight;
        $boundingRatio = $maxWidth / $maxHeight;

        if ($realRatio >= $boundingRatio) {
            $scaledWidth = $maxWidth;
            $scaledHeight = floor($realHeight * ($scaledWidth / $realWidth));
        } else {
            $scaledHeight = $maxHeight;
            $scaledWidth = floor($realWidth * ($scaledHeight / $realHeight));
        }

        return [(int)$scaledWidth, (int)$scaledHeight];
    }

    /**
     * Parses a srcset size (e.g. `100w` or `2x`).
     *
     * @param mixed $size
     * @return array An array of the size value and unit (`w` or `x`)
     * @throws InvalidArgumentException if the size can’t be parsed
     * @since 3.5.0
     */
    public static function parseSrcsetSize(mixed $size): array
    {
        if (is_numeric($size)) {
            $size = $size . 'w';
        }
        if (!is_string($size)) {
            throw new InvalidArgumentException('Invalid srcset size');
        }
        $size = strtolower($size);
        if (!preg_match('/^([\d\.]+)(w|x)$/', $size, $match)) {
            throw new InvalidArgumentException("Invalid srcset size: $size");
        }
        return [(float)$match[1], $match[2]];
    }

    /**
     * Save a file from a filesystem locally.
     *
     * @param FsInterface $fs
     * @param string $uriPath
     * @param string $localPath
     * @return int
     * @throws FsException
     * @since 4.0.0
     */
    public static function downloadFile(FsInterface $fs, string $uriPath, string $localPath): int
    {
        $stream = $fs->getFileStream($uriPath);
        $outputStream = fopen($localPath, 'wb');

        $bytes = stream_copy_to_stream($stream, $outputStream);

        fclose($stream);
        fclose($outputStream);

        return $bytes;
    }

    /**
     * Returns the URL to an asset icon for a given extension.
     *
     * @param string $extension
     * @return string
     * @since 4.0.0
     */
    public static function iconUrl(string $extension): string
    {
        return UrlHelper::actionUrl('assets/icon', [
            'extension' => $extension,
        ]);
    }

    /**
     * Returns the file path to an asset icon for a given extension.
     *
     * @param string $extension
     * @return string
     * @since 4.0.0
     */
    public static function iconPath(string $extension): string
    {
        $path = sprintf('%s%s%s.svg', Craft::$app->getPath()->getAssetsIconsPath(), DIRECTORY_SEPARATOR, strtolower($extension));

        if (file_exists($path)) {
            return $path;
        }

        $svg = file_get_contents(Craft::getAlias('@appicons/file.svg'));

        $extLength = strlen($extension);
        if ($extLength <= 3) {
            $textSize = '20';
        } elseif ($extLength === 4) {
            $textSize = '17';
        } else {
            if ($extLength > 5) {
                $extension = substr($extension, 0, 4) . '…';
            }
            $textSize = '14';
        }

        $textNode = "<text x=\"50\" y=\"73\" text-anchor=\"middle\" font-family=\"sans-serif\" fill=\"#9aa5b1\" font-size=\"$textSize\">" . strtoupper($extension) . '</text>';
        $svg = str_replace('<!-- EXT -->', $textNode, $svg);

        FileHelper::writeToFile($path, $svg);
        return $path;
    }
}
