<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace Craft\Cms\Utility;

use Craft;
use craft\base\UtilityInterface;
use Craft\Cms\Utility\Events\RegisterUtilities;
use craft\enums\CmsEdition;
use craft\queue\QueueInterface;
use craft\utilities\AssetIndexes;
use craft\utilities\ClearCaches;
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
use Illuminate\Support\Facades\Event;

/**
 * The Utilities service provides APIs for managing utilities.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 6.0.0
 */
class Utilities
{
    /**
     * Returns all available utility type classes.
     *
     * @return string[]
     * @phpstan-return class-string<UtilityInterface>[]
     */
    public function getAllUtilityTypes(): array
    {
        $utilityTypes = [
            UpdatesUtility::class,
            SystemReport::class,
            ProjectConfigUtility::class,
            PhpInfo::class,
        ];

        if (Craft::$app->edition->value >= CmsEdition::Pro->value) {
            $utilityTypes[] = SystemMessagesUtility::class;
        }

        if (!empty(Craft::$app->getVolumes()->getAllVolumes())) {
            $utilityTypes[] = AssetIndexes::class;
        }

        // Ensure we only implement queue if we can use the corresponding web based controller.
        if (Craft::$app->getQueue() instanceof QueueInterface) {
            $utilityTypes[] = QueueManager::class;
        }

        $utilityTypes[] = ClearCaches::class;
        $utilityTypes[] = DeprecationErrors::class;

        if (config('craft.general.backupCommand') !== false) {
            $utilityTypes[] = DbBackup::class;
        }

        $utilityTypes[] = FindAndReplace::class;
        $utilityTypes[] = Migrations::class;

        if (Event::hasListeners(RegisterUtilities::class)) {
            Event::dispatch($event = new RegisterUtilities($utilityTypes));
            $utilityTypes = $event->types;
        }

        $disabledUtilities = array_flip(config('craft.general.disabledUtilities'));

        return array_values(array_filter($utilityTypes, fn(string $class) =>
            /** @var class-string<UtilityInterface> $class */
            !isset($disabledUtilities[$class::id()]) && $class::isSelectable()));
    }

    /**
     * Returns all utility type classes that the user has permission to use.
     *
     * @return string[]
     * @phpstan-return class-string<UtilityInterface>[]
     */
    public function getAuthorizedUtilityTypes(): array
    {
        $utilityTypes = [];

        foreach ($this->getAllUtilityTypes() as $class) {
            if ($this->checkAuthorization($class)) {
                $utilityTypes[] = $class;
            }
        }

        return $utilityTypes;
    }

    /**
     * Returns whether the current user is authorized to use a given utility.
     *
     * @param class-string<UtilityInterface> $class The utility class
     * @return bool
     */
    public function checkAuthorization(string $class): bool
    {
        $utilityId = $class::id();

        // The Project Config utility is for admins only!
        if ($class === ProjectConfigUtility::class) {
            if (!Craft::$app->getUser()->getIsAdmin()) {
                return false;
            }
        } elseif (!Craft::$app->getUser()->checkPermission("utility:$utilityId")) {
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
     * @param string $id
     * @return class-string<UtilityInterface>|null
     */
    public function getUtilityTypeById(string $id): ?string
    {
        foreach ($this->getAllUtilityTypes() as $class) {
            /** @var class-string<UtilityInterface> $class */
            if ($class::id() === $id) {
                return $class;
            }
        }

        return null;
    }
}
