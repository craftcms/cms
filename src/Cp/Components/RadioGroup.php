<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

/**
 * Radio group container — the PHP counterpart to the `<craft-radio-group>`
 * web component (and to the legacy `_includes/forms/radioGroup` template).
 * Renders {@see Radio} options, each in its own wrapper; a single value posts
 * under the shared name, so there is no always-post hidden input. The web
 * component adopts that shared name from the slotted radio inputs.
 *
 *     RadioGroup::make()
 *         ->id('mode')
 *         ->options([
 *             Radio::make()->label(t('Auto'))->name('mode')->value('auto'),
 *             Radio::make()->label(t('Manual'))->name('mode')->value('manual'),
 *         ]);
 */
class RadioGroup extends ChoiceGroup
{
    protected bool $toggle = false;

    protected ?string $targetPrefix = null;

    protected function tagName(): string
    {
        return 'craft-radio-group';
    }

    /** Marks the group as a field toggle (reveals `{targetPrefix}{value}` containers). */
    public function toggle(bool $toggle = true): static
    {
        $this->toggle = $toggle;

        return $this;
    }

    public function targetPrefix(?string $targetPrefix): static
    {
        $this->targetPrefix = $targetPrefix;

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'id' => $this->getId(),
            'class' => array_filter([
                'radio-group',
                $this->toggle ? 'fieldtoggle' : null,
            ]),
            'data' => [
                'target-prefix' => $this->toggle ? ($this->targetPrefix ?? '#') : null,
            ],
        ];
    }
}
