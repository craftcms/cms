<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\helpers;

use Craft;
use craft\base\Volume;
use craft\elements\Asset;
use craft\enums\PeriodType;
use craft\events\SetAssetFilenameEvent;
use craft\models\VolumeFolder;
use yii\base\Event;
use yii\base\Exception;

/**
 * Class Assets
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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

    // Properties
    // =========================================================================

    private static $_fileKinds;

    // Public Methods
    // =========================================================================

    /**
     * Get a temporary file path.
     *
     * @param string $extension extension to use. "tmp" by default.
     *
     * @return string The temporary file path
     * @throws Exception in case of failure
     */
    public static function tempFilePath($extension = 'tmp')
    {
        $extension = strpos($extension, '.') !== false ? pathinfo($extension, PATHINFO_EXTENSION) : $extension;
        $filename = uniqid('assets', true).'.'.$extension;
        $path = Craft::$app->getPath()->getTempPath().DIRECTORY_SEPARATOR.$filename;
        if (($handle = fopen($path, 'wb')) === false) {
            throw new Exception('Could not create temp file: '.$path);
        }
        fclose($handle);

        return $path;
    }

    /**
     * Generate a URL for a given Assets file in a Source Type.
     *
     * @param Volume $volume
     * @param Asset  $file
     *
     * @return string
     */
    public static function generateUrl(Volume $volume, Asset $file)
    {
        $baseUrl = $volume->getRootUrl();
        $folderPath = $file->getFolder()->path;
        $filename = $file->filename;
        $appendix = static::urlAppendix($volume, $file);

        return $baseUrl.$folderPath.$filename.$appendix;
    }

    /**
     * Get appendix for an URL based on it's Source caching settings.
     *
     * @param Volume $volume
     * @param Asset  $file
     *
     * @return string
     */
    public static function urlAppendix(Volume $volume, Asset $file)
    {
        $appendix = '';

        if (!empty($volume->expires) && DateTimeHelper::isValidIntervalString($volume->expires)) {
            $appendix = '?mtime='.$file->dateModified->format('YmdHis');
        }

        return $appendix;
    }

    /**
     * Clean an Asset's filename.
     *
     * @param         $name
     * @param boolean $isFilename                 if set to true (default), will separate extension
     *                                            and clean the filename separately.
     * @param boolean $preventPluginModifications if set to true, will prevent plugins from modify
     *
     * @return mixed
     */
    public static function prepareAssetName($name, $isFilename = true, $preventPluginModifications = false)
    {
        if ($isFilename) {
            $baseName = pathinfo($name, PATHINFO_FILENAME);
            $extension = '.'.pathinfo($name, PATHINFO_EXTENSION);
        } else {
            $baseName = $name;
            $extension = '';
        }

        $config = Craft::$app->getConfig();
        $separator = $config->get('filenameWordSeparator');

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
            'asciiOnly' => $config->get('convertFilenamesToAscii'),
            'separator' => $separator
        ]);

        if ($isFilename && empty($baseName)) {
            $baseName = '-';
        }

        return $baseName.$extension;
    }

    /**
     * Mirror a folder structure on a Volume.
     *
     * @param VolumeFolder $sourceParentFolder Folder who's children folder structure should be mirrored.
     * @param VolumeFolder $destinationFolder  The destination folder
     * @param array        $targetTreeMap      map of relative path => existing folder id
     *
     * @return array map of original folder id => new folder id
     */
    public static function mirrorFolderStructure(VolumeFolder $sourceParentFolder, VolumeFolder $destinationFolder, $targetTreeMap = [])
    {
        $assets = Craft::$app->getAssets();
        $sourceTree = $assets->getAllDescendantFolders($sourceParentFolder);
        $previousParent = $sourceParentFolder->getParent();
        $sourcePrefixLength = strlen($previousParent->path);
        $folderIdChanges = [];

        foreach ($sourceTree as $sourceFolder) {
            $relativePath = substr($sourceFolder->path, $sourcePrefixLength);

            // If we have a target tree map, try to see if we should just point to an existing folder.
            if ($targetTreeMap && isset($targetTreeMap[$relativePath])) {
                $folderIdChanges[$sourceFolder->id] = $targetTreeMap[$relativePath];
            } else {
                $folder = new VolumeFolder();
                $folder->name = $sourceFolder->name;
                $folder->volumeId = $destinationFolder->volumeId;
                $folder->path = ltrim(rtrim($destinationFolder->path, '/').'/'.$relativePath, '/');

                // Any and all parent folders should be already mirrored
                $folder->parentId = (isset($folderIdChanges[$sourceFolder->parentId]) ? $folderIdChanges[$sourceFolder->parentId] : $destinationFolder->id);

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
     * @param array $assets          List of assets
     * @param array $folderIdChanges A map of folder id changes
     * @param bool  $merge           If set to true, files will be merged in folders
     *
     * @return array
     */
    public static function fileTransferList($assets, $folderIdChanges, $merge = false)
    {
        $fileTransferList = [];

        // Build the transfer list for files
        foreach ($assets as $asset) {
            $newFolderId = $folderIdChanges[$asset->folderId];
            $transferItem = [
                'assetId' => $asset->id,
                'folderId' => $newFolderId
            ];

            // If we're merging, preemptively figure out if there'll be conflicts and resolve them
            if ($merge) {
                $conflictingAsset = Asset::find()
                    ->folderId($newFolderId)
                    ->filename(Db::escapeParam($asset->filename))
                    ->one();

                if ($conflictingAsset) {
                    $transferItem['userResponse'] = 'replace';
                }
            }

            $fileTransferList[] = $transferItem;
        }

        return $fileTransferList;
    }

    /**
     * Get a list of available periods for Cache duration settings.
     *
     * @return array
     */
    public static function periodList()
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
     * @param array &$tree array passed by reference of the sortable folders.
     */
    public static function sortFolderTree(&$tree)
    {
        $sort = [];

        foreach ($tree as $topFolder) {
            /**
             * @var VolumeFolder $topFolder
             */
            $sort[] = $topFolder->getVolume()->sortOrder;
        }

        array_multisort($sort, $tree);
    }

    /**
     * Returns a list of the supported file kinds.
     *
     * @return array The supported file kinds
     */
    public static function getFileKinds()
    {
        self::_buildFileKinds();

        return self::$_fileKinds;
    }

    /**
     * Returns the label of a given file kind.
     *
     * @param string $kind
     *
     * @return array
     */
    public static function getFileKindLabel($kind)
    {
        self::_buildFileKinds();

        if (isset(self::$_fileKinds[$kind]['label'])) {
            return self::$_fileKinds[$kind]['label'];
        }

        return null;
    }

    /**
     * Return a file's kind by a given extension.
     *
     * @param string $file The file name/path
     *
     * @return string The file kind, or "unknown" if unknown.
     */
    public static function getFileKindByExtension($file)
    {
        if (($ext = pathinfo($file, PATHINFO_EXTENSION)) !== '') {
            $ext = strtolower($ext);

            foreach (static::getFileKinds() as $kind => $info) {
                if (in_array($ext, $info['extensions'])) {
                    return $kind;
                }
            }
        }

        return 'unknown';
    }

    // Private Methods
    // =========================================================================

    /**
     * Builds the internal file kinds array, if it hasn't been built already.
     *
     * @return void
     */
    private static function _buildFileKinds()
    {
        if (self::$_fileKinds === null) {
            self::$_fileKinds = [
                'access' => [
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
                'audio' => [
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
                'compressed' => [
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
                'excel' => [
                    'label' => Craft::t('app', 'Excel'),
                    'extensions' => [
                        'xls',
                        'xlsx',
                        'xlsm',
                        'xltx',
                        'xltm',
                    ]
                ],
                'flash' => [
                    'label' => Craft::t('app', 'Flash'),
                    'extensions' => [
                        'fla',
                        'flv',
                        'swf',
                        'swt',
                        'swc',
                    ]
                ],
                'html' => [
                    'label' => Craft::t('app', 'HTML'),
                    'extensions' => [
                        'html',
                        'htm',
                    ]
                ],
                'illustrator' => [
                    'label' => Craft::t('app', 'Illustrator'),
                    'extensions' => [
                        'ai',
                    ]
                ],
                'image' => [
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
                'javascript' => [
                    'label' => Craft::t('app', 'Javascript'),
                    'extensions' => [
                        'js',
                    ]
                ],
                'json' => [
                    'label' => Craft::t('app', 'JSON'),
                    'extensions' => [
                        'json',
                    ]
                ],
                'pdf' => [
                    'label' => Craft::t('app', 'PDF'),
                    'extensions' => ['pdf']
                ],
                'photoshop' => [
                    'label' => Craft::t('app', 'Photoshop'),
                    'extensions' => [
                        'psd',
                        'psb',
                    ]
                ],
                'php' => [
                    'label' => Craft::t('app', 'PHP'),
                    'extensions' => ['php']
                ],
                'powerpoint' => [
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
                'text' => [
                    'label' => Craft::t('app', 'Text'),
                    'extensions' => [
                        'txt',
                        'text',
                    ]
                ],
                'video' => [
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
                'word' => [
                    'label' => Craft::t('app', 'Word'),
                    'extensions' => [
                        'doc',
                        'docx',
                        'dot',
                        'docm',
                        'dotm',
                    ]
                ],
                'xml' => [
                    'label' => Craft::t('app', 'XML'),
                    'extensions' => [
                        'xml',
                    ]
                ],
            ];
        }
    }
}
