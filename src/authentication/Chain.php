<?php
declare(strict_types=1);

namespace craft\authentication;

use Craft;
use craft\authentication\base\TypeInterface;
use craft\base\Component;
use craft\elements\User;
use craft\errors\AuthenticationException;
use craft\errors\AuthenticationStateException;
use craft\helpers\Authentication as AuthHelper;
use craft\models\authentication\State;
use yii\base\InvalidConfigException;

/**
 *
 * @property-read User|null $authenticatedUser
 * @property-read bool $isNew
 * @property-read bool $isComplete
 */
class Chain extends Component
{
    /**
     * @var array A list of all the authentication branches for this chain, keyed by name.
     */
    private array $branchConfigs;

    /**
     * @var Branch The currently active branch.
     */
    private Branch $_activeBranch;

    /** @var string[] a list of all the possible branch names in preferred order  */
    protected array $branchNames = [];

    /**
     * @var string The auth chain scenario.
     */
    protected string $scenario = '';

    /**
     * @var bool True, if branches were switched mid-auth
     */
    protected bool $didSwitchBranches = false;

    /**
     * Authentication chain constructor.
     *
     * @param string $scenario The auth chain scenario name
     * @param State $state Current state of authentication
     * @param array $branchConfigs A list of branches this chain has that have to be completed.
     * @throws AuthenticationException if something went wrong while creating the auth chain.
     */
    public function __construct(string $scenario, State $state, array $branchConfigs)
    {
        // Move to array based config.
        if (empty($branchConfigs)) {
            throw new AuthenticationException('Impossible to create an authentication chain with no branches!');
        }

        $this->branchConfigs = $branchConfigs;
        $this->branchNames = array_keys($branchConfigs);
        $this->scenario = $scenario;

        $branchName = $state->getAuthenticationBranch();
        $this->_activeBranch = $this->ensureActiveBranch($branchName, $state);

        parent::__construct();
    }

    /**
     * Returns `true` if a given authentication chain is completed successfully.
     *
     * @return bool
     */
    public function getIsComplete(): bool
    {
        return $this->_activeBranch && $this->_activeBranch->getIsComplete();
    }

    /**
     * Returns `true` if no steps have been completed yet.
     *
     * @return bool
     */
    public function getIsNew(): bool
    {
        return $this->_activeBranch && $this->_activeBranch->getIsNew();
    }

    /**
     * Returns the authenticated user if the chain is complete.
     *
     * @return User|null
     */
    public function getAuthenticatedUser(): ?User
    {
        return $this->getIsComplete() ? $this->_activeBranch->getAuthenticatedUser() : null;
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
        $activeBranch = $this->_activeBranch;
        $success = $activeBranch->performAuthenticationStep($stepType, $credentials);

        if ($activeBranch->validate()) {
            return $success;
        }

        $nextBranchName = $this->getNextBranchName($activeBranch->getName());
        $newState = AuthHelper::createAuthState($this->scenario, $nextBranchName);
        $this->_activeBranch = $this->ensureActiveBranch($nextBranchName, $newState);

        return false;
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
        return $this->_activeBranch->switchStep($stepType);
    }

    /**
     * For a given step return a list of alternative steps that can be performed.
     * @param string $chosenStep
     * @return array
     */
    public function getAlternativeSteps(string $chosenStep = ''): array
    {
        return $this->_activeBranch->getAlternativeSteps($chosenStep);
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
        return $this->_activeBranch->getNextAuthenticationStep($stepType);
    }

    /**
     * @return bool
     */
    public function getDidSwitchBranches(): bool
    {
        return $this->didSwitchBranches;
    }

    /**
     * Determine the next possible branch name, based on the last invalid branch name.
     *
     * @param string $invalidBranchName
     * @return string
     * @throws AuthenticationException if impossible to determine a valid branch name.
     */
    protected function getNextBranchName(string $invalidBranchName): string
    {
        $nextBranchName = $this->branchNames[array_search($invalidBranchName, $this->branchNames, true) + 1] ?? null;

        if (!$nextBranchName) {
            throw new AuthenticationException('Impossible to determine a possible branch name');
        }

        $this->didSwitchBranches = true;
        return $nextBranchName;
    }

    /**
     * @param string $branchName
     * @param array $config
     * @param State $state
     * @return Branch
     * @throws InvalidConfigException
     */
    private function createBranch(string $branchName, array $config, State $state): Branch
    {
        return Craft::createObject(Branch::class, [
            [
                'name' => $branchName,
                'steps' => $config['steps'],
                'state' => $state,
            ]
        ]);
    }

    /**
     * This method creates and returns a branch based current branch name and auth state.
     * @param string $branchName
     * @param State $state
     * @throws AuthenticationException if ran out of branches to try
     * @throws AuthenticationStateException if authentication state expects a non-existing branch
     * @throws InvalidConfigException
     */
    private function ensureActiveBranch(string $branchName, State $state): Branch
    {
        if ($branchName && in_array($branchName, $this->branchNames, true)) {
            do {
                $config = $this->branchConfigs[$branchName];
                $branch = $this->createBranch($branchName, $config, $state);

                if ($branch->validate()) {
                    $this->_activeBranch = $branch;
                    Craft::$app->getAuthentication()->storeAuthenticationState($state);
                } else {
                    Craft::warning("Failed to validate the $branchName authentication branch: " . implode("\n", $branch->getErrorSummary(true)));
                    // If we run out of branches, this will throw an exception breaking the loop
                    $branchName = $this->getNextBranchName($branchName);
                }
            } while (!$branch->validate());

            return $branch;
        } else {
            throw new AuthenticationStateException('Authentication state does not match the chain configuration!');
        }
    }
}
