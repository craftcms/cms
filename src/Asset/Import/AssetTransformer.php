<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Import;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Import\ElementTransformer;
use CraftCms\Cms\Support\Facades\Folders;

/**
 * @since 6.0.0
 */
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

        if (is_numeric($value)) {
            $folder = Folders::getFolderById((int) $value);
        }

        // a numeric string that isn't a folder ID in this volume could still be a folder name
        if ((! $folder || $folder->volumeId !== $volume->id) && is_string($value)) {
            $folder = Folders::findFolder(['name' => $value, 'volumeId' => $volume->id]);
        }

        // check that it belongs to the volume that was selected
        if ($folder && $folder->volumeId === $volume->id) {
            return $folder->id;
        }

        $folder = Folders::getRootFolderByVolumeId($volume->id);

        return $folder?->id;
    }
}
