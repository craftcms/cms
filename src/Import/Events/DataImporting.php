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

    /**
     * Promotes the importer config and raw data into a cancellable event fired before import.
     *
     * @param BaseImporter $importer The importer config for this event.
     * @param array $data
     */
    public function __construct(
        public BaseImporter $importer,
        public array $data,
    ) {}
}
