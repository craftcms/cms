<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use Illuminate\Contracts\Support\Htmlable;

use function CraftCms\Cms\t;

/**
 * Checkbox group container — the PHP counterpart to the legacy
 * `_includes/forms/checkboxGroup` template. Renders an always-post hidden
 * input followed by {@see Checkbox} children, each in its own wrapper
 * element, plus an optional "Add option" button for custom options.
 *
 *     CheckboxGroup::make()
 *         ->id('colors')
 *         ->name('colors')
 *         ->options([
 *             Checkbox::make()->label(t('Red'))->name('colors[]')->value('red'),
 *             Checkbox::make()->label(t('Blue'))->name('colors[]')->value('blue'),
 *         ]);
 *
 * @since 6.0.0
 */
class CheckboxGroup extends ChoiceGroup
{
    protected string|Htmlable|null $customOptionTemplate = null;

    protected function tagName(): string
    {
        return 'craft-checkbox-group';
    }

    /**
     * Enables user-added custom options. The template is the markup for one
     * new option, with `__ID__` placeholders for the generated input id, and
     * should already be namespaced for the active input namespace.
     */
    public function customOptionTemplate(string|Htmlable|null $template): static
    {
        $this->customOptionTemplate = $template;

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'id' => $this->getId(),
            'class' => 'checkbox-group',
        ];
    }

    /** The always-post hidden input. */
    #[\Override]
    protected function leadingHtml(): string
    {
        return $this->name !== null ? (string) Html::hiddenInput($this->name, '') : '';
    }

    #[\Override]
    /** @return array<string, mixed> */
    protected function optionWrapperAttributes(ViewComponent $option): array
    {
        return [
            'data' => [
                'custom' => $option instanceof Checkbox && $option->hasCustomInput() ? true : null,
            ],
        ];
    }

    #[\Override]
    protected function trailingHtml(): string
    {
        return $this->customOptionsHtml();
    }

    /**
     * The "Add option" button, with the custom-option wiring registered on
     * the JS stack (ported from the legacy checkboxGroup template).
     */
    protected function customOptionsHtml(): string
    {
        if ($this->customOptionTemplate === null || $this->customOptionTemplate === '') {
            return '';
        }

        $id = $this->getId();
        $addButtonId = "$id-add-btn";

        HtmlStack::jsWithVars(fn ($container, $button, $optionHtml) => <<<JS
            (() => {
              const container = document.querySelector($container);
              const button = document.querySelector($button);
              if (!container || !button) {
                return;
              }
              const customOptionHtml = $optionHtml;

              const initCustomOption = (option) => {
                const checkbox = option.querySelector('input[type=checkbox]');
                const text = option.querySelector('.text');
                if (!checkbox || !text) {
                  return;
                }
                checkbox.addEventListener('change', () => {
                  if (checkbox.checked) {
                    text.disabled = false;
                    text.classList.remove('disabled', 'noteditable');
                    text.focus();
                  } else if (text.value !== '') {
                    text.disabled = true;
                    text.classList.add('disabled', 'noteditable');
                  } else {
                    option.remove();
                    button.focus();
                  }
                });
                text.addEventListener('input', () => {
                  checkbox.value = text.value;
                });
              };

              for (const option of container.querySelectorAll(':scope > [data-custom]')) {
                initCustomOption(option);
              }

              // craft-button fires click for Enter and Space too
              button.addEventListener('click', () => {
                const id = 'option' + Math.floor(Math.random() * 1000000000);
                const template = document.createElement('template');
                template.innerHTML = customOptionHtml.replace(/__ID__/g, id).trim();
                const nodes = [...template.content.childNodes];
                button.before(...nodes);
                const newOption = nodes.find((node) => node instanceof HTMLElement);
                if (newOption) {
                  initCustomOption(newOption);
                  newOption.querySelector('.text')?.focus();
                }
              });
            })();
            JS, [
            'container' => '#'.InputNamespace::namespaceId($id),
            'button' => '#'.InputNamespace::namespaceId($addButtonId),
            'optionHtml' => $this->customOptionTemplate instanceof Htmlable
                ? $this->customOptionTemplate->toHtml()
                : $this->customOptionTemplate,
        ]);

        return Button::make()
            ->id($addButtonId)
            ->label(t('Add option'))
            ->attributes(['class' => ['dashed', 'small', 'icon', 'add']])
            ->toHtml();
    }
}
