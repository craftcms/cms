<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

/**
 * PHP counterpart to the `<craft-copy-attribute>` web component.
 *
 * @since 6.0.0
 */
class CopyAttribute extends ViewComponent
{
    protected string $value = '';

    protected bool $disabled = false;

    protected function tagName(): string
    {
        return 'craft-copy-attribute';
    }

    /** The text to display and copy. */
    public function value(string $value): static
    {
        $this->value = $value;
        $this->slots[static::DEFAULT_SLOT] = $value;

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'value' => $this->value,
            'disabled' => $this->disabled,
        ];
    }
}
