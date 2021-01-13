<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\VolumeInterface;
use craft\db\Table as DbTable;
use craft\elements\Asset;
use craft\elements\db\AssetQuery;
use craft\elements\db\ElementQuery;
use craft\errors\InvalidSubpathException;
use craft\errors\InvalidVolumeException;
use craft\errors\VolumeObjectNotFoundException;
use craft\gql\arguments\elements\Asset as AssetArguments;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\gql\resolvers\elements\Asset as AssetResolver;
use craft\helpers\ArrayHelper;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\FileHelper;
use craft\helpers\Gql;
use craft\helpers\Html;
use craft\web\UploadedFile;
use GraphQL\Type\Definition\Type;
use yii\base\InvalidConfigException;

/**
 * Assets represents an Assets field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Assets extends BaseRelationField
{
    /**
     * @since 3.5.11
     */
    const PREVIEW_MODE_FULL = 'full';
    /**
     * @since 3.5.11
     */
    const PREVIEW_MODE_THUMBS = 'thumbs';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Assets');
    }

    /**
     * @inheritdoc
     */
    protected static function elementType(): string
    {
        return Asset::class;
    }

    /**
     * @inheritdoc
     */
    public static function defaultSelectionLabel(): string
    {
        return Craft::t('app', 'Add an asset');
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return AssetQuery::class;
    }

    /**
     * @var bool Whether related assets should be limited to a single folder
     */
    public $useSingleFolder = false;

    /**
     * @var bool Whether it should be possible to upload files directly to the field.
     * @since 3.5.13
     */
    public $allowUploads = true;

    /**
     * @var string|null Where files should be uploaded to by default, in format
     * "folder:X", where X is the craft\models\VolumeFolder ID
     * (only used if [[useSingleFolder]] is false)
     */
    public $defaultUploadLocationSource;

    /**
     * @var string|null The subpath that files should be uploaded to by default
     * (only used if [[useSingleFolder]] is false)
     */
    public $defaultUploadLocationSubpath;

    /**
     * @var string|null Where files should be restricted to, in format
     * "folder:X", where X is the craft\models\VolumeFolder ID
     * (only used if [[useSingleFolder]] is true)
     */
    public $singleUploadLocationSource;

    /**
     * @var string|null The subpath that files should be restricted to
     * (only used if [[useSingleFolder]] is true)
     */
    public $singleUploadLocationSubpath;

    /**
     * @var bool|null Whether the available assets should be restricted to
     * [[allowedKinds]]
     */
    public $restrictFiles;

    /**
     * @var array|null The file kinds that the field should be restricted to
     * (only used if [[restrictFiles]] is true)
     */
    public $allowedKinds;

    /**
     * @var bool Whether to show input sources for volumes the user doesn’t have permission to view.
     * @since 3.4.0
     */
    public $showUnpermittedVolumes = false;

    /**
     * @var bool Whether to show files the user doesn’t have permission to view, per the
     * “View files uploaded by other users” permission.
     * @since 3.4.0
     */
    public $showUnpermittedFiles = false;

    /**
     * @var string How related assets should be presented within element index views.
     * @since 3.5.11
     */
    public $previewMode = self::PREVIEW_MODE_FULL;

    /**
     * @inheritdoc
     */
    protected $allowLargeThumbsView = true;

    /**
     * @inheritdoc
     */
    protected $settingsTemplate = '_components/fieldtypes/Assets/settings';

    /**
     * @inheritdoc
     */
    protected $inputTemplate = '_components/fieldtypes/Assets/input';

    /**
     * @inheritdoc
     */
    protected $inputJsClass = 'Craft.AssetSelectInput';

    /**
     * @var array|null References for files uploaded as data strings for this field.
     */
    private $_uploadedDataFiles;

    /**
     * @var string|null The default upload location for this field to open in modal
     */
    private $_defaultUploadLocation;

    /**
     * @inheritdoc
     */
    public function __construct(array $config = [])
    {
        // Default showUnpermittedVolumes to true for existing Assets fields
        if (isset($config['id']) && !isset($config['showUnpermittedVolumes'])) {
            $config['showUnpermittedVolumes'] = true;
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->useSingleFolder = (bool)$this->useSingleFolder;
        $this->allowUploads = (bool)$this->allowUploads;
        $this->showUnpermittedVolumes = (bool)$this->showUnpermittedVolumes;
        $this->showUnpermittedFiles = (bool)$this->showUnpermittedFiles;

        $this->defaultUploadLocationSource = $this->_folderSourceToVolumeSource($this->defaultUploadLocationSource);
        $this->singleUploadLocationSource = $this->_folderSourceToVolumeSource($this->singleUploadLocationSource);

        if (is_array($this->sources)) {
            foreach ($this->sources as &$source) {
                $source = $this->_folderSourceToVolumeSource($source);
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [
            ['allowedKinds'], 'required', 'when' => function(self $field): bool {
                return (bool)$field->restrictFiles;
            }
        ];

        $rules[] = [['previewMode'], 'in', 'range' => [self::PREVIEW_MODE_FULL, self::PREVIEW_MODE_THUMBS], 'skipOnEmpty' => false];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSourceOptions(): array
    {
        $sourceOptions = [];

        foreach (Asset::sources('settings') as $key => $volume) {
            if (!isset($volume['heading'])) {
                $sourceOptions[] = [
                    'label' => $volume['label'],
                    'value' => $volume['key']
                ];
            }
        }

        return $sourceOptions;
    }

    /**
     * Returns the available file kind options for the settings
     *
     * @return array
     */
    public function getFileKindOptions(): array
    {
        $fileKindOptions = [];

        foreach (AssetsHelper::getAllowedFileKinds() as $value => $kind) {
            $fileKindOptions[] = ['value' => $value, 'label' => $kind['label']];
        }

        return $fileKindOptions;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml($value, ElementInterface $element = null): string
    {
        try {
            return parent::inputHtml($value, $element);
        } catch (InvalidSubpathException $e) {
            return Html::tag('p', Craft::t('app', 'This field’s target subfolder path is invalid: {path}', [
                'path' => '<code>' . $this->singleUploadLocationSubpath . '</code>'
            ]), [
                'class' => ['warning', 'with-icon'],
            ]);
        } catch (InvalidVolumeException $e) {
            return Html::tag('p', $e->getMessage(), [
                'class' => ['warning', 'with-icon'],
            ]);
        }
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        $this->singleUploadLocationSource = $this->_volumeSourceToFolderSource($this->singleUploadLocationSource);
        $this->defaultUploadLocationSource = $this->_volumeSourceToFolderSource($this->defaultUploadLocationSource);

        if (is_array($this->sources)) {
            foreach ($this->sources as &$source) {
                $source = $this->_volumeSourceToFolderSource($source);
            }
        }

        return parent::getSettingsHtml();
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();
        $rules[] = 'validateFileType';
        $rules[] = 'validateFileSize';

        return $rules;
    }

    /**
     * Validates the files to make sure they are one of the allowed file kinds.
     *
     * @param ElementInterface $element
     */
    public function validateFileType(ElementInterface $element)
    {
        // Make sure the field restricts file types
        if (!$this->restrictFiles) {
            return;
        }

        $filenames = [];

        // Get all the value's assets' filenames
        /** @var AssetQuery $value */
        $value = $element->getFieldValue($this->handle);
        foreach ($value->all() as $asset) {
            /** @var Asset $asset */
            $filenames[] = $asset->filename;
        }

        // Get any uploaded filenames
        $uploadedFiles = $this->_getUploadedFiles($element);
        foreach ($uploadedFiles as $file) {
            $filenames[] = $file['filename'];
        }

        // Now make sure that they all check out
        $allowedExtensions = $this->_getAllowedExtensions();
        foreach ($filenames as $filename) {
            if (!in_array(mb_strtolower(pathinfo($filename, PATHINFO_EXTENSION)), $allowedExtensions, true)) {
                $element->addError($this->handle, Craft::t('app', '“{filename}” is not allowed in this field.', [
                    'filename' => $filename
                ]));
            }
        }
    }

    /**
     * Validates the files to make sure they are one of the allowed file kinds.
     *
     * @param ElementInterface $element
     */
    public function validateFileSize(ElementInterface $element)
    {
        $maxSize = AssetsHelper::getMaxUploadSize();

        $filenames = [];

        // Get any uploaded filenames
        $uploadedFiles = $this->_getUploadedFiles($element);
        foreach ($uploadedFiles as $file) {
            if ($file['type'] === 'data') {
                if (strlen($file['data']) > $maxSize) {
                    $filenames[] = $file['filename'];
                }
            } else {
                if (file_exists($file['location']) && (filesize($file['location']) > $maxSize)) {
                    $filenames[] = $file['filename'];
                }
            }
        }

        foreach ($filenames as $filename) {
            $element->addError($this->handle, Craft::t('app', '“{filename}” is too large.', [
                'filename' => $filename
            ]));
        }
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        // If data strings are passed along, make sure the array keys are retained.
        if (isset($value['data']) && !empty($value['data'])) {
            $this->_uploadedDataFiles = ['data' => $value['data'], 'filename' => $value['filename']];
            unset($value['data'], $value['filename']);

            /** @var Asset $class */
            $class = static::elementType();
            /** @var ElementQuery $query */
            $query = $class::find();

            $targetSite = $this->targetSiteId($element);
            if ($this->targetSiteId) {
                $query->siteId($targetSite);
            } else {
                $query
                    ->siteId('*')
                    ->unique()
                    ->preferSites([$targetSite]);
            }

            // $value might be an array of element IDs
            if (is_array($value)) {
                $query
                    ->id(array_values(array_filter($value)))
                    ->fixedOrder();

                if ($this->allowLimit && $this->limit) {
                    $query->limit($this->limit);
                }

                return $query;
            }
        }

        return parent::normalizeValue($value, $element);
    }

    /**
     * @inheritdoc
     */
    public function isValueEmpty($value, ElementInterface $element): bool
    {
        return parent::isValueEmpty($value, $element) && empty($this->_getUploadedFiles($element));
    }

    /**
     * Resolve source path for uploading for this field.
     *
     * @param ElementInterface|null $element
     * @return int
     */
    public function resolveDynamicPathToFolderId(ElementInterface $element = null): int
    {
        return $this->_determineUploadFolderId($element, true);
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getContentGqlType()
    {
        return [
            'name' => $this->handle,
            'type' => Type::listOf(AssetInterface::getType()),
            'args' => AssetArguments::getArguments(),
            'resolve' => AssetResolver::class . '::resolve',
        ];
    }

    /**
     * @inheritdoc
     */
    protected function elementPreviewHtml(ElementInterface $element): string
    {
        return Cp::elementHtml($element, 'index', Cp::ELEMENT_SIZE_SMALL, null, false, true, $this->previewMode === self::PREVIEW_MODE_FULL);
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element, bool $isNew)
    {
        // Figure out what we're working with and set up some initial variables.
        $isDraftOrRevision = $element && $element->id && ElementHelper::isDraftOrRevision($element);
        $query = $element->getFieldValue($this->handle);
        $assetsService = Craft::$app->getAssets();

        $getTargetFolderId = function() use ($element, $isDraftOrRevision): int {
            static $targetFolderId;
            return $targetFolderId = $targetFolderId ?? $this->_determineUploadFolderId($element, !$isDraftOrRevision);
        };

        // Folder creation and file uploads have been handles for propagating elements already.
        if (!$element->propagating) {
            // Were there any uploaded files?
            $uploadedFiles = $this->_getUploadedFiles($element);

            if (!empty($uploadedFiles)) {
                $targetFolderId = $getTargetFolderId();

                // Convert them to assets
                $assetIds = [];

                foreach ($uploadedFiles as $file) {
                    $tempPath = AssetsHelper::tempFilePath($file['filename']);
                    if ($file['type'] === 'upload') {
                        move_uploaded_file($file['location'], $tempPath);
                    }
                    if ($file['type'] === 'data') {
                        FileHelper::writeToFile($tempPath, $file['data']);
                    }

                    $folder = $assetsService->getFolderById($targetFolderId);
                    $asset = new Asset();
                    $asset->tempFilePath = $tempPath;
                    $asset->filename = $file['filename'];
                    $asset->newFolderId = $targetFolderId;
                    $asset->setVolumeId($folder->volumeId);
                    $asset->uploaderId = Craft::$app->getUser()->getId();
                    $asset->avoidFilenameConflicts = true;
                    $asset->setScenario(Asset::SCENARIO_CREATE);
                    Craft::$app->getElements()->saveElement($asset);

                    $assetIds[] = $asset->id;
                }

                // Add the newly uploaded IDs to the mix.
                if (\is_array($query->id)) {
                    $query = $this->normalizeValue(array_merge($query->id, $assetIds), $element);
                } else {
                    $query = $this->normalizeValue($assetIds, $element);
                }

                $element->setFieldValue($this->handle, $query);

                // Make sure that all traces of processed files are removed.
                $this->_uploadedDataFiles = null;
            }
        }

        // Are there any related assets?
        /** @var AssetQuery $query */
        /** @var Asset[] $assets */
        $assets = $query->all();

        if (!empty($assets)) {
            // Only enforce the single upload folder setting if this isn't a draft or revision
            if ($this->useSingleFolder && !$isDraftOrRevision) {
                $targetFolderId = $getTargetFolderId();
                $assetsToMove = ArrayHelper::where($assets, function(Asset $asset) use ($targetFolderId) {
                    return $asset->folderId != $targetFolderId;
                });
            } else {
                // Find the files with temp sources and just move those.
                $assetsToMove = Asset::find()
                    ->id(ArrayHelper::getColumn($assets, 'id'))
                    ->volumeId(':empty:')
                    ->all();
            }

            if (!empty($assetsToMove)) {
                $folder = $assetsService->getFolderById($getTargetFolderId());

                // Resolve all conflicts by keeping both
                foreach ($assetsToMove as $asset) {
                    $asset->avoidFilenameConflicts = true;
                    try {
                        $assetsService->moveAsset($asset, $folder);
                    } catch (VolumeObjectNotFoundException $e) {
                        // Don't freak out about that.
                        Craft::warning('Couldn’t move asset because the file doesn’t exist: ' . $e->getMessage());
                        Craft::$app->getErrorHandler()->logException($e);
                    }
                }
            }
        }

        parent::afterElementSave($element, $isNew);
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getEagerLoadingGqlConditions()
    {
        $allowedEntities = Gql::extractAllowedEntitiesFromSchema();
        $allowedVolumeUids = $allowedEntities['volumes'] ?? [];

        if (empty($allowedVolumeUids)) {
            return false;
        }

        $volumeIds = Db::idsByUids(DbTable::VOLUMES, $allowedVolumeUids);

        return ['volumeId' => array_values($volumeIds)];
    }

    /**
     * @inheritdoc
     */
    protected function inputSources(ElementInterface $element = null)
    {
        $folderId = $this->_determineUploadFolderId($element, false);
        Craft::$app->getSession()->authorize('saveAssetInVolume:' . $folderId);

        $assetsService = Craft::$app->getAssets();

        if ($this->useSingleFolder) {
            if (!$this->showUnpermittedVolumes) {
                // Make sure they have permission to view the volume
                // (Use singleUploadLocationSource here because the actual folder could belong to a temp volume)
                $volumeId = $this->_volumeIdBySourceKey($this->singleUploadLocationSource);
                $volume = $volumeId ? Craft::$app->getVolumes()->getVolumeById($volumeId) : null;
                if (!$volume || !Craft::$app->getUser()->checkPermission("viewVolume:{$volume->uid}")) {
                    return [];
                }
            }

            $folderPath = $this->_getSourcePathByFolderId($folderId);

            return [$folderPath];
        }

        $sources = [];

        // If it's a list of source IDs, we need to convert them to their folder counterparts
        if (is_array($this->sources)) {
            foreach ($this->sources as $source) {
                if (strpos($source, 'volume:') === 0) {
                    // volume:x → folder:x
                    $sources[] = $this->_volumeSourceToFolderSource($source);
                } else {
                    $sources[] = $source;
                }
            }
        } else {
            foreach (Craft::$app->getElementIndexes()->getSources(Asset::class) as $source) {
                if (isset($source['key'])) {
                    $sources[] = $source['key'];
                }
            }
        }

        // Now enforce the showUnpermittedVolumes setting
        if (!$this->showUnpermittedVolumes && !empty($sources)) {
            $userService = Craft::$app->getUser();
            return ArrayHelper::where($sources, function(string $source) use ($assetsService, $userService) {
                // If it's not a volume folder, let it through
                if (strpos($source, 'folder:') !== 0) {
                    return true;
                }
                // Only show it if they have permission to view it
                $folder = $assetsService->getFolderByUid(explode(':', $source)[1]);
                $volume = $folder ? $folder->getVolume() : null;
                return $volume && $userService->checkPermission("viewVolume:{$volume->uid}");
            }, true, true, false);
        }

        return $sources;
    }

    /**
     * @inheritdoc
     */
    protected function inputTemplateVariables($value = null, ElementInterface $element = null): array
    {
        $variables = parent::inputTemplateVariables($value, $element);

        $uploadVolume = $this->_uploadVolume();
        $variables['hideSidebar'] = $this->useSingleFolder;
        $variables['canUpload'] = (
            $this->allowUploads &&
            $uploadVolume &&
            Craft::$app->getUser()->checkPermission("saveAssetInVolume:$uploadVolume->uid")
        );
        $variables['defaultFieldLayoutId'] = $uploadVolume->fieldLayoutId ?? null;
        $variables['defaultUploadLocation'] = $this->_defaultUploadLocation;

        return $variables;
    }

    /**
     * @inheritdoc
     */
    protected function inputSelectionCriteria(): array
    {
        $criteria = parent::inputSelectionCriteria();
        $criteria['kind'] = ($this->restrictFiles && !empty($this->allowedKinds)) ? $this->allowedKinds : [];

        if ($this->showUnpermittedFiles) {
            $criteria['uploaderId'] = null;
        }

        return $criteria;
    }

    /**
     * Returns any files that were uploaded to the field.
     *
     * @param ElementInterface $element
     * @return array
     */
    private function _getUploadedFiles(ElementInterface $element): array
    {
        $uploadedFiles = [];

        if (ElementHelper::rootElement($element)->getIsRevision()) {
            return $uploadedFiles;
        }

        // Grab data strings
        if (isset($this->_uploadedDataFiles['data']) && is_array($this->_uploadedDataFiles['data'])) {
            foreach ($this->_uploadedDataFiles['data'] as $index => $dataString) {
                if (preg_match('/^data:(?<type>[a-z0-9]+\/[a-z0-9\+]+);base64,(?<data>.+)/i',
                    $dataString, $matches)) {
                    $type = $matches['type'];
                    $data = base64_decode($matches['data']);

                    if (!$data) {
                        continue;
                    }

                    if (!empty($this->_uploadedDataFiles['filename'][$index])) {
                        $filename = $this->_uploadedDataFiles['filename'][$index];
                    } else {
                        $extensions = FileHelper::getExtensionsByMimeType($type);

                        if (empty($extensions)) {
                            continue;
                        }

                        $filename = 'Uploaded_file.' . reset($extensions);
                    }

                    $uploadedFiles[] = [
                        'filename' => $filename,
                        'data' => $data,
                        'type' => 'data'
                    ];
                }
            }
        }

        // See if we have uploaded file(s).
        $paramName = $this->requestParamName($element);

        if ($paramName !== null) {
            $files = UploadedFile::getInstancesByName($paramName);

            foreach ($files as $file) {
                $uploadedFiles[] = [
                    'filename' => $file->name,
                    'location' => $file->tempName,
                    'type' => 'upload'
                ];
            }
        }

        return $uploadedFiles;
    }

    /**
     * Resolve a source path to it's folder ID by the source path and the matched source beginning.
     *
     * @param string $uploadSource
     * @param string $subpath
     * @param ElementInterface|null $element
     * @param bool $createDynamicFolders whether missing folders should be created in the process
     * @return int
     * @throws InvalidSubpathException if the subpath cannot be parsed in full
     * @throws InvalidVolumeException if the volume root folder doesn’t exist
     */
    private function _resolveVolumePathToFolderId(string $uploadSource, string $subpath, ElementInterface $element = null, bool $createDynamicFolders = true): int
    {
        $assetsService = Craft::$app->getAssets();

        $volumeId = $this->_volumeIdBySourceKey($uploadSource);

        // Make sure the volume and root folder actually exists
        if ($volumeId === null || ($rootFolder = $assetsService->getRootFolderByVolumeId($volumeId)) === null) {
            throw new InvalidVolumeException();
        }

        // Are we looking for a subfolder?
        $subpath = is_string($subpath) ? trim($subpath, '/') : '';

        if ($subpath === '') {
            // Get the root folder in the source
            $folderId = $rootFolder->id;
        } else {
            // Prepare the path by parsing tokens and normalizing slashes.
            try {
                $renderedSubpath = Craft::$app->getView()->renderObjectTemplate($subpath, $element);
            } catch (\Throwable $e) {
                throw new InvalidSubpathException($subpath, null, 0, $e);
            }

            // Did any of the tokens return null?
            if (
                $renderedSubpath === '' ||
                trim($renderedSubpath, '/') != $renderedSubpath ||
                strpos($renderedSubpath, '//') !== false
            ) {
                throw new InvalidSubpathException($subpath);
            }

            // Sanitize the subpath
            $segments = array_filter(explode('/', $renderedSubpath), function(string $segment): bool {
                return $segment !== ':ignore:';
            });
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $segments = array_map(function(string $segment) use ($generalConfig): string {
                return FileHelper::sanitizeFilename($segment, [
                    'asciiOnly' => $generalConfig->convertFilenamesToAscii
                ]);
            }, $segments);
            $subpath = implode('/', $segments);

            $folder = $assetsService->findFolder([
                'volumeId' => $volumeId,
                'path' => $subpath . '/'
            ]);

            // Ensure that the folder exists
            if (!$folder) {
                if (!$createDynamicFolders) {
                    throw new InvalidSubpathException($subpath);
                }

                $volume = Craft::$app->getVolumes()->getVolumeById($volumeId);
                $folderId = $assetsService->ensureFolderByFullPathAndVolume($subpath, $volume);
            } else {
                $folderId = $folder->id;
            }
        }

        return $folderId;
    }

    /**
     * Get a list of allowed extensions for a list of file kinds.
     *
     * @return array
     */
    private function _getAllowedExtensions(): array
    {
        if (!is_array($this->allowedKinds)) {
            return [];
        }

        $extensions = [];
        $allKinds = AssetsHelper::getFileKinds();

        foreach ($this->allowedKinds as $allowedKind) {
            foreach ($allKinds[$allowedKind]['extensions'] as $ext) {
                $extensions[] = $ext;
            }
        }

        return $extensions;
    }

    /**
     * Determine an upload folder id by looking at the settings and whether Element this field belongs to is new or not.
     *
     * @param ElementInterface|null $element
     * @param bool $createDynamicFolders whether missing folders should be created in the process
     * @return int
     * @throws InvalidSubpathException if the folder subpath is not valid
     * @throws InvalidVolumeException if there's a problem with the field's volume configuration
     */
    private function _determineUploadFolderId(ElementInterface $element = null, bool $createDynamicFolders = true): int
    {
        $userFolder = null;
        $folderId = null;

        if ($this->useSingleFolder) {
            $uploadVolume = $this->singleUploadLocationSource;
            $subpath = $this->singleUploadLocationSubpath;
            $settingName = Craft::t('app', 'Asset Location');
        } else {
            $uploadVolume = $this->defaultUploadLocationSource;
            $subpath = $this->defaultUploadLocationSubpath;
            $settingName = Craft::t('app', 'Default Asset Location');
        }

        $assets = Craft::$app->getAssets();

        try {
            if (!$uploadVolume) {
                throw new InvalidVolumeException();
            }

            $folderId = $this->_resolveVolumePathToFolderId($uploadVolume, $subpath, $element, $createDynamicFolders);
        } catch (InvalidVolumeException $e) {
            throw new InvalidVolumeException(Craft::t('app', 'The {field} field’s {setting} setting is set to an invalid volume.', [
                'field' => $this->name,
                'setting' => $settingName,
            ]), 0, $e);
        } catch (InvalidSubpathException $e) {
            // If this is a static path, go ahead and create it
            if (!preg_match('/\{|\}/', $subpath)) {
                $volumeId = $this->_volumeIdBySourceKey($uploadVolume);
                $folderId = $assets->ensureFolderByFullPathAndVolume($subpath, Craft::$app->getVolumes()->getVolumeById($volumeId), false);
            }

            // If this is a new/disabled element, the subpath probably just contained a token that returned null, like {id}
            // so use the user's upload folder instead
            if ($element === null || !$element->id || !$element->enabled || !$createDynamicFolders) {
                $userFolder = $assets->getUserTemporaryUploadFolder();
            } else {
                // Existing element, so this is just a bad subpath
                throw new InvalidSubpathException($e->subpath, Craft::t('app', 'The {field} field’s {setting} setting has an invalid subpath (“{subpath}”).', [
                    'field' => $this->name,
                    'setting' => $settingName,
                    'subpath' => $e->subpath,
                ]), 0, $e);
            }
        }

        // If we have resolved everything to a temporary user folder, fine
        if ($userFolder !== null) {
            $folderId = $userFolder->id;
            // But in all other cases, make it the default upload location, too
        } else if (!$this->useSingleFolder) {
            $this->_defaultUploadLocation = $this->_getSourcePathByFolderId($folderId);
        }

        return $folderId;
    }

    /**
     * Returns a volume ID from an upload source key.
     *
     * @param string $sourceKey
     * @return int|null
     */
    public function _volumeIdBySourceKey(string $sourceKey)
    {
        $parts = explode(':', $sourceKey, 2);

        if (count($parts) !== 2) {
            return null;
        }

        $volume = Craft::$app->getVolumes()->getVolumeByUid($parts[1]);
        return $volume ? $volume->id : null;
    }

    /**
     * Returns the target upload volume for the field.
     *
     * @return VolumeInterface|null
     */
    private function _uploadVolume()
    {
        if ($this->useSingleFolder) {
            $sourceKey = $this->singleUploadLocationSource;
        } else {
            $sourceKey = $this->defaultUploadLocationSource;
        }

        if (($volumeId = $this->_volumeIdBySourceKey($sourceKey)) === null) {
            return null;
        }

        return Craft::$app->getVolumes()->getVolumeById($volumeId);
    }

    /**
     * Convert a folder:UID source key to a volume:UID source key.
     *
     * @param mixed $sourceKey
     * @return string
     */
    private function _folderSourceToVolumeSource($sourceKey): string
    {
        if ($sourceKey && is_string($sourceKey) && strpos($sourceKey, 'folder:') === 0) {
            $parts = explode(':', $sourceKey);
            $folder = Craft::$app->getAssets()->getFolderByUid($parts[1]);

            if ($folder) {
                try {
                    $volume = $folder->getVolume();
                    return 'volume:' . $volume->uid;
                } catch (InvalidConfigException $e) {
                    // The volume is probably soft-deleted. Just pretend the folder didn't exist.
                }
            }
        }

        return (string)$sourceKey;
    }

    /**
     * Convert a volume:UID source key to a folder:UID source key.
     *
     * @param mixed $sourceKey
     * @return string
     */
    private function _volumeSourceToFolderSource($sourceKey): string
    {
        if ($sourceKey && is_string($sourceKey) && strpos($sourceKey, 'volume:') === 0) {
            $parts = explode(':', $sourceKey);
            $volume = Craft::$app->getVolumes()->getVolumeByUid($parts[1]);

            if ($volume && $folder = Craft::$app->getAssets()->getRootFolderByVolumeId($volume->id)) {
                return 'folder:' . $folder->uid;
            }
        }

        return (string)$sourceKey;
    }

    /**
     * Generate the full path for a folder in a volume in the form of folder:UID/folder:UID... based on a folder id.
     *
     * @param int $folderId
     * @return string
     */
    private function _getSourcePathByFolderId(int $folderId): string
    {
        $folder = Craft::$app->getAssets()->getFolderById($folderId);
        $folderPath = 'folder:' . $folder->uid;

        // Construct the path
        while ($folder->parentId && $folder->volumeId !== null) {
            $parent = $folder->getParent();
            $folderPath = 'folder:' . $parent->uid . '/' . $folderPath;
            $folder = $parent;
        }

        return $folderPath;
    }
}
