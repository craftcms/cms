<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use craft\authenticators\LoginFormAuthenticator;
use craft\base\authenticators\AuthenticatorInterface;
use craft\base\Component;
use craft\events\RegisterComponentTypesEvent;
use yii\base\InvalidConfigException;

class Authentication extends Component
{
    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering element types.
     *
     * Authenticator types must implement [[AuthenticatorInterface]]. [[BaseAuthenticator]] provides an abstract implementation.
     *
     * ---
     * ```php
     * use craft\events\RegisterComponentTypesEvent;
     * use craft\services\Authentiation;
     * use yii\base\Event;
     *
     * Event::on(Authentication::class,
     *     Authentication::EVENT_REGISTER_AUTHENTICATOR_TYPES,
     *     function(RegisterComponentTypesEvent $event) {
     *         $event->types[] = MyAuthenticatorType::class;
     *     }
     * );
     * ```
     */
    const EVENT_REGISTER_AUTHENTICATOR_TYPES = 'registerAuthenticatorTypes';

    /**
     * Returns all available authenticator class strings.
     *
     * @return string[] Available authenticator classes.
     * @phpstan-return class-string<AuthenticatorInterface>[]
     */
    public function getAllAuthenticationTypes(): array
    {
        $authenticatorTypes = [
            LoginFormAuthenticator::class,
        ];

        $event = new RegisterComponentTypesEvent([
            'types' => $authenticatorTypes,
        ]);
        $this->trigger(self::EVENT_REGISTER_AUTHENTICATOR_TYPES, $event);

        return $event->types;
    }

    /**
     * Returns all available, authenticator classes.
     *
     * @return AuthenticatorInterface[]
     * @throws InvalidConfigException
     */
    public function getAllAuthenticators(): array
    {
        $authenticators = [];

        foreach ($this->getAllAuthenticationTypes() as $class) {
            $authenticator = \Craft::createObject($class);
            $authenticators[$authenticator->handle] = $authenticator;
        }

        return $authenticators;
    }

    /**
     * Return an authenticator by its handle.
     *
     * @param string $handle The authenticator handle.
     * @return AuthenticatorInterface|null
     * @throws InvalidConfigException
     */
    public function getAuthenticatorByHandle(string $handle): ?AuthenticatorInterface
    {
        foreach ($this->getAllAuthenticators() as $authenticator) {
            if ($handle === $authenticator->handle) {
                return $authenticator;
            }
        }
        return null;
    }

}