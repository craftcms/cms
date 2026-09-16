<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Events;

use CraftCms\Cms\Filesystem\Contracts\UploadHandler;
use Illuminate\Http\Request;

/**
 * @event UploadSessionStarting The event triggered after upload authorization, before selecting its temporary filesystem and uploader.
 *
 * Set $filesystem to a Craft filesystem handle or Laravel disk reference, and $uploader to a registered
 * uploader name. Leave either null to use the configured default or automatic selection.
 */
class UploadSessionStarting
{
    /**
     * @param  class-string<UploadHandler>  $handler
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        public readonly Request $request,
        public readonly string $handler,
        public readonly string $filename,
        public readonly int $size,
        public readonly array $parameters,
        public ?string $filesystem = null,
        public ?string $uploader = null,
    ) {}
}
