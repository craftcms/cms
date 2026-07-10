<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use Illuminate\Contracts\Support\Htmlable;
use Stringable;

use function CraftCms\Cms\t;

/**
 * PHP counterpart to the `<craft-checkbox>` web component. The native input
 * and label render in the light DOM (the web component is a Lion styling
 * shell), so input namespacing, form posting and legacy JS hooks
 * (Craft.FieldToggle) keep working.
 *
 *     Checkbox::make()
 *         ->name('remember')
 *         ->label(t('Remember me'))
 *         ->checked($user->remember);
 *
 * Posting matches the legacy checkbox template: a scalar-named checkbox is
 * preceded by an always-post hidden input (empty value), so unchecking posts
 * an empty string; `name[]`-style checkboxes post nothing when unchecked.
 */
class Checkbox extends ViewComponent
{
    use HasDisabled;
    use HasId;

    protected string|Closure|null $name = null;

    protected string|Closure $value = '1';

    protected bool|Closure $checked = false;

    protected bool|Closure $autofocus = false;

    protected string|Htmlable|Stringable|Closure|null $label = null;

    protected string|Closure|null $labelId = null;

    protected string|Stringable|Closure|null $info = null;

    protected string|Closure|null $icon = null;

    protected string|Closure|null $color = null;

    protected bool|Closure $custom = false;

    protected string|Htmlable|Closure|null $customInput = null;

    protected string|Closure|null $toggle = null;

    protected string|Closure|null $reverseToggle = null;

    protected string|Closure|null $targetPrefix = null;

    protected string|Closure|null $labelledBy = null;

    protected string|Closure|bool|null $describedBy = null;

    /** @var array<string, mixed> Additional attributes for the native input. */
    protected array $inputAttributes = [];

    protected function tagName(): string
    {
        return 'craft-checkbox';
    }

    /** Default attributes for the native input. */
    protected function inputDefaults(): array
    {
        return [
            'type' => 'checkbox',
            'class' => ['checkbox'],
        ];
    }

    /** Whether a scalar-named control gets an always-post hidden input. */
    protected function rendersAlwaysPostInput(): bool
    {
        return true;
    }

    /** The label text shown in custom-option mode. */
    protected function customLabelText(): string
    {
        return t('Custom:');
    }

    public function name(string|Closure|null $name): static
    {
        $this->name = $name;

        return $this;
    }

    /** The value posted when checked. */
    public function value(string|int|Closure $value): static
    {
        $this->value = is_int($value) ? (string) $value : $value;

        return $this;
    }

    public function checked(bool|Closure $checked = true): static
    {
        $this->checked = $checked;

        return $this;
    }

    public function autofocus(bool|Closure $autofocus = true): static
    {
        $this->autofocus = $autofocus;

        return $this;
    }

    /** The checkbox label. Plain strings are HTML-encoded. */
    public function label(string|Htmlable|Stringable|Closure|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function labelId(string|Closure|null $labelId): static
    {
        $this->labelId = $labelId;

        return $this;
    }

    /** Info popover content beside the label; supports markdown. */
    public function info(string|Stringable|Closure|null $info): static
    {
        $this->info = $info;

        return $this;
    }

    /** Icon name shown before the label (tinted by `color` when set). */
    public function icon(string|Closure|null $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /** Color swatch (or icon tint) shown before the label. */
    public function color(string|Closure|null $color): static
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Custom-option mode: the label reads "Custom:" and the given input HTML
     * (trusted) renders after the checkbox for the custom value.
     */
    public function custom(string|Htmlable|Closure|null $customInput): static
    {
        $this->custom = $customInput !== null;
        $this->customInput = $customInput;

        return $this;
    }

    /** Whether the checkbox is in custom-option mode. */
    public function hasCustomInput(): bool
    {
        return (bool) $this->evaluate($this->custom);
    }

    /** Selector (or element id) of a container to reveal while checked. */
    public function toggle(string|Closure|null $toggle): static
    {
        $this->toggle = $toggle;

        return $this;
    }

    /** Selector (or element id) of a container to reveal while unchecked. */
    public function reverseToggle(string|Closure|null $reverseToggle): static
    {
        $this->reverseToggle = $reverseToggle;

        return $this;
    }

    /** Craft.FieldToggle target prefix, combined with the checkbox value. */
    public function targetPrefix(string|Closure|null $targetPrefix): static
    {
        $this->targetPrefix = $targetPrefix;

        return $this;
    }

    public function labelledBy(string|Closure|null $labelledBy): static
    {
        $this->labelledBy = $labelledBy;

        return $this;
    }

    public function describedBy(string|Closure|bool|null $describedBy): static
    {
        $this->describedBy = $describedBy;

        return $this;
    }

    /**
     * Merges additional HTML attributes onto the native input element. These
     * win over the computed defaults, so they can also override things like
     * `type`:
     *
     *     Checkbox::make()->inputAttributes(['type' => 'other-type']);
     *
     * The component always renders a raw `<input>` tag — the web component
     * adopts it as its form control, so it can never be another form-control
     * component itself.
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
    protected function renderSlots(): string
    {
        return $this->inputHtml().$this->labelHtml().parent::renderSlots();
    }

    /**
     * Renders the always-post hidden input before the host and the custom
     * option input after it, mirroring the legacy checkbox template.
     */
    #[\Override]
    protected function renderMarkup(): string
    {
        $name = $this->evaluate($this->name);
        $alwaysPost = $this->rendersAlwaysPostInput() && $name !== null && ! str_ends_with($name, '[]');

        return implode('', array_filter([
            $alwaysPost ? (string) Html::hiddenInput($name, '') : '',
            parent::renderMarkup(),
            $this->customInputHtml(),
        ]));
    }

    protected function inputHtml(): string
    {
        $toggle = $this->evaluate($this->toggle);
        $reverseToggle = $this->evaluate($this->reverseToggle);
        $targetPrefix = $this->evaluate($this->targetPrefix);

        $attributes = Arr::merge(Arr::merge($this->inputDefaults(), [
            'slot' => 'input',
            'id' => $this->getId(),
            'name' => $this->evaluate($this->name),
            'value' => $this->evaluate($this->value),
            'checked' => (bool) $this->evaluate($this->checked),
            'autofocus' => (bool) $this->evaluate($this->autofocus) && ! request()->isMobileBrowser(true),
            'disabled' => $this->isDisabled(),
            'class' => array_filter([
                ($targetPrefix ?? $toggle ?? $reverseToggle) !== null ? 'fieldtoggle' : null,
            ]),
            'aria' => [
                'labelledby' => $this->evaluate($this->labelledBy),
                'describedby' => $this->evaluate($this->describedBy),
            ],
            'data' => [
                'target-prefix' => $targetPrefix,
                'target' => $toggle,
                'reverse-target' => $reverseToggle,
            ],
        ]),
            $this->inputAttributes,
        );

        return Html::tag('input', '', $attributes);
    }

    protected function labelHtml(): string
    {
        $label = $this->evaluate($this->label);
        $info = (string) ($this->evaluate($this->info) ?? '');

        $content = implode('', array_filter([
            (bool) $this->evaluate($this->custom) ? Html::encode($this->customLabelText()) : $this->labelContentHtml($label),
            $info !== ''
                ? Html::tag('span', app(ContentHtml::class)->parseMarkdown($info), ['class' => 'info'])
                : '',
        ]));

        if ($content === '') {
            return '';
        }

        return Html::tag('label', $content, [
            'slot' => 'label',
            'for' => $this->getId(),
            'id' => $this->getLabelId(),
        ]);
    }

    protected function labelContentHtml(mixed $label): string
    {
        $labelHtml = $label === null ? '' : $this->renderContent($label);

        $icon = $this->evaluate($this->icon);
        $color = $this->evaluate($this->color);

        if ($icon === null && $color === null) {
            return $labelHtml;
        }

        $chip = $icon !== null
            ? Html::tag('span', Icons::svg($icon), [
                'class' => 'cp-icon puny',
                'style' => $color !== null ? "--icon-color: $color;" : null,
            ])
            : Html::tag('div', Html::tag('div', '', [
                'class' => 'color-preview',
                'style' => "background-color: $color",
            ]), ['class' => 'color small']);

        return Html::tag('div', $chip.Html::tag('span', $labelHtml), [
            'class' => 'flex flex-nowrap gap-xs',
        ]);
    }

    protected function customInputHtml(): string
    {
        if (! (bool) $this->evaluate($this->custom)) {
            return '';
        }

        $input = $this->evaluate($this->customInput);
        $input = $input instanceof Htmlable ? $input->toHtml() : (string) $input;

        return $input !== ''
            ? Html::tag('div', $input, ['class' => 'custom-option-wrapper'])
            : '';
    }

    protected function getLabelId(): ?string
    {
        $labelId = $this->evaluate($this->labelId);

        if ($labelId !== null) {
            return (string) $labelId;
        }

        $id = $this->getId();

        return $id !== null ? "$id-label" : null;
    }
}
