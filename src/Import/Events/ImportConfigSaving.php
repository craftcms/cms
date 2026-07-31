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

    /**
     * Carries the importer config and isNew flag for a cancellable pre-save event.
     *
     * @param BaseImporter $importer The importer config for this event.
     * @param bool $isNew
     */
    public function __construct(
        public BaseImporter $importer,
        public bool $isNew,
    ) {}
}
