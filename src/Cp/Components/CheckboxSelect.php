<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Enums\CheckboxSelectPresentation;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\Enums\AllOptionMode;
use Illuminate\Contracts\Support\Htmlable;
use Stringable;

/**
 * Checkbox select — the PHP counterpart to `<craft-checkbox-select>`. Renders
 * the element with a `div.cp-checkbox-select__item` around each
 * {@see Checkbox}, an optional “All” option first, and a hidden input that
 * posts an empty value while nothing is checked.
 *
 *     CheckboxSelect::make()
 *         ->id('sources')
 *         ->name('sources')
 *         ->allOption(t('All'))
 *         ->options([...]);
 *
 * Give the options `{name}[]`; with the “All” option checked in single-value
 * mode, leave their names off so only its value posts, and the element
 * restores them once it's unchecked.
 *
 * @since 6.0.0
 */
class CheckboxSelect extends ChoiceGroup
{
    use HasDisabled;

    protected string|Htmlable|Stringable|null $allLabel = null;

    protected string $allValue = '*';

    protected AllOptionMode $allMode = AllOptionMode::SingleValue;

    protected bool $allChecked = false;

    protected ?string $allName = null;

    protected bool $sortable = false;

    protected ?string $label = null;

    protected CheckboxSelectPresentation $presentation = CheckboxSelectPresentation::Checkboxes;

    protected function tagName(): string
    {
        return 'craft-checkbox-select';
    }

    /**
     * Adds an “All” option before the others. In single-value mode it posts
     * `$value` alone while checked, under `{name}[]` unless `$name` says
     * otherwise; in each-value mode it checks every option.
     */
    public function allOption(
        string|Htmlable|Stringable|null $label,
        string $value = '*',
        AllOptionMode $mode = AllOptionMode::SingleValue,
        bool $checked = false,
        ?string $name = null,
    ): static {
        $this->allLabel = $label;
        $this->allValue = $value;
        $this->allMode = $mode;
        $this->allChecked = $checked;
        $this->allName = $name;

        return $this;
    }

    /**
     * A visible label, which also names the group. Leave it off inside a
     * field, which labels the group itself.
     */
    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /** Lets the options be put in order. */
    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function presentation(CheckboxSelectPresentation $presentation): static
    {
        $this->presentation = $presentation;

        return $this;
    }

    /**
     * @deprecated in 6.0.0. The sort order is now always posted with the
     * value, so it isn't stored client-side.
     */
    public function storageKey(?string $storageKey): static
    {
        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        $name = $this->baseName();

        return [
            'id' => $this->getId(),
            'class' => 'cp-checkbox-select',
            'name' => $name,
            'label' => $this->label,
            'sortable' => $this->sortable,
            'presentation' => $this->presentation === CheckboxSelectPresentation::Checkboxes
                ? null
                : $this->presentation->value,
            'disabled' => $this->isDisabled(),
            'all-label' => $this->allLabel !== null ? strip_tags((string) $this->allLabel) : null,
            'all-value' => $this->allLabel !== null && $this->allValue !== '*' ? $this->allValue : null,
            'all-mode' => $this->allLabel !== null && $this->allMode === AllOptionMode::EachValue ? 'each-value' : null,
        ];
    }

    /** The empty-value input, then the “All” option. */
    #[\Override]
    protected function leadingHtml(): string
    {
        $html = '';
        $name = $this->baseName();

        if ($name !== null && ! str_ends_with((string) $this->name, '[]')) {
            $html .= Html::hiddenInput($name, '', ['data' => ['empty' => true]]);
        }

        if ($this->allLabel !== null) {
            $html .= Html::tag('div', Checkbox::make()
                ->id("{$this->getId()}-all")
                ->label($this->allLabel)
                ->name($name !== null && $this->allMode === AllOptionMode::SingleValue ? ($this->allName ?? "{$name}[]") : null)
                ->value($this->allValue)
                ->checked($this->allChecked)
                ->disabled($this->isDisabled())
                ->toHtml(), ['class' => ['cp-checkbox-select__item', 'all']]);
        }

        return $html;
    }

    #[\Override]
    /** @return array<string, mixed> */
    protected function optionWrapperAttributes(ViewComponent $option): array
    {
        return ['class' => 'cp-checkbox-select__item'];
    }

    /** The name without a trailing `[]`. */
    private function baseName(): ?string
    {
        return $this->name === null ? null : preg_replace('/\[\]$/', '', $this->name);
    }
}
