<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Contracts;

use CraftCms\Cms\Filesystem\Models\UploadSession;

interface SignsS3Uploads extends Uploader
{
    /** @return array{type: string, options: array{uploadId: string, key: string}} */
    public function clientConfig(UploadSession $session): array;

    /** @return array{url: string} */
    public function sign(UploadSession $session, string $method, ?int $part = null): array;
}
