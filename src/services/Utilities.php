<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\UtilityInterface;
use craft\events\RegisterComponentTypesEvent;
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
use yii\base\Component;

/**
 * The Utilities service provides APIs for managing utilities.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getUtilities()|`Craft::$app->utilities()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Utilities extends Component
{
    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering utility types.
     *
     * Utility types must implement [[UtilityInterface]]. [[\craft\base\Utility]] provides a base implementation.
     *
     * See [Utility Types](https://craftcms.com/docs/4.x/extend/utility-types.html) for documentation on creating utility types.
     * ---
     * ```php
     * use craft\events\RegisterComponentTypesEvent;
     * use craft\services\Utilities;
     * use yii\base\Event;
     *
     * Event::on(Utilities::class,
     *     Utilities::EVENT_REGISTER_UTILITY_TYPES,
     *     function(RegisterComponentTypesEvent $event) {
     *         $event->types[] = MyUtilityType::class;
     *     }
     * );
     * ```
     */
    public const EVENT_REGISTER_UTILITY_TYPES = 'registerUtilityTypes';

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

        if (Craft::$app->getEdition() === Craft::Pro) {
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
        $utilityTypes[] = DbBackup::class;
        $utilityTypes[] = FindAndReplace::class;
        $utilityTypes[] = Migrations::class;

        $event = new RegisterComponentTypesEvent([
            'types' => $utilityTypes,
        ]);
        $this->trigger(self::EVENT_REGISTER_UTILITY_TYPES, $event);

        return $event->types;
    }

    /**
     * Returns all utility type classes that the user has permission to use.
     *
     * @return string[]
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
     * @param string $class The utility class
     * @phpstan-param class-string<UtilityInterface> $class
     * @return bool
     */
    public function checkAuthorization(string $class): bool
    {
        /** @var string|UtilityInterface $class */
        /** @phpstan-var class-string<UtilityInterface>|UtilityInterface $class */
        $utilityId = $class::id();
        $user = Craft::$app->getUser();

        return $utilityId === ProjectConfigUtility::id() ? $user->getIsAdmin() : $user->checkPermission('utility:' . $utilityId);
    }

    /**
     * Returns a utility class by its ID
     *
     * @param string $id
     * @return string|null
     */
    public function getUtilityTypeById(string $id): ?string
    {
        foreach ($this->getAllUtilityTypes() as $class) {
            /** @var string|UtilityInterface $class */
            /** @phpstan-var class-string<UtilityInterface>|UtilityInterface $class */
            if ($class::id() === $id) {
                return $class;
            }
        }

        return null;
    }
}
