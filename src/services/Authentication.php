<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use craft\authenticators\LoginFormAuthenticator;
use craft\base\Component;
use craft\events\RegisterComponentTypesEvent;

class Authentication extends Component
{
    const EVENT_REGISTER_AUTHENTICATOR_TYPES = 'registerAuthenticatorTypes';

    public function getAllAuthenticationTypes(): array
    {
        $authenticatorTypes = [
            LoginFormAuthenticator::class
        ];

        $event = new RegisterComponentTypesEvent([
            'types' => $authenticatorTypes,
        ]);
        $this->trigger(self::EVENT_REGISTER_AUTHENTICATOR_TYPES, $event);

        return $event->types;
    }

    public function getAllAuthenticators(): array
    {
        $authenticators = [];

        foreach ($this->getAllAuthenticationTypes() as $class) {
            $authenticator = \Craft::createObject($class);
            $authenticators[$authenticator->handle] = $authenticator;
        }

        return $authenticators;
    }

}