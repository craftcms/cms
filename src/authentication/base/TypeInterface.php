<?php
declare(strict_types=1);

namespace craft\authentication\base;

use craft\authentication\Branch;
use craft\elements\User;
use craft\models\authentication\State;
use yii\base\InvalidConfigException;

/**
 * Authentication step type interface class. This interface must be implemented by all valid steps in authentication chains.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 *
 * @property-read bool $requiresInput
 * @property-read string[] $fields
 */
interface TypeInterface
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
     * Given a set of credentials, perform authorization and return the new AuthenticationState
     *
     * @param array $credentials
     * @param User|null $user
     * @return State
     * @throws InvalidConfigException If something went wrong while createing authentication chain.
     */
    public function authenticate(array $credentials, User $user = null): State;

    /**
     * Whether this authentication step requires user input
     *
     * @return bool
     */
    public function getRequiresInput(): bool;

    /**
     * Return true if a step is applicable for the currently identified user.
     *
     * @param User|null $user
     * @return bool
     */
    public static function getIsApplicable(?User $user): bool;

    /**
     * Return the description of the authentication step.
     *
     * @return string
     */
    public static function getDescription(): string;

    /**
     * Get the step type name.
     *
     * @return string
     */
    public function getStepType(): string;

    /**
     * Set the current authentication state.
     *
     * @param State $state
     */
    public function setState(State $state): void;

    /**
     * Set the authentication chain branch.
     *
     * @param Branch $branch
     */
    public function setBranch(Branch $branch): void;
}
