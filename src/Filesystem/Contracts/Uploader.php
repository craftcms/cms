<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Contracts;

use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\Filesystem\Models\UploadSession;

interface Uploader
{
    /** Sets the chunk size and any provider state on the session. */
    public function start(UploadSession $session): void;

    /** @return array{type: string, options: array<string, mixed>} */
    public function clientConfig(UploadSession $session): array;

    public function uploaded(UploadSession $session): bool;

    public function complete(UploadSession $session): UploadedFile;

    /** Removes both incomplete transfers and completed temporary objects. */
    public function abort(UploadSession $session): void;
}
