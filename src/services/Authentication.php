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
use craft\authentication\step\EmailCode;
use craft\authentication\step\Credentials;
use craft\authentication\step\IpAddress;
use craft\models\AuthenticationState;
use yii\base\Component;
use yii\base\InvalidConfigException;

class Authentication extends Component
{
    public const AUTHENTICATION_STATE_KEY = 'craft.authentication.state';

    /**
     * Return an authentication chain based on a scenario.
     *
     * @param string $scenario
     * @return Chain
     * @throws InvalidConfigException
     */
    public function getAuthenticationChain(string $scenario): Chain
    {
        if (!$steps = $this->getScenarioSteps($scenario)) {
            throw new InvalidConfigException("Unable to configure authentication chain for `$scenario`");
        }

        $state = $this->getAuthenticationState($scenario);
        /** @var Chain $chain */
        $chain = Craft::createObject(Chain::class, [$state, $steps]);

        return $chain;
    }

    /**
     * Get the current authentication state for a scenario.
     *
     * @param string $scenario
     * @return AuthenticationState
     */
    public function getAuthenticationState(string $scenario): AuthenticationState
    {
        $authStates = Craft::$app->getSession()->get(self::AUTHENTICATION_STATE_KEY, []);
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

        $authStates = $session->get(self::AUTHENTICATION_STATE_KEY, []);
        $authStates[$scenario] = $state->exportState();
        $session->set(self::AUTHENTICATION_STATE_KEY, $authStates);
    }

    /**
     * Invalidate all authentication states for the session.
     */
    public function invalidateAuthenticationState(): void
    {
        Craft::$app->getSession()->remove(self::AUTHENTICATION_STATE_KEY);
    }

    // TODO this is moving to a different home. Maybe database, as benefit for per-environment settings.
    public function getScenarioSteps(string $scenario): ?array
    {
        switch ($scenario) {
            case 'craftLogin':
                return [Credentials::class];
            case 'craft2FA':
                return [Credentials::class, IpAddress::class, EmailCode::class];
        }

        return null;
    }
}
