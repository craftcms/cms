<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;

/**
 * PHP counterpart to the `<craft-button-group>` web component: a group of
 * buttons. With `name` set the component owns selection and, being
 * form-associated, submits the selected value itself. Each option button
 * carries its own `value`.
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

    protected ?string $labelledBy = null;

    protected ?string $name = null;

    protected ?string $value = null;

    protected bool $multiple = false;

    protected function tagName(): string
    {
        return 'craft-button-group';
    }

    /**
     * The grouped buttons (default slot).
     *
     * @param  iterable<array-key, mixed>  $buttons
     */
    public function buttons(iterable $buttons): static
    {
        $this->slots[static::DEFAULT_SLOT] = $buttons;

        return $this;
    }

    public function labelledBy(?string $labelledBy): static
    {
        $this->labelledBy = $labelledBy;

        return $this;
    }

    /** The form field name. */
    public function name(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /** The currently selected value. */
    public function value(?string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    /**
     * The `<craft-button-group>` web component is form-associated: with `name`
     * set it owns selection and submits through ElementInternals. `name` and
     * `value` therefore belong on the host.
     */
    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->name,
            'value' => $this->value,
            'multiple' => $this->multiple,
            'role' => $this->name !== null && ! $this->multiple ? 'radiogroup' : 'group',
            'aria' => [
                'labelledby' => $this->labelledBy,
            ],
        ];
    }
}
