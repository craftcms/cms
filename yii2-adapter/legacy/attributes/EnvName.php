<?php

namespace craft\attributes;

use Attribute;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 5.6.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Attributes\EnvName} instead.
     */
    #[Attribute]
    class EnvName
    {
        public function __construct(
            public readonly string $name,
        ) {
        }
    }
}
