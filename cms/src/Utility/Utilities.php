<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace CraftCms\Cms\Utility;

use Craft;
use craft\enums\CmsEdition;
use craft\queue\QueueInterface;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

/**
 * The Utilities service provides APIs for managing utilities.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 6.0.0
 */
class Utilities
{
    /**
     * Returns all available utility type classes.
     *
     * @return Collection<class-string<Utility>>
     */
    public function getAllUtilityTypes(): Collection
    {
        /** @var \craft\web\Application $craft */
        $craft = app('Craft');

        $generalConfig = $craft->getConfig()->getGeneral();

        $utilityTypes = Collection::make()
            ->push(
                UpdatesUtility::class,
                SystemReport::class,
                ProjectConfigUtility::class,
                PhpInfo::class,
            )
            ->when(
                $craft->edition->value >= CmsEdition::Pro->value,
                fn (Collection $c) => $c->push(SystemMessagesUtility::class)
            )
            ->when(
                ! empty($craft->getVolumes()->getAllVolumes()),
                fn (Collection $c) => $c->push(AssetIndexes::class)
            )
            ->when(
                $craft->getQueue() instanceof QueueInterface,
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
        /** @var \craft\web\Application $craft */
        $craft = app('Craft');

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
        if (in_array($utilityId, $craft->getConfig()->getGeneral()->disabledUtilities)) {
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
}
