<?php
declare(strict_types=1);

namespace craft\authentication;

use Craft;
use craft\authentication\base\TypeInterface;
use craft\base\Component;
use craft\elements\User;
use craft\helpers\Authentication;
use craft\models\authentication\State;
use craft\validators\AuthStepConfigValidator;
use yii\base\InvalidConfigException;

/**
 *
 * @property-read User|null $authenticatedUser
 * @property-read bool $isNew
 * @property-read bool $isValid
 * @property-read bool $isComplete
 */
class Branch extends Component
{
    /**
     * @var string The branch name
     */
    protected string $name;

    /**
     * @var array A list of all the authentication branches for this chain.
     */
    protected array $steps;

    /**
     * @var array A list of all the authentication steps that are applicable for current user.
     */
    protected array $applicableSteps;

    /**
     * @var State The current authentication state.
     */
    protected State $state;

    /**
     * @var bool Whether the current authentication branch is valid.
     */
    protected bool $isValid;

    /**
     * Authentication chain constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->_prepareApplicableStepList();
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * @param array $steps
     */
    public function setSteps(array $steps): void
    {
        $this->steps = $steps;
    }

    /**
     * @return State
     */
    public function getState(): State
    {
        return $this->state;
    }

    /**
     * @param State $state
     */
    public function setState(State $state): void
    {
        $this->state = $state;
    }

    /**
     * @return bool
     */
    public function getIsValid(): bool
    {
        return $this->isValid;
    }

    /**
     * @return array
     */
    public function getApplicableSteps(): array
    {
        return $this->applicableSteps;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::defineRules();
        $rules[] = [['name', 'state', 'steps', 'applicableSteps'], 'required'];
        $rules[] = [['isValid'], 'compare', 'compareValue' => true, 'operator' => '==='];
        $rules[] = [['steps'], AuthStepConfigValidator::class];

        return $rules;
    }

    /**
     * Returns `true` if a given authentication chain is completed successfully.
     *
     * @return bool
     */
    public function getIsComplete(): bool
    {
        $lastCompletedStepType = $this->_getLastCompletedStepType();

        if (!$lastCompletedStepType) {
            return false;
        }

        $finalStepConfig = (array)end($this->applicableSteps);

        return $this->_isPossibleStepType((string)$lastCompletedStepType, $finalStepConfig);
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
            $this->state = $nextStep->authenticate($credentials, $this->_getResolvedUser());

            // Write it down
            Craft::$app->getAuthentication()->storeAuthenticationState($this->state);

            // If advanced in chain
            $success = $this->_getLastCompletedStepType() === get_class($nextStep);

            if ($success) {
                // In case circumstances have changed.
                $this->_prepareApplicableStepList();
            }

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
            throw new InvalidConfigException("Invalid authentication chain configuration. $stepType type requested, but not available at this point of the chain.");
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
            throw new InvalidConfigException("Unterminated authentication chain - {$this->state->getAuthenticationScenario()}, last completed step - {$this->_getLastCompletedStepType()}");
        }

        if (!empty($stepType)) {
            foreach ($availableTypes as $availableType) {
                if ($stepType === $availableType['type']) {
                    return Authentication::createStepFromConfig($availableType, $this->state);
                }
            }

            throw new InvalidConfigException("Invalid authentication chain configuration. $stepType type requested, but not available at this point of the chain.");
        }

        return Authentication::createStepFromConfig(reset($availableTypes), $this->state);
    }

    /**
     * Get the last completed authentication step.
     *
     * @return string|null
     */
    private function _getLastCompletedStepType(): ?string
    {
        return $this->state->getLastCompletedStepType();
    }

    /**
     * Get the resolved user.
     *
     * @return User|null
     */
    private function _getResolvedUser(): ?User
    {
        return $this->state->getResolvedUser();
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
            $availableTypes = (array)reset($this->applicableSteps);
        } else {
            foreach ($this->applicableSteps as $index => $authenticationSteps) {
                $authenticationSteps = (array)$authenticationSteps;

                // If we hit a match, we're after the next step
                if ($this->_isPossibleStepType($lastCompletedStepType, $authenticationSteps)) {
                    $availableTypes = (array)$this->applicableSteps[$index + 1];
                    break;
                }
            }
        }

        return $availableTypes;
    }

    /**
     * Prepare a list of applicable steps.
     */
    private function _prepareApplicableStepList(): void
    {
        // Filter out steps that are not applicable
        $resolvedUser = $this->state->getResolvedUser();

        $filteredSteps = [];
        $this->isValid = true;

        foreach ($this->steps as $stepConfiguration) {
            $filteredCollection = [];
            foreach ($stepConfiguration['choices'] as $step) {
                $stepType = $step['type'];

                if (is_subclass_of($stepType, TypeInterface::class) && $stepType::getIsApplicable($resolvedUser)) {
                    $filteredCollection[] = $step;
                }
            }

            if (!empty($filteredCollection)) {
                $filteredSteps[] = $filteredCollection;
            }

            // If no applicable steps, invalidate the branch
            if (empty($filteredCollection) && $stepConfiguration['required']) {
                $this->isValid = false;
            }
        }

        $this->applicableSteps = $filteredSteps;
    }
}
