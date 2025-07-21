<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace Craft\Cms\Utility;

use Craft;
use Craft\Cms\Utility\Events\RegisterUtilities;
use Craft\Cms\Utility\Utilities\AssetIndexes;
use Craft\Cms\Utility\Utilities\ClearCaches;
use craft\enums\CmsEdition;
use craft\queue\QueueInterface;
use craft\utilities\DbBackup;
use craft\utilities\DeprecationErrors;
use craft\utilities\FindAndReplace;
use craft\utilities\Migrations;
use craft\utilities\PhpInfo;
use craft\utilities\ProjectConfig as ProjectConfigUtility;
use craft\utilities\QueueManager;
use craft\utilities\SystemMessages as SystemMessagesUtility;
use craft\utilities\SystemReport;
use craft\utilities\Updates as UpdatesUtility;
use Illuminate\Support\Collection;
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
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        $utilityTypes = Collection::make()
            ->push(
                UpdatesUtility::class,
                SystemReport::class,
                ProjectConfigUtility::class,
                PhpInfo::class,
            )
            ->when(
                Craft::$app->edition->value >= CmsEdition::Pro->value,
                fn (Collection $c) => $c->push(SystemMessagesUtility::class)
            )
            ->when(
                ! empty(Craft::$app->getVolumes()->getAllVolumes()),
                fn (Collection $c) => $c->push(AssetIndexes::class)
            )
            ->when(
                Craft::$app->getQueue() instanceof QueueInterface,
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
        // The Project Config utility is for admins only!
        if ($class === ProjectConfigUtility::class && ! Craft::$app->getUser()->getIsAdmin()) {
            return false;
        }

        $utilityId = $class::id();

        if (! Craft::$app->getUser()->checkPermission("utility:$utilityId")) {
            return false;
        }

        // Make sure the utility isn't disabled
        if (in_array($utilityId, Craft::$app->getConfig()->getGeneral()->disabledUtilities)) {
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
