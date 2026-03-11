<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig\Events;

use CraftCms\Cms\ProjectConfig\ProjectConfig;

abstract class ConfigEvent
{
    public function __construct(
        public string $path,
        public mixed $oldValue = null,
        public mixed $newValue = null,

        /**
         * @var string[]|null Any parts of the path that were matched by `{uid}` tokens.
         *                    This wil be populated if the handler was registered using {@see ProjectConfig::registerChangeEventHandler()},
         *                    or one of its shortcut methods.
         */
        public ?array $tokenMatches = null,
        public ?array $data = null,
    ) {}
}
