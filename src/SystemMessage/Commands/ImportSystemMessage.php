<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage\Commands;

use CraftCms\Cms\Import\Commands\Import;
use CraftCms\Cms\SystemMessage\Import\SystemMessageImporter;
use Override;

class ImportSystemMessage extends Import
{
    #[Override]
    protected $name = 'craft:import:system-message';

    #[Override]
    protected $description = 'Imports Craft CMS System Messages';

    #[Override]
    protected $aliases = ['import/system-message'];

    #[Override]
    public static function importerClass(): string
    {
        return SystemMessageImporter::class;
    }
}
