<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Events\VolumeConfigPreparing;
use CraftCms\Cms\Asset\Validation\VolumeRules;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Component\Contracts\CpEditable;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\FieldLayout\Concerns\HasFieldLayout;
use CraftCms\Cms\FieldLayout\Contracts\CustomFieldLayoutProviderInterface;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Filesystems as FilesystemsService;
use CraftCms\Cms\Filesystem\Filesystems\MissingFs;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Filesystems;
use CraftCms\Cms\Support\Url;
use CraftCms\RulesetValidation\Attributes\Ruleset;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Log;
use Override;
use RuntimeException;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

/**
 * @property FsInterface $fs
 * @property string $fsHandle
 * @property string|null $assetTransformer
 * @property string $subpath
 */
#[Ruleset(VolumeRules::class)]
class Volume extends Component implements CpEditable, CustomFieldLayoutProviderInterface
{
    use HasFieldLayout;

    public const string STORAGE_DISK_PREFIX = 'disk:';

    public ?int $id = null;

    public ?string $name = null;

    public ?string $handle = null;

    public TranslationMethod $titleTranslationMethod = TranslationMethod::Site;

    public ?string $titleTranslationKeyFormat = null;

    public TranslationMethod $altTranslationMethod = TranslationMethod::None;

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

    public ?string $subpath {
        get => $this->getSubpath();
        set {
            $this->setSubpath($value);
        }
    }

    public ?string $assetTransformer = null;

    private string $_subpath = '';

    private ?FsInterface $_fs = null;

    private ?string $_fsHandle = null;

    private bool $_temporary = false;

    public function __construct(array|object $config = [])
    {
        if (is_object($config)) {
            $config = (array) $config;
        }

        if (isset($config['fs']) && is_string($config['fs'])) {
            $config['fsHandle'] = Arr::pull($config, 'fs');
        }

        parent::__construct($config);
    }

    #[Override]
    public function validationData(): array
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

        return array_merge(parent::validationData(), [
            'fieldLayout' => $fieldLayout,
            'fsHandle' => $this->getFsHandle(false),
            'assetTransformer' => $this->getAssetTransformerHandle(false),
            'subpath' => $this->getSubpath(ensureTrailing: false, parse: false),
        ]);
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            'handle' => t('Handle'),
            'name' => t('Name'),
            'fsHandle' => t('Asset Filesystem'),
            'assetTransformer' => t('Asset Transformer'),
            'subpath' => t('Subpath'),
        ];
    }

    #[Override]
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
            if (
                $diskName === '' ||
                ! $this->diskExists($diskName) ||
                ($this->isInternalDiskName($diskName) && ! $this->_temporary)
            ) {
                return null;
            }

            return $diskName;
        }

        if (Filesystems::getFilesystemByHandle($value)) {
            return Filesystems::toDiskName($value);
        }

        if ($this->diskExists($value)) {
            if ($this->isInternalDiskName($value) && ! $this->_temporary) {
                return null;
            }

            return $value;
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
        if (! $this->id || ! currentUser()?->isAdmin()) {
            return null;
        }

        return Url::cpUrl("settings/assets/volumes/$this->id");
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
                throw new RuntimeException('Volume is missing its filesystem handle.');
            }

            $fs = Filesystems::resolve($this->_fsHandle);
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

    public function getResolvedFsTarget(bool $parse = true): ?string
    {
        return $this->resolveStorageTargetKey($this->_fsHandle, $parse);
    }

    public function getAssetTransformerHandle(bool $parse = true): ?string
    {
        $handle = $parse ? Env::parse($this->assetTransformer) : $this->assetTransformer;

        return is_string($handle) && $handle !== '' ? $handle : null;
    }

    /** @return array<string, array<string, array<string, list<array<string, mixed>|string>|string|null>>|int|string|null> */
    public function getConfig(): array
    {
        $config = [
            'name' => $this->name,
            'handle' => $this->handle,
            'fs' => $this->_fsHandle,
            'subpath' => $this->_subpath,
            'assetTransformer' => $this->assetTransformer ?: null,
            'titleTranslationMethod' => $this->titleTranslationMethod->value,
            'titleTranslationKeyFormat' => $this->titleTranslationKeyFormat ?: null,
            'altTranslationMethod' => $this->altTranslationMethod->value,
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

        event($event = new VolumeConfigPreparing($this, $config));

        return $event->config;
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

    public function sourceDisk(): FilesystemAdapter
    {
        return Filesystems::disk(
            $this->diskNameForOperations(),
            $this->_subpath,
        );
    }

    public function sourceHasUrls(): bool
    {
        return $this->getFs()->hasUrls;
    }

    /** @return class-string<FsInterface> */
    public function sourceFilesystemType(): string
    {
        return $this->getFs()::class;
    }

    public function isTemporary(): bool
    {
        if ($this->_temporary) {
            return true;
        }

        $tempUploadTarget = Env::parse(Cms::config()->tempAssetUploadFs);
        if (! is_string($tempUploadTarget)) {
            return false;
        }

        $tempUploadDisk = Filesystems::resolveDiskName($tempUploadTarget);

        return $tempUploadDisk !== null && $this->resolveStorageTargetKey($this->_fsHandle) === $tempUploadDisk;
    }

    public function markAsTemporary(): void
    {
        $this->_temporary = true;
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
            throw new RuntimeException('Volume is missing or has an invalid filesystem handle.');
        }

        return $target;
    }
}
