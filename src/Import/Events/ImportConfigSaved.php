<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Importers\BaseImporter;

/**
 * @event ImportConfigSaved The event that is triggered after an import config is saved.
 */
final readonly class ImportConfigSaved
{
    public function __construct(
        public BaseImporter $import,
        public bool $isNew,
    ) {}
}
