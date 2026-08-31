<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

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
 * Posting matches the legacy checkbox template: a scalar-named checkbox gets
 * an always-post hidden input (empty value) ahead of it, so unchecking posts
 * an empty string; `name[]`-style checkboxes post nothing when unchecked.
 */
class Checkbox extends ViewComponent
{
    use HasDisabled;
    use HasId;

    protected ?string $name = null;

    protected string $value = '1';

    protected bool $checked = false;

    protected bool $autofocus = false;

    protected string|Htmlable|Stringable|null $label = null;

    protected ?string $labelId = null;

    protected bool $indeterminate = false;

    protected string|Stringable|null $info = null;

    protected ?string $icon = null;

    protected ?string $color = null;

    protected bool $custom = false;

    protected string|Htmlable|null $customInput = null;

    protected ?string $toggle = null;

    protected ?string $reverseToggle = null;

    protected ?string $targetPrefix = null;

    protected ?string $labelledBy = null;

    protected string|bool|null $describedBy = null;

    /** @var array<string, mixed> Additional attributes for the native input. */
    protected array $inputAttributes = [];

    protected function tagName(): string
    {
        return 'craft-checkbox';
    }

    /**
     * The `<craft-checkbox>` host owns the indeterminate state: `indeterminate`
     * isn't a native input attribute (it's a JS-only property), so the web
     * component reflects it from the host and mirrors it onto the slotted input.
     */
    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'indeterminate' => $this->indeterminate,
        ];
    }

    /**
     * Default attributes for the native input.
     *
     * @return array<string, mixed>
     */
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

    public function name(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /** The value posted when checked. */
    public function value(string|int|float $value): static
    {
        $this->value = is_int($value) || is_float($value) ? (string) $value : $value;

        return $this;
    }

    public function checked(bool $checked = true): static
    {
        $this->checked = $checked;

        return $this;
    }

    public function autofocus(bool $autofocus = true): static
    {
        $this->autofocus = $autofocus;

        return $this;
    }

    /** The checkbox label. Plain strings are HTML-encoded. */
    public function label(string|Htmlable|Stringable|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function labelId(?string $labelId): static
    {
        $this->labelId = $labelId;

        return $this;
    }

    public function indeterminate(bool $indeterminate = true): static
    {
        $this->indeterminate = $indeterminate;

        return $this;
    }

    /** Info popover content beside the label; supports markdown. */
    public function info(string|Stringable|null $info): static
    {
        $this->info = $info;

        return $this;
    }

    /** Icon name shown before the label (tinted by `color` when set). */
    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /** Color swatch (or icon tint) shown before the label. */
    public function color(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Custom-option mode: the label reads "Custom:" and the given input HTML
     * (trusted) renders after the checkbox for the custom value.
     */
    public function custom(string|Htmlable|null $customInput): static
    {
        $this->custom = $customInput !== null;
        $this->customInput = $customInput;

        return $this;
    }

    /** Whether the checkbox is in custom-option mode. */
    public function hasCustomInput(): bool
    {
        return $this->custom;
    }

    /** Selector (or element id) of a container to reveal while checked. */
    public function toggle(?string $toggle): static
    {
        $this->toggle = $toggle;

        return $this;
    }

    /** Selector (or element id) of a container to reveal while unchecked. */
    public function reverseToggle(?string $reverseToggle): static
    {
        $this->reverseToggle = $reverseToggle;

        return $this;
    }

    /** Craft.FieldToggle target prefix, combined with the checkbox value. */
    public function targetPrefix(?string $targetPrefix): static
    {
        $this->targetPrefix = $targetPrefix;

        return $this;
    }

    public function labelledBy(?string $labelledBy): static
    {
        $this->labelledBy = $labelledBy;

        return $this;
    }

    public function describedBy(string|bool|null $describedBy): static
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
        return $this->alwaysPostInputHtml().$this->inputHtml().$this->labelHtml().parent::renderSlots();
    }

    /**
     * Renders the custom option input after the host, mirroring the legacy
     * checkbox template.
     */
    #[\Override]
    protected function renderMarkup(): string
    {
        return parent::renderMarkup().$this->customInputHtml();
    }

    /**
     * The hidden input that posts an empty value when a scalar-named checkbox
     * is unchecked, mirroring the legacy checkbox template. It stays ahead of
     * the checkbox in the form's field order, so a checked box's value wins.
     *
     * It renders as the host's first light-DOM child rather than as a
     * preceding sibling, so the component's markup keeps a single root
     * element: a multi-root control can't be slotted — {@see
     * ViewComponent::slotted()} would put `slot="input"` on the hidden input,
     * leaving `<craft-checkbox>` unassigned, and a `<craft-field>` (whose
     * shadow root has no default slot) would render nothing at all.
     *
     * `<craft-checkbox>`'s own shadow root has no default slot either, so the
     * hidden input is never rendered — but it stays in the form's DOM tree, so
     * it still posts. `<craft-switch>` does the same thing with its
     * `hidden-input` slot.
     */
    protected function alwaysPostInputHtml(): string
    {
        if (! $this->rendersAlwaysPostInput() || $this->name === null || str_ends_with($this->name, '[]')) {
            return '';
        }

        return (string) Html::hiddenInput($this->name, '');
    }

    protected function inputHtml(): string
    {
        $attributes = Arr::merge(Arr::merge($this->inputDefaults(), [
            'slot' => 'input',
            'id' => $this->getId(),
            'name' => $this->name,
            'value' => $this->value,
            'checked' => $this->checked,
            'autofocus' => $this->autofocus && ! request()->isMobileBrowser(true),
            'disabled' => $this->isDisabled(),
            'class' => array_filter([
                ($this->targetPrefix ?? $this->toggle ?? $this->reverseToggle) !== null ? 'fieldtoggle' : null,
            ]),
            'aria' => [
                'labelledby' => $this->labelledBy,
                'describedby' => $this->describedBy,
            ],
            'data' => [
                'target-prefix' => $this->targetPrefix,
                'target' => $this->toggle,
                'reverse-target' => $this->reverseToggle,
            ],
        ]),
            $this->inputAttributes,
        );

        return Html::tag('input', '', $attributes);
    }

    protected function labelHtml(): string
    {
        $info = (string) ($this->info ?? '');

        $content = implode('', array_filter([
            $this->custom ? Html::encode($this->customLabelText()) : $this->labelContentHtml($this->label),
            $info !== ''
                ? Html::tag('craft-info-icon', app(ContentHtml::class)->parseMarkdown($info))
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

        if ($this->icon === null && $this->color === null) {
            return $labelHtml;
        }

        $chip = $this->icon !== null
            ? Html::tag('span', Icons::svg($this->icon), [
                'class' => 'cp-icon puny',
                'style' => $this->color !== null ? "--icon-color: {$this->color};" : null,
            ])
            : Html::tag('div', Html::tag('div', '', [
                'class' => 'color-preview',
                'style' => "background-color: {$this->color}",
            ]), ['class' => 'color small']);

        return Html::tag('div', $chip.Html::tag('span', $labelHtml), [
            'class' => 'flex flex-nowrap gap-sm',
        ]);
    }

    protected function customInputHtml(): string
    {
        if (! $this->custom) {
            return '';
        }

        $input = $this->customInput instanceof Htmlable ? $this->customInput->toHtml() : (string) $this->customInput;

        return $input !== ''
            ? Html::tag('div', $input, ['class' => 'custom-option-wrapper'])
            : '';
    }

    protected function getLabelId(): ?string
    {
        if ($this->labelId !== null) {
            return $this->labelId;
        }

        $id = $this->getId();

        return $id !== null ? "$id-label" : null;
    }
}
