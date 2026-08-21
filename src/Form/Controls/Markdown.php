<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\Controls\Concerns\HasTextExpander;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use Illuminate\Support\Arr;

/**
 * A Markdown string Control. Its canonical value is a plain string or null;
 * Markdown is authored and previewed by the renderer, never accepted as HTML.
 */
class Markdown extends Control
{
    use HasTextExpander;

    private int $rows = 8;

    private ?string $placeholder = null;

    private ?int $maxLength = null;

    /** @var list<string> */
    private array $toolbarButtons = [];

    private bool $showToolbar = true;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        $markdown = Html::tag('craft-markdown-field', Html::encode((string) ($value ?? '')), [
            'id' => $attributes['id'],
            'name' => $attributes['name'],
            'rows' => $control->props['rows'] ?? 8,
            'placeholder' => $control->props['placeholder'] ?? null,
            'max-length' => $control->props['maxLength'] ?? null,
            'toolbar-buttons' => Json::encode($control->props['toolbarButtons'] ?? []),
            'show-toolbar' => (bool) ($control->props['showToolbar'] ?? true) && $attributes['name'] !== null,
            'sanitize-html' => true,
            'disabled' => $attributes['name'] === null,
            'required' => $attributes['required'],
            'aria' => [
                'invalid' => $attributes['aria']['invalid'] ?? null,
                'describedby' => $attributes['aria']['describedby'] ?? null,
            ],
        ]);

        return $markdown.self::textExpanderHtml($control, $attributes);
    }

    public function component(): string
    {
        return 'craft:markdown';
    }

    public function rows(int $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    public function placeholder(?string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function maxLength(?int $maxLength): static
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    /** @param list<string> $toolbarButtons */
    public function toolbarButtons(array $toolbarButtons): static
    {
        $this->toolbarButtons = $toolbarButtons;

        return $this;
    }

    public function showToolbar(bool $showToolbar = true): static
    {
        $this->showToolbar = $showToolbar;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return Arr::whereNotNull([
            'rows' => $this->rows,
            'placeholder' => $this->placeholder,
            'maxLength' => $this->maxLength,
            'toolbarButtons' => $this->toolbarButtons,
            'showToolbar' => $this->showToolbar,
            ...$this->textExpanderProps(),
        ]);
    }
}
