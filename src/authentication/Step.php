<?php
declare(strict_types=1);

namespace craft\authentication;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\models\AuthenticationState;
use yii\base\InvalidConfigException;

abstract class Step extends Component implements StepInterface
{
    protected AuthenticationState $state;

    /**
     * @inheritdoc
     */
    public function prepareForAuthentication(): void
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
}
