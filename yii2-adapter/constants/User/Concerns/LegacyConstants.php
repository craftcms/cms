<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Concerns;

use craft\base\ElementEventConstants;
use craft\base\Event as YiiEvent;
use craft\elements\User;
use craft\events\AuthenticateUserEvent;
use craft\events\DefineValueEvent;
use CraftCms\Cms\Auth\Events\UserAuthenticating;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use CraftCms\Cms\User\Events\UserFriendlyNameResolving;
use CraftCms\Cms\User\Events\UserNameResolving;
use CraftCms\Cms\User\Validation\UserRules;
use Deprecated;
use Illuminate\Support\Facades\Event;

/**
 * @internal
 * @deprecated 6.0.0
 * @phpstan-ignore trait.unused
 */
trait LegacyConstants
{
    use ElementEventConstants;

    public const string EVENT_DEFINE_BEHAVIORS = 'defineBehaviors';

    /** @deprecated 6.0.0 use {@see ElementRules::SCENARIO_DEFAULT} instead. */
    public const string SCENARIO_DEFAULT = ElementRules::SCENARIO_DEFAULT;

    /** @deprecated 6.0.0 use {@see ElementRules::SCENARIO_ESSENTIALS} instead. */
    public const string SCENARIO_ESSENTIALS = ElementRules::SCENARIO_ESSENTIALS;

    /** @deprecated 6.0.0 use {@see ElementRules::SCENARIO_LIVE} instead. */
    public const string SCENARIO_LIVE = ElementRules::SCENARIO_LIVE;

    /** @deprecated 6.0.0 use {@see ElementRules::SCENARIO_ACTIVATION} instead. */
    public const string SCENARIO_ACTIVATION = UserRules::SCENARIO_ACTIVATION;

    /** @deprecated 6.0.0 use {@see ElementRules::SCENARIO_REGISTRATION} instead. */
    public const string SCENARIO_REGISTRATION = UserRules::SCENARIO_REGISTRATION;

    /** @deprecated 6.0.0 use {@see ElementRules::SCENARIO_PASSWORD} instead. */
    public const string SCENARIO_PASSWORD = UserRules::SCENARIO_PASSWORD;

    /**
     * @event DefineValueEvent The event that is triggered when defining the user’s name, as returned by [[getName()]] or [[__toString()]].
     *
     * @since 3.7.0
     * @deprecated 6.0.0
     */
    public const string EVENT_DEFINE_NAME = 'defineName';

    /**
     * @event DefineValueEvent The event that is triggered when defining the user’s friendly name, as returned by [[getFriendlyName()]].
     *
     * @since 3.7.0
     * @deprecated 6.0.0
     */
    public const string EVENT_DEFINE_FRIENDLY_NAME = 'defineFriendlyName';

    /**
     * @event AuthenticateUserEvent The event that is triggered before a user is authenticated.
     *
     * If you wish to offload authentication logic, then set [[AuthenticateUserEvent::$performAuthentication]] to `false`, and set [[$authError]] to
     * something if there is an authentication error.
     *
     * @deprecated 6.0.0
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
        Event::listen(function(UserNameResolving $event) {
            if (YiiEvent::hasHandlers(User::class, User::EVENT_DEFINE_NAME)) {
                $yiiEvent = new DefineValueEvent();
                $yiiEvent->sender = $event->user;

                YiiEvent::trigger(User::class, User::EVENT_DEFINE_NAME, $yiiEvent);

                if ($yiiEvent->value !== null) {
                    $event->name = $yiiEvent->value;
                }
            }
        });

        Event::listen(function(UserFriendlyNameResolving $event) {
            if (YiiEvent::hasHandlers(User::class, User::EVENT_DEFINE_FRIENDLY_NAME)) {
                $yiiEvent = new DefineValueEvent();
                $yiiEvent->sender = $event->user;

                YiiEvent::trigger(User::class, User::EVENT_DEFINE_FRIENDLY_NAME, $yiiEvent);

                if ($yiiEvent->value !== null) {
                    $event->name = $yiiEvent->value;
                }
            }
        });

        Event::listen(UserAuthenticating::class, function(UserAuthenticating $event) {
            if (YiiEvent::hasHandlers(User::class, User::EVENT_BEFORE_AUTHENTICATE)) {
                $yiiEvent = new AuthenticateUserEvent(['password' => $event->credentials['password']]);

                YiiEvent::trigger(User::class, self::EVENT_BEFORE_AUTHENTICATE, $yiiEvent);

                $event->performAuthentication = $yiiEvent->performAuthentication;
            }
        });
    }
}
