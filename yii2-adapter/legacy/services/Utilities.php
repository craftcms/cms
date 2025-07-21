<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Component;
use craft\base\Event;
use Craft\Cms\Utility\Events\RegisterUtilities;
use craft\events\RegisterComponentTypesEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event as EventFacade;

/**
 * The Utilities service provides APIs for managing utilities.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 6.0.0. [[\Craft\Cms\Utility\Utilities]] should be used instead.
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

    private Craft\Cms\Utility\Utilities $utilities;

    public function init(): void
    {
        parent::init();

        $this->utilities = app(Craft\Cms\Utility\Utilities::class);
    }

    public function getAllUtilityTypes(): array
    {
        return $this->utilities
            ->getAllUtilityTypes()
            ->values()
            ->all();
    }

    public static function registerEvents(): void
    {
        EventFacade::listen(RegisterUtilities::class, function(RegisterUtilities $event) {
            $yiiEvent = new RegisterComponentTypesEvent(['types' => $event->types->all()]);

            Craft::$app->getUtilities()->trigger(self::EVENT_REGISTER_UTILITIES, $yiiEvent);

            $event->types = Collection::make($yiiEvent->types);

            if ($yiiEvent->handled) {
                return false;
            }
        });
    }
}
