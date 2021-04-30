<?php
declare(strict_types=1);

namespace craft\authentication;

use Craft;
use craft\authentication\base\TypeInterface;
use craft\elements\User;
use craft\helpers\Authentication;
use craft\models\AuthenticationState;
use yii\base\InvalidConfigException;

class Chain
{
    private array $_steps;
    private AuthenticationState $_state;
    private ?string $_recoveryScenario;

    /**
     * Authentication chain constructor.
     *
     * @param AuthenticationState $state Current state of authentication
     * @param array $steps A list of steps that have to be completed.
     * @param ?string $recoveryScenario A recovery scenario, if there is one.
     */
    public function __construct(AuthenticationState $state, array $steps, ?string $recoveryScenario)
    {
        $this->_steps = $steps;
        $this->_state = $state;
        $this->_recoveryScenario = $recoveryScenario;
    }

    /**
     * Returns `true` if a given authentication chain is completed successfully.
     *
     * @return bool
     */
    public function getIsComplete(): bool
    {
        return $this->_getLastCompletedStepType() === (end($this->_steps)['type'] ?? null);
    }

    /**
     * Returns `true` if no steps have been completed yet.
     *
     * @return bool
     */
    public function getIsNew(): bool
    {
        return $this->_getLastCompletedStepType() === null;
    }

    /**
     * Returns the authenticated user if the chain is complete.
     *
     * @return User|null
     */
    public function getAuthenticatedUser(): ?User
    {
        return $this->getIsComplete() ? $this->_getResolvedUser() : null;
    }

    /**
     * Return the name of the recovery scenario for this authentication chain.
     *
     * @return string|null
     */
    public function getRecoveryScenario(): ?string
    {
        return $this->_recoveryScenario;
    }

    /**
     * Return the recovery chain, if configured.
     *
     * @return Chain|null
     */
    public function getRecoveryChain(): ?Chain
    {
        if (!$this->getRecoveryScenario()) {
            return null;
        }

        return Craft::$app->getAuthentication()->getAuthenticationChain($this->getRecoveryScenario());
    }

    /**
     * Perform an authentication step.
     *
     * @param array $credentials
     * @return bool `true`, if at least one step was successfully performed.
     * @throws InvalidConfigException If unable to determine the next authentication step and chain is not complete.
     */
    public function performAuthenticationStep(array $credentials = []): bool
    {
        /** @var TypeInterface $nextStep */
        if ($nextStep = $this->getNextAuthenticationStep()) {
            $this->_state = $nextStep->authenticate($credentials, $this->_getResolvedUser());

            // Write it down
            Craft::$app->getAuthentication()->storeAuthenticationState($this->_state);

            // If advanced in chain
            $success = $this->_getLastCompletedStepType() === get_class($nextStep);

            if ($success && !$this->getIsComplete()) {
                // Prepare the next step.
                /** @var TypeInterface $nextStep */
                $nextStep = $this->getNextAuthenticationStep();
                $nextStep->prepareForAuthentication($this->_getResolvedUser());

                // If next step is not interactive, repeat
                if (!$nextStep->getRequiresInput()) {
                    // Intentionally not use the return result
                    $this->performAuthenticationStep();
                }
            }

            return $success;
        }

        return true;
    }

    /**
     * Get next authentication step.
     *
     * @return TypeInterface|null
     * @throws InvalidConfigException if chain is not complete, yet all the steps are done.
     */
    public function getNextAuthenticationStep(): ?TypeInterface
    {
        if ($this->getIsComplete()) {
            return null;
        }

        $lastCompleted = $this->_getLastCompletedStepType();

        // If no steps performed, return the first one
        if (!$lastCompleted) {
            return Authentication::createTypeFromConfig(reset($this->_steps), $this->_state);
        }

        foreach ($this->_steps as $index => $authenticationStep) {
            // If the current step was the last completed
            if ($authenticationStep['type'] === $lastCompleted) {
                // Return the next step. This should never be false, as it's covered by checking if chain is complete
                return Authentication::createTypeFromConfig($this->_steps[$index + 1], $this->_state);
            }
        }

        throw new InvalidConfigException("Unterminated authentication chain - {$this->_state->getAuthenticationScenario()}, last completed step - {$this->_getLastCompletedStepType()}");
    }

    /**
     * Get the last completed authentication step.
     *
     * @return string|null
     */
    private function _getLastCompletedStepType(): ?string
    {
        return $this->_state->getLastCompletedStepType();
    }

    /**
     * Get the resolved user.
     *
     * @return User|null
     */
    private function _getResolvedUser(): ?User
    {
        return $this->_state->getResolvedUser();
    }
}
