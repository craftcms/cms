<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

use Illuminate\Contracts\Support\Arrayable;

/** @implements Arrayable<string, mixed> */
readonly class UploadSessionData implements Arrayable
{
    /** @param array{part: string, complete: string, cancel: string} $urls */
    public function __construct(
        public string $id,
        public int $chunkSize,
        public int $partCount,
        public array $urls,
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
