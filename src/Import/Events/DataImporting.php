<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event DataImporting The event that is triggered before data is imported.
 */
class DataImporting
{
    use ValidatableEvent;

    public function __construct(
        public BaseImporter $import,
        public array $data,
    ) {}
}
