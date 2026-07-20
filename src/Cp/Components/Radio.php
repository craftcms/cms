<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Support\Html;

use function CraftCms\Cms\t;

/**
 * PHP counterpart to the `<craft-radio>` web component. Shares the light-DOM
 * SSR surface with {@see Checkbox} — a native input and label the web
 * component adopts — with radio posting semantics: no always-post hidden
 * input (the group posts a single value), and custom-option mode labeled
 * "Other:" with the text input in its own wrapper.
 */
class Radio extends Checkbox
{
    #[\Override]
    protected function tagName(): string
    {
        return 'craft-radio';
    }

    /** Radios have no indeterminate state, so drop the Checkbox host attribute. */
    #[\Override]
    protected function hostAttributes(): array
    {
        return [];
    }

    #[\Override]
    protected function inputDefaults(): array
    {
        return [
            'type' => 'radio',
            'class' => ['radio'],
        ];
    }

    #[\Override]
    protected function rendersAlwaysPostInput(): bool
    {
        return false;
    }

    #[\Override]
    protected function customLabelText(): string
    {
        return t('Other:');
    }

    #[\Override]
    protected function customInputHtml(): string
    {
        $html = parent::customInputHtml();

        return $html === '' ? '' : Html::tag('div', $html, ['class' => 'custom-option-wrapper']);
    }
}
