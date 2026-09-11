<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Contracts;

use CraftCms\Cms\Filesystem\Models\UploadSession;

interface ReceivesTusUploads extends Uploader
{
    public function offset(UploadSession $session): int;

    /** @param resource $stream */
    public function receive(UploadSession $session, int $offset, mixed $stream): void;
}
