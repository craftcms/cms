<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Cp\Concerns\HasSize;
use CraftCms\Cms\Form\Controls\Concerns\HasTextExpander;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use Stringable;

use function CraftCms\Cms\currentUserElement;
use function CraftCms\Cms\t;

/**
 * PHP counterpart to the `<craft-input>` web component, mirroring the
 * `_includes/forms/text` template. The native input renders in the light DOM
 * (the web component adopts it as its form control), so input namespacing,
 * form posting and legacy JS hooks (chars-left counters, combobox wiring)
 * keep working.
 *
 *     Input::make()
 *         ->name('siteName')
 *         ->value($site->name)
 *         ->maxlength(255);
 *
 * `size()` is the shared control size (`small`/`medium`/`large`); the native
 * input's character-width `size` attribute is {@see self::inputSize()}.
 *
 * @phpstan-import-type TextExpanderTriggers from HasTextExpander
 */
class Input extends ViewComponent
{
    use HasDisabled;
    use HasId;
    use HasSize;

    protected string $type = 'text';

    protected ?string $name = null;

    protected string|int|float|Stringable|null $value = null;

    protected ?int $maxlength = null;

    /** Native input `size` attribute (character width). */
    protected ?int $inputSize = null;

    protected ?string $width = null;

    protected bool $small = false;

    protected bool $center = false;

    protected bool $monospace = false;

    protected bool $hiddenInput = false;

    protected bool $autofocus = false;

    protected bool|string $autocomplete = false;

    protected bool $autocorrect = true;

    protected bool $autocapitalize = true;

    protected bool $readOnly = false;

    protected ?string $title = null;

    protected ?string $placeholder = null;

    protected string|int|float|null $min = null;

    protected string|int|float|null $max = null;

    protected string|int|float|null $step = null;

    protected ?string $inputmode = null;

    protected ?string $orientation = null;

    protected ?string $role = null;

    protected bool|string|null $expanded = null;

    protected string|Stringable|null $suffix = null;

    protected ?string $descriptionId = null;

    protected bool $showCharsLeft = false;

    protected ?string $labelledBy = null;

    protected ?string $describedBy = null;

    /** @var array<string, mixed> Additional attributes for the native input. */
    protected array $inputAttributes = [];

    /** @var TextExpanderTriggers */
    protected array $textExpanderTriggers = [];

    protected function tagName(): string
    {
        return 'craft-input';
    }

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
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

    /**
     * Maximum character count. Reflected on the host, where it also shrinks
     * the control to the expected character width (see {@see self::width()}).
     */
    public function maxlength(?int $maxlength): static
    {
        $this->maxlength = $maxlength;

        return $this;
    }

    /** The native input's character-width `size` attribute. */
    public function inputSize(?int $inputSize): static
    {
        $this->inputSize = $inputSize;

        return $this;
    }

    /**
     * Overrides the inferred width behavior: `full` spans the column despite
     * a `maxlength`; `auto` shrinks to the input's intrinsic width without
     * one.
     */
    public function width(?string $width): static
    {
        $this->width = $width;

        return $this;
    }

    /** Renders the input at a smaller size. */
    public function small(bool $small = true): static
    {
        $this->small = $small;

        return $this;
    }

    /** Center-aligns the input text. */
    public function center(bool $center = true): static
    {
        $this->center = $center;

        return $this;
    }

    /** Renders the input value in a monospace font. */
    public function monospace(bool $monospace = true): static
    {
        $this->monospace = $monospace;

        return $this;
    }

    /**
     * Visually hides the control while keeping it form-bound, so its value
     * still submits.
     */
    public function hiddenInput(bool $hiddenInput = true): static
    {
        $this->hiddenInput = $hiddenInput;

        return $this;
    }

    /** Honored only when the current user prefers autofocus (and not on mobile). */
    public function autofocus(bool $autofocus = true): static
    {
        $this->autofocus = $autofocus;

        return $this;
    }

    /** Booleans render as `on`/`off`; strings (e.g. `postal-code`) pass through. */
    public function autocomplete(bool|string $autocomplete): static
    {
        $this->autocomplete = $autocomplete;

        return $this;
    }

    /** `false` renders `autocorrect="off"`; `true` omits the attribute. */
    public function autocorrect(bool $autocorrect = true): static
    {
        $this->autocorrect = $autocorrect;

        return $this;
    }

    /** `false` renders `autocapitalize="none"`; `true` omits the attribute. */
    public function autocapitalize(bool $autocapitalize = true): static
    {
        $this->autocapitalize = $autocapitalize;

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

    public function min(string|int|float|null $min): static
    {
        $this->min = $min;

        return $this;
    }

    public function max(string|int|float|null $max): static
    {
        $this->max = $max;

        return $this;
    }

    public function step(string|int|float|null $step): static
    {
        $this->step = $step;

        return $this;
    }

    public function inputmode(?string $inputmode): static
    {
        $this->inputmode = $inputmode;

        return $this;
    }

    /** Writing direction (`ltr`/`rtl`); defaults to the app locale's orientation. */
    public function orientation(?string $orientation): static
    {
        $this->orientation = $orientation;

        return $this;
    }

    public function role(?string $role): static
    {
        $this->role = $role;

        return $this;
    }

    /** `aria-expanded` state; defaults to `false` when the role is `combobox`. */
    public function expanded(bool|string|null $expanded): static
    {
        $this->expanded = $expanded;

        return $this;
    }

    /**
     * Display-only unit suffix rendered in the `suffix` slot, with a hidden
     * description ("Value suffixed by …") linked via `aria-describedby`.
     */
    public function suffix(string|Stringable|null $suffix): static
    {
        $this->suffix = $suffix;

        return $this;
    }

    /** Id of the suffix description element; defaults to `{id}-desc`. */
    public function descriptionId(?string $descriptionId): static
    {
        $this->descriptionId = $descriptionId;

        return $this;
    }

    /**
     * Marks the input for the legacy chars-left counter JS, padding the input
     * on the counter's side to make room for it.
     */
    public function showCharsLeft(bool $showCharsLeft = true): static
    {
        $this->showCharsLeft = $showCharsLeft;

        return $this;
    }

    public function labelledBy(?string $labelledBy): static
    {
        $this->labelledBy = $labelledBy;

        return $this;
    }

    public function describedBy(?string $describedBy): static
    {
        $this->describedBy = $describedBy;

        return $this;
    }

    /**
     * Merges additional HTML attributes onto the native input element. These
     * win over the computed defaults, so they can also override things like
     * `type` or `dir`.
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

    /** @param TextExpanderTriggers $triggers */
    public function textExpanderTriggers(array $triggers): static
    {
        $this->textExpanderTriggers = $triggers;

        return $this;
    }

    #[\Override]
    public function toHtml(): string
    {
        $html = parent::toHtml();

        if ($this->name === null || $this->textExpanderTriggers === []) {
            return $html;
        }

        return $html.Html::tag('craft-text-expander', '', [
            'for' => $this->getId(),
            'triggers' => Json::encode($this->textExpanderTriggers),
        ]);
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'maxlength' => $this->maxlength,
            'size' => $this->getSize(),
            'small' => $this->small,
            'width' => $this->width,
            'center' => $this->center,
            'monospace' => $this->monospace,
            'hidden-input' => $this->hiddenInput,
            // Lion pushes these host properties onto the slotted input when
            // the element upgrades (LionInput::updated()), overwriting the
            // server-rendered attributes with its defaults — so any
            // non-default value must also live on the host. The native input
            // keeps them too, for pre-upgrade correctness (form posts,
            // password masking, CSS).
            'type' => $this->type !== 'text' ? $this->type : null,
            'inputmode' => $this->inputmode,
            'placeholder' => $this->placeholder !== null && $this->placeholder !== '' ? $this->placeholder : null,
            'name' => $this->name !== null && $this->name !== '' ? $this->name : null,
            'disabled' => $this->isDisabled(),
            'readonly' => $this->readOnly,
        ];
    }

    #[\Override]
    protected function renderSlots(): string
    {
        return $this->inputHtml().$this->suffixHtml().parent::renderSlots();
    }

    protected function inputHtml(): string
    {
        $orientation = $this->getOrientation();
        $expanded = $this->expanded ?? ($this->role === 'combobox' ? false : null);

        $attributes = Arr::merge([
            'slot' => 'input',
            'type' => $this->type,
            'id' => $this->getId(),
            'class' => array_filter([
                'text',
                $this->inputSize === null ? 'fullwidth' : null,
            ]),
            'inputmode' => $this->inputmode,
            'size' => $this->inputSize,
            'name' => $this->name,
            'value' => $this->value !== null ? (string) $this->value : null,
            'maxlength' => $this->maxlength,
            'autofocus' => $this->autofocus
                && (currentUserElement()?->getAutofocusPreferred() ?? false)
                && ! request()->isMobileBrowser(true),
            'autocomplete' => is_bool($this->autocomplete) ? ($this->autocomplete ? 'on' : 'off') : $this->autocomplete,
            'autocorrect' => $this->autocorrect ? null : 'off',
            'autocapitalize' => $this->autocapitalize ? null : 'none',
            'disabled' => $this->isDisabled(),
            'readonly' => $this->readOnly,
            'title' => $this->title,
            'placeholder' => $this->placeholder,
            'step' => $this->step,
            'min' => $this->min,
            'max' => $this->max,
            'dir' => $orientation,
            'role' => $this->role,
            'aria' => [
                'labelledby' => $this->labelledBy,
                'describedby' => implode(' ', array_filter([
                    $this->describedBy,
                    $this->hasSuffix() ? $this->getDescriptionId() : null,
                ])) ?: null,
                'expanded' => is_bool($expanded) ? ($expanded ? 'true' : 'false') : $expanded,
            ],
            'data' => [
                'show-chars-left' => $this->showCharsLeft ?: null,
            ],
            'style' => $this->showCharsLeft && $this->maxlength !== null ? [
                'padding-'.($orientation === 'ltr' ? 'right' : 'left') => round(7.2 * strlen((string) $this->maxlength) + 14, 1).'px',
            ] : [],
        ], $this->inputAttributes);

        return Html::tag('input', '', $attributes);
    }

    /**
     * Renders the suffix into the web component's `suffix` slot, plus a
     * hidden description span the input's `aria-describedby` points at
     * (mirroring the legacy suffix markup).
     */
    protected function suffixHtml(): string
    {
        if ($this->suffix === null || (string) $this->suffix === '') {
            return '';
        }

        $descriptionId = $this->getDescriptionId();

        return
            ($descriptionId !== null
                ? Html::tag('span', Html::encode(t('Value suffixed by “{suffix}”.', ['suffix' => (string) $this->suffix])), [
                    'id' => $descriptionId,
                    'hidden' => true,
                ])
                : '').
            Html::tag('div', Html::encode((string) $this->suffix), [
                'slot' => 'suffix',
                'class' => ['label', 'light'],
                'aria' => ['hidden' => 'true'],
            ]);
    }

    protected function hasSuffix(): bool
    {
        return $this->suffix !== null && (string) $this->suffix !== '';
    }

    protected function getDescriptionId(): ?string
    {
        if ($this->descriptionId !== null) {
            return $this->descriptionId;
        }

        $id = $this->getId();

        return $id !== null ? "$id-desc" : null;
    }

    protected function getOrientation(): string
    {
        return (string) (
            $this->orientation
            ?? $this->inputAttributes['dir']
            ?? I18N::getLocale()->getOrientation()
        );
    }
}
