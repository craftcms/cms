<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Data\Import;

/**
 * @event ImportSaved The event that is triggered after an import is saved.
 */
final readonly class ImportSaved
{
    /**
     * Carries the saved import and isNew flag, fired after save.
     *
     * @param  Import  $import  The import this event concerns.
     */
    public function __construct(
        public Import $import,
        public bool $isNew,
    ) {}
}
