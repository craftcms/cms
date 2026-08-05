<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Support\Html;

/**
 * Checkbox select — the PHP counterpart to the legacy
 * `_includes/forms/checkboxSelect` template. Renders a
 * `fieldset.cp-checkbox-select` of {@see Checkbox} items, with an optional
 * "All" checkbox (or an always-post hidden input) first, optionally wrapped
 * in a `<craft-sortable-checkbox-select>` for drag reordering.
 *
 *     CheckboxSelect::make()
 *         ->id('sources')
 *         ->name('sources')
 *         ->allCheckbox(Checkbox::make()->label(t('All'))->name('sources')->value('*'))
 *         ->options([...]);
 */
class CheckboxSelect extends ChoiceGroup
{
    use HasDisabled;

    protected ?Checkbox $allCheckbox = null;

    protected bool $sortable = false;

    protected ?string $storageKey = null;

    protected function tagName(): string
    {
        return 'fieldset';
    }

    /** The "All" checkbox, rendered before the items. */
    public function allCheckbox(?Checkbox $allCheckbox): static
    {
        $this->allCheckbox = $allCheckbox;

        return $this;
    }

    /** Wraps the fieldset in a `<craft-sortable-checkbox-select>`. */
    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    /** Storage key for persisting the sort order client-side. */
    public function storageKey(?string $storageKey): static
    {
        $this->storageKey = $storageKey;

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'id' => $this->getId(),
            'class' => 'cp-checkbox-select',
            'data' => [
                'storage-key' => $this->storageKey,
            ],
        ];
    }

    /**
     * The "All" checkbox — or, without one, the always-post hidden input for
     * a non-array name.
     */
    #[\Override]
    protected function leadingHtml(): string
    {
        if ($this->allCheckbox !== null) {
            return Html::tag('div', $this->allCheckbox->toHtml());
        }

        if ($this->name !== null && (strlen($this->name) < 3 || ! str_ends_with($this->name, '[]'))) {
            return (string) Html::hiddenInput($this->name, '');
        }

        return '';
    }

    #[\Override]
    /** @return array<string, mixed> */
    protected function optionWrapperAttributes(ViewComponent $option): array
    {
        return ['class' => 'cp-checkbox-select__item'];
    }

    /** Wraps the fieldset in the sortable web component when enabled. */
    #[\Override]
    protected function renderMarkup(): string
    {
        $html = parent::renderMarkup();

        if (! $this->sortable) {
            return $html;
        }

        return Html::tag('craft-sortable-checkbox-select', $html, [
            'disabled' => $this->isDisabled(),
        ]);
    }
}
