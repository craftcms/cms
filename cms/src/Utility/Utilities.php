<?php

namespace CraftCms\Cms\Utility;

use craft\queue\QueueInterface;
use craft\web\Application;
use CraftCms\Cms\CmsEdition;
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
use Illuminate\Container\Attributes\Give;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

/**
 * The Utilities service provides APIs for managing utilities.
 *

 * @since 6.0.0
 */
#[Singleton]
final readonly class Utilities
{
    public function __construct(
        #[Give('Craft')] protected Application $craft,
    ) {}

    /**
     * Returns all available utility type classes.
     *
     * @return Collection<class-string<Utility>>
     */
    public function getAllUtilityTypes(): Collection
    {
        $generalConfig = $this->craft->getConfig()->getGeneral();

        $utilityTypes = Collection::make()
            ->push(
                UpdatesUtility::class,
                SystemReport::class,
                ProjectConfigUtility::class,
                PhpInfo::class,
            )
            ->when(
                $this->craft->edition->value >= CmsEdition::Pro->value,
                fn (Collection $c) => $c->push(SystemMessagesUtility::class)
            )
            ->when(
                ! empty($this->craft->getVolumes()->getAllVolumes()),
                fn (Collection $c) => $c->push(AssetIndexes::class)
            )
            ->when(
                $this->craft->getQueue() instanceof QueueInterface,
                fn (Collection $c) => $c->push(QueueManager::class)
            )
            ->push(
                ClearCaches::class,
                DeprecationErrors::class,
            )
            ->when(
                $generalConfig->backupCommand !== false,
                fn (Collection $c) => $c->push(DbBackup::class)
            )
            ->push(
                FindAndReplace::class,
                Migrations::class,
            );

        if (Event::hasListeners(RegisterUtilities::class)) {
            Event::dispatch($event = new RegisterUtilities($utilityTypes));
            $utilityTypes = $event->types;
        }

        $disabledUtilities = array_flip($generalConfig->disabledUtilities);

        return $utilityTypes
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
        /** @var ?\CraftCms\Cms\User\Models\User $user */
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
        if (in_array($utilityId, $this->craft->getConfig()->getGeneral()->disabledUtilities)) {
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
