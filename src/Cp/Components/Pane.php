<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Enums\PaneAppearance;
use CraftCms\Cms\Cp\Enums\PaneVariant;
use Illuminate\Contracts\Support\Htmlable;
use Stringable;

/**
 * PHP counterpart to the `<craft-pane>` web component — the primary CP content
 * surface: a padded, rounded container with optional header and footer regions.
 *
 *     Pane::make()
 *         ->label(t('Details'))
 *         ->padding('md')
 *         ->content($bodyHtml)
 *         ->primaryAction(Button::make()->variant('primary')->label(t('Save')));
 *
 * The header only renders when `label` is set or one of the `header`, `title`,
 * or `header-actions` slots is filled; the footer only when one of the footer
 * slots is. Note that `label` (the attribute) and `title` (a slot) are
 * different things: `label` renders the default `<h1>` inside the `title` slot,
 * which filling `title` replaces.
 *
 * Renders directly (no Blade view) — the component is a single element whose
 * chrome lives in the web component. Content strings are HTML-encoded; pass an
 * `Htmlable` for trusted markup.
 */
class Pane extends ViewComponent
{
    protected PaneAppearance|string|null $appearance = null;

    protected PaneVariant|string|null $variant = null;

    protected string|int|null $padding = null;

    protected ?string $label = null;

    protected function tagName(): string
    {
        return 'craft-pane';
    }

    /**
     * Surface treatment; the web component defaults to `raised`. Strings (e.g.
     * from Twig `ui()` config) are validated against {@see PaneAppearance}.
     */
    public function appearance(PaneAppearance|string|null $appearance): static
    {
        $this->appearance = $appearance;

        return $this;
    }

    public function getAppearance(): ?string
    {
        if ($this->appearance === null) {
            return null;
        }

        return $this->appearance instanceof PaneAppearance ? $this->appearance->value : PaneAppearance::from($this->appearance)->value;
    }

    /**
     * Content treatment; the web component defaults to `plain`. Strings are
     * validated against {@see PaneVariant}.
     */
    public function variant(PaneVariant|string|null $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getVariant(): ?string
    {
        if ($this->variant === null) {
            return null;
        }

        return $this->variant instanceof PaneVariant ? $this->variant->value : PaneVariant::from($this->variant)->value;
    }

    /**
     * Spacing for the header, body, and footer regions: `sm`/`md`/`lg`/`xl`
     * (mapped to `--c-spacing-*`), `0`, a unitless number (pixels), or any CSS
     * length. Free-form by design, so it isn’t validated against a value set;
     * the web component defaults to `lg`.
     */
    public function padding(string|int|null $padding): static
    {
        $this->padding = $padding;

        return $this;
    }

    /**
     * The pane’s title, rendered as an `<h1>` in the header. Named `label`
     * rather than `title` because `title` is a global HTML attribute (and the
     * name of the slot this fills).
     */
    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /** The pane’s body content (default slot). */
    public function content(string|Htmlable|Stringable|ViewComponent|null $content): static
    {
        $this->slots[static::DEFAULT_SLOT] = $content;

        return $this;
    }

    /** The full header region, replacing the default header layout. */
    public function header(string|Htmlable|Stringable|ViewComponent|null $header): static
    {
        $this->slots['header'] = $header;

        return $this;
    }

    /** Title content at the start of the header, replacing the `label` `<h1>`. */
    public function title(string|Htmlable|Stringable|ViewComponent|null $title): static
    {
        $this->slots['title'] = $title;

        return $this;
    }

    /** Action content at the end of the header. */
    public function headerActions(string|Htmlable|Stringable|ViewComponent|null $headerActions): static
    {
        $this->slots['header-actions'] = $headerActions;

        return $this;
    }

    /** The full body region, replacing the padded body wrapper. */
    public function body(string|Htmlable|Stringable|ViewComponent|null $body): static
    {
        $this->slots['body'] = $body;

        return $this;
    }

    /** The full footer region, replacing the default footer layout. */
    public function footer(string|Htmlable|Stringable|ViewComponent|null $footer): static
    {
        $this->slots['footer'] = $footer;

        return $this;
    }

    /** The footer’s contents, keeping the footer chrome. */
    public function footerContent(string|Htmlable|Stringable|ViewComponent|null $footerContent): static
    {
        $this->slots['footer-content'] = $footerContent;

        return $this;
    }

    /** Content at the start of the footer, e.g. a save status message. */
    public function feedback(string|Htmlable|Stringable|ViewComponent|null $feedback): static
    {
        $this->slots['feedback'] = $feedback;

        return $this;
    }

    /** Action content at the end of the footer, replacing the action group. */
    public function actions(string|Htmlable|Stringable|ViewComponent|null $actions): static
    {
        $this->slots['actions'] = $actions;

        return $this;
    }

    /** The footer’s primary action, e.g. a submit button. */
    public function primaryAction(string|Htmlable|Stringable|ViewComponent|null $primaryAction): static
    {
        $this->slots['primary-action'] = $primaryAction;

        return $this;
    }

    /** The footer’s secondary action, e.g. a cancel button. */
    public function secondaryAction(string|Htmlable|Stringable|ViewComponent|null $secondaryAction): static
    {
        $this->slots['secondary-action'] = $secondaryAction;

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'appearance' => $this->getAppearance(),
            'variant' => $this->getVariant(),
            'padding' => $this->padding,
            'label' => $this->label,
        ];
    }
}
