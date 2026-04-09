<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event ImportConfigSaving The event that is triggered before an import config is saved.
 */
class ImportConfigSaving
{
    use ValidatableEvent;

    public function __construct(
        public BaseImporter $import,
        public bool $isNew,
    ) {}
}
