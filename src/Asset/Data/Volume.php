<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

use craft\helpers\UrlHelper;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Validation\VolumeRules;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Component\Contracts\CpEditable;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\FieldLayout\Concerns\HasFieldLayout;
use CraftCms\Cms\FieldLayout\Contracts\FieldLayoutProviderInterface;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Filesystems as FilesystemsService;
use CraftCms\Cms\Filesystem\Filesystems\DiskFilesystem;
use CraftCms\Cms\Filesystem\Filesystems\MissingFs;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Filesystems;
use CraftCms\Cms\Validation\Attributes\Ruleset;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Override;
use RuntimeException;
use yii\base\InvalidConfigException;

use function CraftCms\Cms\t;

/**
 * @property FsInterface $fs
 * @property string $fsHandle
 * @property string $subpath
 * @property string $transformSubpath
 */
#[Ruleset(VolumeRules::class)]
class Volume extends Component implements CpEditable, FieldLayoutProviderInterface
{
    use HasFieldLayout;

    public const string STORAGE_FS_PREFIX = 'fs:';

    public const string STORAGE_DISK_PREFIX = 'disk:';

    public ?int $id = null;

    public ?string $name = null;

    public ?string $handle = null;

    /**
     * @phpstan-var Field::TRANSLATION_METHOD_NONE|Field::TRANSLATION_METHOD_SITE|Field::TRANSLATION_METHOD_SITE_GROUP|Field::TRANSLATION_METHOD_LANGUAGE|Field::TRANSLATION_METHOD_CUSTOM
     */
    public string $titleTranslationMethod = Field::TRANSLATION_METHOD_SITE;

    public ?string $titleTranslationKeyFormat = null;

    public string $altTranslationMethod = Field::TRANSLATION_METHOD_NONE;

    public ?string $altTranslationKeyFormat = null;

    public ?int $sortOrder = null;

    public ?int $fieldLayoutId = null;

    public ?string $uid = null;

    public ?string $fsHandle {
        get => $this->getFsHandle();
        set {
            $this->setFsHandle($value);
        }
    }

    public ?string $transformFsHandle {
        get => $this->getTransformFsHandle();
        set {
            $this->setTransformFsHandle($value);
        }
    }

    public ?string $subpath {
        get => $this->getSubpath();
        set {
            $this->setSubpath($value);
        }
    }

    public ?string $transformSubpath {
        get => $this->getTransformSubpath();
        set {
            $this->setTransformSubpath($value);
        }
    }

    private string $_subpath = '';

    private string $_transformSubpath = '';

    private ?FsInterface $_fs = null;

    private ?string $_fsHandle = null;

    private ?FsInterface $_transformFs = null;

    private ?string $_transformFsHandle = null;

    public function __construct(array|object $config = [])
    {
        if (is_object($config)) {
            $config = (array) $config;
        }

        if (isset($config['fs']) && is_string($config['fs'])) {
            $config['fsHandle'] = Arr::pull($config, 'fs');
        }

        if (isset($config['transformFs']) && is_string($config['transformFs'])) {
            $config['transformFsHandle'] = Arr::pull($config, 'transformFs');
        }

        parent::__construct($config);
    }

    #[Override]
    public function attributes(): array
    {
        return array_values(array_unique(array_merge(parent::attributes(), [
            'fieldLayout',
            'fsHandle',
            'subpath',
            'transformFsHandle',
            'transformSubpath',
        ])));
    }

    #[Override]
    public function getAttributes(): array
    {
        if (is_string($this->name)) {
            $this->name = trim($this->name);
        }

        if (is_string($this->handle)) {
            $this->handle = trim($this->handle);
        }

        try {
            $fieldLayout = $this->getFieldLayout();
        } catch (RuntimeException) {
            $fieldLayout = null;
        }

        return array_merge(parent::getAttributes(), [
            'fieldLayout' => $fieldLayout,
            'fsHandle' => $this->getFsHandle(false),
            'subpath' => $this->getSubpath(ensureTrailing: false, parse: false),
            'transformFsHandle' => $this->getTransformFsHandle(false),
            'transformSubpath' => $this->getTransformSubpath(ensureTrailing: false, parse: false),
        ]);
    }

    #[Override]
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

    public function getAttributeLabel(string $attribute): string
    {
        return $this->attributeLabels()[$attribute] ?? $attribute;
    }

    public function resolveStorageTargetKey(?string $value, bool $parse = true): ?string
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
            if ($diskName === '' || ! $this->diskExists($diskName) || $this->isInternalDiskName($diskName)) {
                return null;
            }

            return self::STORAGE_DISK_PREFIX.$diskName;
        }

        if (Filesystems::getFilesystemByHandle($value)) {
            return self::STORAGE_FS_PREFIX.$value;
        }

        if ($this->diskExists($value)) {
            if ($this->isInternalDiskName($value)) {
                return null;
            }

            return self::STORAGE_DISK_PREFIX.$value;
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

        if (Filesystems::getFilesystemByHandle($value)) {
            return $value;
        }

        if ($this->diskExists($value)) {
            return self::STORAGE_DISK_PREFIX.$value;
        }

        return $value;
    }

    private function filesystemFromTargetKey(string $target): ?FsInterface
    {
        if (str_starts_with($target, self::STORAGE_FS_PREFIX)) {
            $handle = substr($target, strlen(self::STORAGE_FS_PREFIX));

            return Filesystems::getFilesystemByHandle($handle);
        }

        if (str_starts_with($target, self::STORAGE_DISK_PREFIX)) {
            $diskName = substr($target, strlen(self::STORAGE_DISK_PREFIX));
            if ($diskName === '' || ! $this->diskExists($diskName)) {
                return null;
            }

            return $this->diskFilesystem($diskName);
        }

        return null;
    }

    private function diskFilesystem(string $diskName): DiskFilesystem
    {
        $url = config("filesystems.disks.$diskName.url");
        if (is_string($url) && $url !== '') {
            $url = rtrim($url, '/');
        } else {
            $url = null;
        }

        return new DiskFilesystem([
            'disk' => $diskName,
            'name' => $diskName,
            'handle' => self::STORAGE_DISK_PREFIX.$diskName,
            'hasUrls' => $url !== null,
            'url' => $url,
        ]);
    }

    private function diskExists(string $diskName): bool
    {
        return app(FilesystemsService::class)->diskExists($diskName);
    }

    private function isInternalDiskName(string $diskName): bool
    {
        return in_array($diskName, FilesystemsService::INTERNAL_DISK_NAMES, true) ||
            str_starts_with($diskName, FilesystemsService::DISK_PREFIX);
    }

    private function isDynamicStorageHandle(string $value): bool
    {
        return str_contains($value, '$') || str_starts_with($value, '@');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUiLabel(): string
    {
        return t($this->name, category: 'site');
    }

    public function getCpEditUrl(): ?string
    {
        if (! $this->id || ! Auth::user()?->isAdmin()) {
            return null;
        }

        return UrlHelper::cpUrl("settings/assets/volumes/$this->id");
    }

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

        if (! $fieldLayout->validate()) {
            foreach ($fieldLayout->errors()->getMessages() as $attribute => $errors) {
                foreach ($errors as $error) {
                    $this->errors()->add("fieldLayout.$attribute", $error);
                }
            }
        }
    }

    public function getHandle(): ?string
    {
        return $this->handle;
    }

    public function getElementType(): string
    {
        return Asset::class;
    }

    public function getFs(): FsInterface
    {
        if (! isset($this->_fs)) {
            if (! $this->getFsHandle()) {
                throw new InvalidConfigException('Volume is missing its filesystem handle.');
            }

            $target = $this->resolveStorageTargetKey($this->_fsHandle);
            $fs = $target !== null ? $this->filesystemFromTargetKey($target) : null;
            if (! $fs) {
                Log::error("Invalid filesystem handle: $this->_fsHandle for the $this->name volume.");

                return new MissingFs(['handle' => $this->_fsHandle]);
            }

            $this->_fs = $fs;
        }

        return $this->_fs;
    }

    public function setFs(FsInterface $fs): void
    {
        $this->_fs = $fs;
        $this->_fsHandle = $fs->handle ?? null;
    }

    public function getFsHandle(bool $parse = true): ?string
    {
        return $this->parseStorageHandle($this->_fsHandle, $parse);
    }

    public function setFsHandle(?string $handle): void
    {
        $this->_fsHandle = $this->normalizeStorageHandle($handle);
        $this->_fs = null;
    }

    public function getTransformFs(): FsInterface
    {
        if (! isset($this->_transformFs)) {
            if (! $this->getTransformFsHandle()) {
                return $this->getFs();
            }

            $target = $this->resolveStorageTargetKey($this->_transformFsHandle);
            $fs = $target !== null ? $this->filesystemFromTargetKey($target) : null;
            if (! $fs) {
                Log::error("Invalid transform filesystem handle: $this->_transformFsHandle for the $this->name volume.");

                return new MissingFs(['handle' => $this->_transformFsHandle]);
            }

            $this->_transformFs = $fs;
        }

        return $this->_transformFs;
    }

    public function setTransformFs(?FsInterface $fs): void
    {
        if ($fs) {
            $this->_transformFs = $fs;
            $this->_transformFsHandle = $fs->handle ?? null;
        } else {
            $this->_transformFsHandle = $this->_transformFs = null;
        }
    }

    public function getTransformFsHandle(bool $parse = true): ?string
    {
        return $this->parseStorageHandle($this->_transformFsHandle, $parse);
    }

    public function setTransformFsHandle(?string $handle): void
    {
        $this->_transformFsHandle = $this->normalizeStorageHandle($handle);
        $this->_transformFs = null;
    }

    public function getResolvedFsTarget(bool $parse = true): ?string
    {
        return $this->resolveStorageTargetKey($this->_fsHandle, $parse);
    }

    public function getResolvedTransformFsTarget(bool $parse = true): ?string
    {
        return $this->resolveStorageTargetKey($this->_transformFsHandle, $parse);
    }

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

    public function getSubpath(bool $ensureTrailing = true, bool $parse = true): string
    {
        $subpath = $parse ? (Env::parse($this->_subpath) ?? '') : $this->_subpath;

        if ($ensureTrailing && $subpath !== '' && ! str_ends_with($subpath, '/')) {
            $subpath .= '/';
        }

        return $subpath;
    }

    public function setSubpath(?string $subpath): void
    {
        $this->_subpath = $subpath ?? '';
    }

    public function getTransformSubpath(bool $ensureTrailing = true, bool $parse = true): string
    {
        $subpath = $parse ? (Env::parse($this->_transformSubpath) ?? '') : $this->_transformSubpath;

        if ($ensureTrailing && $subpath !== '' && ! str_ends_with($subpath, '/')) {
            $subpath .= '/';
        }

        return $subpath;
    }

    public function setTransformSubpath(?string $subpath): void
    {
        $this->_transformSubpath = $subpath ?? '';
    }

    public function sourceDisk(): FilesystemAdapter
    {
        return $this->storageDiskFor(
            $this->diskNameForOperations(),
            $this->diskPrefix(),
        );
    }

    public function transformDisk(): FilesystemAdapter
    {
        $hasTransformFs = (bool) $this->getTransformFsHandle(false);

        return $this->storageDiskFor(
            $this->diskNameForOperations($hasTransformFs ? $this->_transformFsHandle : $this->_fsHandle),
            $this->diskPrefix($this->_transformSubpath),
        );
    }

    private function diskPrefix(?string $subpath = null): ?string
    {
        $subpath = Env::parse($subpath ?? $this->_subpath) ?? '';
        $subpath = trim($subpath, '/');

        if ($subpath === '') {
            return null;
        }

        return $subpath;
    }

    private function parseStorageHandle(?string $handle, bool $parse): ?string
    {
        if (! $handle) {
            return null;
        }

        return $parse ? Env::parse($handle) : $handle;
    }

    private function diskNameForOperations(?string $handle = null): string
    {
        $target = $this->resolveStorageTargetKey($handle ?? $this->_fsHandle);
        if ($target === null) {
            throw new InvalidConfigException('Volume is missing or has an invalid filesystem handle.');
        }

        if (str_starts_with($target, self::STORAGE_DISK_PREFIX)) {
            return substr($target, strlen(self::STORAGE_DISK_PREFIX));
        }

        if (str_starts_with($target, self::STORAGE_FS_PREFIX)) {
            $handle = substr($target, strlen(self::STORAGE_FS_PREFIX));
            if ($handle === '') {
                throw new InvalidConfigException('Volume has an invalid filesystem handle.');
            }

            return Filesystems::toDiskName($handle);
        }

        throw new InvalidConfigException('Volume has an invalid filesystem handle.');
    }

    private function storageDiskFor(string $diskName, ?string $prefix): FilesystemAdapter
    {
        if ($prefix === null) {
            return Storage::disk($diskName);
        }

        $disk = Storage::build([
            'driver' => 'scoped',
            'disk' => $diskName,
            'prefix' => $prefix,
        ]);

        if (! $disk instanceof FilesystemAdapter) {
            throw new InvalidConfigException('Invalid filesystem disk configuration.');
        }

        return $disk;
    }
}
