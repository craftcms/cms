<?php
declare(strict_types=1);

namespace craft\authentication\base;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\errors\AuthenticationException;
use craft\models\AuthenticationState;

/**
 * @property-read string $fieldHtml
 */
abstract class Step extends Component implements StepInterface
{
    protected AuthenticationState $state;

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
    public function getFields(): ?array
    {
        return null;
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
     * @inheritdoc
     */
    public function getRequiresInput(): bool
    {
        return $this->getFields() !== null;
    }

    /**
     * @inheritdoc
     */
    public function getIsSkippable(User $user): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function skipStep(User $user): AuthenticationState
    {
        if (!$this->getIsSkippable($user)) {
            throw new AuthenticationException('Unable to skip this authentication step');
        }

        return $this->completeStep($user);
    }


    /**
     * Return the field HTML.
     *
     * @return string
     */
    abstract public function getFieldHtml(): string;
}
