<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\Components\Button;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Support\Html;
use InvalidArgumentException;

use function CraftCms\Cms\t;

class ContentBlock extends Control
{
    private ?Form $form = null;

    private ?string $addLabel = null;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        $editable = $attributes['name'] !== null;
        $clear = $editable ? (string) Html::hiddenInput((string) $attributes['name'], '') : '';

        if ($value === null) {
            $button = $editable
                ? Button::make()
                    ->label($control->props['addLabel'])
                    ->icon('plus')
                    ->attributes(['data-content-block-add' => true])
                    ->toHtml()
                : '';

            return Html::tag('craft-content-block-input', $clear.Html::tag('craft-empty', $button, [
                'label' => $control->props['emptyLabel'],
            ]), [
                'add-label' => $control->props['addLabel'],
                'clear-label' => $control->props['clearLabel'],
                'empty-label' => $control->props['emptyLabel'],
            ]);
        }

        $form = $control->forms[0] ?? null;
        $content = $form === null
            ? Html::tag('craft-spinner', '', ['label' => t('Loading')])
            : $renderer->renderNestedForm($form);
        $remove = $editable
            ? Button::make()
                ->label($control->props['clearLabel'])
                ->icon('trash')
                ->attributes(['data-content-block-remove' => true])
                ->toHtml()
            : '';

        return Html::tag('craft-content-block-input', $clear.Html::tag('div', $content.$remove, [
            'class' => 'pane',
            'data-content-block' => true,
        ]), [
            'add-label' => $control->props['addLabel'],
            'clear-label' => $control->props['clearLabel'],
            'empty-label' => $control->props['emptyLabel'],
        ]);
    }

    public function component(): string
    {
        return 'craft:content-block';
    }

    public function form(Form $form): static
    {
        $this->form = $form;

        return $this;
    }

    public function addLabel(string $addLabel): static
    {
        $this->addLabel = $addLabel;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        if ($value !== null && ! is_array($value)) {
            throw new InvalidArgumentException('Content Block values must be arrays or null.');
        }

        return [
            'addLabel' => $this->addLabel ?? t('Add content'),
            'clearLabel' => t('Clear content'),
            'emptyLabel' => t('No content.'),
        ];
    }

    #[\Override]
    public function nestedForms(mixed $value = null): array
    {
        if ($value === null) {
            return [];
        }

        if ($this->form === null) {
            throw new InvalidArgumentException('Non-empty Content Block Controls require a nested Form.');
        }

        return [[
            'scope' => [],
            'form' => $this->form,
            'refreshable' => true,
        ]];
    }
}
