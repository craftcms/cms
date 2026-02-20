<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\BaseFsInterface;
use craft\base\FsInterface;
use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\fs\LaravelDiskFs;
use craft\fs\MissingFs;
use craft\helpers\UrlHelper;
use craft\records\Volume as VolumeRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Component\Contracts\CpEditable;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\FieldLayout\Contracts\FieldLayoutProviderInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Filesystem\DiskRegistry;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Str;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use yii\base\InvalidConfigException;
use function CraftCms\Cms\t;

/**
 * Volume model class.
 *
 * @mixin FieldLayoutBehavior
 * @property FsInterface $fs
 * @property string $fsHandle
 * @property string $subpath
 * @property string $transformSubpath
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Volume extends Model implements
    BaseFsInterface,
    Chippable,
    CpEditable,
    FieldLayoutProviderInterface
{
    private const string STORAGE_FS_PREFIX = 'fs:';
    private const string STORAGE_DISK_PREFIX = 'disk:';

    /**
     * @inheritdoc
     */
    public static function get(string|int $id): ?static
    {
        /** @phpstan-ignore-next-line */
        return Craft::$app->getVolumes()->getVolumeById($id);
    }

    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var string|null Name
     */
    public ?string $name = null;

    /**
     * @var string|null Handle
     */
    public ?string $handle = null;

    /**
     * @var string Title translation method
     * @phpstan-var Field::TRANSLATION_METHOD_NONE|Field::TRANSLATION_METHOD_SITE|Field::TRANSLATION_METHOD_SITE_GROUP|Field::TRANSLATION_METHOD_LANGUAGE|Field::TRANSLATION_METHOD_CUSTOM
     */
    public string $titleTranslationMethod = Field::TRANSLATION_METHOD_SITE;

    /**
     * @var string|null Title translation key format
     */
    public ?string $titleTranslationKeyFormat = null;

    /**
     * @var string Alternative text translation method
     * @since 5.0.0
     */
    public string $altTranslationMethod = Field::TRANSLATION_METHOD_NONE;

    /**
     * @var null|string Alternative text translation key format
     * @since 5.0.0
     */
    public ?string $altTranslationKeyFormat = null;

    /**
     * @var int|null Sort order
     */
    public ?int $sortOrder = null;

    /**
     * @var int|null Field layout ID
     */
    public ?int $fieldLayoutId = null;

    /**
     * @var string|null UID
     */
    public ?string $uid = null;

    /**
     * @var string The subpath to use in the filesystem for uploading files to this volume
     * @see getSubpath()
     * @see setSubpath()
     */
    private string $_subpath = '';

    /**
     * @var string The subpath to use in the transform filesystem
     * @see getTransformSubpath()
     * @see setTransformSubpath()
     */
    private string $_transformSubpath = '';

    /**
     * @var FsInterface|null
     * @see getFs()
     * @see setFs()
     */
    private ?FsInterface $_fs = null;

    /**
     * @var string|null
     * @see getFsHandle()
     * @see setFsHandle()
     */
    private ?string $_fsHandle = null;

    /**
     * @var FsInterface|null
     * @see getTransformFs()
     * @see setTransformFs()
     */
    private ?FsInterface $_transformFs = null;

    /**
     * @var string|null
     * @see getTransformFsHandle()
     * @see setTransformFsHandle()
     */
    private ?string $_transformFsHandle = null;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        if (isset($config['fs']) && is_string($config['fs'])) {
            $config['fsHandle'] = Arr::pull($config, 'fs');
        }

        if (isset($config['transformFs']) && is_string($config['transformFs'])) {
            $config['transformFsHandle'] = Arr::pull($config, 'transformFs');
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function defineBehaviors(): array
    {
        return [
            'fieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => Asset::class,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getUiLabel(): string
    {
        return t($this->name, category: 'site');
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl(): ?string
    {
        if (!$this->id || !Auth::user()?->isAdmin()) {
            return null;
        }
        return UrlHelper::cpUrl("settings/assets/volumes/$this->id");
    }

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        $attributes = parent::attributes();
        $attributes[] = 'subpath';
        $attributes[] = 'transformSubpath';
        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'handle' => t('Handle'),
            'name' => t('Name'),
            'url' => t('URL'),
            'fsHandle' => t('Asset Filesystem'),
            'subpath' => t('Subpath'),
            'transformFsHandle' => t('Transform Filesystem'),
            'transformSubpath' => t('Transform Subpath'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'fieldLayoutId'], 'number', 'integerOnly' => true];
        $rules[] = [['name', 'handle'], 'trim'];
        $rules[] = [['name', 'handle'], UniqueValidator::class, 'targetClass' => VolumeRecord::class];
        $rules[] = [['name', 'handle'], 'required'];
        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => [
                'dateCreated',
                'dateUpdated',
                'edit',
                'id',
                'temp',
                'title',
                'uid',
            ],
        ];
        $rules[] = [['fieldLayout'], 'validateFieldLayout'];
        $rules[] = [['fsHandle'], fn(string $attribute) => $this->validateFilesystemHandle($attribute)];
        $rules[] = [['transformFsHandle'], fn(string $attribute) => $this->validateFilesystemHandle($attribute), 'skipOnEmpty' => true];
        $rules[] = [['subpath'], fn($attribute) => $this->validateUniqueSubpath($attribute), 'skipOnEmpty' => false];

        $tempAssetUploadTarget = $this->resolveStorageTargetKey(Cms::config()->tempAssetUploadFs);
        if ($tempAssetUploadTarget !== null) {
            $rules[] = [
                ['fsHandle'],
                fn(string $attribute) => $this->validateReservedTempUploadFilesystem($attribute, $tempAssetUploadTarget),
            ];
            $rules[] = [
                ['transformFsHandle'],
                fn(string $attribute) => $this->validateReservedTempUploadFilesystem($attribute, $tempAssetUploadTarget),
            ];
        }

        return $rules;
    }

    /**
     * Validate a unique subpath - not just the entire subpath, but even just the first subfolder
     *
     * e.g. if Volume A uses $MY_FS and its subpath is set to foo/bar,
     * and Volume B wishes to also use $MY_FS
     * and its subpath is either empty, or set to foo, foo/bar, or foo/bar/baz,
     * it should result in a validation error due to the conflict with Volume A
     */
    private function validateUniqueSubpath(string $attribute): void
    {
        $storageTarget = $this->resolveStorageTargetKey($this->_fsHandle);
        if ($storageTarget === null) {
            return;
        }

        // get all volumes that use the same FS, excluding current volume
        $records = \CraftCms\Cms\Asset\Models\Volume::query()
            ->when($this->id !== null, fn(Builder $query) => $query->whereNot('id', $this->id))
            ->get()
            ->filter(fn(\CraftCms\Cms\Asset\Models\Volume $record): bool => $this->resolveStorageTargetKey($record->fs) === $storageTarget);

        // if there are other volumes using the same FS
        // and this volume wants to have an empty subpath - add error
        if ($records->isNotEmpty() && empty($this->$attribute)) {
            $this->addError($attribute, t('A subpath is required for this filesystem.'));
        }

        // make sure subpath starts with a unique dir across all volumes that use this FS
        foreach ($records as $record) {
            if (strcmp(explode('/', $record->$attribute)[0], explode('/', $this->$attribute)[0]) === 0) {
                $this->addError($attribute, t('The subpath cannot overlap with any other volumes sharing the same filesystem.'));
            }
        }
    }

    /**
     * Validates the field layout.
     */
    public function validateFieldLayout(): void
    {
        $fieldLayout = $this->getFieldLayout();
        $fieldLayout->reservedFieldHandles = [
            'alt',
            'extension',
            'filename',
            'folder',
            'height',
            'kind',
            'size',
            'volume',
            'width',
        ];

        if (!$fieldLayout->validate()) {
            $this->addModelErrors($fieldLayout, 'fieldLayout');
        }
    }

    /**
     * @inheritdoc
     */
    public function getHandle(): ?string
    {
        return $this->handle;
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout(): FieldLayout
    {
        /** @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('fieldLayout');
        return $behavior->getFieldLayout();
    }

    /**
     * Returns the volume’s filesystem.
     *
     * @return FsInterface
     * @throws InvalidConfigException if [[fsHandle]] is missing or invalid
     */
    public function getFs(): FsInterface
    {
        if (!isset($this->_fs)) {
            if (!$this->getFsHandle()) {
                throw new InvalidConfigException('Volume is missing its filesystem handle.');
            }

            $target = $this->resolveStorageTargetKey($this->_fsHandle);
            $fs = $target !== null ? $this->filesystemFromTargetKey($target) : null;
            if (!$fs) {
                Log::error("Invalid filesystem handle: $this->_fsHandle for the $this->name volume.");
                return new MissingFs(['handle' => $this->_fsHandle]);
            }

            $this->_fs = $fs;
        }

        return $this->_fs;
    }

    /**
     * Set the filesystem.
     *
     * @param FsInterface $fs
     */
    public function setFs(FsInterface $fs): void
    {
        $this->_fs = $fs;
        $this->_fsHandle = $fs->handle ?? null;
    }

    /**
     * Returns the filesystem handle.
     *
     * @param bool $parse Whether to parse the name for an alias or environment variable
     * @return string|null
     */
    public function getFsHandle(bool $parse = true): ?string
    {
        if ($this->_fsHandle) {
            return $parse ? Env::parse($this->_fsHandle) : $this->_fsHandle;
        }
        return null;
    }

    /**
     * Sets the filesystem handle.
     *
     * @param string $handle
     */
    public function setFsHandle(string $handle): void
    {
        $this->_fsHandle = $this->normalizeStorageHandle($handle);
        $this->_fs = null;
    }


    /**
     * Returns the volume’s transform filesystem.
     *
     * @return FsInterface
     * @throws InvalidConfigException if [[fsHandle]] is missing or invalid
     */
    public function getTransformFs(): FsInterface
    {
        if (!isset($this->_transformFs)) {
            if (!$this->getTransformFsHandle()) {
                return $this->getFs();
            }

            $target = $this->resolveStorageTargetKey($this->_transformFsHandle);
            $fs = $target !== null ? $this->filesystemFromTargetKey($target) : null;
            if (!$fs) {
                throw new InvalidConfigException("Invalid filesystem handle: $this->_transformFsHandle");
            }

            $this->_transformFs = $fs;
        }

        return $this->_transformFs;
    }

    /**
     * Set the transform filesystem.
     *
     * @param FsInterface|null $fs
     */
    public function setTransformFs(?FsInterface $fs): void
    {
        if ($fs) {
            $this->_transformFs = $fs;
            $this->_transformFsHandle = $fs->handle ?? null;
        } else {
            $this->_transformFsHandle = $this->_transformFs = null;
        }
    }

    /**
     * Returns the transform filesystem handle. If none set, will return the current fs handle.
     *
     * @param bool $parse Whether to parse the name for an alias or environment variable
     * @return string|null
     */
    public function getTransformFsHandle(bool $parse = true): ?string
    {
        if ($this->_transformFsHandle) {
            return $parse ? Env::parse($this->_transformFsHandle) : $this->_transformFsHandle;
        }
        return null;
    }

    /**
     * Sets the transform filesystem handle.
     *
     * @param string|null $handle
     */
    public function setTransformFsHandle(?string $handle): void
    {
        $this->_transformFsHandle = $this->normalizeStorageHandle($handle);
        $this->_transformFs = null;
    }

    /**
     * Returns the resolved storage target key for this volume’s asset filesystem.
     *
     * @param bool $parse Whether dynamic values should be resolved
     * @return string|null
     */
    public function getResolvedFsTarget(bool $parse = true): ?string
    {
        return $this->resolveStorageTargetKey($this->_fsHandle, $parse);
    }

    /**
     * Returns the resolved storage target key for this volume’s transform filesystem.
     *
     * @param bool $parse Whether dynamic values should be resolved
     * @return string|null
     */
    public function getResolvedTransformFsTarget(bool $parse = true): ?string
    {
        return $this->resolveStorageTargetKey($this->_transformFsHandle, $parse);
    }

    private function validateFilesystemHandle(string $attribute): void
    {
        $handle = match ($attribute) {
            'fsHandle' => $this->_fsHandle,
            'transformFsHandle' => $this->_transformFsHandle,
            default => null,
        };

        if ($handle === null || $handle === '') {
            if ($attribute === 'fsHandle') {
                $this->addError($attribute, t('{attribute} cannot be blank.', [
                    'attribute' => $this->getAttributeLabel($attribute),
                ]));
            }

            return;
        }

        if ($this->isUnresolvedEnvValue($handle)) {
            return;
        }

        if ($this->isInternalDiskReference($handle)) {
            $this->addError($attribute, t('This disk is reserved for internal use.'));
            return;
        }

        if ($this->resolveStorageTargetKey($handle) === null) {
            $this->addError($attribute, t('This filesystem reference is invalid.'));
        }
    }

    private function validateReservedTempUploadFilesystem(string $attribute, string $tempUploadTarget): void
    {
        $handle = match ($attribute) {
            'fsHandle' => $this->_fsHandle,
            'transformFsHandle' => $this->_transformFsHandle,
            default => null,
        };

        if ($handle === null || $handle === '') {
            return;
        }

        $target = $this->resolveStorageTargetKey($handle);
        if ($target !== null && $target === $tempUploadTarget) {
            $this->addError(
                $attribute,
                t('This filesystem has been reserved for temporary asset uploads. Please choose a different one for your volume.'),
            );
        }
    }

    private function resolveStorageTargetKey(?string $value, bool $parse = true): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = $parse ? Env::parse($value) : $value;
        if ($value === null || $value === '') {
            return null;
        }

        if (str_starts_with($value, self::STORAGE_DISK_PREFIX)) {
            $diskName = substr($value, strlen(self::STORAGE_DISK_PREFIX));
            if ($diskName === '' || !$this->diskExists($diskName) || $this->isInternalDiskName($diskName)) {
                return null;
            }

            return self::STORAGE_DISK_PREFIX . $diskName;
        }

        if (Craft::$app->getFs()->getFilesystemByHandle($value)) {
            return self::STORAGE_FS_PREFIX . $value;
        }

        if ($this->diskExists($value)) {
            if ($this->isInternalDiskName($value)) {
                return null;
            }

            return self::STORAGE_DISK_PREFIX . $value;
        }

        return null;
    }

    private function normalizeStorageHandle(?string $value): ?string
    {
        if ($value === null || $value === '' || str_starts_with($value, self::STORAGE_DISK_PREFIX)) {
            return $value;
        }

        if ($this->isDynamicStorageHandle($value)) {
            return $value;
        }

        if (Craft::$app->getFs()->getFilesystemByHandle($value)) {
            return $value;
        }

        if ($this->diskExists($value)) {
            return self::STORAGE_DISK_PREFIX . $value;
        }

        return $value;
    }

    private function filesystemFromTargetKey(string $target): ?FsInterface
    {
        if (str_starts_with($target, self::STORAGE_FS_PREFIX)) {
            $handle = substr($target, strlen(self::STORAGE_FS_PREFIX));
            return Craft::$app->getFs()->getFilesystemByHandle($handle);
        }

        if (str_starts_with($target, self::STORAGE_DISK_PREFIX)) {
            $diskName = substr($target, strlen(self::STORAGE_DISK_PREFIX));
            if ($diskName === '' || !$this->diskExists($diskName)) {
                return null;
            }

            return new LaravelDiskFs([
                'disk' => $diskName,
                'name' => $diskName,
                'handle' => self::STORAGE_DISK_PREFIX . $diskName,
            ]);
        }

        return null;
    }

    private function isUnresolvedEnvValue(string $value): bool
    {
        if (!preg_match('/\\$\\{?\\w+\\}?/', $value)) {
            return false;
        }

        return Env::parse($value) === null;
    }

    private function diskExists(string $diskName): bool
    {
        $diskConfigs = config('filesystems.disks', []);
        return is_array($diskConfigs) && array_key_exists($diskName, $diskConfigs);
    }

    private function isInternalDiskReference(string $value): bool
    {
        $diskName = str_starts_with($value, self::STORAGE_DISK_PREFIX)
            ? substr($value, strlen(self::STORAGE_DISK_PREFIX))
            : $value;

        return $diskName !== '' && $this->diskExists($diskName) && $this->isInternalDiskName($diskName);
    }

    private function isInternalDiskName(string $diskName): bool
    {
        return in_array($diskName, ['craft-tmp', 'rebrand'], true) ||
            str_starts_with($diskName, DiskRegistry::PREFIX);
    }

    private function isDynamicStorageHandle(string $value): bool
    {
        return str_contains($value, '$') || str_starts_with($value, '@');
    }

    /**
     * Returns the volume’s config.
     *
     * @return array
     */
    public function getConfig(): array
    {
        $config = [
            'name' => $this->name,
            'handle' => $this->handle,
            'fs' => $this->_fsHandle,
            'subpath' => $this->_subpath,
            'transformFs' => $this->_transformFsHandle,
            'transformSubpath' => $this->_transformSubpath,
            'titleTranslationMethod' => $this->titleTranslationMethod,
            'titleTranslationKeyFormat' => $this->titleTranslationKeyFormat ?: null,
            'altTranslationMethod' => $this->altTranslationMethod,
            'altTranslationKeyFormat' => $this->altTranslationKeyFormat ?: null,
            'sortOrder' => $this->sortOrder,
        ];

        $fieldLayout = $this->getFieldLayout();
        $fieldLayoutConfig = $fieldLayout->getConfig();
        if ($fieldLayoutConfig) {
            $config['fieldLayouts'] = [
                $fieldLayout->uid => $fieldLayoutConfig,
            ];
        }

        return $config;
    }

    /**
     * @inheritdoc
     */
    public function getRootUrl(): ?string
    {
        $rootUrl = $this->getFs()->getRootUrl() ?? '';
        return ($rootUrl !== '' ? Str::finish($rootUrl, '/') : '') . $this->getSubpath();
    }

    /**
     * Returns the volume’s subpath.
     *
     * @param bool $ensureTrailing Whether to include a trailing slash
     * @param bool $parse Whether to parse the name for an alias or environment variable
     * @return string
     * @since 5.0.0
     */
    public function getSubpath(bool $ensureTrailing = true, bool $parse = true): string
    {
        $subpath = $parse ? (Env::parse($this->_subpath) ?? '') : $this->_subpath;

        if ($ensureTrailing && $subpath !== '' && !str_ends_with($subpath, '/')) {
            $subpath .= '/';
        }

        return $subpath;
    }

    /**
     * Sets the volume’s subpath, ensuring it's a string.
     *
     * @param string|null $subpath
     */
    public function setSubpath(?string $subpath): void
    {
        $this->_subpath = $subpath ?? '';
    }

    /**
     * Returns the volume’s transform subpath.
     *
     * @param bool $ensureTrailing Whether to include a trailing slash
     * @param bool $parse Whether to parse the name for an alias or environment variable
     * @return string
     * @since 5.2.0
     */
    public function getTransformSubpath(bool $ensureTrailing = true, bool $parse = true): string
    {
        $subpath = $parse ? Env::parse($this->_transformSubpath) : $this->_transformSubpath;

        if ($ensureTrailing && $subpath !== '' && !str_ends_with($subpath, '/')) {
            $subpath .= '/';
        }

        return $subpath;
    }

    /**
     * Sets the volume’s transform subpath, ensuring it's a string.
     *
     * @param string|null $subpath
     * @since 5.2.0
     */
    public function setTransformSubpath(?string $subpath): void
    {
        $this->_transformSubpath = $subpath ?? '';
    }

    /**
     * @inheritdoc
     */
    public function getFileList(string $directory = '', bool $recursive = true): Generator
    {
        return $this->getFs()->getFileList($this->getSubpath() . $directory, $recursive);
    }

    /**
     * @inheritdoc
     */
    public function getFileSize(string $uri): int
    {
        return $this->getFs()->getFileSize($this->getSubpath() . $uri);
    }

    /**
     * @inheritdoc
     */
    public function getDateModified(string $uri): int
    {
        return $this->getFs()->getDateModified($this->getSubpath() . $uri);
    }


    /**
     * @inheritdoc
     */
    public function write(string $path, string $contents, array $config = []): void
    {
        $this->getFs()->write($this->getSubpath() . $path, $contents, $config);
    }

    /**
     * @inheritdoc
     */
    public function read(string $path): string
    {
        return $this->getFs()->read($this->getSubpath() . $path);
    }

    /**
     * @inheritdoc
     */
    public function writeFileFromStream(string $path, $stream, array $config = []): void
    {
        $this->getFs()->writeFileFromStream($this->getSubpath() . $path, $stream, $config);
    }

    /**
     * @inheritdoc
     */
    public function fileExists(string $path): bool
    {
        return $this->getFs()->fileExists($this->getSubpath() . $path);
    }

    /**
     * @inheritdoc
     */
    public function deleteFile(string $path): void
    {
        $this->getFs()->deleteFile($this->getSubpath() . $path);
    }

    /**
     * @inheritdoc
     */
    public function renameFile(string $path, string $newPath, array $config = []): void
    {
        $subpath = $this->getSubpath();
        $this->getFs()->renameFile($subpath . $path, $subpath . $newPath);
    }

    /**
     * @inheritdoc
     */
    public function copyFile(string $path, string $newPath, array $config = []): void
    {
        $subpath = $this->getSubpath();
        $this->getFs()->copyFile($subpath . $path, $subpath . $newPath);
    }

    /**
     * @inheritdoc
     */
    public function getFileStream(string $uriPath)
    {
        return $this->getFs()->getFileStream($this->getSubpath() . $uriPath);
    }

    /**
     * @inheritdoc
     */
    public function directoryExists(string $path): bool
    {
        return $this->getFs()->directoryExists($this->getSubpath() . $path);
    }

    /**
     * @inheritdoc
     */
    public function createDirectory(string $path, array $config = []): void
    {
        $this->getFs()->createDirectory($this->getSubpath() . $path, $config);
    }

    /**
     * @inheritdoc
     */
    public function deleteDirectory(string $path): void
    {
        $this->getFs()->deleteDirectory($this->getSubpath() . $path);
    }

    /**
     * @inheritdoc
     */
    public function renameDirectory(string $path, string $newName): void
    {
        $this->getFs()->renameDirectory($this->getSubpath() . $path, $newName);
    }
}
