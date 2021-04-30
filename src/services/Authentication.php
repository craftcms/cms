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
use craft\models\AuthenticationChainConfiguration;
use craft\models\AuthenticationState;
use yii\base\Component;
use yii\base\InvalidConfigException;

class Authentication extends Component
{
    public const AUTHENTICATION_STATE_KEY = 'craft.authentication.state';

    public const CONFIG_AUTH_CHAINS = 'authentication-chains';
    public const CP_AUTHENTICATION_CHAIN = 'cpLogin';

    /**
     * Return an authentication chain based on a scenario.
     *
     * @param string $scenario
     * @return Chain
     * @throws InvalidConfigException
     */
    public function getAuthenticationChain(string $scenario): Chain
    {
        $chainConfig = $this->getScenarioConfiguration($scenario);

        if (!$chainConfig) {
            throw new InvalidConfigException("Unable to configure authentication chain for `$scenario`");
        }

        $state = $this->getAuthenticationState($scenario);

        /** @var Chain $chain */
        $chain = Craft::createObject(Chain::class, [$state, $chainConfig->steps , $chainConfig->recoveryScenario]);

        return $chain;
    }

    /**
     * Get the authentication chain for control panel login.
     *
     * @return Chain
     * @throws InvalidConfigException
     */
    public function getCpAuthenticationChain(): Chain
    {
        return $this->getAuthenticationChain(self::CP_AUTHENTICATION_CHAIN);
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
    public function invalidateAuthenticationState(string $scenario) : void
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
