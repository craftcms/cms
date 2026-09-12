<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

class RegisterImporterTypes
{
    /**
     * Carries the mutable list of registered importer classes for listeners to add to.
     *
     * @param array $importers The registered importer classes.
     */
    public function __construct(
        public array $importers,
    ) {}
}
