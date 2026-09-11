<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Contracts;

use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\Filesystem\Data\UploadSetup;
use CraftCms\Cms\Filesystem\Models\UploadSession;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

interface Uploader
{
    public function start(UploadSession $session): UploadSetup;

    public function handleRequest(Request $request, UploadSession $session): Response;

    public function uploaded(UploadSession $session): bool;

    public function complete(UploadSession $session): UploadedFile;

    /** Removes both incomplete transfers and completed temporary objects. */
    public function abort(UploadSession $session): void;
}
