<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Validation;

use Closure;
use CraftCms\Cms\Asset\AssetProcessors;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Models\Volume as VolumeModel;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Filesystem\Filesystems as FilesystemsService;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Validation\Rules\EnvValueRule;
use CraftCms\Cms\Validation\Rules\HandleRule;
use CraftCms\Cms\Validation\Ruleset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Override;

use function CraftCms\Cms\t;

/** @extends Ruleset<Volume> */
class VolumeRules extends Ruleset
{
    /** @return array<string, list<Closure|object|string>> */
    public function rules(): array
    {
        $rules = [
            'id' => ['nullable', 'integer'],
            'fieldLayoutId' => ['nullable', 'integer'],
            'name' => [
                'required',
                Rule::unique(Table::VOLUMES, 'name')->ignore($this->subject->id)->withoutTrashed('dateDeleted'),
            ],
            'handle' => [
                'required',
                new HandleRule(reservedWords: [
                    'dateCreated',
                    'dateUpdated',
                    'edit',
                    'id',
                    'temp',
                    'title',
                    'uid',
                ]),
                Rule::unique(Table::VOLUMES, 'handle')->ignore($this->subject->id)->withoutTrashed('dateDeleted'),
            ],
            'fieldLayout' => [fn (string $attribute, mixed $value, Closure $fail) => $this->subject->validateFieldLayout()],
            'fsHandle' => [fn (string $attribute, mixed $value, Closure $fail) => $this->validateFilesystemHandle($attribute, $fail)],
            'assetProcessor' => new EnvValueRule([
                'nullable',
                'string',
                Rule::in(app(AssetProcessors::class)->getAllAssetProcessors()->pluck('handle')->all()),
            ]),
            'subpath' => new EnvValueRule([
                Rule::requiredIf(fn () => $this->subpathRequired()),
                fn (string $attribute, mixed $value, Closure $fail) => $this->validateUniqueSubpath($attribute, $fail),
            ]),
        ];

        $tempAssetUploadTarget = $this->subject->resolveStorageTargetKey(Cms::config()->tempAssetUploadFs);

        if ($tempAssetUploadTarget !== null) {
            $rules['fsHandle'][] = fn (string $attribute, mixed $value, Closure $fail) => $this->validateReservedTempUploadFilesystem($attribute, $tempAssetUploadTarget, $fail);
        }

        return $rules;
    }

    #[Override]
    public function messages(): array
    {
        return [
            'subpath.required' => t('A subpath is required for this filesystem.'),
        ];
    }

    private function validateUniqueSubpath(string $attribute, ?Closure $fail = null): void
    {
        $records = $this->volumesSharingStorageTarget();
        if ($records->isEmpty()) {
            return;
        }

        $subpath = $this->subject->getSubpath(ensureTrailing: false, parse: false);
        if ($subpath === '') {
            return;
        }

        $subpathRoot = $this->firstPathSegment($subpath);

        foreach ($records as $record) {
            if (strcmp($this->firstPathSegment((string) $record->$attribute), $subpathRoot) === 0) {
                $this->pushValidationError($attribute, t('The subpath cannot overlap with any other volumes sharing the same filesystem.'), $fail);
            }
        }
    }

    private function firstPathSegment(string $path): string
    {
        return explode('/', $path)[0];
    }

    private function subpathRequired(): bool
    {
        return $this->volumesSharingStorageTarget()->isNotEmpty();
    }

    /**
     * @return Collection<int, VolumeModel>
     */
    private function volumesSharingStorageTarget(): Collection
    {
        $storageTarget = $this->subject->resolveStorageTargetKey($this->subject->getFsHandle(false));
        if ($storageTarget === null) {
            return collect();
        }

        return VolumeModel::query()
            ->when($this->subject->id !== null, fn (Builder $query) => $query->whereNot('id', $this->subject->id))
            ->get()
            ->filter(fn (VolumeModel $record): bool => $this->subject->resolveStorageTargetKey($record->fs) === $storageTarget)
            ->values();
    }

    private function validateFilesystemHandle(string $attribute, ?Closure $fail = null): void
    {
        $handle = $this->storageHandleForAttribute($attribute);

        if ($handle === null || $handle === '') {
            if ($attribute === 'fsHandle') {
                $this->pushValidationError($attribute, t('{attribute} cannot be blank.', [
                    'attribute' => $this->subject->getAttributeLabel($attribute),
                ]), $fail);
            }

            return;
        }

        if ($this->isUnresolvedEnvValue($handle)) {
            return;
        }

        if ($this->isInternalDiskReference($handle)) {
            $this->pushValidationError($attribute, t('This disk is reserved for internal use.'), $fail);

            return;
        }

        if ($this->subject->resolveStorageTargetKey($handle) === null) {
            $this->pushValidationError($attribute, t('This filesystem reference is invalid.'), $fail);
        }
    }

    private function validateReservedTempUploadFilesystem(string $attribute, string $tempUploadTarget, ?Closure $fail = null): void
    {
        $handle = $this->storageHandleForAttribute($attribute);

        if ($handle === null || $handle === '') {
            return;
        }

        $target = $this->subject->resolveStorageTargetKey($handle);
        if ($target !== null && $target === $tempUploadTarget) {
            $this->pushValidationError(
                $attribute,
                t('This filesystem has been reserved for temporary asset uploads. Please choose a different one for your volume.'),
                $fail,
            );
        }
    }

    private function storageHandleForAttribute(string $attribute): ?string
    {
        return match ($attribute) {
            'fsHandle' => $this->subject->getFsHandle(false),
            default => null,
        };
    }

    private function pushValidationError(string $attribute, string $message, ?Closure $fail = null): void
    {
        if ($fail !== null) {
            $fail($message);

            return;
        }

        $this->subject->errors()->add($attribute, $message);
    }

    private function isInternalDiskReference(string $value): bool
    {
        $diskName = $value;
        if (str_starts_with($value, Volume::STORAGE_DISK_PREFIX)) {
            $diskName = substr($value, strlen(Volume::STORAGE_DISK_PREFIX));
        }

        return $diskName !== '' && $this->diskExists($diskName) && $this->isInternalDiskName($diskName);
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

    private function isUnresolvedEnvValue(string $value): bool
    {
        if (! preg_match('/\\$\\{?\\w+\\}?/', $value)) {
            return false;
        }

        return Env::parse($value) === null;
    }
}
