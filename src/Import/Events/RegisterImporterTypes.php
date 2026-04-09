<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

class RegisterImporterTypes
{
    public function __construct(
        public array $importers,
    ) {}
}
