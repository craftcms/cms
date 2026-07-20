<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Support\Html;

/**
 * PHP counterpart to the `<craft-button-group>` web component: a group of
 * buttons. With `name` set the component enters radio mode — it owns single
 * selection and, being form-associated, submits the selected `value` itself
 * (no hidden input needed). Each option button carries its own `value`, which
 * the component matches against the group's `value`.
 *
 *     ButtonGroup::make()
 *         ->name('alignment')
 *         ->value('left')
 *         ->buttons([
 *             Button::make()->label(t('Left'))->value('left'),
 *             Button::make()->label(t('Right'))->value('right'),
 *         ]);
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

    #[\Override]
    protected function renderMarkup(): string
    {
        return Html::tag('craft-listbox',
            Html::tag('craft-button-group',
                $this->renderSlots(),
                $this->renderedAttributes()
            )
        );
    }

    /**
     * The `<craft-button-group>` web component is form-associated: with `name`
     * set it enters radio mode, owns selection, and submits the selected
     * `value` itself (via ElementInternals). So `name`/`value` ride on the host
     * — no wrapping `<craft-listbox>` or hidden input is needed. Child buttons
     * carry their own `value`, which the component matches against the group's.
     */
    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->evaluate($this->name),
            'value' => $this->evaluate($this->value),
            'role' => 'group',
            'aria' => [
                'labelledby' => $this->evaluate($this->labelledBy),
            ],
        ];
    }
}
