<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Import;

use Closure;
use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\AssetDisallowedExtensionException;
use CraftCms\Cms\Asset\Exceptions\AssetException;
use CraftCms\Cms\Asset\Exceptions\FileException;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Facades\Assets as AssetsService;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Nodes\Field as UiField;
use Exception;
use Illuminate\Validation\Validator;
use Override;
use Throwable;

use function CraftCms\Cms\t;

/**
 * Imports data into Asset elements.
 *
 * @since 6.0.0
 */
class AssetImporter extends ElementImporter
{
    public protected(set) ?string $volume = null;

    #[Override]
    public static function targetClass(): string
    {
        return Asset::class;
    }

    /**
     * Convenience factory returning a new instance.
     */
    public static function create(): self
    {
        return new self;
    }

    #[Override]
    public static function displayName(): string
    {
        return t('Assets');
    }

    #[Override]
    public static function getDefaultTransformer(): ?string
    {
        return AssetTransformer::class;
    }

    #[Override]
    public function settingsUi(): array
    {
        return [
            ...parent::settingsUi(),
            UiField::make(t('Volume'), Choice::make(['volume'])
                ->value($this->volume)
                ->placeholder(t('Please select'))
                ->options($this->availableVolumes())
                ->reactive())
                ->instructions(t('The volume to import into.')),
        ];
    }

    #[Override]
    public function refreshSettingsUi(array $settings): void
    {
        parent::refreshSettingsUi($settings);

        if (array_key_exists('volume', $settings)) {
            $this->volume($settings['volume']);
        }
    }

    #[Override]
    public function storeSettings(array $settings): void
    {
        parent::storeSettings($settings);

        $this->volume($settings['volume'] ?? null);
    }

    #[Override]
    public function getSettings(): array
    {
        $settings = parent::getSettings();

        $settings['volume'] = $this->volume;

        return $settings;
    }

    /**
     * Sets the target volume by uid.
     */
    public function volume(string|int|Volume|null $value): self
    {
        $result = self::normalizeVolume($value);
        if (! $result) {
            $this->volume = null;
            $this->fieldLayout(null);
        } else {
            $this->volume = $result->uid;
            $this->fieldLayout($result->getFieldLayout());
        }

        return $this;
    }

    #[Override]
    public static function getSettingsRules(): array
    {
        return array_merge(parent::getSettingsRules(), [
            'settings.volume' => [
                'required',
                fn ($attribute, $value, Closure $fail, Validator $validator) => static::validateVolume($value, $attribute, $fail, $validator),
            ],
        ]);
    }

    #[Override]
    public function toArrayData(): array
    {
        $data = parent::toArrayData();
        $data['settings']['volume'] = $this->volume ?? null;

        return $data;
    }

    public static function validateVolume(mixed $value, string $attribute, Closure $fail, Validator $validator): bool
    {
        // can't be empty
        if (empty($value)) {
            $fail($attribute, t('Volume must be provided.'));

            return false;
        }

        if (self::normalizeVolume($value) === null) {
            $fail($attribute, t('No Volume found for “{volume}”.', [
                'volume' => $value,
            ]));

            return false;
        }

        return true;
    }

    private static function normalizeVolume(string|int|Volume|null $value): ?Volume
    {
        return match (true) {
            $value instanceof Volume => $value,
            $value === null => null,
            is_numeric($value) => Volumes::getVolumeById((int) $value),
            default => Volumes::getVolumeByUid($value) ?? Volumes::getVolumeByHandle($value),
        };
    }

    #[Override]
    public function prepareNewRootElementForImport(array &$data, ?ElementInterface $element = null): ElementInterface
    {
        /** @var Asset $element */
        $element = parent::prepareNewRootElementForImport($data, $element);

        // if it's UI-driven element import where the fieldLayout was chosen in the editable config,
        // we need to ensure the volumeId is set
        if ($this->fieldLayout) {
            $allVolumes = Volumes::getAllVolumes();
            $allFieldLayouts = $allVolumes->mapWithKeys(function ($volume) {
                $fieldLayout = $volume->getFieldLayout();

                return [$fieldLayout->id => $fieldLayout];
            });
            $volume = $allFieldLayouts->firstWhere('uid', $this->fieldLayout)?->provider;
            if ($volume) {
                $element->setVolumeId($volume->id);
                if (isset($data['matchCriteria']['volumeId'])) {
                    unset($data['matchCriteria']['volumeId']);
                }
            }
        }

        return $element;
    }

    #[Override]
    public function prepareRootElementImportQuery(ElementInterface $element, ElementQueryInterface $query): ElementQueryInterface
    {
        /** @var Asset $element */
        /** @var AssetQuery $query */
        return $query->volumeId($element->getVolumeId());
    }

    #[Override]
    public function setAttributesForImport(ElementInterface $element, array $attributes, array $data): void
    {
        /** @var Asset $element */
        // ensure we're not changing volume ID compared to what we chose in the field layout provider step
        unset($attributes['volumeId']);

        // if this is a new asset and we don't have tempFilePath - throw an error and don't bother going further
        if ($element->id === null && ! isset($attributes['tempFilePath'])) {
            // throw new Exception('Cannot import an asset without a tempFilePath');
            throw new AssetException(t('Cannot create a new asset without a file. Please check your mapping and incoming data.'));
        }

        // if folderId was not provided, ensure we have one:
        if (empty($attributes['folderId'])) {
            $folder = Folders::getRootFolderByVolumeId($element->getVolumeId());
            $attributes['folderId'] = $folder->id;
        }

        // deduce filename - was one provided or should we get it from the provided file path
        if (empty($attributes['filename']) && ! empty($attributes['tempFilePath'])) {
            $attributes['filename'] = AssetsHelper::importFilename($attributes['tempFilePath']);
        } elseif (isset($attributes['filename'])) {
            $attributes['filename'] = AssetsHelper::prepareAssetName($attributes['filename']);
        }

        // a filename with an extension can be checked before anything is fetched
        if (isset($attributes['filename']) && pathinfo($attributes['filename'], PATHINFO_EXTENSION) !== '') {
            self::ensureAllowedExtension($attributes['filename']);
        }

        // fetch the file (a local path or a URL) to a temp location
        if (! empty($attributes['tempFilePath'])) {
            $source = $attributes['tempFilePath'];

            try {
                [$tempPath, $extension] = AssetsHelper::fetchImportFile(static::urlValidator(), $source, $attributes['filename']);
            } catch (AssetException|FileException $e) {
                throw $e;
            } catch (Throwable $e) {
                throw new AssetException(t('Couldn’t download “{url}”.', [
                    'url' => $source,
                ]), previous: $e);
            }

            $attributes['tempFilePath'] = $tempPath;

            // without an extension, the fetched file's type decides it
            if (pathinfo($attributes['filename'], PATHINFO_EXTENSION) === '') {
                $attributes['filename'] .= ".$extension";

                try {
                    self::ensureAllowedExtension($attributes['filename']);
                } catch (Throwable $e) {
                    File::delete($tempPath);

                    throw $e;
                }
            }
        } elseif (isset($attributes['filename']) && pathinfo($attributes['filename'], PATHINFO_EXTENSION) === '') {
            // nothing was fetched to tell what an extension-less filename is
            self::ensureAllowedExtension($attributes['filename']);
        }

        // avoid filename conflicts
        if (isset($attributes['filename'])) {
            $suggestedFilename = AssetsService::getNameReplacementInFolder($attributes['filename'], $attributes['folderId']);
            if ($suggestedFilename !== $attributes['filename'] && (! $element->id || $attributes['filename'] !== $element->getFilename())) {
                $attributes['filename'] = $suggestedFilename;
            }
        }

        parent::setAttributesForImport($element, $attributes, $data);
    }

    /**
     * Ensures an imported file’s extension is allowed site-wide.
     *
     * @throws AssetDisallowedExtensionException if it isn’t
     */
    private static function ensureAllowedExtension(string $filename): void
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (! in_array($extension, Cms::config()->allowedFileExtensions, true)) {
            throw new AssetDisallowedExtensionException(t('“{extension}” is not an allowed file extension.', [
                'extension' => $extension,
            ]));
        }
    }

    /**
     * Returns the volumes that can be imported into, as choice options.
     *
     * @return list<array{label: string, value: string}>
     */
    protected function availableVolumes(): array
    {
        return Volumes::getAllVolumes()->map(fn ($volume) => [
            'label' => $volume->name,
            'value' => $volume->uid,
        ])->all();
    }
}
