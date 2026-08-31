<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

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

    /** @var iterable<ViewComponent> */
    protected iterable $options = [];

    protected ?string $name = null;

    /** @param iterable<ViewComponent> $options */
    public function options(iterable $options): static
    {
        $this->options = $options;

        return $this;
    }

    /** The group's base input name. */
    public function name(?string $name): static
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

    /**
     * Attributes for each option's wrapper element.
     *
     * @return array<string, mixed>
     */
    protected function optionWrapperAttributes(ViewComponent $option): array
    {
        return [];
    }

    /** Rendered inside an option's wrapper, before the option itself. */
    protected function optionLeadingHtml(ViewComponent $option): string
    {
        return '';
    }

    #[\Override]
    protected function renderSlots(): string
    {
        $html = [$this->leadingHtml()];

        foreach ($this->options as $option) {
            $html[] = Html::tag(
                'div',
                $this->optionLeadingHtml($option).$option->toHtml(),
                $this->optionWrapperAttributes($option),
            );
        }

        $html[] = $this->trailingHtml();

        return implode('', $html).parent::renderSlots();
    }
}
