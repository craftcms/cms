<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

/**
 * PHP counterpart to the `<craft-field-group>` web component: a grid
 * container for a list of fields.
 *
 *     FieldGroup::make()->children([
 *         Field::make()->label(t('Name'))->input(...),
 *         Field::make()->label(t('Handle'))->input(...),
 *     ]);
 *
 * Renders directly (no Blade view). Children may be components, `Htmlable`s,
 * or plain strings (HTML-encoded).
 */
class FieldGroup extends ViewComponent
{
    protected ?string $gap = null;

    protected function tagName(): string
    {
        return 'craft-field-group';
    }

    /**
     * The grouped fields (default slot).
     *
     * @param  iterable<array-key, mixed>  $children
     */
    public function children(iterable $children): static
    {
        $this->slots[static::DEFAULT_SLOT] = $children;

        return $this;
    }

    /** Grid gap, as a CSS length (sets the component's `--gap` custom property). */
    public function gap(?string $gap): static
    {
        $this->gap = $gap;

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'style' => $this->gap !== null ? "--gap: {$this->gap};" : null,
        ];
    }
}
