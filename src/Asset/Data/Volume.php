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
use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Url;
use CraftCms\RulesetValidation\Attributes\Ruleset;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Override;
use RuntimeException;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

/**
 * @property string $fsHandle
 * @property string|null $assetTransformer
 * @property string $subpath
 */
#[Ruleset(VolumeRules::class)]
class Volume extends Component implements CpEditable, CustomFieldLayoutProviderInterface
{
    use HasFieldLayout;

    public const array INTERNAL_DISK_NAMES = ['craft-tmp', 'craft-asset-temp', 'rebrand'];

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

    public bool $hasUrls = false;

    private string $_subpath = '';

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
            'fsHandle' => t('Disk'),
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

        if ($this->diskExists($value)) {
            if ($this->isInternalDiskName($value) && ! $this->_temporary) {
                return null;
            }

            return $value;
        }

        return null;
    }

    private function diskExists(string $diskName): bool
    {
        $disks = config('filesystems.disks', []);

        return is_array($disks) && array_key_exists($diskName, $disks);
    }

    private function isInternalDiskName(string $diskName): bool
    {
        return in_array($diskName, self::INTERNAL_DISK_NAMES, true);
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

    public function getFsHandle(bool $parse = true): ?string
    {
        return $this->parseStorageHandle($this->_fsHandle, $parse);
    }

    public function setFsHandle(?string $handle): void
    {
        $this->_fsHandle = $handle;
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
            'hasUrls' => $this->hasUrls,
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
        $diskName = $this->diskNameForOperations();
        $subpath = $this->getSubpath(ensureTrailing: false);

        if ($subpath === '') {
            return Storage::disk($diskName);
        }

        $disk = Storage::build([
            'driver' => 'scoped',
            'disk' => $diskName,
            'prefix' => $subpath,
        ]);

        if (! $disk instanceof FilesystemAdapter) {
            throw new FilesystemException("Unable to create a scoped Laravel filesystem disk for volume [$this->name].");
        }

        return $disk;
    }

    public function sourceHasUrls(): bool
    {
        return $this->hasUrls;
    }

    public function isTemporary(): bool
    {
        if ($this->_temporary) {
            return true;
        }

        return $this->resolveStorageTargetKey($this->_fsHandle) === Cms::config()->getTempAssetUploadDisk();
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
            throw new FilesystemException('Volume is missing or has an invalid disk.');
        }

        return $target;
    }
}
