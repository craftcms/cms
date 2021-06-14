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
use craft\errors\AuthenticationStateException;
use craft\helpers\Authentication as AuthHelper;
use craft\models\authentication\Scenario;
use craft\models\authentication\State;
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

        /** @var Scenario $defaultBranch */
        $defaultBranch = $chainConfig->getDefaultBranchName();

        if ($forceNew || !($state = $this->getAuthenticationState($scenario))) {
            $state = AuthHelper::createAuthState($scenario, $defaultBranch);
        }

        /** @var Chain $chain */
        try {
            $chain = Craft::createObject(Chain::class, [$scenario, $state, $chainConfig->branches]);
        } catch (AuthenticationStateException $exception) {
            // Try with a fresh state
            Craft::$app->getErrorHandler()->logException($exception);
            $state = AuthHelper::createAuthState($scenario, $defaultBranch);
            $chain = Craft::createObject(Chain::class, [$state, $chainConfig->branches]);
        }

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
     * @return Scenario|null
     */
    public function getScenarioConfiguration(string $scenario): ?Scenario
    {
        $scenarios = Craft::$app->getProjectConfig()->get(self::CONFIG_AUTH_CHAINS);
        return $scenarios[$scenario] ? Craft::createObject(Scenario::class, [$scenarios[$scenario]]) :  null;
    }

    /**
     * Get the current authentication state for a scenario.
     *
     * @param string $scenario
     * @return State
     */
    public function getAuthenticationState(string $scenario): ?State
    {
        $authStates = $this->getAllAuthenticationStates();

        return !empty($authStates[$scenario]) ? Craft::createObject(State::class, [$authStates[$scenario]]) : null;
    }

    /**
     * Store an authentication state in the session.
     *
     * @param State $state
     */
    public function storeAuthenticationState(State $state): void
    {
        $session = Craft::$app->getSession();
        $scenario = $state->getAuthenticationScenario();

        // Only store one authentication state at a time.
        $authStates = [$scenario => $state->exportState()];
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
