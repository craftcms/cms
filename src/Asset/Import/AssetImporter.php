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
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field as FormField;
use CraftCms\Cms\Support\Facades\Assets as AssetsService;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Support\Facades\ImportLog;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Url;
use Exception;
use Illuminate\Validation\Validator;
use Override;

use function CraftCms\Cms\t;

/**
 * Imports data into Asset elements.
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

    public static function getDefaultTransformer(): ?string
    {
        return AssetTransformer::class;
    }

    #[Override]
    public function settingsForm(FormContext $context): array
    {
        $parent = parent::settingsForm($context);

        return [
            'context' => $parent['context'] ?? $context,
            'nodes' => [
                ...$parent['nodes'] ?? [],
                FormField::make(t('Volume'), Choice::make(['volume'])
                    ->value($this->volume)
                    ->placeholder(t('Please select'))
                    ->options($this->availableVolumes())
                    ->reactive())
                    ->instructions(t('The volume to import into.')),
            ],
        ];
    }

    #[Override]
    public function refreshSettingsForm(array $settings): void
    {
        parent::refreshSettingsForm($settings);

        if (array_key_exists('volume', $settings)) {
            $this->volume($settings['volume']);
            $this->fieldLayout($settings['fieldLayout']);
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
                'string',
                'max:255',
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

    public static function validateVolume(string $value, string $attribute, Closure $fail, Validator $validator): bool
    {
        // can't be empty
        if (empty($value)) {
            $fail($attribute, t('Volume must be provided.'));

            return false;
        }

        if (static::normalizeVolume($value) === null) {
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
        /** @var $element Asset */
        /** @var $query AssetQuery */
        return $query->volumeId($element->getVolumeId());
    }

    #[Override]
    public function setAttributesForImport(ElementInterface $element, array $attributes): void
    {
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
            $attributes['filename'] = AssetsHelper::prepareAssetName(pathinfo(Url::stripQueryString($attributes['tempFilePath']), PATHINFO_BASENAME));
        } elseif (isset($attributes['filename'])) {
            $attributes['filename'] = AssetsHelper::prepareAssetName($attributes['filename']);
        }

        // avoid filename conflicts
        if (isset($attributes['filename'])) {
            $suggestedFilename = AssetsService::getNameReplacementInFolder($attributes['filename'], $attributes['folderId']);
            if ($suggestedFilename !== $attributes['filename'] && (! $element->id || $attributes['filename'] !== $element->getFilename())) {
                $attributes['filename'] = $suggestedFilename;
            }

            // deduce extension and check if it's allowed
            $allowedExtensions = Cms::config()->allowedFileExtensions;
            $extension = strtolower(pathinfo($attributes['filename'], PATHINFO_EXTENSION));
            if (! in_array($extension, $allowedExtensions, true)) {
                throw new AssetDisallowedExtensionException(t('“{extension}” is not an allowed file extension.', [
                    'extension' => $extension,
                ]));
            }
        }

        // process the file path (tempFilePath); if it's in a temp location - use it;
        // if it's an absolute URL - download to a temp location and use it
        if (! empty($attributes['tempFilePath'])) {
            // if it's not an absolute URL
            if (! Url::isAbsoluteUrl($attributes['tempFilePath'])) {
                // check if the file is already located in the temp location - if so, we should be able to just use it
                $value = realpath($attributes['tempFilePath']);

                if ($value === false || ! is_file($value)) {
                    // if we don't have the file path, we shouldn't proceed
                    throw new FileException(t('Cannot establish absolute pathname for “{filePath}” (e.g. file doesn’t exist) or it’s not a file.', [
                        'filePath' => $attributes['tempFilePath'],
                    ]));
                }
                $value = File::normalizePath($value);
                // Make sure it's within a known temp path, the project root, or storage/ folder
                $allowedRoots = Asset::getAllowedTempFileRoots();
                if (! Path::isPathWithinRoots($value, $allowedRoots)) {
                    throw new FileException(t('File “{filePath}” is in a disallowed location. Only temp path, project root and storage folders are allowed.', [
                        'filePath' => $attributes['tempFilePath'],
                    ]));
                }
                $attributes['tempFilePath'] = $value;
            } else {
                // if it's an absolute URL, we need to download the file to a temp location
                $tempPath = AssetsHelper::tempFilePath($extension);
                try {
                    AssetsHelper::downloadUrl($attributes['tempFilePath'], $tempPath);
                    $attributes['tempFilePath'] = $tempPath;
                } catch (Exception $e) {
                    // log error
                    ImportLog::warning("Couldn't download a file while importing an asset: ".$e->getMessage());
                }
                // todo (iwona): what about base64 - feed me supports it, but do we want it for the native import?
            }
        }

        parent::setAttributesForImport($element, $attributes);
    }

    protected function availableVolumes(): array
    {
        return Volumes::getAllVolumes()->map(fn ($volume) => [
            'label' => $volume->name,
            'value' => $volume->uid,
        ])->all();
    }
}
