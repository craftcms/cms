<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use CraftCms\Cms\Asset\Import\AssetImporter;
use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\SystemMessage\Import\SystemMessageImporter;
use CraftCms\Cms\User\Import\UserImporter;
use Illuminate\Container\Attributes\Singleton;

/**
 * Registers the importer types available to import plans.
 *
 * Plugins may register importers from their service provider:
 *
 * ```php
 * public function boot(ImporterTypes $importerTypes): void
 * {
 *     $importerTypes->register(ProductImporter::class);
 * }
 * ```
 *
 * @extends TypeRegistry<BaseImporter>
 *
 * @since 6.0.0
 */
#[Singleton]
class ImporterTypes extends TypeRegistry
{
    protected const string CONTRACT = BaseImporter::class;

    protected const array DEFAULT_TYPES = [
        EntryImporter::class,
        AssetImporter::class,
        UserImporter::class,
        SystemMessageImporter::class,
    ];
}
