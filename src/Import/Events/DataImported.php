<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Importers\BaseImporter;

/**
 * @event DataImported The event that is triggered after data is imported.
 */
final readonly class DataImported
{
    /**
     * Promotes the importer config and imported data into a readonly event payload fired after import.
     *
     * @param BaseImporter $import
     * @param array $data
     */
    public function __construct(
        public BaseImporter $import,
        public array $data,
    ) {}
}
