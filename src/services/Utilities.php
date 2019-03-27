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
use craft\utilities\AssetIndexes;
use craft\utilities\ClearCaches;
use craft\utilities\DbBackup;
use craft\utilities\DeprecationErrors;
use craft\utilities\FindAndReplace;
use craft\utilities\Migrations;
use craft\utilities\PhpInfo;
use craft\utilities\SearchIndexes;
use craft\utilities\SystemMessages as SystemMessagesUtility;
use craft\utilities\SystemReport;
use craft\utilities\Updates as UpdatesUtility;
use yii\base\Component;

/**
 * The Utilities service provides APIs for managing utilities.
 * An instance of the Utilities service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getUtilities()|`Craft::$app->utilities()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Utilities extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering utility types.
     *
     * Utility types must implement [[UtilityInterface]]. [[\craft\base\Utility]] provides a base implementation.
     *
     * See [Utility Types](https://docs.craftcms.com/v3/utility-types.html) for documentation on creating utility types.
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
    const EVENT_REGISTER_UTILITY_TYPES = 'registerUtilityTypes';

    // Public Methods
    // =========================================================================

    /**
     * Returns all available utility type classes.
     *
     * @return string[]
     */
    public function getAllUtilityTypes(): array
    {
        $utilityTypes = [
            UpdatesUtility::class,
            SystemReport::class,
            PhpInfo::class,
        ];

        if (Craft::$app->getEdition() === Craft::Pro) {
            $utilityTypes[] = SystemMessagesUtility::class;
        }

        $utilityTypes[] = SearchIndexes::class;

        if (!empty(Craft::$app->getVolumes()->getAllVolumes())) {
            $utilityTypes[] = AssetIndexes::class;
        }

        $utilityTypes[] = ClearCaches::class;
        $utilityTypes[] = DeprecationErrors::class;
        $utilityTypes[] = DbBackup::class;
        $utilityTypes[] = FindAndReplace::class;
        $utilityTypes[] = Migrations::class;

        $event = new RegisterComponentTypesEvent([
            'types' => $utilityTypes
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
     * @return bool
     */
    public function checkAuthorization(string $class): bool
    {
        /** @var string|UtilityInterface $class */
        return Craft::$app->getUser()->checkPermission('utility:' . $class::id());
    }

    /**
     * Returns a utility class by its ID
     *
     * @param string $id
     * @return string|null
     */
    public function getUtilityTypeById(string $id)
    {
        foreach ($this->getAllUtilityTypes() as $class) {
            /** @var UtilityInterface $class */
            if ($class::id() === $id) {
                return $class;
            }
        }

        return null;
    }
}
