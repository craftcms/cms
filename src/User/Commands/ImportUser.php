<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Commands;

use CraftCms\Cms\Import\Commands\Import;
use CraftCms\Cms\User\Import\UserImporter;
use Override;

class ImportUser extends Import
{
    #[Override]
    protected $name = 'craft:import:user';

    #[Override]
    protected $description = 'Imports Craft CMS Users';

    #[Override]
    protected $aliases = ['import/user'];

    #[Override]
    public static function importerClass(): string
    {
        return UserImporter::class;
    }
}
