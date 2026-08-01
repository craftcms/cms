<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Cp\Concerns\HasSize;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Html;
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
 */
class Input extends ViewComponent
{
    use HasDisabled;
    use HasId;
    use HasSize;

    protected string|Closure $type = 'text';

    protected string|Closure|null $name = null;

    protected string|int|float|Stringable|Closure|null $value = null;

    protected int|Closure|null $maxlength = null;

    /** Native input `size` attribute (character width). */
    protected int|Closure|null $inputSize = null;

    protected string|Closure|null $width = null;

    protected bool|Closure $small = false;

    protected bool|Closure $center = false;

    protected bool|Closure $monospace = false;

    protected bool|Closure $hiddenInput = false;

    protected bool|Closure $autofocus = false;

    protected bool|string|Closure $autocomplete = false;

    protected bool|Closure $autocorrect = true;

    protected bool|Closure $autocapitalize = true;

    protected bool|Closure $readOnly = false;

    protected string|Closure|null $title = null;

    protected string|Closure|null $placeholder = null;

    protected string|int|float|Closure|null $min = null;

    protected string|int|float|Closure|null $max = null;

    protected string|int|float|Closure|null $step = null;

    protected string|Closure|null $inputmode = null;

    protected string|Closure|null $orientation = null;

    protected string|Closure|null $role = null;

    protected bool|string|Closure|null $expanded = null;

    protected string|Stringable|Closure|null $suffix = null;

    protected string|Closure|null $descriptionId = null;

    protected bool|Closure $showCharsLeft = false;

    protected string|Closure|null $labelledBy = null;

    protected string|Closure|null $describedBy = null;

    /** @var array<string, mixed> Additional attributes for the native input. */
    protected array $inputAttributes = [];

    protected function tagName(): string
    {
        return 'craft-input';
    }

    public function type(string|Closure $type): static
    {
        $this->trackConfiguration('type');
        $this->type = $type;

        return $this;
    }

    public function name(string|Closure|null $name): static
    {
        $this->trackConfiguration('name');
        $this->name = $name;

        return $this;
    }

    public function value(string|int|float|Stringable|Closure|null $value): static
    {
        $this->trackConfiguration('value');
        $this->value = $value;

        return $this;
    }

    /**
     * Maximum character count. Reflected on the host, where it also shrinks
     * the control to the expected character width (see {@see self::width()}).
     */
    public function maxlength(int|Closure|null $maxlength): static
    {
        $this->trackConfiguration('maxlength');
        $this->maxlength = $maxlength;

        return $this;
    }

    /** The native input's character-width `size` attribute. */
    public function inputSize(int|Closure|null $inputSize): static
    {
        $this->trackConfiguration('inputSize');
        $this->inputSize = $inputSize;

        return $this;
    }

    /**
     * Overrides the inferred width behavior: `full` spans the column despite
     * a `maxlength`; `auto` shrinks to the input's intrinsic width without
     * one.
     */
    public function width(string|Closure|null $width): static
    {
        $this->trackConfiguration('width');
        $this->width = $width;

        return $this;
    }

    /** Renders the input at a smaller size. */
    public function small(bool|Closure $small = true): static
    {
        $this->trackConfiguration('small');
        $this->small = $small;

        return $this;
    }

    /** Center-aligns the input text. */
    public function center(bool|Closure $center = true): static
    {
        $this->trackConfiguration('center');
        $this->center = $center;

        return $this;
    }

    /** Renders the input value in a monospace font. */
    public function monospace(bool|Closure $monospace = true): static
    {
        $this->trackConfiguration('monospace');
        $this->monospace = $monospace;

        return $this;
    }

    /**
     * Visually hides the control while keeping it form-bound, so its value
     * still submits.
     */
    public function hiddenInput(bool|Closure $hiddenInput = true): static
    {
        $this->trackConfiguration('hiddenInput');
        $this->hiddenInput = $hiddenInput;

        return $this;
    }

    /** Honored only when the current user prefers autofocus (and not on mobile). */
    public function autofocus(bool|Closure $autofocus = true): static
    {
        $this->trackConfiguration('autofocus');
        $this->autofocus = $autofocus;

        return $this;
    }

    /** Booleans render as `on`/`off`; strings (e.g. `postal-code`) pass through. */
    public function autocomplete(bool|string|Closure $autocomplete): static
    {
        $this->trackConfiguration('autocomplete');
        $this->autocomplete = $autocomplete;

        return $this;
    }

    /** `false` renders `autocorrect="off"`; `true` omits the attribute. */
    public function autocorrect(bool|Closure $autocorrect = true): static
    {
        $this->trackConfiguration('autocorrect');
        $this->autocorrect = $autocorrect;

        return $this;
    }

    /** `false` renders `autocapitalize="none"`; `true` omits the attribute. */
    public function autocapitalize(bool|Closure $autocapitalize = true): static
    {
        $this->trackConfiguration('autocapitalize');
        $this->autocapitalize = $autocapitalize;

        return $this;
    }

    public function readOnly(bool|Closure $readOnly = true): static
    {
        $this->trackConfiguration('readOnly');
        $this->readOnly = $readOnly;

        return $this;
    }

    public function title(string|Closure|null $title): static
    {
        $this->trackConfiguration('title');
        $this->title = $title;

        return $this;
    }

    public function placeholder(string|Closure|null $placeholder): static
    {
        $this->trackConfiguration('placeholder');
        $this->placeholder = $placeholder;

        return $this;
    }

    public function min(string|int|float|Closure|null $min): static
    {
        $this->trackConfiguration('min');
        $this->min = $min;

        return $this;
    }

    public function max(string|int|float|Closure|null $max): static
    {
        $this->trackConfiguration('max');
        $this->max = $max;

        return $this;
    }

    public function step(string|int|float|Closure|null $step): static
    {
        $this->trackConfiguration('step');
        $this->step = $step;

        return $this;
    }

    public function inputmode(string|Closure|null $inputmode): static
    {
        $this->trackConfiguration('inputmode');
        $this->inputmode = $inputmode;

        return $this;
    }

    /** Writing direction (`ltr`/`rtl`); defaults to the app locale's orientation. */
    public function orientation(string|Closure|null $orientation): static
    {
        $this->trackConfiguration('orientation');
        $this->orientation = $orientation;

        return $this;
    }

    public function role(string|Closure|null $role): static
    {
        $this->trackConfiguration('role');
        $this->role = $role;

        return $this;
    }

    /** `aria-expanded` state; defaults to `false` when the role is `combobox`. */
    public function expanded(bool|string|Closure|null $expanded): static
    {
        $this->trackConfiguration('expanded');
        $this->expanded = $expanded;

        return $this;
    }

    /**
     * Display-only unit suffix rendered in the `suffix` slot, with a hidden
     * description ("Value suffixed by …") linked via `aria-describedby`.
     */
    public function suffix(string|Stringable|Closure|null $suffix): static
    {
        $this->trackConfiguration('suffix');
        $this->suffix = $suffix;

        return $this;
    }

    /** Id of the suffix description element; defaults to `{id}-desc`. */
    public function descriptionId(string|Closure|null $descriptionId): static
    {
        $this->trackConfiguration('descriptionId');
        $this->descriptionId = $descriptionId;

        return $this;
    }

    /**
     * Marks the input for the legacy chars-left counter JS, padding the input
     * on the counter's side to make room for it.
     */
    public function showCharsLeft(bool|Closure $showCharsLeft = true): static
    {
        $this->trackConfiguration('showCharsLeft');
        $this->showCharsLeft = $showCharsLeft;

        return $this;
    }

    public function labelledBy(string|Closure|null $labelledBy): static
    {
        $this->trackConfiguration('labelledBy');
        $this->labelledBy = $labelledBy;

        return $this;
    }

    public function describedBy(string|Closure|null $describedBy): static
    {
        $this->trackConfiguration('describedBy');
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
        $this->trackConfiguration('inputAttributes');
        $this->inputAttributes = Arr::merge(
            static::normalizeClasses($this->inputAttributes),
            static::normalizeClasses($attributes),
        );

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        $type = (string) $this->evaluate($this->type);
        $placeholder = $this->evaluate($this->placeholder);
        $name = $this->evaluate($this->name);

        return [
            'maxlength' => $this->evaluate($this->maxlength),
            'size' => $this->getSize(),
            'small' => (bool) $this->evaluate($this->small),
            'width' => $this->evaluate($this->width),
            'center' => (bool) $this->evaluate($this->center),
            'monospace' => (bool) $this->evaluate($this->monospace),
            'hidden-input' => (bool) $this->evaluate($this->hiddenInput),
            // Lion pushes these host properties onto the slotted input when
            // the element upgrades (LionInput::updated()), overwriting the
            // server-rendered attributes with its defaults — so any
            // non-default value must also live on the host. The native input
            // keeps them too, for pre-upgrade correctness (form posts,
            // password masking, CSS).
            'type' => $type !== 'text' ? $type : null,
            'placeholder' => $placeholder !== null && $placeholder !== '' ? (string) $placeholder : null,
            'name' => $name !== null && $name !== '' ? (string) $name : null,
            'disabled' => $this->isDisabled(),
            'readonly' => (bool) $this->evaluate($this->readOnly),
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
        $maxlength = $this->evaluate($this->maxlength);
        $inputSize = $this->evaluate($this->inputSize);
        $role = $this->evaluate($this->role);
        $autocomplete = $this->evaluate($this->autocomplete);
        $expanded = $this->evaluate($this->expanded) ?? ($role === 'combobox' ? false : null);
        $showCharsLeft = (bool) $this->evaluate($this->showCharsLeft);
        $value = $this->evaluate($this->value);

        $attributes = Arr::merge([
            'slot' => 'input',
            'type' => $this->evaluate($this->type),
            'id' => $this->getId(),
            'class' => array_filter([
                'text',
                $inputSize === null ? 'fullwidth' : null,
            ]),
            'inputmode' => $this->evaluate($this->inputmode),
            'size' => $inputSize,
            'name' => $this->evaluate($this->name),
            'value' => $value !== null ? (string) $value : null,
            'maxlength' => $maxlength,
            'autofocus' => (bool) $this->evaluate($this->autofocus)
                && (currentUserElement()?->getAutofocusPreferred() ?? false)
                && ! request()->isMobileBrowser(true),
            'autocomplete' => is_bool($autocomplete) ? ($autocomplete ? 'on' : 'off') : $autocomplete,
            'autocorrect' => (bool) $this->evaluate($this->autocorrect) ? null : 'off',
            'autocapitalize' => (bool) $this->evaluate($this->autocapitalize) ? null : 'none',
            'disabled' => $this->isDisabled(),
            'readonly' => (bool) $this->evaluate($this->readOnly),
            'title' => $this->evaluate($this->title),
            'placeholder' => $this->evaluate($this->placeholder),
            'step' => $this->evaluate($this->step),
            'min' => $this->evaluate($this->min),
            'max' => $this->evaluate($this->max),
            'dir' => $orientation,
            'role' => $role,
            'aria' => [
                'labelledby' => $this->evaluate($this->labelledBy),
                'describedby' => implode(' ', array_filter([
                    $this->evaluate($this->describedBy),
                    $this->hasSuffix() ? $this->getDescriptionId() : null,
                ])) ?: null,
                'expanded' => is_bool($expanded) ? ($expanded ? 'true' : 'false') : $expanded,
            ],
            'data' => [
                'show-chars-left' => $showCharsLeft ?: null,
            ],
            'style' => $showCharsLeft && $maxlength !== null ? [
                'padding-'.($orientation === 'ltr' ? 'right' : 'left') => round(7.2 * strlen((string) $maxlength) + 14, 1).'px',
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
        $suffix = $this->evaluate($this->suffix);

        if ($suffix === null || (string) $suffix === '') {
            return '';
        }

        $descriptionId = $this->getDescriptionId();

        return
            ($descriptionId !== null
                ? Html::tag('span', Html::encode(t('Value suffixed by “{suffix}”.', ['suffix' => (string) $suffix])), [
                    'id' => $descriptionId,
                    'hidden' => true,
                ])
                : '').
            Html::tag('div', Html::encode((string) $suffix), [
                'slot' => 'suffix',
                'class' => ['label', 'light'],
                'aria' => ['hidden' => 'true'],
            ]);
    }

    protected function hasSuffix(): bool
    {
        $suffix = $this->evaluate($this->suffix);

        return $suffix !== null && (string) $suffix !== '';
    }

    protected function getDescriptionId(): ?string
    {
        $descriptionId = $this->evaluate($this->descriptionId);

        if ($descriptionId !== null) {
            return (string) $descriptionId;
        }

        $id = $this->getId();

        return $id !== null ? "$id-desc" : null;
    }

    protected function getOrientation(): string
    {
        return (string) (
            $this->evaluate($this->orientation)
            ?? $this->inputAttributes['dir']
            ?? I18N::getLocale()->getOrientation()
        );
    }
}
