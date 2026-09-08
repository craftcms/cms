<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Contracts;

use CraftCms\Cms\Asset\Data\UploadedAssetFile;
use CraftCms\Cms\Asset\Data\UploadPartRequest;
use CraftCms\Cms\Asset\Models\UploadSession;

interface AssetUploader
{
    /** Sets the chunk size and any provider state on the session. */
    public function start(UploadSession $session): void;

    public function partRequest(UploadSession $session, int $part): UploadPartRequest;

    public function complete(UploadSession $session): UploadedAssetFile;

    /** Removes both incomplete transfers and completed temporary objects. */
    public function abort(UploadSession $session): void;
}
