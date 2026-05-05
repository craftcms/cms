<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

class AssetFileKindsResolving
{
    public function __construct(
        /** @var array<string, array{
         *     label?: string,
         *     extensions?: string[],
        }> */
        public array $fileKinds,
    ) {}
}
