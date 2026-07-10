<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;

/**
 * Radio group container — the PHP counterpart to the legacy
 * `_includes/forms/radioGroup` template. Renders {@see Radio} options, each
 * in its own wrapper; a single value posts under the shared name, so there is
 * no always-post hidden input.
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
    protected bool|Closure $toggle = false;

    protected string|Closure|null $targetPrefix = null;

    protected function tagName(): string
    {
        return 'div';
    }

    /** Marks the group as a field toggle (reveals `{targetPrefix}{value}` containers). */
    public function toggle(bool|Closure $toggle = true): static
    {
        $this->toggle = $toggle;

        return $this;
    }

    public function targetPrefix(string|Closure|null $targetPrefix): static
    {
        $this->targetPrefix = $targetPrefix;

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        $toggle = (bool) $this->evaluate($this->toggle);

        return [
            'id' => $this->getId(),
            'class' => array_filter([
                'radio-group',
                $toggle ? 'fieldtoggle' : null,
            ]),
            'data' => [
                'target-prefix' => $toggle ? ($this->evaluate($this->targetPrefix) ?? '#') : null,
            ],
        ];
    }
}
