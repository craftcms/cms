<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage\Commands;

use CraftCms\Cms\Import\Commands\ImportCommand;
use CraftCms\Cms\SystemMessage\Import\SystemMessageImporter;
use Override;

/**
 * @since 6.0.0
 */
class ImportSystemMessagesCommand extends ImportCommand
{
    #[Override]
    protected $name = 'craft:import:system-messages';

    #[Override]
    protected $description = 'Imports system messages.';

    #[Override]
    protected $aliases = ['import/system-messages'];

    #[Override]
    public static function importerClass(): string
    {
        return SystemMessageImporter::class;
    }
}
