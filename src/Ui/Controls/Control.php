<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Controls;

use CraftCms\Cms\Ui\Contracts\Control as ControlContract;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Ui;

/**
 * @since 6.0.0
 */
abstract class Control implements ControlContract
{
    protected mixed $value = null;

    protected ControlMode $mode = ControlMode::Editable;

    protected bool $reactive = false;

    /** @var string|list<string>|null */
    protected string|array|null $deltaGroup = null;

    /**
     * Creates a Control bound to a path relative to its Ui context.
     *
     * Construction is centralized so every concrete Control uses the same
     * path storage and resolver semantics.
     *
     * @param  string|list<string>  $path
     */
    final protected function __construct(protected string|array $path) {}

    /**
     * Creates a new instance bound to the given relative path.
     *
     * Strings may use dot notation; UiResolver normalizes either form into
     * an absolute segment path when it builds the payload.
     *
     * @param  string|list<string>  $path
     */
    final public static function make(string|array $path): static
    {
        return new static($path);
    }

    /**
     * Returns the authored path relative to the Ui context namespace.
     *
     * UiResolver owns validation, normalization, namespacing, uniqueness,
     * and conversion to the absolute payload path.
     *
     * @return string|list<string>
     */
    final public function path(): string|array
    {
        return $this->path;
    }

    /**
     * Returns a copy bound to a different path, preserving the control's configuration.
     *
     * @param  string|list<string>  $path
     */
    final public function withPath(string|array $path): static
    {
        $control = clone $this;
        $control->path = $path;

        return $control;
    }

    /** @param string|list<string> $path */
    final public function deltaGroup(string|array $path): static
    {
        $this->deltaGroup = $path;

        return $this;
    }

    final public function deltaGroupAtNamespace(): static
    {
        return $this->deltaGroup([]);
    }

    /** @return string|list<string>|null */
    final public function getDeltaGroup(): string|array|null
    {
        return $this->deltaGroup;
    }

    /**
     * Sets the canonical default value for this Control.
     *
     * UiContext values take precedence during resolution. The value must
     * use the concrete Control's documented JSON-safe value shape.
     */
    final public function value(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Returns the canonical default value used when UiContext has no value
     * at the resolved path.
     */
    final public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Declares this Control's mode within an otherwise editable Ui.
     *
     * UiResolver combines this value with the broader UiContext mode to
     * produce the effective mode written to UiPayload.
     */
    final public function mode(ControlMode|string $mode): static
    {
        $this->mode = is_string($mode) ? ControlMode::from($mode) : $mode;

        return $this;
    }

    /**
     * Returns the locally declared mode before UiContext policy is applied.
     */
    final public function getMode(): ControlMode
    {
        return $this->mode;
    }

    final public function reactive(bool $reactive = true): static
    {
        $this->reactive = $reactive;

        return $this;
    }

    final public function isReactive(): bool
    {
        return $this->reactive;
    }

    /**
     * What the control reads when its value is missing. See the contract.
     *
     * Controls whose value is a shape override this; the rest inherit null and
     * ship nothing.
     */
    public function emptyValue(): mixed
    {
        return null;
    }

    public function omitNullValue(): bool
    {
        return false;
    }

    /** Whether the control renders nested forms. See the contract. */
    public function nestsUis(): bool
    {
        return false;
    }

    /**
     * Returns type-specific, JSON-safe configuration for both renderers.
     *
     * Concrete Controls should override this method for their typed properties
     * and omit generic state already represented by the resolved payload.
     *
     * @return array<string, mixed>
     */
    public function props(mixed $value = null): array
    {
        return [];
    }

    public function resolveProps(mixed $value, ControlMode $mode): array
    {
        return $this->props($value);
    }

    /**
     * @return list<array{scope: string|list<string>, form: Ui, refreshable: bool}>
     */
    public function nestedUis(mixed $value = null): array
    {
        return [];
    }

    protected static function parentInputName(string $name): ?string
    {
        $position = strrpos($name, '[');

        return $position === false ? null : substr($name, 0, $position);
    }

    /** Returns the trailing segment of a bracketed input name. */
    protected static function leafName(string $name): string
    {
        preg_match('/(?:^|\[)([^\[\]]+)]?$/', $name, $matches);

        return $matches[1] ?? $name;
    }
}
