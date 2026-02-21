<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

use BadMethodCallException;
use Craft;
use craft\base\FsInterface;
use craft\base\Model;
use craft\fs\LaravelDiskFs;
use craft\fs\MissingFs;
use craft\helpers\UrlHelper;
use craft\records\Volume as VolumeRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Volume as VolumeModel;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\Contracts\CpEditable;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\FieldLayout\Concerns\HasFieldLayout;
use CraftCms\Cms\FieldLayout\Contracts\FieldLayoutProviderInterface;
use CraftCms\Cms\Filesystem\DiskRegistry;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Env;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\FilesystemAdapter as LaravelFilesystemAdapter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Support\Traits\Macroable;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;

use function CraftCms\Cms\t;

/**
 * @mixin \Illuminate\Contracts\Filesystem\Filesystem
 *
 * @property FsInterface $fs
 * @property string $fsHandle
 * @property string $subpath
 * @property string $transformSubpath
 */
class Volume extends Model implements CpEditable, FieldLayoutProviderInterface
{
    use ForwardsCalls;
    use HasFieldLayout;
    use Macroable {
        __call as macroCall;
    }

    private const string STORAGE_FS_PREFIX = 'fs:';

    private const string STORAGE_DISK_PREFIX = 'disk:';

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

    private string $_subpath = '';

    private string $_transformSubpath = '';

    private ?FsInterface $_fs = null;

    private ?string $_fsHandle = null;

    private ?FsInterface $_transformFs = null;

    private ?string $_transformFsHandle = null;

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

    #[\Override]
    public function __get($name)
    {
        try {
            return parent::__get($name);
        } catch (InvalidCallException|UnknownPropertyException $e) {
            $normalizedName = ucfirst((string) $name);
            $getter = 'get'.$normalizedName;
            if (static::hasMacro($getter)) {
                return $this->$getter();
            }

            if (static::hasMacro('set'.$normalizedName)) {
                throw new InvalidCallException('Getting write-only property: '.static::class.'::'.$name);
            }

            throw $e;
        }
    }

    #[\Override]
    public function __set($name, $value): void
    {
        try {
            parent::__set($name, $value);
        } catch (InvalidCallException|UnknownPropertyException $e) {
            $normalizedName = ucfirst((string) $name);
            $setter = 'set'.$normalizedName;
            if (static::hasMacro($setter)) {
                $this->$setter($value);

                return;
            }

            if (static::hasMacro('get'.$normalizedName)) {
                throw new InvalidCallException('Setting read-only property: '.static::class.'::'.$name);
            }

            throw $e;
        }
    }

    #[\Override]
    public function __call($name, $params)
    {
        try {
            return $this->macroCall($name, $params);
        } catch (BadMethodCallException) {
            try {
                return parent::__call($name, $params);
            } catch (BadMethodCallException) {
                return $this->forwardFilesystemCall((string) $name, $params);
            }
        }
    }

    private function forwardFilesystemCall(string $method, array $params): mixed
    {
        if ($method === 'deleteDirectory') {
            $directory = $params[0] ?? null;
            if (($directory === null || $directory === '') && $this->diskPrefix() === null) {
                return false;
            }

            $params[0] = $directory ?? '';
        }

        return $this->forwardCallTo($this->storageDisk(), $method, $params);
    }

    #[\Override]
    public function canGetProperty($name, $checkVars = true, $checkBehaviors = true): bool
    {
        if (parent::canGetProperty($name, $checkVars, $checkBehaviors)) {
            return true;
        }

        return static::hasMacro('get'.ucfirst((string) $name));
    }

    #[\Override]
    public function canSetProperty($name, $checkVars = true, $checkBehaviors = true): bool
    {
        if (parent::canSetProperty($name, $checkVars, $checkBehaviors)) {
            return true;
        }

        return static::hasMacro('set'.ucfirst((string) $name));
    }

    #[\Override]
    public function attributes(): array
    {
        $attributes = parent::attributes();
        $attributes[] = 'subpath';
        $attributes[] = 'transformSubpath';

        return $attributes;
    }

    #[\Override]
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

    #[\Override]
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
        $rules[] = [['fsHandle'], $this->validateFilesystemHandle(...)];
        $rules[] = [['transformFsHandle'], $this->validateFilesystemHandle(...), 'skipOnEmpty' => true];
        $rules[] = [['subpath'], $this->validateUniqueSubpath(...), 'skipOnEmpty' => false];

        $tempAssetUploadTarget = $this->resolveStorageTargetKey(Cms::config()->tempAssetUploadFs);
        if ($tempAssetUploadTarget !== null) {
            $rules[] = [
                ['fsHandle'],
                fn (string $attribute) => $this->validateReservedTempUploadFilesystem($attribute, $tempAssetUploadTarget),
            ];
            $rules[] = [
                ['transformFsHandle'],
                fn (string $attribute) => $this->validateReservedTempUploadFilesystem($attribute, $tempAssetUploadTarget),
            ];
        }

        return $rules;
    }

    private function validateUniqueSubpath(string $attribute): void
    {
        $storageTarget = $this->resolveStorageTargetKey($this->_fsHandle);
        if ($storageTarget === null) {
            return;
        }

        $records = VolumeModel::query()
            ->when($this->id !== null, fn (Builder $query) => $query->whereNot('id', $this->id))
            ->get()
            ->filter(fn (VolumeModel $record): bool => $this->resolveStorageTargetKey($record->fs) === $storageTarget);

        if ($records->isNotEmpty() && empty($this->$attribute)) {
            $this->addError($attribute, t('A subpath is required for this filesystem.'));
        }

        foreach ($records as $record) {
            if (strcmp(explode('/', (string) $record->$attribute)[0], explode('/', (string) $this->$attribute)[0]) === 0) {
                $this->addError($attribute, t('The subpath cannot overlap with any other volumes sharing the same filesystem.'));
            }
        }
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
            if ($diskName === '' || ! $this->diskExists($diskName) || $this->isInternalDiskName($diskName)) {
                return null;
            }

            return self::STORAGE_DISK_PREFIX.$diskName;
        }

        if (Craft::$app->getFs()->getFilesystemByHandle($value)) {
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

        if (Craft::$app->getFs()->getFilesystemByHandle($value)) {
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

            return Craft::$app->getFs()->getFilesystemByHandle($handle);
        }

        if (str_starts_with($target, self::STORAGE_DISK_PREFIX)) {
            $diskName = substr($target, strlen(self::STORAGE_DISK_PREFIX));
            if ($diskName === '' || ! $this->diskExists($diskName)) {
                return null;
            }

            return new LaravelDiskFs([
                'disk' => $diskName,
                'name' => $diskName,
                'handle' => self::STORAGE_DISK_PREFIX.$diskName,
            ]);
        }

        return null;
    }

    private function isUnresolvedEnvValue(string $value): bool
    {
        if (! preg_match('/\\$\\{?\\w+\\}?/', $value)) {
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
            $this->addModelErrors($fieldLayout, 'fieldLayout');
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
        if ($this->_fsHandle) {
            return $parse ? Env::parse($this->_fsHandle) : $this->_fsHandle;
        }

        return null;
    }

    public function setFsHandle(string $handle): void
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
                throw new InvalidConfigException("Invalid filesystem handle: $this->_transformFsHandle");
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
        if ($this->_transformFsHandle) {
            return $parse ? Env::parse($this->_transformFsHandle) : $this->_transformFsHandle;
        }

        return null;
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

    private function diskPrefix(): ?string
    {
        $subpath = Env::parse($this->_subpath) ?? '';
        $subpath = trim($subpath, '/');

        if ($subpath === '') {
            return null;
        }

        return $subpath;
    }

    private function diskNameForOperations(): string
    {
        $target = $this->resolveStorageTargetKey($this->_fsHandle);
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

            return Craft::$app->getFs()->toDiskName($handle);
        }

        throw new InvalidConfigException('Volume has an invalid filesystem handle.');
    }

    private function storageDisk(): LaravelFilesystemAdapter
    {
        $diskName = $this->diskNameForOperations();
        $prefix = $this->diskPrefix();

        if ($prefix === null) {
            return Storage::disk($diskName);
        }

        return Storage::build([
            'driver' => 'scoped',
            'disk' => $diskName,
            'prefix' => $prefix,
        ]);
    }

    private function swapDirectoryPrefix(string $path, string $prefix, string $replacement): string
    {
        $prefix = trim($prefix, '/');
        $replacement = trim($replacement, '/');

        return preg_replace(
            '/^'.preg_quote($prefix, '/').'(?=\/|$)/',
            $replacement,
            trim($path, '/'),
            1,
        ) ?? trim($path, '/');
    }
}
