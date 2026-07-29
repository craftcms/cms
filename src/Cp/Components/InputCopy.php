<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use Stringable;

/**
 * PHP counterpart to the `<craft-input-copy>` web component — a read-only
 * text input with an integrated copy-to-clipboard button in the suffix.
 *
 *     InputCopy::make()
 *         ->name('apiKey')
 *         ->value($apiKey);
 *
 * When the value displayed in the textbox should differ from what is sent to
 * the clipboard (e.g. a masked token), pass the full value via
 * {@see self::copyValue()}:
 *
 *     InputCopy::make()
 *         ->value('sk-••••••••••••••1234')
 *         ->copyValue($fullToken);
 *
 * The component is always read-only; {@see self::disabled()} is supported for
 * cases where the field should be entirely inert.
 */
class InputCopy extends ViewComponent
{
    use HasDisabled;
    use HasId;

    protected string|Closure|null $name = null;

    protected string|int|float|Stringable|Closure|null $value = null;

    /**
     * Value sent to the clipboard when the copy button is clicked. When
     * omitted, the displayed value is used instead.
     */
    protected string|int|float|Stringable|Closure|null $copyValue = null;

    protected bool|Closure $monospace = false;

    protected string|Closure|null $labelledBy = null;

    protected string|Closure|null $describedBy = null;

    /** @var array<string, mixed> Additional attributes for the native input. */
    protected array $inputAttributes = [];

    protected function tagName(): string
    {
        return 'craft-input-copy';
    }

    public function name(string|Closure|null $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function value(string|int|float|Stringable|Closure|null $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Value sent to the clipboard when the copy button is clicked. When
     * omitted, the displayed value is copied instead.
     */
    public function copyValue(string|int|float|Stringable|Closure|null $copyValue): static
    {
        $this->copyValue = $copyValue;

        return $this;
    }

    /** Renders the input value in a monospace font. */
    public function monospace(bool|Closure $monospace = true): static
    {
        $this->monospace = $monospace;

        return $this;
    }

    public function labelledBy(string|Closure|null $labelledBy): static
    {
        $this->labelledBy = $labelledBy;

        return $this;
    }

    public function describedBy(string|Closure|null $describedBy): static
    {
        $this->describedBy = $describedBy;

        return $this;
    }

    /**
     * Merges additional HTML attributes onto the native input element.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function inputAttributes(array $attributes): static
    {
        $this->inputAttributes = Arr::merge(
            static::normalizeClasses($this->inputAttributes),
            static::normalizeClasses($attributes),
        );

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        $copyValue = $this->evaluate($this->copyValue);
        $name = $this->evaluate($this->name);

        return [
            'monospace' => (bool) $this->evaluate($this->monospace),
            'copy-value' => $copyValue !== null ? (string) $copyValue : null,
            'disabled' => $this->isDisabled(),
            // Lion reads name/disabled from the host to keep them in sync with
            // the slotted input after upgrade.
            'name' => $name !== null && $name !== '' ? (string) $name : null,
        ];
    }

    #[\Override]
    protected function renderSlots(): string
    {
        return $this->inputHtml().parent::renderSlots();
    }

    protected function inputHtml(): string
    {
        $value = $this->evaluate($this->value);

        $attributes = Arr::merge([
            'slot' => 'input',
            'type' => 'text',
            'id' => $this->getId(),
            'class' => ['text', 'fullwidth'],
            'name' => $this->evaluate($this->name),
            'value' => $value !== null ? (string) $value : null,
            'readonly' => true,
            'disabled' => $this->isDisabled(),
            'aria' => [
                'labelledby' => $this->evaluate($this->labelledBy),
                'describedby' => $this->evaluate($this->describedBy) ?: null,
            ],
        ], $this->inputAttributes);

        return Html::tag('input', '', $attributes);
    }
}
