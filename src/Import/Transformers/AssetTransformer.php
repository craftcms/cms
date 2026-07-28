<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Transformers;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Support\Facades\Folders;

class AssetTransformer extends ElementTransformer
{
    /**
     * Normalize folder ID to an existing folder ID or null.
     */
    protected function normalizeFolderId(mixed $value, ElementInterface $element): ?int
    {
        // if folder ID wasn't provided in the incoming data (which should be very common)
        // we need to get the root folder for given volume
        if ($value === null) {
            return null;
        }

        $folder = null;
        /** @var Asset $element */
        $volume = $element->getVolume();

        if (is_int($value) || is_numeric($value)) {
            $folder = Folders::getFolderById((int) $value);
        }

        if (is_string($value)) {
            $folder = Folders::findFolder(['name' => $value]);
        }

        // check that it belongs to the volume that was selected
        if ($folder && $folder->volumeId === $volume->id) {
            return $folder->id;
        }

        $folder = Folders::getRootFolderByVolumeId($volume->id);

        return $folder?->id;
    }
}
