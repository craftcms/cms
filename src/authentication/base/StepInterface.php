<?php
declare(strict_types=1);

namespace craft\authentication\base;

use craft\elements\User;
use craft\errors\AuthenticationException;
use craft\models\AuthenticationState;

/**
 * @property-read bool $requiresInput
 * @property-read string[] $fields
 */
interface StepInterface
{
    /**
     * Return a list of field names available for this authorization step.
     *
     * @return array|null
     */
    public function getFields(): ?array;

    /**
     * Perform any actions that are required before authentication can take place.
     *
     * @param User|null $user
     * @return void
     */
    public function prepareForAuthentication(User $user = null): void;

    /**
     * Given a set of credentials, perform authorization and return an Identity
     *
     * @param array $credentials
     * @param User|null $user
     * @return AuthenticationState
     */
    public function authenticate(array $credentials, User $user = null): AuthenticationState;

    /**
     * Whether this authentication step requires user input
     *
     * @return bool
     */
    public function getRequiresInput(): bool;

    /**
     * Return true if a step is skippable for the currently identified user.
     *
     * @param User $user
     * @return bool
     */
    public function getIsSkippable(User $user): bool;

    /**
     * Skip an authentication step.
     *
     * @param User $user
     * @return AuthenticationState
     * @throws AuthenticationException if unskippable step.
     */
    public function skipStep(User $user): AuthenticationState;
}
