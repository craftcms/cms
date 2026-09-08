<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

use Illuminate\Contracts\Support\Arrayable;

/** @implements Arrayable<string, mixed> */
readonly class UploadPartRequest implements Arrayable
{
    /** @param array<string, string> $headers */
    public function __construct(
        public string $url,
        public string $method = 'PUT',
        public array $headers = [],
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
