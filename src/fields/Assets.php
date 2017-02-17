<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\fields;

// @TODO asset source -> volume in settings and everywhere.

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Volume;
use craft\elements\Asset;
use craft\elements\db\AssetQuery;
use craft\elements\db\ElementQuery;
use craft\errors\AssetConflictException;
use craft\errors\InvalidSubpathException;
use craft\errors\InvalidVolumeException;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\models\VolumeFolder;
use craft\web\UploadedFile;
use yii\base\ErrorException;

/**
 * Assets represents an Assets field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
     * @var int|null The asset volume ID that files should be uploaded to by default (only used if [[useSingleFolder]] is false)
     */
    public $defaultUploadLocationSource;

    /**
     * @var string|null The subpath that files should be uploaded to by default (only used if [[useSingleFolder]] is false)
     */
    public $defaultUploadLocationSubpath;

    /**
     * @var int|null The asset volume ID that files should be restricted to (only used if [[useSingleFolder]] is true)
     */
    public $singleUploadLocationSource;

    /**
     * @var string|null The subpath that files should be restricted to (only used if [[useSingleFolder]] is true)
     */
    public $singleUploadLocationSubpath;

    /**
     * @var bool|null Whether the available assets should be restricted to [[allowedKinds]]
     */
    public $restrictFiles;

    /**
     * @var array|null The file kinds that the field should be restricted to (only used if [[restrictFiles]] is true)
     */
    public $allowedKinds;

    /**
     * Uploaded files that failed validation.
     *
     * @var UploadedFile[]
     */
    private $_failedFiles = [];

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
     * Returns the available folder options for the settings
     *
     * @return array
     */
    public function getFolderOptions(): array
    {
        $folderOptions = [];

        foreach (Asset::sources('settings') as $key => $volume) {
            if (!isset($volume['heading'])) {
                $folderOptions[] = [
                    'label' => $volume['label'],
                    'value' => $volume['key']
                ];
            }
        }

        return $folderOptions;
    }

    /**
     * @inheritdoc
     */
    public function getSourceOptions(): array
    {
        $sourceOptions = [];

        foreach (Craft::$app->getVolumes()->getAllVolumes() as $volume) {
            /** @var Volume $volume */
            $sourceOptions[] = [
                'label' => $volume->name,
                'value' => $volume->id
            ];
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
            $message = $this->useSingleFolder ? Craft::t('app', 'This field’s single upload location Volume is missing') : Craft::t('app', 'This field’s default upload location Volume is missing');

            return '<p class="warning">'.
                '<span data-icon="alert"></span> '.
                $message.
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

        return $rules;
    }

    /**
     * Validates the files to make sure they are one of the allowed file kinds.
     *
     * @param ElementInterface $element
     *
     * @return void
     */
    public function validateFileType(ElementInterface $element)
    {
        /** @var Element $element */
        $value = $element->getFieldValue($this->handle);

        // Check if this field restricts files and if files are passed at all.
        if ($this->restrictFiles && !empty($this->allowedKinds) && is_array($value) && !empty($value)) {
            $allowedExtensions = $this->_getAllowedExtensions();

            foreach ($value as $assetId) {
                $file = Craft::$app->getAssets()->getAssetById($assetId);

                if ($file && !in_array(mb_strtolower(pathinfo($file->filename, PATHINFO_EXTENSION)), $allowedExtensions, true)) {
                    $element->addError($this->handle, Craft::t('app', '"{filename}" is not allowed in this field.', ['filename' => $file->filename]));
                }
            }
        }

        foreach ($this->_failedFiles as $file) {
            $element->addError($this->handle, Craft::t('app', '"{filename}" is not allowed in this field.', ['filename' => $file]));
        }
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        // If data strings are passed along, make sure the array keys are retained.
        if (isset($value['data']) && !empty($value['data'])) {
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

        return parent::normalizeValue($value,
            $element);
    }


    /**
     * Resolve source path for uploading for this field.
     *
     * @param ElementInterface|null $element
     *
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
     *
     * @todo All of the validation stuff here should be moved to an actual validation function
     */
    public function beforeElementSave(ElementInterface $element, bool $isNew): bool
    {
        /** @var Element $element */
        $incomingFiles = [];

        /** @var AssetQuery $newValue */
        $query = $element->getFieldValue($this->handle);
        $value = !empty($query->id) ? $query->id : [];

        // Grab data strings
        if (isset($value['data']) && is_array($value['data'])) {
            foreach ($value['data'] as $index => $dataString) {
                if (preg_match('/^data:(?<type>[a-z0-9]+\/[a-z0-9]+);base64,(?<data>.+)/i',
                    $dataString, $matches)) {
                    $type = $matches['type'];
                    $data = base64_decode($matches['data']);

                    if (!$data) {
                        continue;
                    }

                    if (!empty($value['filenames'][$index])) {
                        $filename = $value['filenames'][$index];
                    } else {
                        $extensions = FileHelper::getExtensionsByMimeType($type);

                        if (empty($extensions)) {
                            continue;
                        }

                        $filename = 'Uploaded_file.'.reset($extensions);
                    }

                    $incomingFiles[] = [
                        'filename' => $filename,
                        'data' => $data,
                        'type' => 'data'
                    ];
                }
            }
        }

        // Remove these so they don't interfere.
        if (isset($value['data']) || isset($value['filenames'])) {
            unset($value['data'], $value['filenames']);
        }

        // See if we have uploaded file(s).
        $paramName = $this->requestParamName($element);

        if ($paramName !== null) {
            $files = UploadedFile::getInstancesByName($paramName);

            foreach ($files as $file) {
                $incomingFiles[] = [
                    'filename' => $file->name,
                    'location' => $file->tempName,
                    'type' => 'upload'
                ];
            }
        }

        if ($this->restrictFiles && !empty($this->allowedKinds)) {
            $allowedExtensions = $this->_getAllowedExtensions();
        } else {
            $allowedExtensions = false;
        }

        if (is_array($allowedExtensions)) {
            foreach ($incomingFiles as $file) {
                $extension = StringHelper::toLowerCase(pathinfo($file['filename'], PATHINFO_EXTENSION));

                if (!in_array($extension, $allowedExtensions, true)) {
                    $this->_failedFiles[] = $file['filename'];
                }
            }
        }

        if (!empty($this->_failedFiles)) {
            return parent::beforeElementSave($element, $isNew);
        }

        // If we got here either there are no restrictions or all files are valid so let's turn them into Assets
        // If ther are any..
        if (!empty($incomingFiles)) {
            $assetIds = [];
            $targetFolderId = $this->_determineUploadFolderId($element);

            if (!empty($targetFolderId)) {
                foreach ($incomingFiles as $file) {
                    $tempPath = AssetsHelper::tempFilePath($file['filename']);
                    if ($file['type'] === 'upload') {
                        move_uploaded_file($file['location'], $tempPath);
                    }
                    if ($file['type'] === 'data') {
                        FileHelper::writeToFile($tempPath, $file['data']);
                    }

                    $folder = Craft::$app->getAssets()->getFolderById($targetFolderId);
                    $asset = new Asset();
                    $asset->title = StringHelper::toTitleCase(pathinfo($file['filename'], PATHINFO_FILENAME));
                    $asset->newFilePath = $tempPath;
                    $asset->filename = $file['filename'];
                    $asset->folderId = $targetFolderId;
                    $asset->volumeId = $folder->volumeId;
                    Craft::$app->getAssets()->saveAsset($asset);

                    $assetIds[] = $asset->id;
                    try {
                        FileHelper::removeFile($tempPath);
                    } catch (ErrorException $e) {
                        Craft::warning("Unable to delete the file \"{$tempPath}\": ".$e->getMessage(), __METHOD__);
                    }
                }

                $assetIds = array_unique(array_merge($value, $assetIds));

                /** @var AssetQuery $newValue */
                $newValue = $this->normalizeValue($assetIds, $element);
                $element->setFieldValue($this->handle, $newValue);
            }
        }

        return parent::beforeElementSave($element, $isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element, bool $isNew)
    {
        $value = $element->getFieldValue($this->handle);
        $assetsToMove = [];

        if ($value instanceof AssetQuery) {
            $value = $value->all();
        }

        if (is_array($value) && count($value)) {
            if ($this->useSingleFolder) {
                $targetFolderId = $this->_resolveVolumePathToFolderId(
                    $this->singleUploadLocationSource,
                    $this->singleUploadLocationSubpath,
                    $element
                );

                // Move only those Assets that have had their folder changed.
                foreach ($value as $asset) {
                    if ($targetFolderId != $asset->folderId) {
                        $assetsToMove[] = $asset;
                    }
                }
            } else {
                $assetIds = [];

                foreach ($value as $elementFile) {
                    $assetIds[] = $elementFile->id;
                }

                // Find the files with temp sources and just move those.
                $query = Asset::find();
                Craft::configure($query, [
                    'id' => array_merge(['in'], $assetIds),
                    'volumeId' => ':empty:'
                ]);
                $assetsToMove = $query->all();

                // If we have some files to move, make sure the folder exists.
                if (!empty($assetsToMove)) {
                    $targetFolderId = $this->_resolveVolumePathToFolderId(
                        $this->defaultUploadLocationSource,
                        $this->defaultUploadLocationSubpath,
                        $element
                    );
                }
            }

            if (!empty($assetsToMove) && !empty($targetFolderId)) {

                // Resolve all conflicts by keeping both
                foreach ($assetsToMove as $asset) {
                    $conflictingAsset = Asset::find()
                        ->folderId($targetFolderId)
                        ->filename(Db::escapeParam($asset->filename))
                        ->one();

                    if ($conflictingAsset) {
                        $newFilename = Craft::$app->getAssets()->getNameReplacementInFolder($asset->filename,
                            $targetFolderId);
                        Craft::$app->getAssets()->moveAsset($asset,
                            $targetFolderId, $newFilename);
                    } else {
                        Craft::$app->getAssets()->moveAsset($asset,
                            $targetFolderId);
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
        Craft::$app->getSession()->authorize('uploadToVolume:'.$folderId);

        if ($this->useSingleFolder) {
            $folderPath = 'folder:'.$folderId.':single';

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
    protected function inputSelectionCriteria(): array
    {
        return [
            'kind' => ($this->restrictFiles && !empty($this->allowedKinds)) ? $this->allowedKinds : [],
        ];
    }

    // Private Methods
    // =========================================================================

    /**
     * Resolve a source path to it's folder ID by the source path and the matched source beginning.
     *
     * @param int                   $volumeId
     * @param string                $subpath
     * @param ElementInterface|null $element
     * @param bool                  $createDynamicFolders whether missing folders should be created in the process
     *
     * @throws InvalidVolumeException if the volume root folder doesn’t exist
     * @throws InvalidSubpathException if the subpath cannot be parsed in full
     * @return int
     */
    private function _resolveVolumePathToFolderId(int $volumeId, string $subpath, ElementInterface $element = null, bool $createDynamicFolders = true): int
    {
        // Get the root folder in the source
        $rootFolder = Craft::$app->getAssets()->getRootFolderByVolumeId($volumeId);

        // Are we looking for a subfolder?
        $subpath = is_string($subpath) ? trim($subpath, '/') : '';

        // Make sure the root folder actually exists
        if (!$rootFolder) {
            throw new InvalidVolumeException();
        }


        if ($subpath === '') {
            // Get the root folder in the source
            $folder = $rootFolder;
        } else {
            // Prepare the path by parsing tokens and normalizing slashes.
            try {
                $renderedSubpath = Craft::$app->getView()->renderObjectTemplate($subpath, $element);
            } catch (\Exception $e) {
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
                    'asciiOnly' => Craft::$app->getConfig()->get('convertFilenamesToAscii')
                ]);
            }
            unset($segment);
            $subpath = implode('/', $segments);

            $folder = Craft::$app->getAssets()->findFolder([
                'volumeId' => $volumeId,
                'path' => $subpath.'/'
            ]);

            // Ensure that the folder exists
            if (!$folder) {
                if (!$createDynamicFolders) {
                    throw new InvalidSubpathException($subpath);
                }

                // Start at the root, and, go over each folder in the path and create it if it's missing.
                $parentFolder = $rootFolder;

                $segments = explode('/', $subpath);
                foreach ($segments as $segment) {
                    $folder = Craft::$app->getAssets()->findFolder([
                        'parentId' => $parentFolder->id,
                        'name' => $segment
                    ]);

                    // Create it if it doesn't exist
                    if (!$folder) {
                        $folder = $this->_createSubfolder($parentFolder, $segment);
                    }

                    // In case there's another segment after this...
                    $parentFolder = $folder;
                }
            }
        }

        return $folder->id;
    }

    /**
     * Create a subfolder within a folder with the given name.
     *
     * @param VolumeFolder $currentFolder
     * @param string       $folderName
     *
     * @return VolumeFolder The new subfolder
     */
    private function _createSubfolder(VolumeFolder $currentFolder, string $folderName): VolumeFolder
    {
        $newFolder = new VolumeFolder();
        $newFolder->parentId = $currentFolder->id;
        $newFolder->name = $folderName;
        $newFolder->volumeId = $currentFolder->volumeId;
        $newFolder->path = ltrim(rtrim($currentFolder->path, '/').'/'.$folderName, '/').'/';

        try {
            Craft::$app->getAssets()->createFolder($newFolder);
        } catch (AssetConflictException $e) {
            // If folder doesn't exist in DB, but we can't create it, it probably exists on the server.
            Craft::$app->getAssets()->storeFolderRecord($newFolder);
        }

        return $newFolder;
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
     * @param bool                  $createDynamicFolders whether missing folders should be created in the process
     *
     * @throws InvalidSubpathException if the folder subpath is not valid
     * @return int
     */
    private function _determineUploadFolderId(ElementInterface $element = null, bool $createDynamicFolders = true): int
    {
        if ($this->useSingleFolder) {
            $volumeId = $this->singleUploadLocationSource;
            $subpath = $this->singleUploadLocationSubpath;
        } else {
            $volumeId = $this->defaultUploadLocationSource;
            $subpath = $this->defaultUploadLocationSubpath;
        }

        try {
            $folderId = $this->_resolveVolumePathToFolderId($volumeId, $subpath, $element, $createDynamicFolders);
        } catch (InvalidSubpathException $exception) {
            // If this is a new element, the subpath probably just contained a token that returned null, like {id}
            // so use the user's upload folder instead
            if (empty($element->id) || !$createDynamicFolders) {
                $userModel = Craft::$app->getUser()->getIdentity();

                $userFolder = Craft::$app->getAssets()->getUserFolder($userModel);

                $folderName = 'field_'.$this->id;
                $elementFolder = Craft::$app->getAssets()->findFolder([
                    'parentId' => $userFolder->id,
                    'name' => $folderName
                ]);

                if (!$elementFolder) {
                    $folder = new VolumeFolder([
                        'parentId' => $userFolder->id,
                        'name' => $folderName,
                        'path' => $userFolder->path.$folderName.'/'
                    ]);

                    Craft::$app->getAssets()->createFolder($folder);
                } else {
                    $folder = $elementFolder;
                }

                FileHelper::createDirectory(Craft::$app->getPath()->getAssetsTempVolumePath().DIRECTORY_SEPARATOR.$folderName);
                $folderId = $folder->id;
            } else {
                // Existing element, so this is just a bad subpath
                throw $exception;
            }
        }

        return $folderId;
    }
}
