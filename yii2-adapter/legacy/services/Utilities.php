<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Component;
use craft\base\Event;
use craft\base\UtilityInterface;
use craft\events\RegisterComponentTypesEvent;

/**
 * The Utilities service provides APIs for managing utilities.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 * @deprecated in 6.0.0. [[\CraftCms\Cms\Utility\Utilities]] should be used instead.
 */
class Utilities extends Component
{
    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering utilities.
     *
     * Utilities must implement [[UtilityInterface]]. [[\craft\base\Utility]] provides a base implementation.
     *
     * Read more about creating utilities in the [documentation](https://craftcms.com/docs/5.x/extend/utilities.html).
     * ---
     * ```php
     * use craft\events\RegisterComponentTypesEvent;
     * use craft\services\Utilities;
     * use yii\base\Event;
     *
     * Event::on(Utilities::class,
     *     Utilities::EVENT_REGISTER_UTILITIES,
     *     function(RegisterComponentTypesEvent $event) {
     *         $event->types[] = MyUtilityType::class;
     *     }
     * );
     * ```
     */
    public const EVENT_REGISTER_UTILITIES = 'registerUtilities';

    private \CraftCms\Cms\Utility\Utilities $utilities;

    public function init(): void
    {
        parent::init();

        $this->utilities = app(\CraftCms\Cms\Utility\Utilities::class);
    }

    public function getAllUtilityTypes(): array
    {
        return $this->utilities
            ->getAllUtilityTypes()
            ->values()
            ->all();
    }

    /**
     * Returns all utility type classes that the user has permission to use.
     *
     * @return array<class-string<\CraftCms\Cms\Utility\Utility>>
     */
    public function getAuthorizedUtilityTypes(): array
    {
        return $this->utilities->getAuthorizedUtilityTypes()->values()->all();
    }

    /**
     * Returns whether the current user is authorized to use a given utility.
     *
     * @param  class-string<UtilityInterface>  $class  The utility class
     */
    public function checkAuthorization(string $class): bool
    {
        /** @phpstan-ignore-next-line */
        return $this->utilities->checkAuthorization($class);
    }

    /**
     * Returns a utility class by its ID
     *
     * @return class-string<UtilityInterface>|null
     */
    public function getUtilityTypeById(string $id): ?string
    {
        /** @phpstan-ignore-next-line */
        return $this->utilities->getUtilityTypeById($id);
    }
}
