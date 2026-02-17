<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\Support\Facades\InputNamespace;
use Illuminate\Container\Attributes\Scoped;

/**
 * Tracks delta input names and their initial values for a single request lifecycle.
 *
 * When a form that supports delta updates is submitted, any delta inputs (or groups of inputs)
 * that didn't change over the lifespan of the page will be omitted from the POST request.
 * This registry collects the input names, their initial values, and which names should be
 * considered pre-modified, so the client-side JavaScript can perform the diff.
 */
#[Scoped]
final class DeltaRegistry
{
    private bool $active = false;

    /** @var string[] */
    private array $names = [];

    /** @var string[] */
    private array $modifiedNames = [];

    /** @var array<string, mixed> */
    private array $initialValues = [];

    /**
     * Returns whether delta input name registration is currently active.
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Sets whether delta input name registration is active.
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * Executes the given callback with delta registration set to the given state,
     * restoring the previous state afterward.
     */
    public function withActive(bool $active, callable $callback): mixed
    {
        $previous = $this->active;

        try {
            $this->active = $active;

            return $callback();
        } finally {
            $this->active = $previous;
        }
    }

    /**
     * Registers a delta input name.
     *
     * This can be either the name of a single form input, or a prefix used by multiple input names.
     * The input name will be namespaced with the currently active input namespace, if any.
     *
     * Note that delta input names will only be registered if delta registration is active.
     */
    public function registerName(string $inputName, bool $forceModified = false): void
    {
        if (! $this->active) {
            return;
        }

        $inputName = InputNamespace::namespaceInputName($inputName);
        $this->names[] = $inputName;

        if ($forceModified) {
            $this->modifiedNames[] = $inputName;
        }
    }

    /**
     * Returns all the registered delta input names.
     *
     * @return string[]
     */
    public function getNames(): array
    {
        return $this->names;
    }

    /**
     * Returns all the registered delta input names that should be considered modified.
     *
     * @return string[]
     */
    public function getModifiedNames(): array
    {
        return $this->modifiedNames;
    }

    /**
     * Sets the initial value of a delta input name.
     *
     * The input name will be namespaced with the currently active input namespace, if any.
     * Only stores the value if delta registration is active.
     */
    public function setInitialValue(string $inputName, mixed $value): void
    {
        if (! $this->active) {
            return;
        }

        $this->initialValues[InputNamespace::namespaceInputName($inputName)] = $value;
    }

    /**
     * Returns the initial values of all delta inputs.
     *
     * @return array<string, mixed>
     */
    public function getInitialValues(): array
    {
        return $this->initialValues;
    }
}
