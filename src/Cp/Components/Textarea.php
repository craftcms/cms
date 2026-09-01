<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use Stringable;

/**
 * PHP counterpart to the `<craft-textarea>` web component, mirroring the
 * `_includes/forms/textarea` template. The native textarea renders in the
 * light DOM (the web component adopts it as its form control), so input
 * namespacing, form posting and legacy JS hooks (chars-left counters) keep
 * working.
 *
 *     Textarea::make()
 *         ->name('notes')
 *         ->value($entry->notes)
 *         ->rows(4);
 *
 * Unlike {@see Input}, `autofocus()` is honored for any current user (no
 * `getAutofocusPreferred()` check) — matching the legacy textarea template.
 */
class Textarea extends ViewComponent
{
    use HasDisabled;
    use HasId;

    protected ?string $name = null;

    protected string|int|float|Stringable|null $value = null;

    protected ?int $maxlength = null;

    protected int $rows = 2;

    protected ?int $cols = null;

    protected bool $monospace = false;

    protected bool $autofocus = false;

    protected bool $readOnly = false;

    protected ?string $title = null;

    protected ?string $placeholder = null;

    protected ?string $inputmode = null;

    protected bool $showCharsLeft = false;

    protected ?string $describedBy = null;

    /** @var array<string, mixed> Additional attributes for the native textarea. */
    protected array $inputAttributes = [];

    protected function tagName(): string
    {
        return 'craft-textarea';
    }

    public function name(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function value(string|int|float|Stringable|null $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function maxlength(?int $maxlength): static
    {
        $this->maxlength = $maxlength;

        return $this;
    }

    /** Number of visible text lines. Defaults to `2`. */
    public function rows(int $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    /**
     * Visible character width. Defaults to `50` when rendered, but leaving it
     * unconfigured (`null`) keeps the textarea full-width — configuring it
     * (to any value) drops the `fullwidth` class, matching the legacy
     * template.
     */
    public function cols(?int $cols): static
    {
        $this->cols = $cols;

        return $this;
    }

    /** Renders the textarea value in a monospace font. */
    public function monospace(bool $monospace = true): static
    {
        $this->monospace = $monospace;

        return $this;
    }

    /** Honored for any current user (and not on mobile) — no autofocus-preference check. */
    public function autofocus(bool $autofocus = true): static
    {
        $this->autofocus = $autofocus;

        return $this;
    }

    public function readOnly(bool $readOnly = true): static
    {
        $this->readOnly = $readOnly;

        return $this;
    }

    public function title(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function placeholder(?string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function inputmode(?string $inputmode): static
    {
        $this->inputmode = $inputmode;

        return $this;
    }

    /**
     * Marks the textarea for the legacy chars-left counter JS. Unlike
     * {@see Input}, no padding is added to make room for the counter.
     */
    public function showCharsLeft(bool $showCharsLeft = true): static
    {
        $this->showCharsLeft = $showCharsLeft;

        return $this;
    }

    public function describedBy(?string $describedBy): static
    {
        $this->describedBy = $describedBy;

        return $this;
    }

    /**
     * Merges additional HTML attributes onto the native textarea element.
     * These win over the computed defaults, so they can also override things
     * like `rows` or `cols`.
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
        return [
            'monospace' => $this->monospace,
            // Lion pushes these host properties onto the slotted textarea
            // when the element upgrades (LionTextarea::updated()),
            // overwriting the server-rendered attributes with its defaults —
            // so any non-default value must also live on the host. The
            // native textarea keeps them too, for pre-upgrade correctness
            // (form posts, CSS).
            'placeholder' => $this->placeholder !== null && $this->placeholder !== '' ? $this->placeholder : null,
            'name' => $this->name !== null && $this->name !== '' ? $this->name : null,
            'disabled' => $this->isDisabled(),
            'readonly' => $this->readOnly,
            'rows' => $this->rows !== 2 ? $this->rows : null,
        ];
    }

    #[\Override]
    protected function renderSlots(): string
    {
        return $this->textareaHtml().parent::renderSlots();
    }

    protected function textareaHtml(): string
    {
        $attributes = Arr::merge([
            'slot' => 'input',
            'id' => $this->getId(),
            'class' => array_filter([
                'text',
                $this->cols === null ? 'fullwidth' : null,
            ]),
            'inputmode' => $this->inputmode,
            'rows' => $this->rows,
            'cols' => $this->cols ?? 50,
            'name' => $this->name,
            'maxlength' => $this->maxlength,
            'autofocus' => $this->autofocus
                && ! request()->isMobileBrowser(true),
            'disabled' => $this->isDisabled(),
            'readonly' => $this->readOnly,
            'title' => $this->title,
            'placeholder' => $this->placeholder,
            'aria' => [
                'describedby' => $this->describedBy,
            ],
            'data' => [
                'show-chars-left' => $this->showCharsLeft ?: null,
            ],
        ], $this->inputAttributes);

        return Html::tag('textarea', Html::encode((string) ($this->value ?? '')), $attributes);
    }
}
