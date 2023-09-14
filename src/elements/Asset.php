<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use Craft;
use craft\base\Element;
use craft\base\Field;
use craft\base\LocalVolumeInterface;
use craft\base\VolumeInterface;
use craft\controllers\ElementIndexesController;
use craft\controllers\ElementsController;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\db\Table;
use craft\elements\actions\CopyReferenceTag;
use craft\elements\actions\CopyUrl;
use craft\elements\actions\DeleteAssets;
use craft\elements\actions\DownloadAssetFile;
use craft\elements\actions\Edit;
use craft\elements\actions\EditImage;
use craft\elements\actions\MoveAssets;
use craft\elements\actions\PreviewAsset;
use craft\elements\actions\RenameFile;
use craft\elements\actions\ReplaceFile;
use craft\elements\db\AssetQuery;
use craft\elements\db\ElementQueryInterface;
use craft\errors\AssetException;
use craft\errors\AssetTransformException;
use craft\errors\FileException;
use craft\errors\VolumeObjectNotFoundException;
use craft\events\AssetEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Assets;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\FileHelper;
use craft\helpers\Html;
use craft\helpers\Image;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\models\AssetTransform;
use craft\models\VolumeFolder;
use craft\records\Asset as AssetRecord;
use craft\search\SearchQuery;
use craft\search\SearchQueryTerm;
use craft\search\SearchQueryTermGroup;
use craft\validators\AssetLocationValidator;
use craft\validators\DateTimeValidator;
use craft\validators\StringValidator;
use craft\volumes\Temp;
use DateTime;
use Twig\Markup;
use yii\base\ErrorHandler;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\base\UnknownPropertyException;

/**
 * Asset represents an asset element.
 *
 * @property int|float|null $height the image height
 * @property int|float|null $width the image width
 * @property int|null $volumeId the volume ID
 * @property string|array|null $focalPoint the focal point represented as an array with `x` and `y` keys, or null if it's not an image
 * @property-read Markup|null $img an `<img>` tag based on this asset
 * @property-read VolumeFolder $folder the asset’s volume folder
 * @property-read VolumeInterface $volume the asset’s volume
 * @property-read bool $hasFocalPoint whether a user-defined focal point is set on the asset
 * @property-read string $extension the file extension
 * @property-read string $path the asset's path in the volume
 * @property-read string|null $mimeType the file’s MIME type, if it can be determined
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Asset extends Element
{
    // Events
    // -------------------------------------------------------------------------

    /**
     * @event AssetEvent The event that is triggered before an asset is uploaded to volume.
     */
    const EVENT_BEFORE_HANDLE_FILE = 'beforeHandleFile';

    // Location error codes
    // -------------------------------------------------------------------------

    const ERROR_DISALLOWED_EXTENSION = 'disallowed_extension';
    const ERROR_FILENAME_CONFLICT = 'filename_conflict';

    // Validation scenarios
    // -------------------------------------------------------------------------

    /**
     * Validation scenario that should be used when the asset is only getting *moved*; not renamed.
     *
     * @since 3.7.1
     */
    const SCENARIO_MOVE = 'move';
    const SCENARIO_FILEOPS = 'fileOperations';
    const SCENARIO_INDEX = 'index';
    const SCENARIO_CREATE = 'create';
    const SCENARIO_REPLACE = 'replace';

    // File kinds
    // -------------------------------------------------------------------------

    const KIND_ACCESS = 'access';
    const KIND_AUDIO = 'audio';
    /**
     * @since 3.6.0
     */
    const KIND_CAPTIONS_SUBTITLES = 'captionsSubtitles';
    const KIND_COMPRESSED = 'compressed';
    const KIND_EXCEL = 'excel';
    /**
     * @deprecated in 3.7.0
     */
    const KIND_FLASH = 'flash';
    const KIND_HTML = 'html';
    const KIND_ILLUSTRATOR = 'illustrator';
    const KIND_IMAGE = 'image';
    const KIND_JAVASCRIPT = 'javascript';
    const KIND_JSON = 'json';
    const KIND_PDF = 'pdf';
    const KIND_PHOTOSHOP = 'photoshop';
    const KIND_PHP = 'php';
    const KIND_POWERPOINT = 'powerpoint';
    const KIND_TEXT = 'text';
    const KIND_VIDEO = 'video';
    const KIND_WORD = 'word';
    const KIND_XML = 'xml';
    const KIND_UNKNOWN = 'unknown';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Asset');
    }

    /**
     * @inheritdoc
     */
    public static function lowerDisplayName(): string
    {
        return Craft::t('app', 'asset');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('app', 'Assets');
    }

    /**
     * @inheritdoc
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('app', 'assets');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle()
    {
        return 'asset';
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     * @return AssetQuery The newly created [[AssetQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new AssetQuery(static::class);
    }

    /**
     * @inheritdoc
     * @since 3.4.0
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle)
    {
        if ($handle === 'uploader') {
            // Get the source element IDs
            $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

            $map = (new Query())
                ->select(['id as source', 'uploaderId as target'])
                ->from([Table::ASSETS])
                ->where(['and', ['id' => $sourceElementIds], ['not', ['uploaderId' => null]]])
                ->all();

            return [
                'elementType' => User::class,
                'map' => $map,
            ];
        }

        return parent::eagerLoadingMap($sourceElements, $handle);
    }

    /**
     * @inheritdoc
     * @since 3.4.0
     */
    public function setEagerLoadedElements(string $handle, array $elements)
    {
        if ($handle === 'uploader') {
            $uploader = $elements[0] ?? null;
            $this->setUploader($uploader);
        } else {
            parent::setEagerLoadedElements($handle, $elements);
        }
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function gqlTypeNameByContext($context): string
    {
        return $context->handle . '_Asset';
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function gqlScopesByContext($context): array
    {
        return ['volumes.' . $context->uid];
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public static function gqlMutationNameByContext($context): string
    {
        /** @var VolumeInterface $context */
        return 'save_' . $context->handle . '_Asset';
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context = null): array
    {
        $sources = [];

        if ($context === 'index') {
            $volumeIds = Craft::$app->getVolumes()->getViewableVolumeIds();
        } else {
            $volumeIds = Craft::$app->getVolumes()->getAllVolumeIds();
        }

        $assetsService = Craft::$app->getAssets();
        $user = Craft::$app->getUser()->getIdentity();

        foreach ($volumeIds as $volumeId) {
            $folder = $assetsService->getRootFolderByVolumeId($volumeId);
            $sources[] = self::_assembleSourceInfoForFolder($folder, $user);
        }

        // Add the Temporary Uploads location, if that's not set to a real volume
        if (
            $context !== 'settings' &&
            !Craft::$app->getRequest()->getIsConsoleRequest() &&
            !Craft::$app->getProjectConfig()->get('assets.tempVolumeUid')
        ) {
            $temporaryUploadFolder = Craft::$app->getAssets()->getUserTemporaryUploadFolder();
            $temporaryUploadFolder->name = Craft::t('app', 'Temporary Uploads');
            $sources[] = self::_assembleSourceInfoForFolder($temporaryUploadFolder);
        }

        return $sources;
    }

    /**
     * @inheritdoc
     */
    public static function findSource(string $sourceKey, ?string $context = null): ?array
    {
        if (preg_match('/^folder:[\w\-]+(?:\/.+)?\/folder:([\w\-]+)$/', $sourceKey, $match)) {
            $folder = Craft::$app->getAssets()->getFolderByUid($match[1]);
            if ($folder) {
                $source = self::_assembleSourceInfoForFolder($folder, Craft::$app->getUser()->getIdentity());
                $source['keyPath'] = $sourceKey;
                return $source;
            }
        }

        return null;
    }

    public static function sourcePath(string $sourceKey, string $stepKey, ?string $context): ?array
    {
        if (!preg_match('/^folder:([\w\-]+)$/', $stepKey, $match)) {
            return null;
        }

        $folder = Craft::$app->getAssets()->getFolderByUid($match[1]);

        if (!$folder) {
            return null;
        }

        $path = [$folder->getSourcePathInfo()];

        while ($parent = $folder->getParent()) {
            array_unshift($path, $parent->getSourcePathInfo());
            $folder = $parent;
        }

        return $path;
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    protected static function defineFieldLayouts(string $source): array
    {
        $fieldLayouts = [];
        if (
            preg_match('/^folder:(.+)$/', $source, $matches) &&
            ($folder = Craft::$app->getAssets()->getFolderByUid($matches[1])) &&
            $fieldLayout = $folder->getVolume()->getFieldLayout()
        ) {
            $fieldLayouts[] = $fieldLayout;
        }
        return $fieldLayouts;
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        // Only match the first folder ID - ignore nested folders
        if (
            preg_match('/^folder:([a-z0-9\-]+)/', $source, $matches) &&
            $folder = Craft::$app->getAssets()->getFolderByUid($matches[1])
        ) {
            $volume = $folder->getVolume();
            $isTemp = $volume instanceof Temp;

            $actions[] = [
                'type' => PreviewAsset::class,
                'label' => Craft::t('app', 'Preview file'),
            ];

            // Download
            $actions[] = DownloadAssetFile::class;

            // Edit
            $actions[] = [
                'type' => Edit::class,
                'label' => Craft::t('app', 'Edit {type}', [
                    'type' => static::lowerDisplayName(),
                ]),
            ];

            $userSession = Craft::$app->getUser();
            if ($isTemp || $userSession->checkPermission("replaceFilesInVolume:$volume->uid")) {
                // Rename/Replace File
                $actions[] = RenameFile::class;
                $actions[] = ReplaceFile::class;
            }

            // Copy URL
            if ($volume->hasUrls) {
                $actions[] = CopyUrl::class;
            }

            // Copy Reference Tag
            $actions[] = [
                'type' => CopyReferenceTag::class,
                'elementType' => static::class,
            ];

            // Edit Image
            if ($isTemp || $userSession->checkPermission("editImagesInVolume:$volume->uid")) {
                $actions[] = EditImage::class;
            }

            // Move
            $actions[] = MoveAssets::class;

            // Delete
            if ($isTemp || $userSession->checkPermission("deleteFilesAndFoldersInVolume:$volume->uid")) {
                $actions[] = DeleteAssets::class;
            }
        }

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['filename', 'extension', 'kind'];
    }

    /**
     * @inheritdoc
     */
    public static function sortOptions(): array
    {
        if (self::isFolderIndex()) {
            return [
                'title' => Craft::t('app', 'Folder'),
            ];
        }

        return parent::sortOptions();
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'filename' => Craft::t('app', 'Filename'),
            'size' => Craft::t('app', 'File Size'),
            [
                'label' => Craft::t('app', 'File Modification Date'),
                'orderBy' => 'dateModified',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Uploaded'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'ID'),
                'orderBy' => 'elements.id',
                'attribute' => 'id',
            ],
            [
                'label' => Craft::t('app', 'Width'),
                'orderBy' => 'width',
                'attribute' => 'width',
            ],
            [
                'label' => Craft::t('app', 'Height'),
                'orderBy' => 'height',
                'attribute' => 'height',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableAttributes(): array
    {
        if (self::isFolderIndex()) {
            return [
                'title' => ['label' => Craft::t('app', 'Folder')],
            ];
        }

        return parent::tableAttributes();
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes = [
            'title' => ['label' => Craft::t('app', 'Asset')],
            'filename' => ['label' => Craft::t('app', 'Filename')],
            'size' => ['label' => Craft::t('app', 'File Size')],
            'kind' => ['label' => Craft::t('app', 'File Kind')],
            'imageSize' => ['label' => Craft::t('app', 'Dimensions')],
            'width' => ['label' => Craft::t('app', 'Image Width')],
            'height' => ['label' => Craft::t('app', 'Image Height')],
            'link' => ['label' => Craft::t('app', 'Link'), 'icon' => 'world'],
            'id' => ['label' => Craft::t('app', 'ID')],
            'uid' => ['label' => Craft::t('app', 'UID')],
            'dateModified' => ['label' => Craft::t('app', 'File Modified Date')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Uploaded')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
            'uploader' => ['label' => Craft::t('app', 'Uploaded By')],
        ];

        // Hide Author from Craft Solo
        if (Craft::$app->getEdition() !== Craft::Pro) {
            unset($attributes['uploader']);
        }

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'filename',
            'size',
            'dateModified',
            'uploader',
            'link',
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function prepElementQueryForTableAttribute(ElementQueryInterface $elementQuery, string $attribute)
    {
        if ($attribute === 'uploader') {
            $elementQuery->andWith('uploader');
        } else {
            parent::prepElementQueryForTableAttribute($elementQuery, $attribute);
        }
    }

    /**
     * @inheritdoc
     */
    protected static function indexElements(ElementQueryInterface $elementQuery, ?string $sourceKey): array
    {
        $assets = [];

        // Include folders in the results?
        /** @var AssetQuery $elementQuery */
        if (self::_includeFoldersInIndexElements($elementQuery, $sourceKey, $queryFolder)) {
            $assetsService = Craft::$app->getAssets();
            $folderQuery = self::_createFolderQueryForIndex($elementQuery, $queryFolder);
            $totalFolders = $folderQuery->count();

            if ($totalFolders > $elementQuery->offset) {
                $source = ElementHelper::findSource(static::class, $sourceKey);
                if (isset($source['criteria']['folderId'])) {
                    $baseFolder = $assetsService->getFolderById($source['criteria']['folderId']);
                } else {
                    $baseFolder = $assetsService->getRootFolderByVolumeId($queryFolder->getVolume()->id);
                }
                $baseSourcePathStep = $baseFolder->getSourcePathInfo();

                $folderQuery
                    ->offset($elementQuery->offset)
                    ->limit($elementQuery->limit);

                $folders = array_map(function(array $result) {
                    return new VolumeFolder($result);
                }, $folderQuery->all());

                $foldersByPath = ArrayHelper::index($folders, function(VolumeFolder $folder) {
                    return rtrim($folder->path, '/');
                });

                foreach ($folders as $folder) {
                    $sourcePath = [$baseSourcePathStep];
                    $path = rtrim($baseFolder->path ?? '', '/');
                    $pathSegs = ArrayHelper::filterEmptyStringsFromArray(explode('/', StringHelper::removeLeft($folder['path'], $baseFolder->path ?? '')));
                    foreach ($pathSegs as $i => $seg) {
                        $path .= ($path !== '' ? '/' : '') . $seg;
                        if (isset($foldersByPath[$path])) {
                            $stepFolder = $foldersByPath[$path];
                        } else {
                            $stepFolder = $assetsService->findFolder([
                                'volumeId' => $queryFolder->volumeId,
                                'path' => "$path/",
                            ]);
                            if (!$stepFolder) {
                                $stepFolderId = $assetsService->ensureFolderByFullPathAndVolume($path, $queryFolder->getVolume());
                                $stepFolder = $assetsService->getFolderById($stepFolderId);
                            }
                            $foldersByPath[$path] = $stepFolder;
                        }

                        if ($i < count($pathSegs) - 1) {
                            $stepFolder->setHasChildren(true);
                        }
                        $sourcePath[] = $stepFolder->getSourcePathInfo();
                    }

                    $path = rtrim($folder->path, '/');
                    $assets[] = new self([
                        'isFolder' => true,
                        'volumeId' => $queryFolder->volumeId,
                        'folderId' => $folder->id,
                        'folderPath' => $path,
                        'title' => StringHelper::removeLeft($path, $queryFolder->path ?? ''),
                        'sourcePath' => $sourcePath,
                    ]);
                }
            }

            // Is there room for any normal assets as well?
            $totalAssets = count($assets);
            if ($totalAssets < $elementQuery->limit) {
                $elementQuery->offset(max($elementQuery->offset - $totalFolders, 0));
                $elementQuery->limit($elementQuery->limit - $totalAssets);
            }
        }

        // if it's a 'foldersOnly' request, or we have enough folders to hit the query limit,
        // return the folders directly
        if (
            self::isFolderIndex() ||
            count($assets) == $elementQuery->limit
        ) {
            return $assets;
        }

        // otherwise merge in the resulting assets
        return array_merge($assets, $elementQuery->all());
    }

    /**
     * @inheritdoc
     */
    public static function indexElementCount(ElementQueryInterface $elementQuery, ?string $sourceKey): int
    {
        $count = 0;

        /** @var AssetQuery $elementQuery */
        if (self::_includeFoldersInIndexElements($elementQuery, $sourceKey, $queryFolder)) {
            try {
                $count += self::_createFolderQueryForIndex($elementQuery, $queryFolder)->count();
            } catch (QueryAbortedException $e) {
                return 0;
            }
        }

        if (!self::isFolderIndex()) {
            $count += parent::indexElementCount($elementQuery, $sourceKey);
        }

        return $count;
    }

    private static function _includeFoldersInIndexElements(AssetQuery $assetQuery, ?string $sourceKey, ?VolumeFolder &$queryFolder = null): bool
    {
        if (
            !Craft::$app->getRequest()->getBodyParam('showFolders') ||
            !StringHelper::startsWith($sourceKey, 'folder:') ||
            !is_numeric($assetQuery->folderId)
        ) {
            return false;
        }

        if ($queryFolder === null) {
            $assetsService = Craft::$app->getAssets();
            $queryFolder = $assetsService->getFolderById($assetQuery->folderId);
            if (!$queryFolder) {
                return false;
            }
        }

        if ($queryFolder->getVolume() instanceof Temp) {
            return false;
        }

        if ($assetQuery->search) {
            $assetQuery->search = $searchQuery = Craft::$app->getSearch()->normalizeSearchQuery($assetQuery->search);
            $tokens = $searchQuery->getTokens();
            if (count($tokens) !== 1 || !self::_validateSearchTermForIndex(reset($tokens))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param SearchQueryTerm|SearchQueryTermGroup $token
     * @return bool
     */
    private static function _validateSearchTermForIndex($token): bool
    {
        if ($token instanceof SearchQueryTermGroup) {
            foreach ($token->terms as $term) {
                if (!self::_validateSearchTermForIndex($term)) {
                    return false;
                }
            }
            return true;
        }

        /** @var SearchQueryTerm $token */
        return !$token->exclude && !$token->attribute;
    }

    /**
     * @throws QueryAbortedException
     */
    private static function _createFolderQueryForIndex(AssetQuery $assetQuery, ?VolumeFolder $queryFolder = null): Query
    {
        if (
            is_array($assetQuery->orderBy) &&
            is_string($firstOrderByCol = ArrayHelper::firstKey($assetQuery->orderBy)) &&
            in_array($firstOrderByCol, ['title', 'filename'])
        ) {
            $sortDir = $assetQuery->orderBy[$firstOrderByCol];
        } else {
            $sortDir = SORT_ASC;
        }

        $assetsService = Craft::$app->getAssets();
        $query = $assetsService->createFolderQuery()
            ->orderBy(['name' => $sortDir]);

        if ($assetQuery->includeSubfolders) {
            if ($queryFolder === null) {
                $queryFolder = $assetsService->getFolderById($assetQuery->folderId);
                if (!$queryFolder) {
                    throw new QueryAbortedException();
                }
            }
            $query
                ->where(['volumeId' => $queryFolder->volumeId])
                ->andWhere(['not', ['id' => $queryFolder->id]])
                ->andWhere(['like', 'path', "$queryFolder->path%", false]);
        } else {
            $query->where(['parentId' => $assetQuery->folderId]);
        }

        if ($assetQuery->search) {
            // `search` will already be normalized to a SearchQuery obj via _includeFoldersInIndexElements(),
            // and we already know it only has one token
            /** @var SearchQuery $searchQuery */
            $searchQuery = $assetQuery->search;
            $token = ArrayHelper::firstValue($searchQuery->getTokens());
            $query->andWhere(self::_buildFolderQuerySearchCondition($token));
        }

        return $query;
    }

    /**
     * @param SearchQueryTerm|SearchQueryTermGroup $token
     * @return array
     */
    private static function _buildFolderQuerySearchCondition($token): array
    {
        if ($token instanceof SearchQueryTermGroup) {
            $condition = ['or'];
            foreach ($token->terms as $term) {
                $condition[] = self::_buildFolderQuerySearchCondition($term);
            }
            return $condition;
        }

        $isPgsql = Craft::$app->getDb()->getIsPgsql();

        /** @var SearchQueryTerm $token */
        if ($token->subLeft || $token->subRight) {
            return [$isPgsql ? 'ilike' : 'like', 'name', sprintf('%s%s%s',
                $token->subLeft ? '%' : '',
                $token->term,
                $token->subRight ? '%' : ''
            ), false];
        }

        // Only Postgres supports case-sensitive queries
        if ($isPgsql) {
            return ['=', 'lower([[name]])', mb_strtolower($token->term)];
        }

        return ['name' => $token->term];
    }

    /**
     * Transforms an VolumeFolderModel into a source info array.
     *
     * @param VolumeFolder $folder
     * @param User|null $user
     * @return array
     */
    private static function _assembleSourceInfoForFolder(VolumeFolder $folder, User $user = null): array
    {
        $volume = $folder->getVolume();

        if ($volume instanceof Temp) {
            $volumeHandle = 'temp';
        } elseif (!$folder->parentId) {
            $volumeHandle = $volume->handle ?? false;
        } else {
            $volumeHandle = false;
        }

        $userSession = Craft::$app->getUser();
        $canUpload = $userSession->checkPermission("saveAssetInVolume:$volume->uid");
        $canMoveTo = $canUpload && $userSession->checkPermission("deleteFilesAndFoldersInVolume:$volume->uid");
        $canMovePeerFilesTo = (
            $canMoveTo &&
            $userSession->checkPermission("editPeerFilesInVolume:$volume->uid") &&
            $userSession->checkPermission("deletePeerFilesInVolume:$volume->uid")
        );

        $sourcePathInfo = $folder->getSourcePathInfo();

        $source = [
            'key' => 'folder:' . $folder->uid,
            'label' => $folder->parentId ? $folder->name : Craft::t('site', $folder->name),
            'hasThumbs' => true,
            'criteria' => ['folderId' => $folder->id],
            'defaultSort' => ['dateCreated', 'desc'],
            'defaultSourcePath' => $sourcePathInfo ? [$sourcePathInfo] : null,
            'data' => [
                'volume-handle' => $volumeHandle,
                'folder-id' => $folder->id,
                'has-children' => $folder->getHasChildren(),
                'can-upload' => $folder->volumeId === null || $canUpload,
                'can-move-to' => $canMoveTo,
                'can-move-peer-files-to' => $canMovePeerFilesTo,
            ],
        ];

        if ($user) {
            if (!$user->can("viewPeerFilesInVolume:$volume->uid")) {
                $source['criteria']['uploaderId'] = $user->id;
            }
        }

        return $source;
    }

    private static function isFolderIndex(): bool
    {
        return (
            (Craft::$app->controller instanceof ElementIndexesController || Craft::$app->controller instanceof ElementsController) &&
            Craft::$app->getRequest()->getBodyParam('foldersOnly')
        );
    }

    /**
     * @var bool Whether this asset represents a folder.
     * @since 3.8.0
     * @internal
     */
    public $isFolder = false;

    /**
     * @var array|null The source path, if this represents a folder.
     * @since 3.8.0
     * @internal
     */
    public $sourcePath;

    /**
     * @var int|null Folder ID
     */
    public $folderId;

    /**
     * @var int|null The ID of the user who first added this asset (if known)
     */
    public $uploaderId;

    /**
     * @var string|null Folder path
     */
    public $folderPath;

    /**
     * @var string|null Filename
     * @todo rename to private $_basename w/ getter & setter in 4.0; and getFilename() should not include the extension (to be like PATHINFO_FILENAME). We can add a getBasename() for getting the whole thing.
     */
    public $filename;

    /**
     * @var string|null Kind
     */
    public $kind;

    /**
     * @var int|null Size
     */
    public $size;

    /**
     * @var bool|null Whether the file was kept around when the asset was deleted
     */
    public $keptFile;

    /**
     * @var DateTime|null Date modified
     */
    public $dateModified;

    /**
     * @var string|null New file location
     */
    public $newLocation;

    /**
     * @var string|null Location error code
     * @see AssetLocationValidator::validateAttribute()
     */
    public $locationError;

    /**
     * @var string|null New filename
     */
    public $newFilename;

    /**
     * @var int|null New folder ID
     */
    public $newFolderId;

    /**
     * @var string|null The temp file path
     */
    public $tempFilePath;

    /**
     * @var bool Whether the asset should avoid filename conflicts when saved.
     */
    public $avoidFilenameConflicts = false;

    /**
     * @var string|null The suggested filename in case of a conflict.
     */
    public $suggestedFilename;

    /**
     * @var string|null The filename that was used that caused a conflict.
     */
    public $conflictingFilename;

    /**
     * @var bool Whether the asset was deleted along with its volume
     * @see beforeDelete()
     */
    public $deletedWithVolume = false;

    /**
     * @var bool Whether the associated file should be preserved if the asset record is deleted.
     * @see beforeDelete()
     * @see afterDelete()
     */
    public $keepFileOnDelete = false;

    /**
     * @var int|null Volume ID
     */
    private $_volumeId;

    /**
     * @var int|float|null Width
     */
    private $_width;

    /**
     * @var int|float|null Height
     */
    private $_height;

    /**
     * @var array|null Focal point
     */
    private $_focalPoint;

    /**
     * @var AssetTransform|null
     */
    private $_transform;

    /**
     * @var string
     */
    private $_transformSource = '';

    /**
     * @var VolumeInterface|null
     */
    private $_volume;

    /**
     * @var User|null
     */
    private $_uploader;

    /**
     * @var int|null
     */
    private $_oldVolumeId;

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        try {
            if ($this->_transform !== null && ($url = (string)$this->getUrl())) {
                return $url;
            }
            return parent::__toString();
        } catch (\Exception $e) {
            ErrorHandler::convertExceptionToError($e);
        }
    }

    /**
     * Checks if a property is set.
     *
     * This method will check if $name is one of the following:
     * - a magic property supported by [[Element::__isset()]]
     * - an image transform handle
     *
     * @param string $name The property name
     * @return bool Whether the property is set
     */
    public function __isset($name): bool
    {
        return (
            parent::__isset($name) ||
            strncmp($name, 'transform:', 10) === 0 ||
            Craft::$app->getAssetTransforms()->getTransformByHandle($name)
        );
    }

    /**
     * Returns a property value.
     *
     * This method will check if $name is one of the following:
     * - a magic property supported by [[Element::__get()]]
     * - an image transform handle
     *
     * @param string $name The property name
     * @return mixed The property value
     * @throws UnknownPropertyException if the property is not defined
     * @throws InvalidCallException if the property is write-only.
     */
    public function __get($name)
    {
        if (strncmp($name, 'transform:', 10) === 0) {
            return $this->copyWithTransform(substr($name, 10));
        }

        try {
            return parent::__get($name);
        } catch (UnknownPropertyException $e) {
            // Is $name a transform handle?
            if (($transform = Craft::$app->getAssetTransforms()->getTransformByHandle($name)) !== null) {
                return $this->copyWithTransform($transform);
            }

            throw $e;
        }
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function init()
    {
        parent::init();
        $this->_oldVolumeId = $this->_volumeId;
    }

    /**
     * Returns the volume’s ID.
     *
     * @return int|null
     */
    public function getVolumeId()
    {
        return (int)$this->_volumeId ?: null;
    }

    /**
     * Sets the volume’s ID.
     *
     * @param int|null $id
     */
    public function setVolumeId(int $id = null)
    {
        if ($id !== $this->getVolumeId()) {
            $this->_volumeId = $id;
            $this->_volume = null;
        }
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'dateModified';
        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['title'], StringValidator::class, 'max' => 255, 'disallowMb4' => true, 'on' => [self::SCENARIO_CREATE]];
        $rules[] = [['volumeId', 'folderId', 'width', 'height', 'size'], 'number', 'integerOnly' => true];
        $rules[] = [['dateModified'], DateTimeValidator::class];
        $rules[] = [['filename', 'kind'], 'required'];
        $rules[] = [['kind'], 'string', 'max' => 50];
        $rules[] = [['newLocation'], 'required', 'on' => [self::SCENARIO_CREATE, self::SCENARIO_MOVE, self::SCENARIO_FILEOPS]];
        $rules[] = [['tempFilePath'], 'required', 'on' => [self::SCENARIO_CREATE, self::SCENARIO_REPLACE]];

        // Validate the extension unless all we're doing is moving the file
        $rules[] = [
            ['newLocation'],
            AssetLocationValidator::class,
            'avoidFilenameConflicts' => $this->avoidFilenameConflicts,
            'except' => [self::SCENARIO_MOVE],
        ];
        $rules[] = [
            ['newLocation'],
            AssetLocationValidator::class,
            'avoidFilenameConflicts' => $this->avoidFilenameConflicts,
            'allowedExtensions' => '*',
            'on' => [self::SCENARIO_MOVE],
        ];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_INDEX] = [];

        return $scenarios;
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getCacheTags(): array
    {
        $tags = [
            "volume:$this->_volumeId",
        ];

        // Did the volume just change?
        if ($this->_volumeId != $this->_oldVolumeId) {
            $tags[] = "volume:$this->_oldVolumeId";
        }

        return $tags;
    }

    /**
     * @inheritdoc
     */
    protected function isEditable(): bool
    {
        if ($this->isFolder) {
            return false;
        }

        $volume = $this->getVolume();
        $userSession = Craft::$app->getUser();
        $isUploader = $this->uploaderId && $this->uploaderId == $userSession->getId();

        if ($isUploader) {
            return $userSession->checkPermission("saveAssetInVolume:$volume->uid");
        }

        return $userSession->checkPermission("editPeerFilesInVolume:$volume->uid");
    }

    /**
     * @inheritdoc
     * @since 3.5.15
     */
    protected function isDeletable(): bool
    {
        if ($this->isFolder) {
            return false;
        }

        $volume = $this->getVolume();

        if ($volume instanceof Temp) {
            return true;
        }

        $userSession = Craft::$app->getUser();
        return (
            $userSession->checkPermission("deleteFilesAndFoldersInVolume:$volume->uid") &&
            ($userSession->getId() == $this->uploaderId || $userSession->checkPermission("deletePeerFilesInVolume:$volume->uid"))
        );
    }

    /**
     * @inheritdoc
     * ---
     * ```php
     * $url = $asset->cpEditUrl;
     * ```
     * ```twig{2}
     * {% if asset.isEditable %}
     *   <a href="{{ asset.cpEditUrl }}">Edit</a>
     * {% endif %}
     * ```
     * @since 3.4.0
     */
    protected function cpEditUrl(): ?string
    {
        if ($this->isFolder) {
            return null;
        }

        $volume = $this->getVolume();
        if ($volume instanceof Temp) {
            return null;
        }

        $filename = $this->getFilename(false);
        $path = "assets/edit/$this->id-$filename";

        $params = [];
        if (Craft::$app->getIsMultiSite()) {
            $params['site'] = $this->getSite()->handle;
        }

        return UrlHelper::cpUrl($path, $params);
    }

    /**
     * Returns an `<img>` tag based on this asset.
     *
     * @param AssetTransform|string|array|null $transform The transform to use when generating the html.
     * @param string[]|null $sizes The widths/x-descriptors that should be used for the `srcset` attribute
     * (see [[getSrcset()]] for example syntaxes)
     * @return Markup|null
     * @throws InvalidArgumentException
     */
    public function getImg($transform = null, array $sizes = null)
    {
        if ($this->kind !== self::KIND_IMAGE) {
            return null;
        }

        $volume = $this->getVolume();

        if (!$volume->hasUrls) {
            return null;
        }

        if ($transform) {
            $oldTransform = $this->_transform;
            $this->setTransform($transform);
        }

        $img = Html::tag('img', '', [
            'src' => $this->getUrl(),
            'width' => $this->getWidth(),
            'height' => $this->getHeight(),
            'srcset' => $sizes ? $this->getSrcset($sizes) : false,
            'alt' => $this->title,
        ]);

        if (isset($oldTransform)) {
            $this->setTransform($oldTransform);
        }

        return Template::raw($img);
    }

    /**
     * Returns a `srcset` attribute value based on the given widths or x-descriptors.
     *
     * For example, if you pass `['100w', '200w']`, you will get:
     *
     * ```
     * image-url@100w.ext 100w,
     * image-url@200w.ext 200w
     * ```
     *
     * If you pass x-descriptors, it will be assumed that the image’s current width is the `1x` width.
     * So if you pass `['1x', '2x']` on an image with a 100px-wide transform applied, you will get:
     *
     * ```
     * image-url@100w.ext,
     * image-url@200w.ext 2x
     * ```
     *
     * @param string[] $sizes
     * @param AssetTransform|string|array|null $transform A transform handle or configuration that should be applied to the image
     * @return string|false The `srcset` attribute value, or `false` if it can’t be determined
     * @throws InvalidArgumentException
     * @since 3.5.0
     */
    public function getSrcset(array $sizes, $transform = null)
    {
        $urls = $this->getUrlsBySize($sizes, $transform);

        if (empty($urls)) {
            return false;
        }

        $srcset = [];

        foreach ($urls as $size => $url) {
            if ($size === '1x') {
                $srcset[] = $url;
            } else {
                $srcset[] = "$url $size";
            }
        }

        return implode(', ', $srcset);
    }

    /**
     * Returns an array of image transform URLs based on the given widths or x-descriptors.
     *
     * For example, if you pass `['100w', '200w']`, you will get:
     *
     * ```php
     * [
     *     '100w' => 'image-url@100w.ext',
     *     '200w' => 'image-url@200w.ext'
     * ]
     * ```
     *
     * If you pass x-descriptors, it will be assumed that the image’s current width is the indented 1x width.
     * So if you pass `['1x', '2x']` on an image with a 100px-wide transform applied, you will get:
     *
     * ```php
     * [
     *     '1x' => 'image-url@100w.ext',
     *     '2x' => 'image-url@200w.ext'
     * ]
     * ```
     *
     * @param string[] $sizes
     * @param AssetTransform|string|array|null $transform A transform handle or configuration that should be applied to the image
     * @return array
     * @since 3.7.16
     */
    public function getUrlsBySize(array $sizes, $transform = null): array
    {
        if ($this->kind !== self::KIND_IMAGE) {
            return [];
        }

        $urls = [];

        if (
            ($transform !== null || $this->_transform) &&
            Image::canManipulateAsImage($this->getExtension())
        ) {
            $transform = Craft::$app->getAssetTransforms()->normalizeTransform($transform ?? $this->_transform);
        } else {
            $transform = null;
        }

        [$currentWidth, $currentHeight] = $this->_dimensions($transform);

        if (!$currentWidth || !$currentHeight) {
            return [];
        }

        foreach ($sizes as $size) {
            if ($size === '1x') {
                $urls[$size] = $this->getUrl($transform);
                continue;
            }

            [$value, $unit] = Assets::parseSrcsetSize($size);

            $sizeTransform = $transform ? $transform->toArray([
                'format',
                'height',
                'interlace',
                'mode',
                'position',
                'quality',
                'width',
            ]) : [];

            if ($unit === 'w') {
                $sizeTransform['width'] = (int)$value;
            } else {
                $sizeTransform['width'] = (int)ceil($currentWidth * $value);
            }

            // Only set the height if the current transform has a height set on it
            if ($transform && $transform->height) {
                if ($unit === 'w') {
                    $sizeTransform['height'] = (int)ceil($currentHeight * $sizeTransform['width'] / $currentWidth);
                } else {
                    $sizeTransform['height'] = (int)ceil($currentHeight * $value);
                }
            }

            $urls["$value$unit"] = $this->getUrl($sizeTransform);
        }

        return $urls;
    }

    /**
     * @inheritdoc
     */
    public function getIsTitleTranslatable(): bool
    {
        return ($this->getVolume()->titleTranslationMethod !== Field::TRANSLATION_METHOD_NONE);
    }

    /**
     * @inheritdoc
     */
    public function getTitleTranslationDescription()
    {
        return ElementHelper::translationDescription($this->getVolume()->titleTranslationMethod);
    }

    /**
     * @inheritdoc
     */
    public function getTitleTranslationKey(): string
    {
        $type = $this->getVolume();
        return ElementHelper::translationKey($this, $type->titleTranslationMethod, $type->titleTranslationKeyFormat);
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout()
    {
        if (($fieldLayout = parent::getFieldLayout()) !== null) {
            return $fieldLayout;
        }

        $volume = $this->getVolume();

        if ($volume instanceof Temp) {
            // See if a default field layout ID was posted
            $request = Craft::$app->getRequest();
            if (!$request->isConsoleRequest) {
                $fieldLayoutId = $request->getBodyParam('defaultFieldLayoutId');
                if ($fieldLayoutId) {
                    $fieldLayout = Craft::$app->getFields()->getLayoutById($fieldLayoutId);
                    if ($fieldLayout) {
                        return $fieldLayout;
                    }
                }
            }
        }

        return $volume->getFieldLayout();
    }

    /**
     * Returns the asset’s volume folder.
     *
     * @return VolumeFolder
     * @throws InvalidConfigException if [[folderId]] is missing or invalid
     */
    public function getFolder(): VolumeFolder
    {
        if ($this->folderId === null) {
            throw new InvalidConfigException('Asset is missing its folder ID');
        }

        if (($folder = Craft::$app->getAssets()->getFolderById($this->folderId)) === null) {
            throw new InvalidConfigException('Invalid folder ID: ' . $this->folderId);
        }

        return $folder;
    }

    /**
     * Returns the asset’s volume.
     *
     * @return VolumeInterface
     * @throws InvalidConfigException if [[volumeId]] is missing or invalid
     */
    public function getVolume(): VolumeInterface
    {
        if ($this->_volume !== null) {
            return $this->_volume;
        }

        if ($this->_volumeId === null) {
            return new Temp();
        }

        if (($volume = Craft::$app->getVolumes()->getVolumeById($this->_volumeId)) === null) {
            throw new InvalidConfigException('Invalid volume ID: ' . $this->_volumeId);
        }

        return $this->_volume = $volume;
    }

    /**
     * Returns the user that uploaded the asset, if known.
     *
     * @return User|null
     * @since 3.4.0
     */
    public function getUploader()
    {
        if ($this->_uploader !== null) {
            return $this->_uploader;
        }

        if ($this->uploaderId === null) {
            return null;
        }

        if (($this->_uploader = Craft::$app->getUsers()->getUserById($this->uploaderId)) === null) {
            // The uploader is probably soft-deleted. Just pretend no uploader is set
            return null;
        }

        return $this->_uploader;
    }

    /**
     * Sets the asset's uploader.
     *
     * @param User|null $uploader
     * @since 3.4.0
     */
    public function setUploader(User $uploader = null)
    {
        $this->_uploader = $uploader;
    }

    /**
     * Sets the transform.
     *
     * @param AssetTransform|string|array|null $transform A transform handle or configuration that should be applied to the image
     * @return Asset
     * @throws AssetTransformException if $transform is an invalid transform handle
     */
    public function setTransform($transform): Asset
    {
        $this->_transform = Craft::$app->getAssetTransforms()->normalizeTransform($transform);

        return $this;
    }

    /**
     * Returns the element’s full URL.
     *
     * @param AssetTransform|string|array|null $transform A transform handle or configuration that should be applied to the
     * image If an array is passed, it can optionally include a `transform` key that defines a base transform
     * which the rest of the settings should be applied to.
     * @param bool|null $generateNow Whether the transformed image should be generated immediately if it doesn’t exist. If `null`, it will be left
     * up to the `generateTransformsBeforePageLoad` config setting.
     * @return string|null
     * @throws InvalidConfigException
     */
    public function getUrl($transform = null, bool $generateNow = null)
    {
        if ($this->isFolder) {
            return null;
        }

        $volume = $this->getVolume();

        if (!$volume->hasUrls || !$this->folderId) {
            return null;
        }

        $mimeType = $this->getMimeType();
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if (
            ($mimeType === 'image/gif' && !$generalConfig->transformGifs) ||
            ($mimeType === 'image/svg+xml' && !$generalConfig->transformSvgs)
        ) {
            return Assets::generateUrl($volume, $this);
        }

        // Normalize empty transform values
        $transform = $transform ?: null;

        if (is_array($transform)) {
            if (isset($transform['width'])) {
                $transform['width'] = round((float)$transform['width']);
            }
            if (isset($transform['height'])) {
                $transform['height'] = round((float)$transform['height']);
            }
            $assetTransformsService = Craft::$app->getAssetTransforms();
            $transform = $assetTransformsService->normalizeTransform($transform);
        }

        if ($transform === null && $this->_transform !== null) {
            $transform = $this->_transform;
        }

        try {
            return Craft::$app->getAssets()->getAssetUrl($this, $transform, $generateNow);
        } catch (VolumeObjectNotFoundException $e) {
            Craft::error("Could not determine asset's URL ($this->id): {$e->getMessage()}");
            Craft::$app->getErrorHandler()->logException($e);
            return UrlHelper::actionUrl('not-found', null, null, false);
        }
    }

    /**
     * @inheritdoc
     */
    public function getThumbUrl(int $size)
    {
        if ($this->isFolder) {
            return Craft::$app->getAssetManager()->getPublishedUrl('@app/web/assets/cp/dist', true, 'images/folder.svg');
        }

        if ($this->getWidth() && $this->getHeight()) {
            [$width, $height] = Assets::scaledDimensions($this->getWidth(), $this->getHeight(), $size, $size);
        } else {
            $width = $height = $size;
        }

        return Craft::$app->getAssets()->getThumbUrl($this, $width, $height, false);
    }

    /**
     * @inheritdoc
     */
    public function getHasCheckeredThumb(): bool
    {
        if ($this->isFolder) {
            return false;
        }

        return in_array(strtolower($this->getExtension()), ['png', 'gif', 'svg'], true);
    }

    /**
     * Returns preview thumb image HTML.
     *
     * @param int $width
     * @param int $height
     * @return string
     * @throws NotSupportedException if the asset can't have a thumbnail, and $fallbackToIcon is `false`
     * @since 3.4.0
     */
    public function getPreviewThumbImg(int $width, int $height): string
    {
        $assetsService = Craft::$app->getAssets();
        $srcsets = [];
        [$width, $height] = Assets::scaledDimensions($this->getWidth() ?? 0, $this->getHeight() ?? 0, $width, $height);
        $thumbSizes = [
            [$width, $height],
            [$width * 2, $height * 2],
        ];
        foreach ($thumbSizes as [$width, $height]) {
            $thumbUrl = $assetsService->getThumbUrl($this, $width, $height, false, false);
            $srcsets[] = $thumbUrl . ' ' . $width . 'w';
        }

        return Html::tag('img', '', [
            'sizes' => "{$thumbSizes[0][0]}px",
            'srcset' => implode(', ', $srcsets),
            'alt' => $this->title,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getPreviewTargets(): array
    {
        return [];
    }

    /**
     * Returns the file name, with or without the extension.
     *
     * @param bool $withExtension
     * @return string
     */
    public function getFilename(bool $withExtension = true): string
    {
        if ($this->isFolder) {
            return '';
        }

        if ($withExtension) {
            return $this->filename;
        }

        return pathinfo($this->filename, PATHINFO_FILENAME);
    }

    /**
     * Returns the file extension.
     *
     * @return string
     */
    public function getExtension(): string
    {
        return pathinfo($this->filename, PATHINFO_EXTENSION);
    }

    /**
     * Returns the file’s MIME type, if it can be determined.
     *
     * @param AssetTransform|string|array|null $transform A transform handle or configuration that should be applied to the mime type
     * @return string|null
     * @throws AssetTransformException if $transform is an invalid transform handle
     */
    public function getMimeType(mixed $transform = null): ?string
    {
        $transform = $transform ?? $this->_transform;
        $transform = Craft::$app->getAssetTransforms()->normalizeTransform($transform);

        if (!Image::canManipulateAsImage($this->getExtension()) || !$transform || !$transform->format) {
            // todo: maybe we should be passing this off to the volume
            // so Local can call FileHelper::getMimeType() (uses magic file instead of ext)
            return FileHelper::getMimeTypeByExtension($this->filename);
        }

        // Prepend with '.' to let pathinfo() work
        return FileHelper::getMimeTypeByExtension('.' . $transform->format);
    }

    /**
     * Returns the image height.
     *
     * @param AssetTransform|string|array|null $transform A transform handle or configuration that should be applied to the image
     * @return int|float|null
     */

    public function getHeight($transform = null)
    {
        return $this->_dimensions($transform)[1];
    }

    /**
     * Sets the image height.
     *
     * @param int|float|null $height the image height
     */
    public function setHeight($height)
    {
        $this->_height = $height;
    }

    /**
     * Returns the image width.
     *
     * @param AssetTransform|string|array|null $transform A transform handle or configuration that should be applied to the image
     * @return int|float|null
     */
    public function getWidth($transform = null)
    {
        return $this->_dimensions($transform)[0];
    }

    /**
     * Sets the image width.
     *
     * @param int|float|null $width the image width
     */
    public function setWidth($width)
    {
        $this->_width = $width;
    }

    /**
     * Returns the formatted file size, if known.
     *
     * @param int|null $decimals the number of digits after the decimal point
     * @param bool $short whether the size should be returned in short form (“kB” instead of “kilobytes”)
     * @return string|null
     * @since 3.4.0
     */
    public function getFormattedSize(int $decimals = null, bool $short = true)
    {
        if ($this->size === null) {
            return null;
        }
        if ($short) {
            return Craft::$app->getFormatter()->asShortSize($this->size, $decimals);
        }
        return Craft::$app->getFormatter()->asSize($this->size, $decimals);
    }

    /**
     * Returns the formatted file size in bytes, if known.
     *
     * @param bool $short whether the size should be returned in short form (“B” instead of “bytes”)
     * @return string|null
     * @since 3.4.0
     */
    public function getFormattedSizeInBytes(bool $short = true)
    {
        $params = [
            'n' => $this->size,
            'nFormatted' => Craft::$app->getFormatter()->asDecimal($this->size),
        ];
        if ($short) {
            return Craft::t('yii', '{nFormatted} B', $params);
        }
        return Craft::t('yii', '{nFormatted} {n, plural, =1{byte} other{bytes}}', $params);
    }

    /**
     * Returns the image dimensions.
     *
     * @return string|null
     * @since 3.4.0
     */
    public function getDimensions()
    {
        $width = $this->getWidth();
        $height = $this->getHeight();
        if (!$width || !$height) {
            return null;
        }
        return "{$width}×{$height}";
    }

    /**
     * @return string
     */
    public function getTransformSource(): string
    {
        if (!$this->_transformSource) {
            Craft::$app->getAssetTransforms()->getLocalImageSource($this);
        }

        return $this->_transformSource;
    }

    /**
     * Set a source to use for transforms for this asset.
     *
     * @param string $uri
     */
    public function setTransformSource(string $uri)
    {
        $this->_transformSource = $uri;
    }

    /**
     * Returns the asset's path in the volume.
     *
     * @param string|null $filename Filename to use. If not specified, the asset's filename will be used.
     * @return string
     * @deprecated in 3.0.0-RC12
     */
    public function getUri(string $filename = null): string
    {
        Craft::$app->getDeprecator()->log(self::class . '::getUri()', '`' . self::class . '::getUri()` has been deprecated. Use `getPath()` instead.');

        return $this->getPath($filename);
    }

    /**
     * Returns the asset's path in the volume.
     *
     * @param string|null $filename Filename to use. If not specified, the asset's filename will be used.
     * @return string
     */
    public function getPath(string $filename = null): string
    {
        return $this->folderPath . ($filename ?: $this->filename);
    }

    /**
     * Return the path where the source for this Asset's transforms should be.
     *
     * @return string
     */
    public function getImageTransformSourcePath(): string
    {
        $volume = $this->getVolume();

        if ($volume instanceof LocalVolumeInterface) {
            return FileHelper::normalizePath($volume->getRootPath() . DIRECTORY_SEPARATOR . $this->getPath());
        }

        return Craft::$app->getPath()->getAssetSourcesPath() . DIRECTORY_SEPARATOR . $this->id . '.' . $this->getExtension();
    }

    /**
     * Get a temporary copy of the actual file.
     *
     * @return string
     */
    public function getCopyOfFile(): string
    {
        $tempFilename = FileHelper::uniqueName($this->filename);
        $tempPath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $tempFilename;
        $this->getVolume()->saveFileLocally($this->getPath(), $tempPath);

        return $tempPath;
    }

    /**
     * Returns a stream of the actual file.
     *
     * @return resource
     * @throws InvalidConfigException if [[volumeId]] is missing or invalid
     * @throws AssetException if a stream could not be created
     */
    public function getStream()
    {
        return $this->getVolume()->getFileStream($this->getPath());
    }

    /**
     * Returns the file’s contents.
     *
     * @return string
     * @throws InvalidConfigException if [[volumeId]] is missing or invalid
     * @throws AssetException if a stream could not be created
     * @since 3.0.6
     */
    public function getContents(): string
    {
        return stream_get_contents($this->getStream());
    }

    /**
     * Generates a base64-encoded [data URL](https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/Data_URIs) for the asset.
     *
     * @return string
     * @throws InvalidConfigException if [[volumeId]] is missing or invalid
     * @throws AssetException if a stream could not be created
     * @since 3.5.13
     */
    public function getDataUrl(): string
    {
        return Html::dataUrlFromString($this->getContents(), $this->getMimeType());
    }

    /**
     * Return whether the asset has a URL.
     *
     * @return bool
     * @deprecated in 3.0.0-RC12. Use getVolume()->hasUrls instead.
     */
    public function getHasUrls(): bool
    {
        Craft::$app->getDeprecator()->log(self::class . '::getHasUrls()', '`' . self::class . '::getHasUrls()` has been deprecated. Use `getVolume()->hasUrls` instead.');

        $volume = $this->getVolume();
        return $volume && $volume->hasUrls;
    }

    /**
     * Returns whether this asset can be edited by the image editor.
     *
     * @return bool
     */
    public function getSupportsImageEditor(): bool
    {
        $ext = $this->getExtension();
        return (strcasecmp($ext, 'svg') !== 0 && Image::canManipulateAsImage($ext));
    }

    /**
     * Returns whether this asset can be previewed.
     *
     * @return bool
     * @deprecated in 3.4.0. Use [[\craft\services\Assets::getAssetPreviewHandler]] instead.
     */
    public function getSupportsPreview(): bool
    {
        Craft::$app->getDeprecator()->log(self::class . '::getSupportsPreview()', '`' . self::class . '::getSupportsPreview()` has been deprecated. Use `craft\services\Assets::getAssetPreview()` instead.');

        return in_array($this->kind, [self::KIND_IMAGE, self::KIND_HTML, self::KIND_JAVASCRIPT, self::KIND_JSON], true);
    }

    /**
     * Returns whether a user-defined focal point is set on the asset.
     *
     * @return bool
     */
    public function getHasFocalPoint(): bool
    {
        return $this->_focalPoint !== null;
    }

    /**
     * Returns the focal point represented as an array with `x` and `y` keys, or null if it's not an image.
     *
     * @param bool $asCss whether the value should be returned in CSS syntax ("50% 25%") instead
     * @return array|string|null
     */
    public function getFocalPoint(bool $asCss = false)
    {
        if (!in_array($this->kind, [self::KIND_IMAGE, self::KIND_VIDEO], true)) {
            return null;
        }

        $focal = $this->_focalPoint ?? ['x' => 0.5, 'y' => 0.5];

        if ($asCss) {
            return ($focal['x'] * 100) . '% ' . ($focal['y'] * 100) . '%';
        }

        return $focal;
    }

    /**
     * Sets the asset's focal point.
     *
     * @param $value string|array|null
     * @throws \InvalidArgumentException if $value is invalid
     */
    public function setFocalPoint($value)
    {
        if (is_array($value)) {
            if (!isset($value['x'], $value['y'])) {
                throw new \InvalidArgumentException('$value should be a string or array with \'x\' and \'y\' keys.');
            }
            $value = [
                'x' => (float)$value['x'],
                'y' => (float)$value['y'],
            ];
        } elseif ($value !== null) {
            $focal = explode(';', $value);
            if (count($focal) !== 2) {
                throw new \InvalidArgumentException('$value should be a string or array with \'x\' and \'y\' keys.');
            }
            $value = [
                'x' => (float)$focal[0],
                'y' => (float)$focal[1],
            ];
        }

        if ($value !== null && (
            $value['x'] < 0 ||
            $value['x'] > 1 ||
            $value['y'] < 0 ||
            $value['y'] > 1
        )) {
            $value = null;
        }

        $this->_focalPoint = $value;
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function getTableAttributeHtml(string $attribute): string
    {
        if ($this->isFolder) {
            return '';
        }

        return parent::getTableAttributeHtml($attribute);
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'uploader':
                $uploader = $this->getUploader();
                return $uploader ? Cp::elementHtml($uploader) : '';

            case 'filename':
                return Html::tag('span', Html::encode($this->filename), [
                    'class' => 'break-word',
                ]);

            case 'kind':
                return Assets::getFileKindLabel($this->kind);

            case 'size':
                if ($this->size === null) {
                    return '';
                }
                return Html::tag('span', $this->getFormattedSize(0), [
                    'title' => $this->getFormattedSizeInBytes(false),
                ]);

            case 'imageSize':
                return $this->getDimensions() ?? '';

            case 'width':
            case 'height':
                $size = $this->$attribute;
                return ($size ? $size . 'px' : '');
        }

        return parent::tableAttributeHtml($attribute);
    }

    /**
     * Returns the HTML for asset previews.
     *
     * @return string
     * @throws InvalidConfigException
     */
    public function getPreviewHtml(): string
    {
        $html = '';

        // See if we can show a thumbnail
        try {
            // Is the image editable, and is the user allowed to edit?
            $userSession = Craft::$app->getUser();

            $volume = $this->getVolume();
            $previewable = Craft::$app->getAssets()->getAssetPreviewHandler($this) !== null;
            $editable = (
                $this->getSupportsImageEditor() &&
                $userSession->checkPermission("editImagesInVolume:$volume->uid") &&
                ($userSession->getId() == $this->uploaderId || $userSession->checkPermission("editPeerImagesInVolume:$volume->uid"))
            );

            $html = Html::tag('div',
                Html::tag('div', $this->getPreviewThumbImg(350, 190), [
                    'class' => 'preview-thumb',
                ]) .
                Html::tag(
                    'div',
                    ($previewable ? Html::tag('button', Craft::t('app', 'Preview'), ['class' => 'btn preview-btn', 'id' => 'preview-btn', 'type' => 'button']) : '') .
                    ($editable ? Html::tag('button', Craft::t('app', 'Edit'), ['class' => 'btn edit-btn', 'id' => 'edit-btn', 'type' => 'button']) : ''),
                    ['class' => 'buttons']
                ),
                [
                    'class' => array_filter([
                        'preview-thumb-container',
                        $this->getHasCheckeredThumb() ? 'checkered' : null,
                    ]),
                ]
            );
        } catch (NotSupportedException $e) {
            // NBD
        }

        return $html;
    }

    /**
     * @inheritdoc
     */
    public function getSidebarHtml(): string
    {
        $components = [

            // Omit preview button on sidebar of slideouts
            $this->getPreviewHtml(false),

            parent::getSidebarHtml(),
        ];

        return implode("\n", $components);
    }

    /**
     * @inheritdoc
     */
    protected function metaFieldsHtml(): string
    {
        return implode('', [
            Cp::textFieldHtml([
                'label' => Craft::t('app', 'Filename'),
                'id' => 'newFilename',
                'name' => 'newFilename',
                'value' => $this->filename,
                'errors' => $this->getErrors('newLocation'),
                'first' => true,
                'required' => true,
                'class' => ['text', 'filename'],
            ]),
            parent::metaFieldsHtml(),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function metadata(): array
    {
        $volume = $this->getVolume();

        return [
            Craft::t('app', 'Location') => function() use ($volume) {
                $loc = [Html::encode(Craft::t('site', $volume->name))];
                if ($this->folderPath) {
                    array_push($loc, ...ArrayHelper::filterEmptyStringsFromArray(explode('/', $this->folderPath)));
                }
                return implode(' → ', $loc);
            },
            Craft::t('app', 'File size') => function() {
                $size = $this->getFormattedSize(0);
                if (!$size) {
                    return false;
                }
                $inBytes = $this->getFormattedSizeInBytes(false);
                return Html::tag('div', $size, [
                    'title' => $inBytes,
                    'aria' => [
                        'label' => $inBytes,
                    ],
                ]);
            },
            Craft::t('app', 'Uploaded by') => function() {
                $uploader = $this->getUploader();
                return $uploader ? Cp::elementHtml($uploader) : false;
            },
            Craft::t('app', 'Dimensions') => $this->getDimensions() ?: false,
        ];
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getGqlTypeName(): string
    {
        return static::gqlTypeNameByContext($this->getVolume());
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        $names = parent::attributes();
        $names[] = 'extension';
        $names[] = 'filename';
        $names[] = 'focalPoint';
        $names[] = 'hasFocalPoint';
        $names[] = 'height';
        $names[] = 'mimeType';
        $names[] = 'path';
        $names[] = 'volumeId';
        $names[] = 'width';
        return $names;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        $names = parent::extraFields();
        $names[] = 'folder';
        $names[] = 'volume';
        return $names;
    }

    /**
     * Returns a copy of the asset with the given transform applied to it.
     *
     * @param AssetTransform|string|array|null $transform The transform handle or configuration that should be applied to the image
     * @return Asset
     * @throws AssetTransformException if $transform is an invalid transform handle
     */
    public function copyWithTransform($transform): Asset
    {
        $model = clone $this;
        $model->setFieldValues($this->getFieldValues());
        $model->setTransform($transform);

        return $model;
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function beforeSave(bool $isNew): bool
    {
        // newFolderId/newFilename => newLocation.
        if ($this->newFilename === '') {
            $this->newFilename = null;
        }
        if ($this->newFolderId !== null || $this->newFilename !== null) {
            $folderId = $this->newFolderId ?: $this->folderId;
            $filename = $this->newFilename ?? $this->filename;
            $this->newLocation = "{folder:$folderId}$filename";
            $this->newFolderId = $this->newFilename = null;
        }

        // Get the (new?) folder ID
        if ($this->newLocation !== null) {
            [$folderId] = Assets::parseFileLocation($this->newLocation);
        } else {
            $folderId = $this->folderId;
        }

        // Fire a 'beforeHandleFile' event if we're going to be doing any file operations in afterSave()
        if (
            ($this->newLocation !== null || $this->tempFilePath !== null) &&
            $this->hasEventHandlers(self::EVENT_BEFORE_HANDLE_FILE)
        ) {
            $this->trigger(self::EVENT_BEFORE_HANDLE_FILE, new AssetEvent([
                'asset' => $this,
                'isNew' => !$this->id,
            ]));
        }

        // Set the kind based on filename, if not set already
        if ($this->kind === null && $this->filename !== null) {
            $this->kind = Assets::getFileKindByExtension($this->filename);
        }

        // Give it a default title based on the file name, if it doesn't have a title yet
        if (!$this->id && !$this->title) {
            $this->title = Assets::filename2Title(pathinfo($this->filename, PATHINFO_FILENAME));
        }

        // Set the field layout
        $volume = Craft::$app->getAssets()->getFolderById($folderId)->getVolume();

        if (!$volume instanceof Temp) {
            $this->fieldLayoutId = $volume->fieldLayoutId;
        }

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     * @throws Exception if the asset isn't new but doesn't have a row in the `assets` table for some reason
     */
    public function afterSave(bool $isNew)
    {
        if (!$this->propagating) {
            $isCpRequest = Craft::$app->getRequest()->getIsCpRequest();
            $sanitizeCpImageUploads = Craft::$app->getConfig()->getGeneral()->sanitizeCpImageUploads;

            if (
                in_array($this->getScenario(), [self::SCENARIO_REPLACE, self::SCENARIO_CREATE], true) &&
                Assets::getFileKindByExtension($this->tempFilePath) === static::KIND_IMAGE &&
                !($isCpRequest && !$sanitizeCpImageUploads)
            ) {
                Image::cleanImageByPath($this->tempFilePath);
            }

            // Relocate the file?
            if ($this->newLocation !== null || $this->tempFilePath !== null) {
                $this->_relocateFile();
            }

            // Get the asset record
            if (!$isNew) {
                $record = AssetRecord::findOne($this->id);

                if (!$record) {
                    throw new Exception('Invalid asset ID: ' . $this->id);
                }
            } else {
                $record = new AssetRecord();
                $record->id = (int)$this->id;
            }

            $record->filename = $this->filename;
            $record->volumeId = $this->getVolumeId();
            $record->folderId = (int)$this->folderId;
            $record->uploaderId = (int)$this->uploaderId ?: null;
            $record->kind = $this->kind;
            $record->size = (int)$this->size ?: null;
            $record->width = (int)$this->_width ?: null;
            $record->height = (int)$this->_height ?: null;
            $record->dateModified = $this->dateModified;

            if ($this->getHasFocalPoint()) {
                $focal = $this->getFocalPoint();
                $record->focalPoint = number_format($focal['x'], 4) . ';' . number_format($focal['y'], 4);
            } else {
                $record->focalPoint = null;
            }

            $record->save(false);
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        // Update the asset record
        Db::update(Table::ASSETS, [
            'deletedWithVolume' => $this->deletedWithVolume,
            'keptFile' => $this->keepFileOnDelete,
        ], [
            'id' => $this->id,
        ], [], false);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        if (!$this->keepFileOnDelete) {
            $this->getVolume()->deleteFile($this->getPath());
        }

        Craft::$app->getAssetTransforms()->deleteAllTransformData($this);
        parent::afterDelete();
    }

    /**
     * @inheritdoc
     */
    public function beforeRestore(): bool
    {
        // Only allow the asset to be restored if the file was kept on delete
        return $this->keptFile && parent::beforeRestore();
    }

    /**
     * @inheritdoc
     */
    public function getHtmlAttributes(string $context): array
    {
        if ($this->isFolder) {
            $attributes = [
                'data-is-folder' => null,
                'data-folder-id' => $this->folderId,
                'data-source-path' => Json::encode($this->sourcePath),
                'data-has-children' => Craft::$app->getAssets()->foldersExist(['parentId' => $this->folderId]),
            ];

            $volume = $this->getVolume();
            $userSession = Craft::$app->getUser();

            if (
                $userSession->checkPermission("editPeerFilesInVolume:$volume->uid") &&
                $userSession->checkPermission("deletePeerFilesInVolume:$volume->uid")
            ) {
                $attributes['data-movable'] = null;
            }

            return $attributes;
        }

        return parent::getHtmlAttributes($context);
    }

    /**
     * @inheritdoc
     */
    protected function htmlAttributes(string $context): array
    {
        $attributes = [
            'data-kind' => $this->kind,
        ];

        if ($this->kind === self::KIND_IMAGE) {
            $attributes['data-image-width'] = $this->getWidth();
            $attributes['data-image-height'] = $this->getHeight();
        }

        $volume = $this->getVolume();
        $userSession = Craft::$app->getUser();
        $imageEditable = $context === 'index' && $this->getSupportsImageEditor();

        if ($volume instanceof Temp || $userSession->getId() == $this->uploaderId) {
            $attributes['data-own-file'] = null;
            $movable = $replaceable = true;
        } else {
            $attributes['data-peer-file'] = null;
            $movable = (
                $userSession->checkPermission("editPeerFilesInVolume:$volume->uid") &&
                $userSession->checkPermission("deletePeerFilesInVolume:$volume->uid")
            );
            $replaceable = $userSession->checkPermission("replacePeerFilesInVolume:$volume->uid");
            $imageEditable = (
                $imageEditable &&
                ($userSession->checkPermission("editPeerImagesInVolume:$volume->uid"))
            );
        }

        if ($movable) {
            $attributes['data-movable'] = null;
        }

        if ($replaceable) {
            $attributes['data-replaceable'] = null;
        }

        if ($imageEditable) {
            $attributes['data-editable-image'] = null;
        }

        return $attributes;
    }

    /**
     * Returns whether the current user can move/rename the asset.
     *
     * @return bool
     */
    private function _isMovable(): bool
    {
        $userSession = Craft::$app->getUser();
        if ($userSession->getId() == $this->uploaderId) {
            return true;
        }

        $volume = $this->getVolume();
        return (
            $userSession->checkPermission("editPeerFilesInVolume:$volume->uid") &&
            $userSession->checkPermission("deletePeerFilesInVolume:$volume->uid")
        );
    }

    /**
     * Returns the width and height of the image.
     *
     * @param AssetTransform|string|array|null $transform
     * @return array
     */
    private function _dimensions($transform = null): array
    {
        if (!in_array($this->kind, [self::KIND_IMAGE, self::KIND_VIDEO], true)) {
            return [null, null];
        }

        if (!$this->_width || !$this->_height) {
            if (
                $this->kind === self::KIND_IMAGE &&
                $this->getScenario() !== self::SCENARIO_CREATE
            ) {
                Craft::warning("Asset $this->id is missing its width or height", __METHOD__);
            }

            return [null, null];
        }

        $transform = $transform ?? $this->_transform;

        if ($transform === null || !Image::canManipulateAsImage($this->getExtension())) {
            return [$this->_width, $this->_height];
        }

        $transform = Craft::$app->getAssetTransforms()->normalizeTransform($transform);

        return Image::targetDimensions(
            $this->_width,
            $this->_height,
            $transform->width,
            $transform->height,
            $transform->mode
        );
    }

    /**
     * Relocates the file after the element has been saved.
     *
     * @throws FileException if the file is being moved but cannot be read
     */
    private function _relocateFile()
    {
        $assetsService = Craft::$app->getAssets();

        // Get the (new?) folder ID & filename
        if ($this->newLocation !== null) {
            [$folderId, $filename] = Assets::parseFileLocation($this->newLocation);
        } else {
            $folderId = $this->folderId;
            $filename = $this->filename;
        }

        $hasNewFolder = $folderId != $this->folderId;

        $tempPath = null;

        $oldFolder = $this->folderId ? $assetsService->getFolderById($this->folderId) : null;
        $oldVolume = $oldFolder ? $oldFolder->getVolume() : null;

        $newFolder = $hasNewFolder ? $assetsService->getFolderById($folderId) : $oldFolder;
        $newVolume = $hasNewFolder ? $newFolder->getVolume() : $oldVolume;

        $oldPath = $this->folderId ? $this->getPath() : null;
        $newPath = ($newFolder->path ? rtrim($newFolder->path, '/') . '/' : '') . $filename;

        // Is this just a simple move/rename within the same volume?
        if ($this->tempFilePath === null && $oldFolder !== null && $oldFolder->volumeId == $newFolder->volumeId) {
            $oldVolume->renameFile($oldPath, $newPath);
        } else {
            // Get the temp path
            if ($this->tempFilePath !== null) {
                if (!$this->_validateTempFilePath()) {
                    Craft::warning("Prevented saving $this->tempFilePath as an asset. It must be located within a temp directory or the project root (excluding system directories).");
                    throw new FileException(Craft::t('app', "There was an error relocating the file."));
                }

                $tempPath = $this->tempFilePath;
            } else {
                $tempFilename = uniqid(pathinfo($filename, PATHINFO_FILENAME), true) . '.' . pathinfo($filename, PATHINFO_EXTENSION);
                $tempPath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $tempFilename;
                $oldVolume->saveFileLocally($oldPath, $tempPath);
            }

            // Try to open a file stream
            if (($stream = fopen($tempPath, 'rb')) === false) {
                if (file_exists($tempPath)) {
                    FileHelper::unlink($tempPath);
                }
                throw new FileException(Craft::t('app', 'Could not open file for streaming at {path}', ['path' => $tempPath]));
            }

            if ($this->folderId) {
                // Delete the old file
                $oldVolume->deleteFile($oldPath);
            }

            // Upload the file to the new location
            $newVolume->createFileByStream($newPath, $stream, [
                'mimetype' => FileHelper::getMimeType($tempPath),
            ]);

            // Rackspace will disconnect the stream automatically
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if ($this->folderId) {
            // Nuke the transforms
            Craft::$app->getAssetTransforms()->deleteAllTransformData($this);
        }

        // Update file properties
        $this->setVolumeId($newFolder->volumeId);
        $this->folderId = $folderId;
        $this->folderPath = $newFolder->path;
        $this->filename = $filename;
        $this->_volume = $newVolume;

        // If there was a new file involved, update file data.
        if ($tempPath && file_exists($tempPath)) {
            $this->kind = Assets::getFileKindByExtension($filename);

            if ($this->kind === self::KIND_IMAGE) {
                [$this->_width, $this->_height] = Image::imageSize($tempPath);
            } else {
                $this->_width = null;
                $this->_height = null;
            }

            $this->size = filesize($tempPath);
            $this->dateModified = new DateTime('@' . filemtime($tempPath));

            // Delete the temp file
            FileHelper::unlink($tempPath);
        }

        // Clear out the temp location properties
        $this->newLocation = null;
        $this->tempFilePath = null;
    }

    /**
     * Validates that the temp file path exists and is someplace safe.
     *
     * @return bool
     */
    private function _validateTempFilePath(): bool
    {
        $tempFilePath = realpath($this->tempFilePath);

        if ($tempFilePath === false || !is_file($tempFilePath)) {
            return false;
        }

        $tempFilePath = FileHelper::normalizePath($tempFilePath);

        // Make sure it's within a known temp path, the project root, or storage/ folder
        $pathService = Craft::$app->getPath();
        $allowedRoots = [
            [$pathService->getTempPath(), true],
            [$pathService->getTempAssetUploadsPath(), true],
            [sys_get_temp_dir(), true],
            [Craft::getAlias('@root', false), false],
            [Craft::getAlias('@storage', false), false],
        ];

        $inAllowedRoot = false;
        foreach ($allowedRoots as [$root, $isTempDir]) {
            $root = $this->_normalizeTempPath($root);
            if ($root !== false && StringHelper::startsWith($tempFilePath, $root)) {
                // If this is a known temp dir, we’re good here
                if ($isTempDir) {
                    return true;
                }
                $inAllowedRoot = true;
                break;
            }
        }
        if (!$inAllowedRoot) {
            return false;
        }

        // Make sure it's *not* within a system directory though
        $systemDirs = $pathService->getSystemPaths();
        $systemDirs = array_map([$this, '_normalizeTempPath'], $systemDirs);
        $systemDirs = array_filter($systemDirs, function($value) {
            return ($value !== false);
        });

        foreach ($systemDirs as $dir) {
            if (StringHelper::startsWith($tempFilePath, $dir)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns a normalized temp path or false, if realpath fails.
     *
     * @param string|false $path
     * @return false|string
     */
    private function _normalizeTempPath($path)
    {
        if (!$path || !($path = realpath($path))) {
            return false;
        }

        return FileHelper::normalizePath($path) . DIRECTORY_SEPARATOR;
    }
}
