<?php

declare(strict_types=1);

namespace craft\authentication\base;

use Craft;
use craft\authentication\Branch;
use craft\authentication\State;
use craft\base\Component;
use craft\elements\User;

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
    /**
     * @var State Current authentication state.
     */
    protected State $state;

    /**
     * @var Branch Current authentication branch.
     */
    protected Branch $branch;

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
    public static function getIsApplicable(?User $user): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function setState(State $state): void
    {
        $this->state = $state;
    }

    /**
     * Complete an authentication step.
     *
     * @param User|null $user
     * @return State
     */
    protected function completeStep(User $user = null): State
    {
        /** @var State $state */
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $state = Craft::createObject(State::class, [[
            'resolvedUser' => $user,
            'lastCompletedStepType' => $this->getStepType(),
            'authenticationScenario' => $this->state->getAuthenticationScenario(),
            'authenticationBranch' => $this->state->getAuthenticationBranch(),
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
