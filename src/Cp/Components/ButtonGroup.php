<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Support\Html;

/**
 * PHP counterpart to the `<craft-button-group>` web component: an exclusive
 * group of buttons, wrapped in a `<craft-listbox>` for selection behavior,
 * optionally posting the selected value through a hidden input.
 *
 *     ButtonGroup::make()
 *         ->buttons([
 *             Button::make()->label(t('Left'))->active(),
 *             Button::make()->label(t('Right')),
 *         ])
 *         ->name('alignment');
 */
class ButtonGroup extends ViewComponent
{
    use HasDisabled;
    use HasId;

    protected string|Closure|null $labelledBy = null;

    protected string|Closure|null $name = null;

    protected string|Closure|null $value = null;

    /**
     * The grouped buttons (default slot).
     *
     * @param  iterable<array-key, mixed>|Closure  $buttons
     */
    public function buttons(iterable|Closure $buttons): static
    {
        $this->slots[static::DEFAULT_SLOT] = $buttons;

        return $this;
    }

    public function labelledBy(string|Closure|null $labelledBy): static
    {
        $this->labelledBy = $labelledBy;

        return $this;
    }

    /** Renders a hidden input posting the selected value under this name. */
    public function name(string|Closure|null $name): static
    {
        $this->name = $name;

        return $this;
    }

    /** The currently selected value, posted by the hidden input. */
    public function value(string|Closure|null $value): static
    {
        $this->value = $value;

        return $this;
    }

    protected function tagName(): string
    {
        return 'craft-button-group';
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'id' => $this->getId(),
            'role' => 'group',
            'aria' => [
                'labelledby' => $this->evaluate($this->labelledBy),
            ],
        ];
    }

    /**
     * Wraps the group in the `<craft-listbox>` that owns selection behavior,
     * alongside the hidden input, mirroring the buttonGroup template.
     */
    #[\Override]
    protected function renderMarkup(): string
    {
        $name = $this->evaluate($this->name);
        $id = $this->getId();

        return Html::tag(
            'craft-listbox',
            parent::renderMarkup().($name !== null
                ? (string) Html::hiddenInput($name, (string) ($this->evaluate($this->value) ?? ''), [
                    'id' => $id !== null ? "$id-input" : null,
                ])
                : ''),
            [
                'disabled' => $this->isDisabled(),
            ],
        );
    }
}
