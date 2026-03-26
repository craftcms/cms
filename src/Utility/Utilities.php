<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Utility\Events\RegisterUtilities;
use CraftCms\Cms\Utility\Utilities\AssetIndexes;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
use CraftCms\Cms\Utility\Utilities\DbBackup;
use CraftCms\Cms\Utility\Utilities\DeprecationErrors;
use CraftCms\Cms\Utility\Utilities\FindAndReplace;
use CraftCms\Cms\Utility\Utilities\Migrations;
use CraftCms\Cms\Utility\Utilities\PhpInfo;
use CraftCms\Cms\Utility\Utilities\ProjectConfig as ProjectConfigUtility;
use CraftCms\Cms\Utility\Utilities\QueueManager;
use CraftCms\Cms\Utility\Utilities\SystemMessages as SystemMessagesUtility;
use CraftCms\Cms\Utility\Utilities\SystemReport;
use CraftCms\Cms\Utility\Utilities\Updates as UpdatesUtility;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

#[Singleton]
readonly class Utilities
{
    public function __construct(
        private GeneralConfig $generalConfig,
    ) {}

    /**
     * Returns all available utility type classes.
     *
     * @return Collection<class-string<Utility>>
     */
    public function getAllUtilityTypes(): Collection
    {
        $utilityTypes = Collection::make()
            ->push(
                UpdatesUtility::class,
                SystemReport::class,
                ProjectConfigUtility::class,
                PhpInfo::class,
            )
            ->when(
                Edition::isAtLeast(Edition::Pro),
                fn (Collection $c) => $c->push(SystemMessagesUtility::class)
            )
            ->unless(
                Volumes::getAllVolumes()->isEmpty(),
                fn (Collection $c) => $c->push(AssetIndexes::class)
            )
            ->push(
                QueueManager::class,
                ClearCaches::class,
                DeprecationErrors::class,
            )
            ->when(
                $this->generalConfig->backupCommand !== false,
                fn (Collection $c) => $c->push(DbBackup::class)
            )
            ->push(
                FindAndReplace::class,
                Migrations::class,
            );

        event($event = new RegisterUtilities($utilityTypes));

        $disabledUtilities = array_flip($this->generalConfig->disabledUtilities);

        return $event->types
            /** @var class-string<Utility> $class */
            ->filter(fn (string $class) => ! isset($disabledUtilities[$class::id()]) && $class::isSelectable());
    }

    /**
     * Returns all utility type classes that the user has permission to use.
     *
     * @return Collection<class-string<Utility>>
     */
    public function getAuthorizedUtilityTypes(): Collection
    {
        return Collection::make($this->getAllUtilityTypes())
            ->filter(fn (string $class) => $this->checkAuthorization($class));
    }

    /**
     * Returns whether the current user is authorized to use a given utility.
     *
     * @param  class-string<Utility>  $class  The utility class
     */
    public function checkAuthorization(string $class): bool
    {
        $user = Auth::user();

        // The Project Config utility is for admins only!
        if ($class === ProjectConfigUtility::class && ! $user?->isAdmin()) {
            return false;
        }

        $utilityId = $class::id();

        if (! $user?->can("utility:$utilityId")) {
            return false;
        }

        // Make sure the utility isn't disabled
        if (in_array($utilityId, $this->generalConfig->disabledUtilities)) {
            return false;
        }

        return true;
    }

    /**
     * Returns a utility class by its ID
     *
     * @return class-string<Utility>|null
     */
    public function getUtilityTypeById(string $id): ?string
    {
        return $this->getAllUtilityTypes()
            /** @var class-string<Utility> $class */
            ->first(fn (string $class) => $class::id() === $id);
    }

    /**
     * Retrieves the total badge count for all utilities
     * the current user is authorized to access.
     */
    public function getUtilitiesBadgeCount(): int
    {
        return $this->getAuthorizedUtilityTypes()
            /** @var class-string<Utility> $class */
            ->sum(fn (string $class) => $class::badgeCount());
    }
}
