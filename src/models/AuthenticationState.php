<?php
declare(strict_types=1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;
use craft\elements\User;
use Craft;

/**
 * Authentication state model class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 *
 * @property-read User|null $resolvedUser
 * @property-read bool $isNew
 */
class AuthenticationState extends Model
{
    /**
     * @var string The authentication chain scenario
     */
    protected string $authenticationScenario;

    /**
     * @var string|null Last step performed in the chain.
     */
    protected ?string $lastCompletedStep = null;

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
     * Get the last completed authentication step.
     *
     * @return string|null
     */
    public function getLastCompletedStep(): ?string
    {
        return $this->lastCompletedStep;
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
            'lastCompletedStep' => $this->lastCompletedStep,
            'resolvedUserId' => $this->resolvedUserId,
        ];
    }

    protected function setAuthenticationScenario(string $scenario): void
    {
        $this->authenticationScenario = $scenario;
    }

    protected function setResolvedUserId(int $userId = null): void
    {
        $this->resolvedUserId = $userId;
    }

    protected function setLastCompletedStep(string $step = null): void
    {
        $this->lastCompletedStep = $step;
    }
}
