<?php
declare(strict_types=1);

namespace craft\authentication;

use Craft;
use craft\authentication\base\TypeInterface;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\Authentication;
use craft\models\AuthenticationState;
use yii\base\InvalidConfigException;

class Chain
{
    private array $_steps;
    private AuthenticationState $_state;

    /**
     * Authentication chain constructor.
     *
     * @param AuthenticationState $state Current state of authentication
     * @param array $steps A list of steps that have to be completed.
     */
    public function __construct(AuthenticationState $state, array $steps)
    {
        // Normalize all steps to be an array of types.
        foreach ($steps as &$step) {
            if (ArrayHelper::isAssociative($step)) {
                $step = [$step];
            }
        }
        unset ($step);

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
        $lastCompletedStepType = $this->_getLastCompletedStepType();
        $finalSteps = (array)end($this->_steps);

        return $this->_isPossibleStepType((string)$lastCompletedStepType, $finalSteps);
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
     * Perform an authentication step.
     *
     * @param string $stepType The step type to be performed
     * @param array $credentials
     * @return bool `true`, if at least one step was successfully performed.
     * @throws InvalidConfigException If unable to determine the next authentication step and chain is not complete.
     */
    public function performAuthenticationStep(string $stepType, array $credentials = []): bool
    {
        /** @var TypeInterface $nextStep */
        if ($nextStep = $this->getNextAuthenticationStep($stepType)) {
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
                    $this->performAuthenticationStep(get_class($nextStep));
                }
            }

            return $success;
        }

        return true;
    }

    /**
     * Switch to an alternative step.
     *
     * @param string $stepType
     * @return TypeInterface
     * @throws InvalidConfigException if invalid switch attempted.
     */
    public function switchStep(string $stepType): TypeInterface
    {
        $switchedStep = $this->getNextAuthenticationStep($stepType);

        if (!$switchedStep) {
            throw new InvalidConfigException("Invalid authentication chain configuration. {$stepType} type requested, but not available at this point of the chain.");
        }

        $switchedStep->prepareForAuthentication($this->_getResolvedUser());
        return $switchedStep;
    }

    /**
     * For a given step return a list of alternative steps that can be performed.
     * @param string $chosenStep
     * @return array
     */
    public function getAlternativeSteps(string $chosenStep = ''): array
    {
        if ($this->getIsComplete()) {
            return [];
        }

        $availableTypes = $this->_getAvailableStepTypes();
        $alternativeSteps = [];

        if (empty($chosenStep)) {
            $chosenStep = reset($availableTypes)['type'];
        }
        foreach ($availableTypes as $config) {
            if ($config['type'] !== $chosenStep) {
                $step = $config['type'];
                $alternativeSteps[$step] = $step::displayName();
            }
        }

        return $alternativeSteps;
    }

    /**
     * Get next authentication step.
     *
     * @param string $stepType the step type to use, if multiple possible
     * @return TypeInterface|null
     * @throws InvalidConfigException if chain is not complete, yet all the steps are done.
     */
    public function getNextAuthenticationStep(string $stepType = ''): ?TypeInterface
    {
        if ($this->getIsComplete()) {
            return null;
        }

        $availableTypes = $this->_getAvailableStepTypes();

        if (empty($availableTypes)) {
            throw new InvalidConfigException("Unterminated authentication chain - {$this->_state->getAuthenticationScenario()}, last completed step - {$this->_getLastCompletedStepType()}");
        }

        if (!empty($stepType)) {
            foreach ($availableTypes as $availableType) {
                if ($stepType === $availableType['type']) {
                    return Authentication::createStepFromConfig($availableType, $this->_state);
                }
            }

            throw new InvalidConfigException("Invalid authentication chain configuration. {$stepType} type requested, but not available at this point of the chain.");
        }

        return Authentication::createStepFromConfig(reset($availableTypes), $this->_state);
    }

    /**
     * Returns true if chain contains a step of a given type.
     *
     * @param string $stepType
     * @return bool
     */
    public function containsStepType(string $stepType): bool
    {
        foreach ($this->_steps as $stepList) {
            foreach ($stepList as $step) {
                if ($step['type'] === $stepType) {
                    return true;
                }
            }
        }

        return false;
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

    /**
     * Given a step type and a list of step configurations, return true, if step type is part of the list.
     *
     * @param string $stepType
     * @param array $availableStepConfigurations
     * @return bool
     */
    private function _isPossibleStepType(string $stepType, array $availableStepConfigurations): bool
    {
        foreach ($availableStepConfigurations as $stepConfiguration) {
            if ($stepType === $stepConfiguration['type']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get available step types for the current state.
     *
     * @return array
     */
    private function _getAvailableStepTypes(): array
    {
        $lastCompletedStepType = $this->_getLastCompletedStepType();
        $availableTypes = [];

        // If no steps performed, return the first one
        if (!$lastCompletedStepType) {
            $availableTypes = (array)reset($this->_steps);
        } else {
            foreach ($this->_steps as $index => $authenticationSteps) {
                $authenticationSteps = (array)$authenticationSteps;

                // If we hit a match, we're after the next step
                if ($this->_isPossibleStepType($lastCompletedStepType, $authenticationSteps)) {
                    $availableTypes = (array)$this->_steps[$index + 1];
                    break;
                }
            }
        }

        return $availableTypes;
    }
}
