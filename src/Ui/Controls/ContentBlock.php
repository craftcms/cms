<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Controls;

use CraftCms\Cms\Cp\Components\Button;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\ControlPayload;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use InvalidArgumentException;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class ContentBlock extends Control
{
    private ?Ui $ui = null;

    private ?string $addLabel = null;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, UiHtmlRenderer $renderer): string
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

        $ui = $control->uis[0] ?? null;
        $content = $ui === null
            ? Html::tag('craft-spinner', '', ['label' => t('Loading')])
            : $renderer->renderNestedUi($ui);
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

    public function ui(Ui $ui): static
    {
        $this->ui = $ui;

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
    public function nestsUis(): bool
    {
        return true;
    }

    #[\Override]
    public function nestedUis(mixed $value = null): array
    {
        if ($value === null) {
            return [];
        }

        if ($this->ui === null) {
            throw new InvalidArgumentException('Non-empty Content Block Controls require a nested Ui.');
        }

        return [[
            'scope' => [],
            'ui' => $this->ui,
            'refreshable' => true,
        ]];
    }
}
