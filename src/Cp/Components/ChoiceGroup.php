<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Support\Html;

/**
 * Base class for choice-group containers (checkbox groups, checkbox selects,
 * radio groups): a host element rendering option components, each in its own
 * wrapper element, with hooks for leading/trailing content.
 */
abstract class ChoiceGroup extends ViewComponent
{
    use HasId;

    /** @var iterable<mixed>|Closure */
    protected iterable|Closure $options = [];

    protected string|Closure|null $name = null;

    /** @param iterable<mixed>|Closure $options */
    public function options(iterable|Closure $options): static
    {
        $this->options = $options;

        return $this;
    }

    /** The group's base input name. */
    public function name(string|Closure|null $name): static
    {
        $this->name = $name;

        return $this;
    }

    /** Rendered before the options (hidden inputs, an "All" checkbox, …). */
    protected function leadingHtml(): string
    {
        return '';
    }

    /** Rendered after the options (add-option buttons, …). */
    protected function trailingHtml(): string
    {
        return '';
    }

    /** Attributes for each option's wrapper element. */
    protected function optionWrapperAttributes(ViewComponent $option): array
    {
        return [];
    }

    #[\Override]
    protected function renderSlots(): string
    {
        $options = $this->evaluatedOptions();
        $html = [$this->leadingHtml()];

        foreach ($options as $option) {
            $html[] = Html::tag('div', $option->toHtml(), $this->optionWrapperAttributes($option));
        }

        $html[] = $this->trailingHtml();

        return implode('', $html).parent::renderSlots();
    }

    /** @return iterable<ViewComponent> */
    protected function evaluatedOptions(): iterable
    {
        return $this->evaluate($this->options);
    }
}
