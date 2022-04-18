<?php

declare(strict_types=1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\authentication\base\ElevatedSessionTypeInterface;
use craft\authentication\base\MfaTypeInterface;
use craft\authentication\base\UserConfigurableTypeInterface;
use craft\authentication\State;
use craft\authentication\type\AuthenticatorCode;
use craft\authentication\type\EmailCode;
use craft\authentication\type\Password;
use craft\authentication\type\WebAuthn;
use craft\elements\User;
use craft\errors\AuthenticationException;
use craft\events\AuthenticationEvent;
use craft\events\RegisterComponentTypesEvent;
use yii\base\Component;

/**
 *
 * @property-read null|State $authState
 * @property-read bool $isWebAuthnAllowed
 * @property-read string[] $allStepTypes
 * @property-read string[] $mfaTypes
 * @property-read array $elevatedSessionTypes
 * @property-read string[] $userConfigurableTypes
 */
class Authentication extends Component
{
    public const AUTHENTICATION_STATE_KEY = 'craft.authentication.state';

    /**
     * @event AuthenticationEvent The event that is triggered before an auth flow is configured for the user.
     * @see getAuthFlow()
     * @since 4.0.0
     */
    public const EVENT_BEFORE_CONSTRUCT_AUTH_FLOW = 'beforeConfigureAuthFlow';

    /**
     * @event AuthenticationEvent The event that is triggered after the auth flow has been configured for the user.
     * @see getAuthFlow()
     * @since 4.0.0
     */
    public const EVENT_AFTER_CONSTRUCT_AUTH_FLOW = 'afterConfigureAuthFlow';

    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering auth step types.
     * @see getAllStepTypes()
     * @since 4.0.0
     */
    public const EVENT_REGISTER_AUTH_STEP_TYPES = 'registerAuthStepTypes';

    /**
     * A list of all the authentication step types.
     *
     * @var array|null
     */
    private ?array $_stepTypes = null;

    /**
     * Authentication state.
     *
     * @var State|null
     */
    private ?State $_state = null;

    public function getAuthFlow(?User $user = null): array
    {
        // Fire a 'beforeConfigureAuthFlow' event
        $event = new AuthenticationEvent([
            'flow' => [],
        ]);
        $this->trigger(self::EVENT_BEFORE_CONSTRUCT_AUTH_FLOW, $event);

        $flow = $event->flow;

        if ($user && $this->isWebAuthnAvailable($user)) {
            $flow[] = [
                'type' => WebAuthn::class,
            ];
        }

        $authentication = [
            'type' => Password::class,
        ];

        if ($user && $this->isMfaRequired($user)) {
            $availableTypes = $this->getAvailableMfaTypes($user);

            if (empty($availableTypes)) {
                throw new AuthenticationException('Unable to find a supported MFA authentication step type, but it is required.');
            }

            $authentication['then'] = array_map(static fn($type) => ['type' => $type], $availableTypes);
        }

        $flow[] = $authentication;

        // Fire a 'afterConfigureAuthFlow' event
        $event = new AuthenticationEvent([
            'flow' => $flow,
        ]);
        $this->trigger(self::EVENT_AFTER_CONSTRUCT_AUTH_FLOW, $event);

        return $flow;
    }

    /**
     * Returns true if WebAuthn credentials are available for a given user.
     *
     * @param User $user
     * @return bool
     */
    public function isWebAuthnAvailable(User $user): bool
    {
        return $this->getIsWebAuthnAllowed() && WebAuthn::getIsApplicable($user);
    }

    /**
     * Returns true if MFA is required for a given user.
     *
     * @param User $user
     * @return bool
     */
    public function isMfaRequired(User $user): bool
    {
        if ($user->enable2fa || $user->hasAuthenticatorSecret()) {
            return true;
        }

        $require2fa = Craft::$app->getProjectConfig()->get(ProjectConfig::PATH_USERS)['require2fa'] ?? [];

        if (in_array('everyone', $require2fa, true)) {
            return true;
        }

        if ($user->admin && in_array('admin', $require2fa, true)) {
            return true;
        }

        $userGroups = $user->getGroups();
        foreach ($userGroups as $userGroup) {
            if (in_array($userGroup->handle, $require2fa, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return a list of all the multi-factor authentication step types.
     *
     * @return string[]
     */
    public function getMfaTypes(): array
    {
        return array_filter($this->getAllStepTypes(), static fn($type) => is_subclass_of($type, MfaTypeInterface::class));
    }

    /**
     * Return a list of all the authentication step types that must be configured by the user.
     *
     * @return string[]
     */
    public function getUserConfigurableTypes(): array
    {
        return array_filter($this->getAllStepTypes(), static fn($type) => is_subclass_of($type, UserConfigurableTypeInterface::class));
    }

    /**
     * Return a list of all the authentication step types that can be used when elevating a session.
     *
     * @return array
     */
    public function getElevatedSessionTypes(): array
    {
        return array_filter($this->getAllStepTypes(), static fn($type) => is_subclass_of($type, ElevatedSessionTypeInterface::class));
    }

    /**
     * @return string[]
     */
    public function getAllStepTypes(): array
    {
        if (!is_null($this->_stepTypes)) {
            return $this->_stepTypes;
        }

        $stepTypes = [
            WebAuthn::class,
            Password::class,
            AuthenticatorCode::class,
            EmailCode::class,
        ];

        $event = new RegisterComponentTypesEvent([
            'types' => $stepTypes,
        ]);

        $this->trigger(self::EVENT_REGISTER_AUTH_STEP_TYPES, $event);


        return $this->_stepTypes = $event->types;
    }

    /**
     * Return an array of all the available mfa types for a given user.
     *
     * @param User $user
     * @return array
     */
    public function getAvailableMfaTypes(User $user): array
    {
        $availableTypes = [];

        foreach ($this->getMfaTypes() as $type) {
            /** @var MfaTypeInterface $type */
            if ($type::isAvailableForUser($user)) {
                $availableTypes[] = $type;
            }
        }

        return $availableTypes;
    }

    /**
     * Get the current authentication state
     *
     * @return State|null
     */
    public function getAuthState(): ?State
    {
        if ($this->_state) {
            return $this->_state;
        }

        $session = Craft::$app->getSession();
        $serializedState = $session->get(self::AUTHENTICATION_STATE_KEY);

        if ($serializedState) {
            $this->_state = unserialize($serializedState, [State::class, User::class]);
        } else {
            $this->_state = Craft::createObject(State::class, [
                'authFlow' => $this->getAuthFlow(),
            ]);
        }

        return $this->_state;
    }

    /**
     * Store an authentication state in the session.
     *
     * @param State $state
     */
    public function persistAuthenticationState(State $state): void
    {
        $this->_state = $state;
        $session = Craft::$app->getSession();
        $session->set(self::AUTHENTICATION_STATE_KEY, serialize($state));
    }

    /**
     * Invalidate all authentication states for the session.
     */
    public function invalidateAuthenticationState(): void
    {
        $this->_state = null;
        Craft::$app->getSession()->remove(self::AUTHENTICATION_STATE_KEY);
    }

    /**
     * Returns whether WebAuthn is allowed on this environment.
     *
     * @return bool
     */
    protected function getIsWebAuthnAllowed()
    {
        return (bool)(Craft::$app->getProjectConfig()->get(ProjectConfig::PATH_USERS)['allowWebAuthn'] ?? false);
    }
}
