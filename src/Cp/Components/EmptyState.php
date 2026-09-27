<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Enums\Appearance;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;
use Stringable;

/**
 * PHP counterpart to the `<craft-empty>` web component.
 *
 *     EmptyState::make()
 *         ->icon('empty-set')
 *         ->label(t('No results'))
 *         ->actions(Button::make()->label(t('New entry')));
 *
 * Renders directly (no Blade view) — the component is a single element whose
 * chrome lives in the web component. Content strings are HTML-encoded; pass
 * an `Htmlable` for trusted markup.
 */
class EmptyState extends ViewComponent
{
    /** The appearances `<craft-empty>` supports, out of the shared set. */
    private const array APPEARANCES = [Appearance::Plain, Appearance::Outline];

    protected ?string $label = null;

    protected ?string $icon = null;

    protected ?Appearance $appearance = null;

    protected function tagName(): string
    {
        return 'craft-empty';
    }

    /** The message. Say what is missing, not that something is missing. */
    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /** Name of an icon shown above the message. */
    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * `outline` draws a border around the empty state; `plain` (the web
     * component's default) doesn't. Only these two of the shared appearances
     * apply.
     *
     * @param  Appearance|'plain'|'outline'|null  $appearance
     */
    public function appearance(Appearance|string|null $appearance): static
    {
        if (is_string($appearance)) {
            $appearance = Appearance::from($appearance);
        }

        if ($appearance !== null && ! in_array($appearance, self::APPEARANCES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Empty states support the "plain" and "outline" appearances, not "%s".',
                $appearance->value,
            ));
        }

        $this->appearance = $appearance;

        return $this;
    }

    /** Artwork shown above the message, replacing the `icon` (`graphic` slot). */
    public function graphic(string|Htmlable|Stringable|ViewComponent|null $graphic): static
    {
        $this->slots['graphic'] = $graphic;

        return $this;
    }

    /** The message region, replacing the `label` (`content` slot). */
    public function message(string|Htmlable|Stringable|ViewComponent|null $message): static
    {
        $this->slots['content'] = $message;

        return $this;
    }

    /**
     * Shown after the message, usually the action to take (default slot).
     *
     * @param  string|Htmlable|Stringable|ViewComponent|iterable<array-key, mixed>|null  $actions
     */
    public function actions(string|Htmlable|Stringable|ViewComponent|iterable|null $actions): static
    {
        $this->slots[static::DEFAULT_SLOT] = $actions;

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'label' => $this->label,
            'icon' => $this->icon,
            'appearance' => $this->appearance?->value,
        ];
    }
}
