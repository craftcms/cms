<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity;

use CraftCms\Cms\Entry\Elements\Entry;

/**
 * @internal
 *
 * @since 6.0.0
 */
readonly class DraftWrite
{
    /**
     * @param  array<string, mixed>  $originalFieldValues
     * @param  string[]  $dirtyAttributes
     * @param  string[]  $dirtyFields
     */
    public function __construct(
        public bool $isNew,
        public bool $metadataChanged,
        public ?Entry $original,
        public array $originalFieldValues,
        public array $dirtyAttributes,
        public array $dirtyFields,
    ) {}
}
