<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Data;

readonly class UploadSetup
{
    /**
     * @param  array<string, mixed>  $state
     * @param  array{type: string, options: array<string, mixed>}  $transport
     */
    public function __construct(
        public int $chunkSize,
        public array $state,
        public array $transport,
    ) {}
}
