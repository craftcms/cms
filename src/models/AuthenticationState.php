<?php
declare(strict_types=1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\elements\User;

/**
 * Authentication state model class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 *
 * @property-read User|null $resolvedUser
 * @property-write null|string $lastCompletedStep
 * @property-read bool $isNew
 */
class AuthenticationState extends Model
{
    /**
     * @var string The authentication chain scenario
     */
    protected string $authenticationScenario;

    /**
     * @var string|null Last step type performed in the chain.
     */
    protected ?string $lastCompletedStepType = null;

    /**
     * @var int|null The resolved user id
     */
    protected ?int $resolvedUserId = null;

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
     * Return the resolved user id, if any.
     *
     * @return int|null
     */
    public function getResolvedUserId(): ?int
    {
        return $this->resolvedUserId;
    }

    /**
     * Return the resolved user, if any.
     *
     * @return User|null
     */
    public function getResolvedUser(): ?User
    {
        return $this->resolvedUserId ? Craft::$app->getUsers()->getUserById($this->resolvedUserId) : null;
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
            'lastCompletedStepType' => $this->lastCompletedStepType,
            'resolvedUserId' => $this->resolvedUserId,
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
     * Set the resolved user id value.
     *
     * @param ?int $userId
     */
    protected function setResolvedUserId(int $userId = null): void
    {
        $this->resolvedUserId = $userId;
    }

    /**
     * Set the last completed step type value.
     *
     * @param ?string $stepType
     */
    protected function setLastCompletedStep(string $stepType = null): void
    {
        $this->lastCompletedStepType = $stepType;
    }
}
