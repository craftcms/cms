<?php
declare(strict_types=1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models\authentication;

use Craft;
use craft\base\Model;
use craft\elements\User;

/**
 * Authentication state model class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 *
 * @property-read User $resolvedUser
 * @property-write null|string $lastCompletedStep
 * @property-read bool $isNew
 */
class State extends Model
{
    /**
     * @var string The authentication chain scenario
     */
    protected string $authenticationScenario;

    /**
     * @var string The authentication chain branch
     */
    protected string $authenticationBranch;

    /**
     * @var string|null Last step type performed in the chain.
     */
    protected ?string $lastCompletedStepType = null;

    /**
     * @var User The resolved user
     */
    protected User $resolvedUser;

    /**
     * Get the authentication scenario.
     *
     * @return string
     */
    public function getAuthenticationScenario(): string
    {
        return $this->authenticationScenario;
    }

    /**
     * Get the last completed authentication step type.
     *
     * @return string|null
     */
    public function getLastCompletedStepType(): ?string
    {
        return $this->lastCompletedStepType;
    }

    /**
     * Get the last completed authentication step type.
     *
     * @return string
     */
    public function getAuthenticationBranch(): string
    {
        return $this->authenticationBranch;
    }

    /**
     * Return the resolved user.
     *
     * @return User
     */
    public function getResolvedUser(): User
    {
        // The only way `resolvedUserId` doesn't return a valid user id is if we're faking it to defeat user enumeration.
        return $this->resolvedUser;
    }

    /**
     * Export the current authentication state.
     *
     * @return array
     */
    public function exportState(): array
    {
        return [
            'authenticationScenario' => $this->authenticationScenario,
            'authenticationBranch' => $this->authenticationBranch,
            'lastCompletedStepType' => $this->lastCompletedStepType,
            'resolvedUser' => $this->resolvedUser->toArray(['username', 'email', 'id', 'uid']),
        ];
    }

    /**
     * Set the scenario value.
     *
     * @param string $scenario
     */
    protected function setAuthenticationScenario(string $scenario): void
    {
        $this->authenticationScenario = $scenario;
    }

    /**
     * Set the authentication branch value.
     *
     * @param string $branch
     */
    protected function setAuthenticationBranch(string $branch): void
    {
        $this->authenticationBranch = $branch;
    }

    /**
     * Set the resolved user value.
     *
     * @param User $user
     */
    protected function setResolvedUser($user): void
    {
       if (is_array($user)) {
           $user = Craft::createObject(User::class, [$user]);
       }

        $this->resolvedUser = $user;
    }

    /**
     * Set the last completed step type value.
     *
     * @param ?string $stepType
     */
    protected function setLastCompletedStepType(string $stepType = null): void
    {
        $this->lastCompletedStepType = $stepType;
    }
}
