<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Volume;
use craft\elements\Asset;
use craft\elements\db\AssetQuery;
use craft\elements\db\ElementQuery;
use craft\errors\InvalidSubpathException;
use craft\errors\InvalidVolumeException;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\FileHelper;
use craft\web\UploadedFile;

/**
 * Assets represents an Assets field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Assets extends BaseRelationField
{
    // Static
    // =========================================================================

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

    // Properties
    // =========================================================================

    /**
     * @var bool|null Whether related assets should be limited to a single folder
     */
    public $useSingleFolder;

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
     * @var array|null References for files uploaded as data strings for this field.
     */
    private $_uploadedDataFiles;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->allowLargeThumbsView = true;
        $this->settingsTemplate = '_components/fieldtypes/Assets/settings';
        $this->inputTemplate = '_components/fieldtypes/Assets/input';
        $this->inputJsClass = 'Craft.AssetSelectInput';
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

        foreach (AssetsHelper::getFileKinds() as $value => $kind) {
            $fileKindOptions[] = ['value' => $value, 'label' => $kind['label']];
        }

        return $fileKindOptions;
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        try {
            return parent::getInputHtml($value, $element);
        } catch (InvalidSubpathException $e) {
            return '<p class="warning">'.
                '<span data-icon="alert"></span> '.
                Craft::t('app', 'This field’s target subfolder path is invalid: {path}', [
                    'path' => '<code>'.$this->singleUploadLocationSubpath.'</code>'
                ]).
                '</p>';
        } catch (InvalidVolumeException $e) {
            return '<p class="warning">'.
                '<span data-icon="alert"></span> '.
                $e->getMessage().
                '</p>';
        }
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
        /** @var Element $element */
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
                $element->addError($this->handle, Craft::t('app', '"{filename}" is not allowed in this field.', [
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
                if (filesize($file['location']) > $maxSize) {
                    $filenames[] = $file['filename'];
                }
            }
        }

        foreach ($filenames as $filename) {
            $element->addError($this->handle, Craft::t('app', '"{filename}" is too large.', [
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
            $query = $class::find()
                ->siteId($this->targetSiteId($element));

            // $value might be an array of element IDs
            if (is_array($value)) {
                $query
                    ->id(array_filter($value))
                    ->fixedOrder();

                if ($this->allowLimit === true && $this->limit) {
                    $query->limit($this->limit);
                } else {
                    $query->limit(null);
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

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element, bool $isNew)
    {
        // Everything has been handled for propagating fields already.
        if (!$element->propagating) {
            // Were there any uploaded files?
            $uploadedFiles = $this->_getUploadedFiles($element);

            $query = $element->getFieldValue($this->handle);
            
            if (!empty($uploadedFiles)) {
                $targetFolderId = $this->_determineUploadFolderId($element);

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

                    $folder = Craft::$app->getAssets()->getFolderById($targetFolderId);
                    $asset = new Asset();
                    $asset->tempFilePath = $tempPath;
                    $asset->filename = $file['filename'];
                    $asset->newFolderId = $targetFolderId;
                    $asset->volumeId = $folder->volumeId;
                    $asset->setScenario(Asset::SCENARIO_CREATE);
                    $asset->avoidFilenameConflicts = true;
                    Craft::$app->getElements()->saveElement($asset);

                    $assetIds[] = $asset->id;
                }

                // Add the with newly uploaded IDs to the mix.
                if (\is_array($query->id)) {
                    $query = $this->normalizeValue(array_merge($query->id, $assetIds), $element);
                } else {
                    $query = $this->normalizeValue($assetIds, $element);    
                }
                
                $element->setFieldValue($this->handle, $query);

                // Make sure that all traces of processed files are removed.
                $this->_uploadedDataFiles = null;
            }

            // Are there any related assets?
            /** @var AssetQuery $query */
            /** @var Asset[] $assets */
            $assets = $query->all();

            if (!empty($assets)) {
                $targetFolderId = $targetFolderId ?? $this->_determineUploadFolderId($element);

                // Figure out which (if any) we need to move into place
                $assetsToMove = [];

                if ($this->useSingleFolder) {
                    // Move only those Assets that have had their folder changed.
                    foreach ($assets as $asset) {
                        if ($targetFolderId != $asset->folderId) {
                            $assetsToMove[] = $asset;
                        }
                    }
                } else {
                    $assetIds = [];

                    foreach ($assets as $elementFile) {
                        $assetIds[] = $elementFile->id;
                    }

                    // Find the files with temp sources and just move those.
                    $query = Asset::find();
                    Craft::configure($query, [
                        'id' => $assetIds,
                        'volumeId' => ':empty:'
                    ]);
                    $assetsToMove = $query->all();
                }

                if (!empty($assetsToMove) && !empty($targetFolderId)) {
                    $assetService = Craft::$app->getAssets();
                    $folder = $assetService->getFolderById($targetFolderId);

                    // Resolve all conflicts by keeping both
                    foreach ($assetsToMove as $asset) {
                        $asset->avoidFilenameConflicts = true;
                        $assetService->moveAsset($asset, $folder);
                    }
                }
            }
        }

        parent::afterElementSave($element, $isNew);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function inputSources(ElementInterface $element = null)
    {
        $folderId = $this->_determineUploadFolderId($element, false);
        Craft::$app->getSession()->authorize('saveAssetInVolume:'.$folderId);

        if ($this->useSingleFolder) {
            $folderPath = 'folder:'.$folderId;
            $folder = Craft::$app->getAssets()->getFolderById($folderId);

            // Construct the path
            while ($folder->parentId && $folder->volumeId !== null) {
                $parent = $folder->getParent();
                $folderPath = 'folder:'.$parent->id.'/'.$folderPath;
                $folder = $parent;
            }

            return [$folderPath];
        }

        $sources = [];

        // If it's a list of source IDs, we need to convert them to their folder counterparts
        if (is_array($this->sources)) {
            foreach ($this->sources as $source) {
                if (strpos($source, 'folder:') === 0) {
                    $sources[] = $source;
                }
            }
        } else {
            if ($this->sources === '*') {
                $sources = '*';
            }
        }

        return $sources;
    }

    /**
     * @inheritdoc
     */
    protected function inputTemplateVariables($value = null, ElementInterface $element = null): array
    {
        $variables = parent::inputTemplateVariables($value, $element);
        $variables['hideSidebar'] = (int)$this->useSingleFolder;
        $variables['defaultFieldLayoutId'] = $this->_uploadVolume()->fieldLayoutId ?? null;

        return $variables;
    }


    /**
     * @inheritdoc
     */
    protected function inputSelectionCriteria(): array
    {
        return [
            'kind' => ($this->restrictFiles && !empty($this->allowedKinds)) ? $this->allowedKinds : [],
        ];
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns any files that were uploaded to the field.
     *
     * @param ElementInterface $element
     * @return array
     */
    private function _getUploadedFiles(ElementInterface $element): array
    {
        /** @var Element $element */
        $uploadedFiles = [];

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

                    if (!empty($this->_uploadedDataFiles['filenames'][$index])) {
                        $filename = $this->_uploadedDataFiles['filenames'][$index];
                    } else {
                        $extensions = FileHelper::getExtensionsByMimeType($type);

                        if (empty($extensions)) {
                            continue;
                        }

                        $filename = 'Uploaded_file.'.reset($extensions);
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
     * @throws InvalidVolumeException if the volume root folder doesn’t exist
     * @throws InvalidSubpathException if the subpath cannot be parsed in full
     * @return int
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
                throw new InvalidSubpathException($subpath);
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
            $segments = explode('/', $renderedSubpath);
            foreach ($segments as &$segment) {
                $segment = FileHelper::sanitizeFilename($segment, [
                    'asciiOnly' => Craft::$app->getConfig()->getGeneral()->convertFilenamesToAscii
                ]);
            }
            unset($segment);
            $subpath = implode('/', $segments);

            $folder = $assetsService->findFolder([
                'volumeId' => $volumeId,
                'path' => $subpath.'/'
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
        /** @var Element $element */
        if ($this->useSingleFolder) {
            $uploadVolume = $this->singleUploadLocationSource;
            $subpath = $this->singleUploadLocationSubpath;
            $settingName = Craft::t('app', 'Upload Location');
        } else {
            $uploadVolume = $this->defaultUploadLocationSource;
            $subpath = $this->defaultUploadLocationSubpath;
            $settingName = Craft::t('app', 'Default Upload Location');
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
            // If this is a new/disabled element, the subpath probably just contained a token that returned null, like {id}
            // so use the user's upload folder instead
            if ($element === null || !$element->id || !$element->enabled || !$createDynamicFolders) {
                $userModel = Craft::$app->getUser()->getIdentity();
                $userFolder = $assets->getUserTemporaryUploadFolder($userModel);
                $folderId = $userFolder->id;
            } else {
                // Existing element, so this is just a bad subpath
                throw new InvalidSubpathException($e->subpath, Craft::t('app', 'The {field} field’s {setting} setting has an invalid subpath (“{subpath}”).', [
                    'field' => $this->name,
                    'setting' => $settingName,
                    'subpath' => $e->subpath,
                ]), 0, $e);
            }
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

        if (count($parts) !== 2 || !is_numeric($parts[1])) {
            return null;
        }

        $folder = Craft::$app->getAssets()->getFolderById((int)$parts[1]);

        return $folder->volumeId ?? null;
    }

    /**
     * Returns the target upload volume for the field.
     *
     * @return Volume|null
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
}
