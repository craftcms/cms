<?php
declare(strict_types=1);

namespace craft\authentication\base;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\models\AuthenticationState;

/**
 * Authentication step type base class. This class must be implemented for all steps indented to be used in Craft CP.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 *
 * @property-read string $description
 * @property-read string $name
 * @property-read string $inputFieldHtml
 * @property-read string $stepType
 * @property-read string $fieldHtml
 */
abstract class Type extends Component implements TypeInterface
{
    protected AuthenticationState $state;

    /**
     * Return the field HTML.
     *
     * @return string
     */
    abstract public function getInputFieldHtml(): string;

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
    public static function getIsApplicable(User $user): bool
    {
        return true;
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
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $state = Craft::createObject(AuthenticationState::class, [[
            'resolvedUserId' => $user->id ?? null,
            'lastCompletedStepType' => $this->getStepType(),
            'authenticationScenario' => $this->state->getAuthenticationScenario()
        ]]);

        return $state;
    }

    /**
     * @inheritdoc
     */
    public function getStepType(): string
    {
        return static::class;
    }


}
