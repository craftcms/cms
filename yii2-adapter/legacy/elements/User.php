<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use craft\base\ElementEventConstants;
use craft\base\Event as YiiEvent;
use craft\base\LegacyEventConstants;
use craft\events\AuthenticateUserEvent;
use craft\events\DefineValueEvent;
use CraftCms\Cms\Auth\Events\Authenticating;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Events\DefineFriendlyName;
use CraftCms\Cms\User\Events\DefineName;
use CraftCms\Cms\User\Validation\UserRules;
use Deprecated;
use Illuminate\Support\Facades\Event;

/**
 * @deprecated 6.0.0 use {@see UserElement} instead.
 */
class User extends UserElement
{
    use LegacyEventConstants;
    use ElementEventConstants;

    public const string SCENARIO_DEFAULT = ElementRules::SCENARIO_DEFAULT;

    public const string SCENARIO_ESSENTIALS = ElementRules::SCENARIO_ESSENTIALS;

    public const string SCENARIO_LIVE = ElementRules::SCENARIO_LIVE;

    public const string SCENARIO_ACTIVATION = UserRules::SCENARIO_ACTIVATION;

    public const string SCENARIO_REGISTRATION = UserRules::SCENARIO_REGISTRATION;

    public const string SCENARIO_PASSWORD = UserRules::SCENARIO_PASSWORD;

    /**
     * @event DefineValueEvent The event that is triggered when defining the user’s name, as returned by [[getName()]] or [[__toString()]].
     *
     * @since 3.7.0
     */
    public const string EVENT_DEFINE_NAME = 'defineName';

    /**
     * @event DefineValueEvent The event that is triggered when defining the user’s friendly name, as returned by [[getFriendlyName()]].
     *
     * @since 3.7.0
     */
    public const string EVENT_DEFINE_FRIENDLY_NAME = 'defineFriendlyName';

    /**
     * @event AuthenticateUserEvent The event that is triggered before a user is authenticated.
     *
     * If you wish to offload authentication logic, then set [[AuthenticateUserEvent::$performAuthentication]] to `false`, and set [[$authError]] to
     * something if there is an authentication error.
     */
    public const string EVENT_BEFORE_AUTHENTICATE = 'beforeAuthenticate';

    /**
     * Returns the user’s full name.
     */
    #[Deprecated(message: 'in 4.0.0. [[fullName]] should be used instead.')]
    #[AllowedInSandbox]
    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public static function registerEvents(): void
    {
        Event::listen(function(DefineName $event) {
            if (YiiEvent::hasHandlers(self::class, self::EVENT_DEFINE_NAME)) {
                $yiiEvent = new DefineValueEvent();
                $yiiEvent->sender = $event->user;

                YiiEvent::trigger(self::class, self::EVENT_DEFINE_NAME, $yiiEvent);

                if ($yiiEvent->value !== null) {
                    $event->name = $yiiEvent->value;
                }
            }
        });

        Event::listen(function(DefineFriendlyName $event) {
            if (YiiEvent::hasHandlers(self::class, self::EVENT_DEFINE_FRIENDLY_NAME)) {
                $yiiEvent = new DefineValueEvent();
                $yiiEvent->sender = $event->user;

                YiiEvent::trigger(self::class, self::EVENT_DEFINE_FRIENDLY_NAME, $yiiEvent);

                if ($yiiEvent->value !== null) {
                    $event->name = $yiiEvent->value;
                }
            }
        });

        Event::listen(Authenticating::class, function(Authenticating $event) {
            if (YiiEvent::hasHandlers(self::class, self::EVENT_BEFORE_AUTHENTICATE)) {
                $yiiEvent = new AuthenticateUserEvent(['password' => $event->credentials['password']]);

                YiiEvent::trigger(self::class, self::EVENT_BEFORE_AUTHENTICATE, $yiiEvent);

                $event->performAuthentication = $yiiEvent->performAuthentication;
            }
        });
    }
}
