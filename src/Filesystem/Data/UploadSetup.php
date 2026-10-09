<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Data;

/**
 * @since 6.0.0
 */
readonly class UploadSetup
{
    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, mixed>  $transportOptions
     */
    public function __construct(
        public int $chunkSize,
        public array $state,
        public string $transportType,
        public array $transportOptions,
    ) {}
}
