<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Contracts;

use CraftCms\Cms\Filesystem\Data\UploadedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

interface UploadHandler
{
    /**
     * Validates the file metadata and authorizes its destination before each upload operation.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed> The destination parameters to bind to the session.
     */
    public function authorize(Request $request, array $parameters, string $filename, int $size): array;

    /** @param array<string, mixed> $parameters */
    public function complete(Request $request, array $parameters, UploadedFile $file): JsonResponse;
}
