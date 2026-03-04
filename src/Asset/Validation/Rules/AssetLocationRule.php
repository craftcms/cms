<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Validation\Rules;

use Closure;
use craft\helpers\Assets;
use craft\helpers\Assets as AssetsHelper;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Assets as AssetsService;
use CraftCms\Cms\Support\Facades\Folders;
use Illuminate\Contracts\Validation\ValidationRule;

use function CraftCms\Cms\t;

/**
 * Validates an asset's location (folder + filename).
 */
final readonly class AssetLocationRule implements ValidationRule
{
    public function __construct(
        private Asset $asset,
        private string $folderIdAttribute = 'folderId',
        private string $filenameAttribute = 'filename',
        private string $suggestedFilenameAttribute = 'suggestedFilename',
        private string $conflictingFilenameAttribute = 'conflictingFilename',
        private string $errorCodeAttribute = 'locationError',
        private string|array|null $allowedExtensions = null,
        private ?string $disallowedExtension = null,
        private ?string $filenameConflict = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        [$folderId, $filename] = Assets::parseFileLocation($value);

        $hasNewFolderId = $folderId !== $this->asset->{$this->folderIdAttribute};
        $hasNewFilename = $filename !== $this->asset->{$this->filenameAttribute};

        if (! $hasNewFolderId && ! $hasNewFilename) {
            $this->asset->{$attribute} = null;

            return;
        }

        if (Folders::getFolderById($folderId) === null) {
            $fail(t('Invalid folder ID: {folderId}', ['folderId' => $folderId]));

            return;
        }

        $extension = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));
        $allowedExtensions = $this->allowedExtensions ?? Cms::config()->allowedFileExtensions;

        if (is_array($allowedExtensions) && ! in_array($extension, $allowedExtensions, true)) {
            $this->addLocationError(
                Asset::ERROR_DISALLOWED_EXTENSION,
                $this->disallowedExtension ?? t('"{extension}" is not an allowed file extension.'),
                ['extension' => $extension],
                $fail,
            );

            return;
        }

        $filename = AssetsHelper::prepareAssetName($filename);
        $suggestedFilename = AssetsService::getNameReplacementInFolder($filename, $folderId);

        if ($suggestedFilename !== $filename) {
            $this->asset->{$this->conflictingFilenameAttribute} = $filename;
            $this->asset->{$this->suggestedFilenameAttribute} = $suggestedFilename;

            if (! $this->asset->avoidFilenameConflicts) {
                $this->addLocationError(
                    Asset::ERROR_FILENAME_CONFLICT,
                    $this->filenameConflict ?? t('A file with the name "{filename}" already exists.'),
                    ['filename' => $filename],
                    $fail,
                );

                return;
            }
        }

        $this->asset->{$attribute} = "{folder:$folderId}$suggestedFilename";
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function addLocationError(
        string $errorCode,
        string $message,
        array $params,
        Closure $fail,
    ): void {
        $fail(strtr($message, array_map(
            fn ($value) => (string) $value,
            array_combine(
                array_map(fn ($key) => "{{$key}}", array_keys($params)),
                array_values($params),
            ),
        )));

        $this->asset->{$this->errorCodeAttribute} = $errorCode;
    }
}
