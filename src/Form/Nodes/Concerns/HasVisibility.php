<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Nodes\Concerns;

/**
 * Lets a Node be hidden from view without being removed from the Form.
 *
 * A hidden Node still resolves: its Controls keep their paths, their values
 * stay in the payload, and they keep posting. That is the point — dropping the
 * Node instead would drop its value, so toggling a setting off and back on
 * would lose whatever the author had typed, and saving would fall back to the
 * property default. Only the presentation changes.
 *
 * Visibility is evaluated server-side while the Form is built, so it updates on
 * the Form's next refresh rather than instantly. For settings driven by a
 * lightswitch in the same Form that round trip is ~100ms (see
 * `FormRenderer.vue`); a client-side predicate would be the next step if that
 * proves too slow.
 *
 * Hidden Nodes render with both the `hidden` attribute — which also removes
 * them from the accessibility tree — and a `hidden` class, since some hosts
 * (`craft-disclosure`) set `:host { display: block }`, which the UA stylesheet's
 * `[hidden]` rule can't override.
 */
trait HasVisibility
{
    private bool $hidden = false;

    /** Hides the Node when `$visible` is false; the value is kept either way. */
    public function visible(bool $visible = true): static
    {
        $this->hidden = ! $visible;

        return $this;
    }

    /** Hides the Node when `$hidden` is true; the value is kept either way. */
    public function hidden(bool $hidden = true): static
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function isVisible(): bool
    {
        return ! $this->hidden;
    }

    /** @return array{hidden?: true} */
    protected function visibilityProps(): array
    {
        return $this->hidden ? ['hidden' => true] : [];
    }

    /**
     * The attributes that hide a rendered Node, for `renderHtml()` to merge in.
     *
     * @param  array<string, mixed>  $props
     * @return array{hidden?: true, class?: string}
     */
    protected static function visibilityAttributes(array $props): array
    {
        return ($props['hidden'] ?? false) ? ['hidden' => true, 'class' => 'hidden'] : [];
    }
}
