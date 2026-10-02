<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event ItemImporting The event that is triggered before data is imported.
 *
 * @since 6.0.0
 */
class ItemImporting
{
    use ValidatableEvent;

    /**
     * Promotes the importer config, raw data and import run ID into a cancellable event fired before import.
     * A cancelled item doesn't fire `ItemImported`, so listeners tracking imported elements won't see it.
     *
     * @param  BaseImporter  $importer  The importer config for this event.
     * @param  array<string, mixed>  $data  The raw data about to be imported - before remapping.
     * @param  string|null  $runId  The unique ID of the import run this item belongs to, if any.
     */
    public function __construct(
        public BaseImporter $importer,
        public array $data,
        public ?string $runId = null,
    ) {}
}
