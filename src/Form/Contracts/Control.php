<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Contracts;

use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormHtmlRenderer;

interface Control
{
    /**
     * Renders the resolved control for the non-Vue HTML fallback.
     *
     * Implementations own their control-specific markup and should compose
     * existing CP components where suitable. They must preserve the common
     * ID, name, state, validation, and accessibility attributes supplied by
     * the renderer. Nested Controls may delegate recursion back to the
     * renderer.
     *
     * @param  ControlPayload  $control  The resolved control payload, including its props, path, and mode.
     * @param  mixed  $value  The current value at the control's resolved path.
     * @param  array<string, mixed>  $attributes  Common HTML attributes computed from the field and control state.
     * @param  FormHtmlRenderer  $renderer  The renderer coordinating the complete fallback form.
     */
    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string;

    /**
     * Returns the Vue component registry name used to render this control.
     *
     * The component must be registered before the form is mounted and accept
     * the control props emitted by the matching implementation.
     */
    public function component(): string;

    /**
     * Returns the control's path relative to the form context namespace.
     *
     * Strings may use dot notation. Array paths must contain non-empty string
     * segments. The resolved path identifies the value, input name, errors,
     * and reconciliation unit, and must be unique within the form.
     *
     * @return string|list<string>
     */
    public function path(): string|array;

    /**
     * Returns a copy bound to a different path, preserving the control's configuration.
     *
     * @param  string|list<string>  $path
     */
    public function withPath(string|array $path): static;

    /**
     * Returns an optional ancestor path whose complete value must mutate atomically.
     *
     * @return string|list<string>|null
     */
    public function getDeltaGroup(): string|array|null;

    /**
     * Returns the control's default value.
     *
     * The resolver uses this value only when the form context does not contain
     * a value at the resolved path. It must be JSON-serializable.
     */
    public function getValue(): mixed;

    /**
     * Returns the control's requested interaction mode.
     *
     * A non-editable mode set on the form context takes precedence over this
     * value when the payload is resolved.
     */
    public function getMode(): ControlMode;

    public function mode(ControlMode|string $mode): static;

    /**
     * Returns control-specific configuration for the resolved value and both renderers.
     *
     * Generic data such as the component name, path, value, and mode belongs
     * to the resolved control payload and should not be repeated here. Every
     * returned value must be JSON-serializable.
     *
     * @return array<string, mixed>
     */
    public function props(mixed $value = null): array;

    /**
     * Returns Forms owned by this Control, scoped relative to its path.
     *
     * @return list<array{scope: string|list<string>, form: Form, refreshable: bool}>
     */
    public function nestedForms(mixed $value = null): array;
}
