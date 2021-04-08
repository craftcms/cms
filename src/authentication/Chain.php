<?php
declare(strict_types=1);

namespace craft\authentication;

use Craft;
use craft\elements\User;
use craft\models\AuthenticationState;
use yii\base\InvalidConfigException;

class Chain
{
    private array $_steps;
    private AuthenticationState $_state;

    /**
     * Authentication chain constructor.
     *
     * @param AuthenticationState $state
     * @param array $steps
     */
    public function __construct(AuthenticationState $state, array $steps)
    {
        $this->_steps = $steps;
        $this->_state = $state;
    }

    /**
     * Returns `true` if a given authentication chain is completed successfully.
     *
     * @return bool
     */
    public function getIsComplete(): bool
    {
        return $this->_state->getLastCompletedStep() === end($this->_steps);
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
     * Perform an authentication step.
     *
     * @param array $credentials
     * @throws InvalidConfigException If unable to determine the next authentication step and chain is not complete.
     */
    public function performAuthenticationStep(array $credentials = []): void
    {
        /** @var StepInterface $nextStep */
        if ($nextStep = $this->getNextAuthenticationStep()) {
            $this->_state = $nextStep->authenticate($credentials, $this->_getResolvedUser());

            // Write it down
            Craft::$app->getAuthentication()->storeAuthenticationState($this->_state);

            // If advanced in chain
            if (($this->_getLastCompletedStep() === get_class($nextStep)) && !$this->getIsComplete()) {
                // Prepare the next step.
                /** @var StepInterface $nextStep */
                $nextStep = $this->getNextAuthenticationStep();
                $nextStep->prepareForAuthentication();

                // If next step is not interactive, repeat
                if (!$nextStep->getRequiresInput()) {
                    $this->performAuthenticationStep();
                }
            }
        }
    }

    /**
     * Get the authentication message passed back from the authentication step.
     *
     * @return string|null
     */
    public function getChainAuthenticationMessage(): ?string
    {
        return $this->_state->getAuthenticationMessage();
    }

    /**
     * Get next authentication step.
     *
     * @return StepInterface|null
     * @throws InvalidConfigException if chain is not complete, yet all the steps are done.
     */
    public function getNextAuthenticationStep(): ?StepInterface
    {
        if ($this->getIsComplete()) {
            return null;
        }

        $lastCompleted = $this->_getLastCompletedStep();

        // If no steps performed, return the first one
        if (!$lastCompleted) {
            /** @var StepInterface $nextStep */
            $nextStep = Craft::createObject(reset($this->_steps), [['state' => $this->_state]]);
            return $nextStep;
        }

        foreach ($this->_steps as $index => $authenticationStep) {
            // If the current step was the last completed
            if ($authenticationStep === $lastCompleted) {
                // Return the next step. This should never be false, as it's covered by checking if chain is complete
                /** @var StepInterface $nextStep */
                $nextStep = Craft::createObject($this->_steps[$index + 1], [['state' => $this->_state]]);
                return $nextStep;
            }
        }

        throw new InvalidConfigException("Unterminated authentication chain - {$this->_state->getAuthenticationScenario()}, last completed step - {$this->_getLastCompletedStep()}");
    }

    /**
     * Get the last completed authentication step.
     *
     * @return string|null
     */
    private function _getLastCompletedStep(): ?string
    {
        return $this->_state->getLastCompletedStep();
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
