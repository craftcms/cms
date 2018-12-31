<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\base\LocalVolumeInterface;
use craft\base\Volume;
use craft\base\VolumeInterface;
use craft\elements\Asset;
use craft\enums\PeriodType;
use craft\events\RegisterAssetFileKindsEvent;
use craft\events\SetAssetFilenameEvent;
use craft\models\VolumeFolder;
use yii\base\Event;
use yii\base\Exception;

/**
 * Class Assets
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Assets
{
    // Constants
    // =========================================================================

    const INDEX_SKIP_ITEMS_PATTERN = '/.*(Thumbs\.db|__MACOSX|__MACOSX\/|__MACOSX\/.*|\.DS_STORE)$/i';

    /**
     * @event SetElementTableAttributeHtmlEvent The event that is triggered when defining an asset’s filename.
     */
    const EVENT_SET_FILENAME = 'setFilename';

    /**
     * @event RegisterAssetFileKindsEvent The event that is triggered when registering asset file kinds.
     */
    const EVENT_REGISTER_FILE_KINDS = 'registerFileKinds';

    // Properties
    // =========================================================================

    private static $_fileKinds;

    // Public Methods
    // =========================================================================

    /**
     * Get a temporary file path.
     *
     * @param string $extension extension to use. "tmp" by default.
     * @return string The temporary file path
     * @throws Exception in case of failure
     */
    public static function tempFilePath(string $extension = 'tmp'): string
    {
        $extension = strpos($extension, '.') !== false ? pathinfo($extension, PATHINFO_EXTENSION) : $extension;
        $filename = uniqid('assets', true) . '.' . $extension;
        $path = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $filename;
        if (($handle = fopen($path, 'wb')) === false) {
            throw new Exception('Could not create temp file: ' . $path);
        }
        fclose($handle);

        return $path;
    }

    /**
     * Generate a URL for a given Assets file in a Source Type.
     *
     * @param VolumeInterface $volume
     * @param Asset $file
     * @return string
     */
    public static function generateUrl(VolumeInterface $volume, Asset $file): string
    {
        $baseUrl = $volume->getRootUrl();
        $folderPath = $file->getFolder()->path;
        $filename = $file->filename;
        $appendix = static::urlAppendix($volume, $file);

        return $baseUrl . $folderPath . $filename . $appendix;
    }

    /**
     * Get appendix for an URL based on it's Source caching settings.
     *
     * @param VolumeInterface $volume
     * @param Asset $file
     * @return string
     */
    public static function urlAppendix(VolumeInterface $volume, Asset $file): string
    {
        $appendix = '';

        /** @var Volume $volume */
        if (!empty($volume->expires) && DateTimeHelper::isValidIntervalString($volume->expires) && $file->dateModified) {
            $appendix = '?mtime=' . $file->dateModified->format('YmdHis');
        }

        return $appendix;
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
    public static function prepareAssetName(string $name, bool $isFilename = true, bool $preventPluginModifications = false)
    {
        if ($isFilename) {
            $baseName = pathinfo($name, PATHINFO_FILENAME);
            $extension = '.' . pathinfo($name, PATHINFO_EXTENSION);
        } else {
            $baseName = $name;
            $extension = '';
        }

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $separator = $generalConfig->filenameWordSeparator;

        if (!is_string($separator)) {
            $separator = null;
        }

        if ($isFilename && !$preventPluginModifications) {
            $event = new SetAssetFilenameEvent([
                'filename' => $baseName
            ]);
            Event::trigger(self::class, self::EVENT_SET_FILENAME, $event);
            $baseName = $event->filename;
        }

        $baseName = FileHelper::sanitizeFilename($baseName, [
            'asciiOnly' => $generalConfig->convertFilenamesToAscii,
            'separator' => $separator
        ]);

        if ($isFilename && empty($baseName)) {
            $baseName = '-';
        }

        return $baseName . $extension;
    }

    /**
     * Generates a default asset title based on its filename.
     *
     * @param string $filename The asset's filename
     * @return string
     */
    public static function filename2Title(string $filename): string
    {
        $filename = mb_strtolower($filename);
        $filename = str_replace(['.', '_', '-'], ' ', $filename);
        return StringHelper::toTitleCase($filename);
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
                $assets->createFolder($folder, true);

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
                'force' => true
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
     * @param VolumeFolder[] &$tree array passed by reference of the sortable folders.
     */
    public static function sortFolderTree(array &$tree)
    {
        $sort = [];

        foreach ($tree as $topFolder) {
            /** @var Volume $volume */
            $volume = $topFolder->getVolume();
            $sort[] = $volume->sortOrder;
        }

        array_multisort($sort, $tree);
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
     * @throws Exception if the file location is invalid
     */
    public static function parseFileLocation($location)
    {
        if (!preg_match('/^\{folder:(\d+)\}(.+)$/', $location, $matches)) {
            throw new Exception('Invalid file location format: ' . $location);
        }

        list(, $folderId, $filename) = $matches;

        return [$folderId, $filename];
    }

    // Private Methods
    // =========================================================================

    /**
     * Builds the internal file kinds array, if it hasn't been built already.
     */
    private static function _buildFileKinds()
    {
        if (self::$_fileKinds === null) {
            self::$_fileKinds = [
                Asset::KIND_ACCESS => [
                    'label' => Craft::t('app', 'Access'),
                    'extensions' => [
                        'adp',
                        'accdb',
                        'mdb',
                        'accde',
                        'accdt',
                        'accdr',
                    ]
                ],
                Asset::KIND_AUDIO => [
                    'label' => Craft::t('app', 'Audio'),
                    'extensions' => [
                        '3gp',
                        'aac',
                        'act',
                        'aif',
                        'aiff',
                        'aifc',
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
                    ]
                ],
                Asset::KIND_COMPRESSED => [
                    'label' => Craft::t('app', 'Compressed'),
                    'extensions' => [
                        'bz2',
                        'tar',
                        'gz',
                        '7z',
                        's7z',
                        'dmg',
                        'rar',
                        'zip',
                        'tgz',
                        'zipx',
                    ]
                ],
                Asset::KIND_EXCEL => [
                    'label' => Craft::t('app', 'Excel'),
                    'extensions' => [
                        'xls',
                        'xlsx',
                        'xlsm',
                        'xltx',
                        'xltm',
                    ]
                ],
                Asset::KIND_FLASH => [
                    'label' => Craft::t('app', 'Flash'),
                    'extensions' => [
                        'fla',
                        'flv',
                        'swf',
                        'swt',
                        'swc',
                    ]
                ],
                Asset::KIND_HTML => [
                    'label' => Craft::t('app', 'HTML'),
                    'extensions' => [
                        'html',
                        'htm',
                    ]
                ],
                Asset::KIND_ILLUSTRATOR => [
                    'label' => Craft::t('app', 'Illustrator'),
                    'extensions' => [
                        'ai',
                    ]
                ],
                Asset::KIND_IMAGE => [
                    'label' => Craft::t('app', 'Image'),
                    'extensions' => [
                        'jfif',
                        'jp2',
                        'jpx',
                        'jpg',
                        'jpeg',
                        'jpe',
                        'tiff',
                        'tif',
                        'png',
                        'gif',
                        'bmp',
                        'webp',
                        'ppm',
                        'pgm',
                        'pnm',
                        'pfm',
                        'pam',
                        'svg',
                    ]
                ],
                Asset::KIND_JAVASCRIPT => [
                    'label' => Craft::t('app', 'JavaScript'),
                    'extensions' => [
                        'js',
                    ]
                ],
                Asset::KIND_JSON => [
                    'label' => Craft::t('app', 'JSON'),
                    'extensions' => [
                        'json',
                    ]
                ],
                Asset::KIND_PDF => [
                    'label' => Craft::t('app', 'PDF'),
                    'extensions' => ['pdf']
                ],
                Asset::KIND_PHOTOSHOP => [
                    'label' => Craft::t('app', 'Photoshop'),
                    'extensions' => [
                        'psd',
                        'psb',
                    ]
                ],
                Asset::KIND_PHP => [
                    'label' => Craft::t('app', 'PHP'),
                    'extensions' => ['php']
                ],
                Asset::KIND_POWERPOINT => [
                    'label' => Craft::t('app', 'PowerPoint'),
                    'extensions' => [
                        'pps',
                        'ppsm',
                        'ppsx',
                        'ppt',
                        'pptm',
                        'pptx',
                        'potx',
                    ]
                ],
                Asset::KIND_TEXT => [
                    'label' => Craft::t('app', 'Text'),
                    'extensions' => [
                        'txt',
                        'text',
                    ]
                ],
                Asset::KIND_VIDEO => [
                    'label' => Craft::t('app', 'Video'),
                    'extensions' => [
                        'avchd',
                        'asf',
                        'asx',
                        'avi',
                        'flv',
                        'fla',
                        'mov',
                        'm4v',
                        'mng',
                        'mpeg',
                        'mpg',
                        'm1s',
                        'm2t',
                        'mp2v',
                        'm2v',
                        'm2s',
                        'mp4',
                        'mkv',
                        'qt',
                        'flv',
                        'mp4',
                        'ogg',
                        'ogv',
                        'rm',
                        'wmv',
                        'webm',
                        'vob',
                    ]
                ],
                Asset::KIND_WORD => [
                    'label' => Craft::t('app', 'Word'),
                    'extensions' => [
                        'doc',
                        'docx',
                        'dot',
                        'docm',
                        'dotm',
                    ]
                ],
                Asset::KIND_XML => [
                    'label' => Craft::t('app', 'XML'),
                    'extensions' => [
                        'xml',
                    ]
                ],
            ];

            // Merge with the extraFileKinds setting
            static::$_fileKinds = ArrayHelper::merge(static::$_fileKinds, Craft::$app->getConfig()->getGeneral()->extraFileKinds);

            // Allow plugins to modify file kinds
            $event = new RegisterAssetFileKindsEvent([
                'fileKinds' => self::$_fileKinds,
            ]);

            Event::trigger(self::class, self::EVENT_REGISTER_FILE_KINDS, $event);
            self::$_fileKinds = $event->fileKinds;

            // Sort by label
            ArrayHelper::multisort(static::$_fileKinds, 'label');
        }
    }

    /**
     * Return an image path to use in Image Editor for an Asset by id and size.
     *
     * @param integer $assetId
     * @param integer $size
     * @return false|string
     * @throws Exception in case of failure
     */
    public static function getImageEditorSource(int $assetId, int $size)
    {
        $asset = Craft::$app->getAssets()->getAssetById($assetId);

        if (!$asset || !Image::canManipulateAsImage($asset->getExtension())) {
            return false;
        }

        /** @var Volume $volume */
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
        $maxCachedSize = Craft::$app->getAssetTransforms()->getCachedCloudImageSize();

        if (!$volume instanceof LocalVolumeInterface && $maxCachedSize > $size) {
            // For remote sources we get a transform source, if maxCachedImageSizes is not smaller than that.
            $localSource = $asset->getTransformSource();
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
    public static function getMaxUploadSize()
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
}
