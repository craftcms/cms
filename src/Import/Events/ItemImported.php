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
     * Promotes the importer config, imported data and import run ID into a readonly event payload fired after import.
     *
     * @param  BaseImporter  $importer  The importer config for this event.
     * @param  array  $data  The imported data.
     * @param  string|null  $runId  The unique ID of the import run this item belongs to, if any.
     */
    public function __construct(
        public BaseImporter $importer,
        public array $data,
        public ?string $runId = null,
    ) {}
}
