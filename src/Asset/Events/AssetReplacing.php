<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event AssetReplacing The event that is triggered before an asset's file is replaced.
 *
 * @since 6.0.0
 */
class AssetReplacing
{
    use ValidatableEvent;

    /**
     * The path to the new file on the server. A staged upload is only downloaded when this is read.
     */
    public string $replaceWith {
        get => $this->replaceWith ??= $this->upload->localPath();
    }

    private ?UploadedFile $upload = null;

    public function __construct(
        public Asset $asset,
        string|UploadedFile $replaceWith,
        public string $filename,
    ) {
        if ($replaceWith instanceof UploadedFile) {
            $this->upload = $replaceWith;
        } else {
            $this->replaceWith = $replaceWith;
        }
    }
}
