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
use craft\events\AuthenticateUserEvent;
use craft\events\DefineValueEvent;
use CraftCms\Cms\Auth\Events\Authenticating;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Events\DefineFriendlyName;
use CraftCms\Cms\User\Events\DefineName;
use Illuminate\Support\Facades\Event;

/**
 * @deprecated 6.0.0 use {@see UserElement} instead.
 */
class User extends UserElement
{
    use ElementEventConstants;

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
