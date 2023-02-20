<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Asset;
use craft\elements\conditions\ElementCondition;
use craft\elements\db\AssetQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\ElementCollection;
use craft\errors\FsObjectNotFoundException;
use craft\errors\InvalidFsException;
use craft\errors\InvalidSubpathException;
use craft\events\LocateUploadedFilesEvent;
use craft\fs\Temp;
use craft\gql\arguments\elements\Asset as AssetArguments;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\gql\resolvers\elements\Asset as AssetResolver;
use craft\helpers\ArrayHelper;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\Cp;
use craft\helpers\ElementHelper;
use craft\helpers\FileHelper;
use craft\helpers\Gql;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\Html;
use craft\models\GqlSchema;
use craft\models\Volume;
use craft\models\VolumeFolder;
use craft\services\ElementSources;
use craft\services\Gql as GqlService;
use craft\web\UploadedFile;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
use Twig\Error\RuntimeError;
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
    public const PREVIEW_MODE_FULL = 'full';
    /**
     * @since 3.5.11
     */
    public const PREVIEW_MODE_THUMBS = 'thumbs';

    /**
     * @event LocateUploadedFilesEvent The event that is triggered when identifying any uploaded files that
     * should be stored as assets and related by the field.
     * @since 4.0.2
     */
    public const EVENT_LOCATE_UPLOADED_FILES = 'locateUploadedFiles';

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
    public static function elementType(): string
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
        return sprintf('\\%s|\\%s<\\%s>', AssetQuery::class, ElementCollection::class, Asset::class);
    }

    /**
     * @var bool Whether assets should be restricted to a single location.
     * @since 4.0.0
     */
    public bool $restrictLocation = false;

    /**
     * @var string|null The source key where assets can be selected from, if assets are restricted.
     * @since 4.0.0
     */
    public ?string $restrictedLocationSource = null;

    /**
     * @var string|null The subpath where assets can be selected from, if assets are restricted.
     * @since 4.0.0
     */
    public ?string $restrictedLocationSubpath = null;

    /**
     * @var bool Whether assets can be selected from subfolders, if assets are restricted.
     * @since 4.0.0
     */
    public bool $allowSubfolders = false;

    /**
     * @var string|null The subpath where assets should be uploaded to by default, if assets are restricted and subfolders are allowed.
     * @since 4.0.0
     */
    public ?string $restrictedDefaultUploadSubpath = null;

    /**
     * @var string|null The source where assets should be uploaded by default, if assets aren’t restricted.
     */
    public ?string $defaultUploadLocationSource = null;

    /**
     * @var string|null The subpath where assets should be uploaded by default, if assets aren’t restricted.
     */
    public ?string $defaultUploadLocationSubpath = null;

    /**
     * @var bool Whether it should be possible to upload files directly to the field.
     * @since 3.5.13
     */
    public bool $allowUploads = true;

    /**
     * @var bool Whether the available assets should be restricted to
     * [[allowedKinds]]
     */
    public bool $restrictFiles = false;

    /**
     * @var array|null The file kinds that the field should be restricted to
     * (only used if [[restrictFiles]] is true)
     */
    public ?array $allowedKinds = null;

    /**
     * @var bool Whether to show input sources for volumes the user doesn’t have permission to view.
     * @since 3.4.0
     */
    public bool $showUnpermittedVolumes = false;

    /**
     * @var bool Whether to show files the user doesn’t have permission to view, per the
     * “View files uploaded by other users” permission.
     * @since 3.4.0
     */
    public bool $showUnpermittedFiles = false;

    /**
     * @var string How related assets should be presented within element index views.
     * @phpstan-var self::PREVIEW_MODE_FULL|self::PREVIEW_MODE_THUMBS
     * @since 3.5.11
     */
    public string $previewMode = self::PREVIEW_MODE_FULL;

    /**
     * @inheritdoc
     */
    protected bool $allowLargeThumbsView = true;

    /**
     * @inheritdoc
     */
    protected string $settingsTemplate = '_components/fieldtypes/Assets/settings.twig';

    /**
     * @inheritdoc
     */
    protected string $inputTemplate = '_components/fieldtypes/Assets/input.twig';

    /**
     * @inheritdoc
     */
    protected ?string $inputJsClass = 'Craft.AssetSelectInput';

    /**
     * @var array|null References for files uploaded as data strings for this field.
     */
    private ?array $_uploadedDataFiles = null;

    /**
     * @inheritdoc
     */
    public function __construct(array $config = [])
    {
        // Rename old settings
        $oldSettings = [
            'useSingleFolder' => 'restrictLocation',
            'singleUploadLocationSource' => 'restrictedLocationSource',
            'singleUploadLocationSubpath' => 'restrictedLocationSubpath',
        ];
        foreach ($oldSettings as $old => $new) {
            if (array_key_exists($old, $config)) {
                $config[$new] = ArrayHelper::remove($config, $old);
            }
        }

        // Default showUnpermittedVolumes to true for existing Assets fields
        if (isset($config['id']) && !isset($config['showUnpermittedVolumes'])) {
            $config['showUnpermittedVolumes'] = true;
        }

        parent::__construct($config);
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
            },
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

        foreach (Asset::sources('settings') as $volume) {
            if (!isset($volume['heading'])) {
                $sourceOptions[] = [
                    'label' => $volume['label'],
                    'value' => $volume['key'],
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
    protected function inputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        try {
            return parent::inputHtml($value, $element);
        } catch (InvalidSubpathException) {
            return Html::tag('p', Craft::t('app', 'This field’s target subfolder path is invalid: {path}', [
                'path' => '<code>' . $this->restrictedLocationSubpath . '</code>',
            ]), [
                'class' => ['warning', 'with-icon'],
            ]);
        } catch (InvalidFsException $e) {
            return Html::tag('p', $e->getMessage(), [
                'class' => ['warning', 'with-icon'],
            ]);
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
    public function validateFileType(ElementInterface $element): void
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
            $filenames[] = $asset->getFilename();
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
                    'filename' => $filename,
                ]));
            }
        }
    }

    /**
     * Validates the files to make sure they are under the allowed max file size.
     *
     * @param ElementInterface $element
     */
    public function validateFileSize(ElementInterface $element): void
    {
        $maxSize = AssetsHelper::getMaxUploadSize();

        $filenames = [];

        // Get any uploaded filenames
        $uploadedFiles = $this->_getUploadedFiles($element);
        foreach ($uploadedFiles as $file) {
            switch ($file['type']) {
                case 'data':
                    if (strlen($file['data']) > $maxSize) {
                        $filenames[] = $file['filename'];
                    }
                    break;
                case 'file':
                case 'upload':
                    if (file_exists($file['path']) && (filesize($file['path']) > $maxSize)) {
                        $filenames[] = $file['filename'];
                    }
                    break;
            }
        }

        foreach ($filenames as $filename) {
            $element->addError($this->handle, Craft::t('app', '“{filename}” is too large.', [
                'filename' => $filename,
            ]));
        }
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        // If data strings are passed along, make sure the array keys are retained.
        if (is_array($value) && isset($value['data']) && !empty($value['data'])) {
            $this->_uploadedDataFiles = ['data' => $value['data'], 'filename' => $value['filename']];
            unset($value['data'], $value['filename']);

            /** @var Asset $class */
            $class = static::elementType();
            $query = $class::find();

            $targetSite = $this->targetSiteId($element);
            if ($this->targetSiteId) {
                $query->siteId($targetSite);
            } else {
                $query
                    ->site('*')
                    ->unique()
                    ->preferSites([$targetSite]);
            }

            // $value might be an array of element IDs
            if (is_array($value)) {
                $query
                    ->id(array_values(array_filter($value)))
                    ->fixedOrder();

                if ($this->allowLimit && $this->maxRelations) {
                    $query->limit($this->maxRelations);
                }

                return $query;
            }
        }

        return parent::normalizeValue($value, $element);
    }

    /**
     * @inheritdoc
     */
    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        return parent::isValueEmpty($value, $element) && empty($this->_getUploadedFiles($element));
    }

    /**
     * Resolve source path for uploading for this field.
     *
     * @param ElementInterface|null $element
     * @return int
     */
    public function resolveDynamicPathToFolderId(?ElementInterface $element = null): int
    {
        return $this->_uploadFolder($element)->id;
    }

    /**
     * @inheritdoc
     */
    public function includeInGqlSchema(GqlSchema $schema): bool
    {
        return Gql::canQueryAssets($schema);
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getContentGqlType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => Type::nonNull(Type::listOf(AssetInterface::getType())),
            'args' => AssetArguments::getArguments(),
            'resolve' => AssetResolver::class . '::resolve',
            'complexity' => GqlHelper::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(Collection $elements): string
    {
        return Cp::elementPreviewHtml($elements->all(), Cp::ELEMENT_SIZE_SMALL, false, true, $this->previewMode === self::PREVIEW_MODE_FULL);
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element, bool $isNew): void
    {
        // No special treatment for revisions
        $rootElement = ElementHelper::rootElement($element);
        if (!$rootElement->getIsRevision()) {
            // Figure out what we're working with and set up some initial variables.
            $isCanonical = $rootElement->getIsCanonical();
            $query = $element->getFieldValue($this->handle);
            $assetsService = Craft::$app->getAssets();

            $getUploadFolderId = function() use ($element, $isCanonical, &$_targetFolderId): int {
                return $_targetFolderId ?? ($_targetFolderId = $this->_uploadFolder($element, $isCanonical)->id);
            };

            // Only handle file uploads for the initial site
            if (!$element->propagating) {
                // Were there any uploaded files?
                $uploadedFiles = $this->_getUploadedFiles($element);

                if (!empty($uploadedFiles)) {
                    $uploadFolderId = $getUploadFolderId();

                    // Convert them to assets
                    $assetIds = [];

                    foreach ($uploadedFiles as $file) {
                        $tempPath = AssetsHelper::tempFilePath($file['filename']);
                        switch ($file['type']) {
                            case 'data':
                                FileHelper::writeToFile($tempPath, $file['data']);
                                break;
                            case 'file':
                                rename($file['path'], $tempPath);
                                break;
                            case 'upload':
                                move_uploaded_file($file['path'], $tempPath);
                                break;
                        }

                        $uploadFolder = $assetsService->getFolderById($uploadFolderId);
                        $asset = new Asset();
                        $asset->tempFilePath = $tempPath;
                        $asset->setFilename($file['filename']);
                        $asset->newFolderId = $uploadFolderId;
                        $asset->setVolumeId($uploadFolder->volumeId);
                        $asset->uploaderId = Craft::$app->getUser()->getId();
                        $asset->avoidFilenameConflicts = true;
                        $asset->setScenario(Asset::SCENARIO_CREATE);

                        if (Craft::$app->getElements()->saveElement($asset)) {
                            $assetIds[] = $asset->id;
                        } else {
                            Craft::warning('Couldn’t save uploaded asset due to validation errors: ' . implode(', ', $asset->getFirstErrors()), __METHOD__);
                        }
                    }

                    if (!empty($assetIds)) {
                        // Add the newly uploaded IDs to the mix.
                        if (is_array($query->id)) {
                            $query = $this->normalizeValue(array_merge($query->id, $assetIds), $element);
                        } else {
                            $query = $this->normalizeValue($assetIds, $element);
                        }

                        $element->setFieldValue($this->handle, $query);

                        // Make sure that all traces of processed files are removed.
                        $this->_uploadedDataFiles = null;
                    }
                }
            }

            // Are there any related assets?
            /** @var AssetQuery $query */
            /** @var Asset[] $assets */
            $assets = $query->all();

            if (!empty($assets)) {
                // Only enforce the restricted asset location for canonical elements
                if ($this->restrictLocation && $isCanonical) {
                    if (!$this->allowSubfolders) {
                        $rootRestrictedFolderId = $getUploadFolderId();
                    } else {
                        $rootRestrictedFolderId = $this->_uploadFolder($element, true, false)->id;
                    }

                    $assetsToMove = array_filter($assets, function(Asset $asset) use ($rootRestrictedFolderId, $assetsService) {
                        if ($asset->folderId === $rootRestrictedFolderId) {
                            return false;
                        }
                        if (!$this->allowSubfolders) {
                            return true;
                        }
                        $rootRestrictedFolder = $assetsService->getFolderById($rootRestrictedFolderId);
                        return (
                            $asset->volumeId !== $rootRestrictedFolder->volumeId ||
                            !str_starts_with($asset->folderPath, $rootRestrictedFolder->path)
                        );
                    });
                } else {
                    // Find the files with temp sources and just move those.
                    /** @var Asset[] $assetsToMove */
                    $assetsToMove = $assetsService->createTempAssetQuery()
                        ->id(ArrayHelper::getColumn($assets, 'id'))
                        ->all();
                }

                if (!empty($assetsToMove)) {
                    $uploadFolder = $assetsService->getFolderById($getUploadFolderId());

                    // Resolve all conflicts by keeping both
                    foreach ($assetsToMove as $asset) {
                        $asset->avoidFilenameConflicts = true;
                        try {
                            $assetsService->moveAsset($asset, $uploadFolder);
                        } catch (FsObjectNotFoundException $e) {
                            // Don't freak out about that.
                            Craft::warning('Couldn’t move asset because the file doesn’t exist: ' . $e->getMessage());
                            Craft::$app->getErrorHandler()->logException($e);
                        }
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
    public function getEagerLoadingGqlConditions(): ?array
    {
        $allowedEntities = Gql::extractAllowedEntitiesFromSchema();
        $volumeUids = $allowedEntities['volumes'] ?? [];

        if (empty($volumeUids)) {
            return null;
        }

        $volumesService = Craft::$app->getVolumes();
        $volumeIds = array_filter(array_map(function(string $uid) use ($volumesService) {
            $volume = $volumesService->getVolumeByUid($uid);
            return $volume->id ?? null;
        }, $volumeUids));

        return [
            'volumeId' => $volumeIds,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getInputSources(?ElementInterface $element = null): array|string|null
    {
        $folder = $this->_uploadFolder($element, false, false);
        Craft::$app->getSession()->authorize('saveAssets:' . $folder->getVolume()->uid);

        if ($this->restrictLocation) {
            if (!$this->showUnpermittedVolumes) {
                // Make sure they have permission to view the volume
                // (Use restrictedLocationSource here because the actual folder could belong to a temp volume)
                $volume = $this->_volumeBySourceKey($this->restrictedLocationSource);

                if (!$volume || !Craft::$app->getUser()->checkPermission("viewAssets:$volume->uid")) {
                    return [];
                }
            }

            $sources = [$this->_sourceKeyByFolder($folder)];

            if ($this->allowSubfolders) {
                $userFolder = Craft::$app->getAssets()->getUserTemporaryUploadFolder();
                if ($userFolder->id !== $folder->id) {
                    $sources[] = $this->_sourceKeyByFolder($userFolder);
                }
            }

            return $sources;
        }

        if (is_array($this->sources)) {
            $sources = array_merge($this->sources);
        } else {
            $sources = [];
            foreach (Craft::$app->getElementSources()->getSources(Asset::class) as $source) {
                if ($source['type'] !== ElementSources::TYPE_HEADING) {
                    $sources[] = $source['key'];
                }
            }
        }

        // Now enforce the showUnpermittedVolumes setting
        if (!$this->showUnpermittedVolumes && !empty($sources)) {
            $userService = Craft::$app->getUser();
            $volumesService = Craft::$app->getVolumes();
            return ArrayHelper::where($sources, function(string $source) use ($volumesService, $userService) {
                // If it’s not a volume folder, let it through
                if (!str_starts_with($source, 'volume:')) {
                    return true;
                }
                // Only show it if they have permission to view it, or if it's the temp volume
                $volumeUid = explode(':', $source)[1];
                if ($userService->checkPermission("viewAssets:$volumeUid")) {
                    return true;
                }
                $volume = $volumesService->getVolumeByUid($volumeUid);
                return $volume?->getFs() instanceof Temp;
            }, true, true, false);
        }

        return $sources;
    }

    /**
     * @inheritdoc
     */
    protected function inputTemplateVariables(array|ElementQueryInterface $value = null, ?ElementInterface $element = null): array
    {
        $variables = parent::inputTemplateVariables($value, $element);

        $uploadVolume = $this->_uploadVolume();
        $variables['showFolders'] = !$this->restrictLocation || $this->allowSubfolders;
        $variables['canUpload'] = (
            $this->allowUploads &&
            $uploadVolume &&
            Craft::$app->getUser()->checkPermission("saveAssets:$uploadVolume->uid")
        );
        $variables['defaultFieldLayoutId'] = $uploadVolume->fieldLayoutId ?? null;

        if ($this->restrictLocation && !$this->allowSubfolders) {
            $variables['showSourcePath'] = false;
        }

        if (!$this->restrictLocation || $this->allowSubfolders) {
            $uploadFolder = $this->_uploadFolder($element, false);
            if ($uploadFolder->volumeId) {
                // If the location is restricted, don't go passed the base source folder
                $baseUploadFolder = $this->restrictLocation ? $this->_uploadFolder($element, false, false) : null;
                $folders = $this->_folderWithAncestors($uploadFolder, $baseUploadFolder);
                $variables['defaultSource'] = $this->_sourceKeyByFolder($folders[0]);
                $variables['defaultSourcePath'] = array_map(function(VolumeFolder $folder) {
                    return $folder->getSourcePathInfo();
                }, $folders);
            }
        }

        return $variables;
    }

    /**
     * @inheritdoc
     */
    public function getInputSelectionCriteria(): array
    {
        $criteria = parent::getInputSelectionCriteria();
        $criteria['kind'] = ($this->restrictFiles && !empty($this->allowedKinds)) ? $this->allowedKinds : [];

        if ($this->showUnpermittedFiles) {
            $criteria['uploaderId'] = null;
        }

        return $criteria;
    }

    /**
     * @inheritdoc
     */
    protected function createSelectionCondition(): ?ElementCondition
    {
        $condition = Asset::createCondition();
        $condition->queryParams = ['volume', 'volumeId', 'kind'];
        return $condition;
    }

    /**
     * Returns any files that were uploaded to the field.
     *
     * @param ElementInterface $element
     * @return array
     */
    private function _getUploadedFiles(ElementInterface $element): array
    {
        $files = [];

        if (ElementHelper::isRevision($element)) {
            return $files;
        }

        // Grab data strings
        if (isset($this->_uploadedDataFiles['data']) && is_array($this->_uploadedDataFiles['data'])) {
            foreach ($this->_uploadedDataFiles['data'] as $index => $dataString) {
                if (preg_match('/^data:(?<type>[a-z0-9]+\/[a-z0-9\+\-\.]+);base64,(?<data>.+)/i', $dataString, $matches)) {
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

                    $files[] = [
                        'filename' => $filename,
                        'data' => $data,
                        'type' => 'data',
                    ];
                }
            }
        }

        // See if we have uploaded file(s).
        $paramName = $this->requestParamName($element);

        if ($paramName !== null) {
            $uploadedFiles = UploadedFile::getInstancesByName($paramName);

            foreach ($uploadedFiles as $uploadedFile) {
                $files[] = [
                    'filename' => $uploadedFile->name,
                    'path' => $uploadedFile->tempName,
                    'type' => 'upload',
                ];
            }
        }

        $event = new LocateUploadedFilesEvent([
            'element' => $element,
            'files' => $files,
        ]);
        $this->trigger(self::EVENT_LOCATE_UPLOADED_FILES, $event);
        return $event->files;
    }

    /**
     * Finds a volume folder by a source key and (dynamic?) subpath.
     *
     * @param string $sourceKey
     * @param string|null $subpath
     * @param ElementInterface|null $element
     * @param bool $createDynamicFolders whether missing folders should be created in the process
     * @return VolumeFolder
     * @throws InvalidSubpathException if the subpath cannot be parsed in full
     * @throws InvalidFsException if the volume root folder doesn’t exist
     */
    private function _findFolder(string $sourceKey, ?string $subpath, ?ElementInterface $element, bool $createDynamicFolders): VolumeFolder
    {
        // Make sure the volume and root folder actually exist
        $volume = $this->_volumeBySourceKey($sourceKey);
        if (!$volume) {
            throw new InvalidFsException("Invalid source key: $sourceKey");
        }

        $assetsService = Craft::$app->getAssets();
        $rootFolder = $assetsService->getRootFolderByVolumeId($volume->id);
        if (!$rootFolder) {
            $rootFolder = Craft::$app->getVolumes()->ensureTopFolder($volume);
        }

        // Are we looking for the root folder?
        $subpath = trim($subpath ?? '', '/');
        if ($subpath === '') {
            return $rootFolder;
        }

        $isDynamic = preg_match('/\{|\}/', $subpath);

        if ($isDynamic) {
            // Prepare the path by parsing tokens and normalizing slashes.
            try {
                $renderedSubpath = Craft::$app->getView()->renderObjectTemplate($subpath, $element);
            } catch (InvalidConfigException|RuntimeError $e) {
                throw new InvalidSubpathException($subpath, null, 0, $e);
            }

            // Did any of the tokens return null?
            if (
                $renderedSubpath === '' ||
                trim($renderedSubpath, '/') != $renderedSubpath ||
                str_contains($renderedSubpath, '//')
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
                    'asciiOnly' => $generalConfig->convertFilenamesToAscii,
                ]);
            }, $segments);
            $subpath = implode('/', $segments);
        }

        $folder = $assetsService->findFolder([
            'volumeId' => $volume->id,
            'path' => $subpath . '/',
        ]);

        // Ensure that the folder exists
        if (!$folder) {
            if (!$isDynamic && !$createDynamicFolders) {
                throw new InvalidSubpathException($subpath);
            }

            $folder = $assetsService->ensureFolderByFullPathAndVolume($subpath, $volume);
        }

        return $folder;
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
     * Returns the upload folder that should be used for an element.
     *
     * @param ElementInterface|null $element
     * @param bool $createDynamicFolders whether missing folders should be created in the process
     * @param bool $resolveSubtreeDefaultLocation Whether the folder should resolve to the default upload location for subtree fields.
     * @return VolumeFolder
     * @throws InvalidSubpathException if the folder subpath is not valid
     * @throws InvalidFsException if there's a problem with the field's volume configuration
     */
    private function _uploadFolder(
        ?ElementInterface $element = null,
        bool $createDynamicFolders = true,
        bool $resolveSubtreeDefaultLocation = true,
    ): VolumeFolder {
        if ($this->restrictLocation) {
            $uploadVolume = $this->restrictedLocationSource;
            $subpath = $this->restrictedLocationSubpath;

            if ($this->allowSubfolders && $resolveSubtreeDefaultLocation) {
                $subpath = implode('/', ArrayHelper::filterEmptyStringsFromArray(array_map(fn($segment) => trim($segment, '/'), [
                    $subpath ?? '',
                    $this->restrictedDefaultUploadSubpath ?? '',
                ])));
                $settingName = fn() => Craft::t('app', 'Default Upload Location');
            } else {
                $settingName = fn() => Craft::t('app', 'Asset Location');
            }
        } else {
            $uploadVolume = $this->defaultUploadLocationSource;
            $subpath = $this->defaultUploadLocationSubpath;
            $settingName = fn() => Craft::t('app', 'Default Upload Location');
        }

        $assetsService = Craft::$app->getAssets();

        try {
            if (!$uploadVolume) {
                throw new InvalidFsException();
            }

            return $this->_findFolder($uploadVolume, $subpath, $element, $createDynamicFolders);
        } catch (InvalidFsException $e) {
            throw new InvalidFsException(Craft::t('app', 'The {field} field’s {setting} setting is set to an invalid volume.', [
                'field' => $this->name,
                'setting' => $settingName(),
            ]), 0, $e);
        } catch (InvalidSubpathException $e) {
            // If this is a new/disabled/draft element, the subpath probably just contained a token that returned null, like {id}
            // so use the user’s upload folder instead
            if (
                $element === null ||
                !$element->id ||
                !$element->enabled ||
                !$createDynamicFolders ||
                ElementHelper::isDraft($element)
            ) {
                return $assetsService->getUserTemporaryUploadFolder();
            }

            // Existing element, so this is just a bad subpath
            throw new InvalidSubpathException($e->subpath, Craft::t('app', 'The {field} field’s {setting} setting has an invalid subpath (“{subpath}”).', [
                'field' => $this->name,
                'setting' => $settingName(),
                'subpath' => $e->subpath,
            ]), 0, $e);
        }
    }

    /**
     * Returns a volume via its source key.
     */
    public function _volumeBySourceKey(?string $sourceKey): ?Volume
    {
        if (!$sourceKey) {
            return null;
        }

        $parts = explode(':', $sourceKey, 2);

        if (count($parts) !== 2) {
            return null;
        }

        return Craft::$app->getVolumes()->getVolumeByUid($parts[1]);
    }

    /**
     * Returns the target upload volume for the field.
     */
    private function _uploadVolume(): ?Volume
    {
        if ($this->restrictLocation) {
            return $this->_volumeBySourceKey($this->restrictedLocationSource);
        }

        return $this->_volumeBySourceKey($this->defaultUploadLocationSource);
    }

    /**
     * Returns the full source key for a folder, in the form of `volume:UID/folder:UID/...`.
     */
    private function _sourceKeyByFolder(VolumeFolder $folder): string
    {
        if (!$folder->volumeId) {
            // Probably the user's temp folder
            return "folder:$folder->uid";
        }

        $segments = array_map(function(VolumeFolder $folder) {
            if ($folder->parentId) {
                return "folder:$folder->uid";
            }
            return sprintf('volume:%s', $folder->getVolume()->uid);
        }, $this->_folderWithAncestors($folder));

        return implode('/', $segments);
    }

    /**
     * Returns the given folder along with each of its ancestors.
     *
     * @return VolumeFolder[]
     */
    private function _folderWithAncestors(VolumeFolder $folder, ?VolumeFolder $untilFolder = null): array
    {
        $folders = [$folder];

        while ($folder->parentId && $folder->volumeId !== null && (!$untilFolder || $folder->id !== $untilFolder->id)) {
            $folder = $folder->getParent();
            array_unshift($folders, $folder);
        }

        return $folders;
    }
}
