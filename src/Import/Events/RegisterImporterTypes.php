<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\Importers\BaseImporter;

/**
 * @since 6.0.0
 */
class RegisterImporterTypes
{
    /**
     * Carries the mutable list of registered importer classes for listeners to add to.
     *
     * @param  list<class-string<BaseImporter>>  $importers  The registered importer classes.
     */
    public function __construct(
        public array $importers,
    ) {}
}
