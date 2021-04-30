<?php
declare(strict_types=1);

namespace craft\authentication\base;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\errors\AuthenticationException;
use craft\models\AuthenticationState;

/**
 * @property-read string $description
 * @property-read string $name
 * @property-read string $fieldHtml
 */
abstract class Step extends Component implements StepInterface
{
    protected AuthenticationState $state;

    /**
     * Return the field HTML.
     *
     * @return string
     */
    abstract public function getFieldHtml(): string;

    /**
     * @inheritdoc
     */
    public function getFields(): ?array
    {
        return null;
    }

    /**
     * Return the name of the authentication step.
     *
     * @return string
     */
    public function getName(): string
    {
        return self::displayName();
    }

    /**
     * Return the description of the authentication step.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function prepareForAuthentication(User $user = null): void
    {
        // Do nothing.
    }

    /**
     * @inheritdoc
     */
    public function getRequiresInput(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getIsApplicable(User $user): bool
    {
        return false;
    }

    /**
     * Setter for the Authentication state. Protected, to avoid exposing state.
     *
     * @param AuthenticationState $state
     */
    protected function setState(AuthenticationState $state): void
    {
        $this->state = $state;
    }

    /**
     * Complete an authentication step.
     *
     * @param User|null $user
     * @return AuthenticationState
     */
    protected function completeStep(User $user = null): AuthenticationState
    {
        /** @var AuthenticationState $state */
        $state = Craft::createObject(AuthenticationState::class, [[
            'resolvedUserId' => $user->id ?? null,
            'lastCompletedStep' => static::class,
            'authenticationScenario' => $this->state->getAuthenticationScenario()
        ]]);

        return $state;
    }
}
