<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Importers\BaseImporter;

/**
 * @event ItemImported The event that is triggered after data is imported.
 */
final readonly class ItemImported
{
    /**
     * Promotes the importer config and imported data into a readonly event payload fired after import.
     */
    public function __construct(
        public BaseImporter $importer,
        public array $data,
    ) {}
}
