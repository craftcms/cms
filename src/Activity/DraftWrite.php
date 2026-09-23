<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity;

use CraftCms\Cms\Entry\Elements\Entry;

/** @internal */
readonly class DraftWrite
{
    /**
     * @param  string[]  $dirtyAttributes
     * @param  string[]  $dirtyFields
     */
    public function __construct(
        public bool $isNew,
        public bool $metadataChanged,
        public ?Entry $original,
        public array $dirtyAttributes,
        public array $dirtyFields,
    ) {}
}
