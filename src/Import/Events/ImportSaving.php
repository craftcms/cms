<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Data\Import;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event ImportSaving The event that is triggered before an import is saved.
 */
class ImportSaving
{
    use ValidatableEvent;

    /**
     * Carries the import and isNew flag for a cancellable pre-save event.
     *
     * @param  Import  $import  The import this event concerns.
     * @param  bool  $isNew  Whether the import is newly created.
     */
    public function __construct(
        public Import $import,
        public bool $isNew,
    ) {}
}
