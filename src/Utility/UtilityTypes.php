<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility;

use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Utility\Utilities\AssetIndexes;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
use CraftCms\Cms\Utility\Utilities\DbBackup;
use CraftCms\Cms\Utility\Utilities\DeprecationErrors;
use CraftCms\Cms\Utility\Utilities\FindAndReplace;
use CraftCms\Cms\Utility\Utilities\Migrations;
use CraftCms\Cms\Utility\Utilities\PhpInfo;
use CraftCms\Cms\Utility\Utilities\ProjectConfig;
use CraftCms\Cms\Utility\Utilities\QueueManager;
use CraftCms\Cms\Utility\Utilities\SystemMessages;
use CraftCms\Cms\Utility\Utilities\SystemReport;
use CraftCms\Cms\Utility\Utilities\Updates;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;

/**
 * Registers utility type classes available in the control panel.
 *
 * ```php
 * public function boot(UtilityTypes $utilityTypes): void
 * {
 *     $utilityTypes->register(MyUtility::class);
 * }
 * ```
 *
 * @extends TypeRegistry<Utility>
 */
#[Singleton]
class UtilityTypes extends TypeRegistry
{
    protected const string CONTRACT = Utility::class;

    protected const array DEFAULT_TYPES = [
        Updates::class,
        SystemReport::class,
        ProjectConfig::class,
        PhpInfo::class,
        SystemMessages::class,
        AssetIndexes::class,
        QueueManager::class,
        ClearCaches::class,
        DeprecationErrors::class,
        DbBackup::class,
        FindAndReplace::class,
        Migrations::class,
    ];

    public function __construct(
        private readonly GeneralConfig $generalConfig,
    ) {
        parent::__construct();
    }

    #[\Override]
    public function types(): Collection
    {
        $types = parent::types()
            /** @var class-string<Utility> $class */
            ->reject(fn (string $class) => match ($class) {
                SystemMessages::class => ! Edition::isAtLeast(Edition::Pro),
                AssetIndexes::class => Volumes::getAllVolumes()->isEmpty(),
                DbBackup::class => $this->generalConfig->backupCommand === false,
                default => false,
            });

        $disabledUtilities = array_flip($this->generalConfig->disabledUtilities);

        return $types
            /** @var class-string<Utility> $class */
            ->filter(fn (string $class) => ! isset($disabledUtilities[$class::id()]) && $class::isSelectable());
    }

    /** @param class-string<Utility> $type */
    #[\Override]
    protected function identity(string $type): string
    {
        return $type::id();
    }
}
