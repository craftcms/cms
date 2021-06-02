<?php
declare(strict_types=1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\authentication\Chain;
use craft\authentication\type\mfa\AuthenticatorCode;
use craft\authentication\type\mfa\EmailCode;
use craft\authentication\type\mfa\WebAuthn;
use craft\elements\User;
use craft\models\AuthenticationChainConfiguration;
use craft\models\AuthenticationState;
use craft\records\AuthAuthenticator;
use yii\base\Component;
use yii\base\InvalidConfigException;

class Authentication extends Component
{
    public const AUTHENTICATION_STATE_KEY = 'craft.authentication.state';

    public const CONFIG_AUTH_CHAINS = 'authentication-chains';
    public const CP_AUTHENTICATION_CHAIN = 'cpLogin';
    public const CP_RECOVERY_CHAIN = 'cpRecovery';

    /**
     * Return an authentication chain based on a scenario.
     *
     * @param string $scenario
     * @return Chain
     * @throws InvalidConfigException
     */
    public function getAuthenticationChain(string $scenario, $forceNew = false): Chain
    {
        $chainConfig = $this->getScenarioConfiguration($scenario);

        if (!$chainConfig) {
            throw new InvalidConfigException("Unable to configure authentication chain for `$scenario`");
        }

        $state = $forceNew ? Craft::createObject(AuthenticationState::class, [['authenticationScenario' => $scenario]]) : $this->getAuthenticationState($scenario);

        /** @var Chain $chain */
        $chain = Craft::createObject(Chain::class, [$state, $chainConfig->steps]);

        return $chain;
    }

    /**
     * Return a list of all the multi-factor authentication types.
     * @return string[]
     */
    public function getMfaTypes(): array
    {
        // TODO event here
        return [
            AuthenticatorCode::class,
            WebAuthn::class,
            EmailCode::class
        ];
    }

    /**
     * Get the authentication chain for control panel login.
     *
     * @param bool $forceNew whether a new state should be forced.
     * @return Chain
     * @throws InvalidConfigException
     */
    public function getCpAuthenticationChain($forceNew = false): Chain
    {
        return $this->getAuthenticationChain(self::CP_AUTHENTICATION_CHAIN, $forceNew);
    }

    /**
     * Get the recovery chain for control panel login.
     *
     * @param bool $forceNew whether a new state should be forced.
     * @return Chain
     * @throws InvalidConfigException
     */
    public function getCpRecoveryChain($forceNew = false): Chain
    {
        return $this->getAuthenticationChain(self::CP_RECOVERY_CHAIN, $forceNew);
    }

    /**
     * Get scenario configuration for a give scenario.
     *
     * @param string $scenario
     * @return AuthenticationChainConfiguration|null
     */
    public function getScenarioConfiguration(string $scenario): ?AuthenticationChainConfiguration
    {
        $scenarios = Craft::$app->getProjectConfig()->get(self::CONFIG_AUTH_CHAINS);
        return $scenarios[$scenario] ? new AuthenticationChainConfiguration($scenarios[$scenario]) :  null;
    }

    /**
     * Get the current authentication state for a scenario.
     *
     * @param string $scenario
     * @return AuthenticationState
     */
    public function getAuthenticationState(string $scenario): AuthenticationState
    {
        $authStates = $this->getAllAuthenticationStates();
        $stateData = $authStates[$scenario] ?? ['authenticationScenario' => $scenario];

        /** @var AuthenticationState $state */
        $state = Craft::createObject(AuthenticationState::class, [$stateData]);

        return $state;
    }

    /**
     * Store an authentication state in the session.
     *
     * @param AuthenticationState $state
     */
    public function storeAuthenticationState(AuthenticationState $state): void
    {
        $session = Craft::$app->getSession();
        $scenario = $state->getAuthenticationScenario();

        $authStates = $this->getAllAuthenticationStates();
        $authStates[$scenario] = $state->exportState();
        $session->set(self::AUTHENTICATION_STATE_KEY, $authStates);
    }

    /**
     * Invalidate all authentication states for the session.
     */
    public function invalidateAllAuthenticationState(): void
    {
        Craft::$app->getSession()->remove(self::AUTHENTICATION_STATE_KEY);
    }

    /**
     * Invalidate an authentication state by scenario.
     *
     * @param string $scenario
     */
    public function invalidateAuthenticationState(string $scenario): void
    {
        $states = $this->getAllAuthenticationStates();
        unset($states[$scenario]);
        Craft::$app->getSession()->set(self::AUTHENTICATION_STATE_KEY, $states);
    }

    /**
     * Return all active authentication states.
     *
     * @return mixed
     */
    private function getAllAuthenticationStates(): array
    {
        return Craft::$app->getSession()->get(self::AUTHENTICATION_STATE_KEY, []);
    }
}
