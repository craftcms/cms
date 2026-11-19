<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Commands;

use CraftCms\Cms\Import\Commands\ImportCommand;
use CraftCms\Cms\User\Import\UserImporter;
use Override;

/**
 * @since 6.0.0
 */
class ImportUsersCommand extends ImportCommand
{
    #[Override]
    protected $name = 'craft:import:users';

    #[Override]
    protected $description = 'Imports users.';

    #[Override]
    protected $aliases = ['import/users'];

    #[Override]
    public static function importerClass(): string
    {
        return UserImporter::class;
    }
}
