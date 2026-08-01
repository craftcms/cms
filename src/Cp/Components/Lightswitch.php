<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Cp\Concerns\HasSize;
use CraftCms\Cms\Cp\Enums\Size;
use CraftCms\Cms\Cp\FormDefinitions\Contracts\ProjectableFormElement;
use CraftCms\Cms\Cp\FormDefinitions\Data\FormElementData;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\HtmlString;
use Stringable;

/**
 * PHP counterpart to the `<craft-switch>` web component, mirroring the
 * `_includes/forms/lightswitch` template.
 *
 *     Lightswitch::make()
 *         ->name('enabled')
 *         ->on($entry->enabled)
 *         ->toggle('#enabled-settings');
 *
 * The switch button and hidden input render in the light DOM so input
 * namespacing and legacy JS hooks (Craft.FieldToggle) keep working, and so
 * the posted value matches the legacy behavior exactly: `value` when on,
 * `indeterminateValue` when indeterminate, an empty string when off, nothing
 * when unnamed.
 */
class Lightswitch extends ViewComponent implements ProjectableFormElement
{
    use HasDisabled;
    use HasId;
    use HasSize;

    protected const string DEFAULT_VALUE = '1';

    protected const string DEFAULT_INDETERMINATE_VALUE = '-';

    protected bool|Closure $on = false;

    protected bool|Closure $indeterminate = false;

    protected string|Closure|null $name = null;

    /** @var array<string, mixed> Additional attributes for the switch button. */
    protected array $buttonAttributes = [];

    protected string|Closure $value = self::DEFAULT_VALUE;

    protected string|Closure $indeterminateValue = self::DEFAULT_INDETERMINATE_VALUE;

    protected string|Closure|null $label = null;

    protected string|Closure|null $onLabel = null;

    protected string|Closure|null $offLabel = null;

    protected string|Closure|null $toggle = null;

    protected string|Closure|null $reverseToggle = null;

    protected string|Closure|null $labelledBy = null;

    protected string|Closure|null $describedBy = null;

    protected string|Stringable|Closure|null $instructions = null;

    public static function formElementType(): string
    {
        return 'craft:lightswitch-input';
    }

    public static function isFormElementContainer(): bool
    {
        return false;
    }

    protected function tagName(): string
    {
        return 'craft-switch';
    }

    public function on(bool|Closure $on = true): static
    {
        $this->trackConfiguration('on');
        $this->on = $on;

        return $this;
    }

    /** Mixed state; only meaningful while the switch is off. */
    public function indeterminate(bool|Closure $indeterminate = true): static
    {
        $this->trackConfiguration('indeterminate');
        $this->indeterminate = $indeterminate;

        return $this;
    }

    /** Renders a hidden input posting the switch state under this name. */
    public function name(string|Closure|null $name): static
    {
        $this->name = $name;

        return $this;
    }

    /** The value posted when the switch is on. */
    public function value(string|int|float|Closure $value): static
    {
        $this->trackConfiguration('value');
        $this->value = is_int($value) || is_float($value) ? (string) $value : $value;

        return $this;
    }

    /** The value posted when the switch is indeterminate. */
    public function indeterminateValue(string|Closure $indeterminateValue): static
    {
        $this->trackConfiguration('indeterminateValue');
        $this->indeterminateValue = $indeterminateValue;

        return $this;
    }

    public function label(string|Closure|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    /** State label rendered after the control; omitted when equal to the label. */
    public function onLabel(string|Closure|null $onLabel): static
    {
        $this->onLabel = $onLabel;

        return $this;
    }

    /** State label rendered before the control. */
    public function offLabel(string|Closure|null $offLabel): static
    {
        $this->offLabel = $offLabel;

        return $this;
    }

    /** Selector (or element id) of a container to reveal while the switch is on. */
    public function toggle(string|Closure|null $toggle): static
    {
        $this->trackConfiguration('toggle');
        $this->toggle = $toggle;

        return $this;
    }

    /** Selector (or element id) of a container to reveal while the switch is off. */
    public function reverseToggle(string|Closure|null $reverseToggle): static
    {
        $this->trackConfiguration('reverseToggle');
        $this->reverseToggle = $reverseToggle;

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

    /** Help text below the switch; supports markdown. */
    public function instructions(string|Stringable|Closure|null $instructions): static
    {
        $this->trackConfiguration('instructions');
        $this->instructions = $instructions;

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        $on = (bool) $this->evaluate($this->on);
        $label = $this->evaluate($this->label);
        $onLabel = $this->evaluate($this->onLabel) ?? $label;
        $offLabel = $this->evaluate($this->offLabel);
        $value = (string) $this->evaluate($this->value);
        $indeterminateValue = (string) $this->evaluate($this->indeterminateValue);

        return [
            'checked' => $on,
            'indeterminate' => $this->effectiveIndeterminateValue() !== null,
            'disabled' => $this->isDisabled(),
            'size' => $this->getSize(),
            'value' => $value !== self::DEFAULT_VALUE ? $value : null,
            'indeterminate-value' => $indeterminateValue !== self::DEFAULT_INDETERMINATE_VALUE ? $indeterminateValue : null,
            'label' => $label,
            'on-label' => $onLabel !== null && $onLabel !== $label ? $onLabel : null,
            'off-label' => $offLabel,
        ];
    }

    #[\Override]
    protected function renderSlots(): string
    {
        $instructions = (string) ($this->evaluate($this->instructions) ?? '');

        return implode('', array_filter([
            $this->switchButtonHtml(),
            $this->hiddenInputHtml(),
            $instructions !== ''
                ? $this->renderSlot('help-text', new HtmlString(
                    Html::tag('div', app(ContentHtml::class)->parseMarkdown($instructions)),
                ))
                : '',
            parent::renderSlots(),
        ]));
    }

    /**
     * Merges additional HTML attributes onto the `craft-switch-button`
     * element. These win over the computed defaults.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function buttonAttributes(array $attributes): static
    {
        $this->trackConfiguration('buttonAttributes');
        $this->buttonAttributes = Arr::merge(
            static::normalizeClasses($this->buttonAttributes),
            static::normalizeClasses($attributes),
        );

        return $this;
    }

    protected function switchButtonHtml(): string
    {
        $on = (bool) $this->evaluate($this->on);
        $indeterminate = $this->effectiveIndeterminateValue() !== null;
        $toggle = $this->evaluate($this->toggle);
        $reverseToggle = $this->evaluate($this->reverseToggle);

        return Html::tag('craft-switch-button', '', Arr::merge(
            [
                'slot' => 'input',
                'id' => $this->getId(),
                'role' => 'switch',
                'size' => $this->getSize() ?? 'medium',
                'checked' => $on,
                'indeterminate' => $indeterminate,
                'disabled' => $this->isDisabled(),
                'class' => array_filter([
                    $toggle || $reverseToggle ? 'fieldtoggle' : null,
                ]),
                'data' => [
                    'tag-name' => 'craft-switch-button',
                    'target' => $toggle ?: null,
                    'reverse-target' => $reverseToggle ?: null,
                ],
                'aria' => [
                    'checked' => $on ? 'true' : ($indeterminate ? 'mixed' : 'false'),
                    'labelledby' => $this->evaluate($this->labelledBy),
                    'describedby' => $this->evaluate($this->describedBy),
                ],
            ],
            $this->buttonAttributes,
        ));
    }

    protected function hiddenInputHtml(): string
    {
        $name = $this->evaluate($this->name);

        if ($name === null) {
            return '';
        }

        return (string) Html::hiddenInput($name, $this->postedValue(), [
            'disabled' => $this->isDisabled(),
            'slot' => 'hidden-input',
        ]);
    }

    protected function postedValue(): string
    {
        if ($this->evaluate($this->on)) {
            return (string) $this->evaluate($this->value);
        }

        return $this->effectiveIndeterminateValue() ?? '';
    }

    public function toFormElementData(): FormElementData
    {
        $this->rejectConfiguredOptions([
            'on',
            'indeterminate',
            'disabled',
            'id',
            'slot',
            'buttonAttributes',
            'value',
            'indeterminateValue',
            'toggle',
            'reverseToggle',
            'labelledBy',
            'describedBy',
            'instructions',
        ], 'Form Definition');

        $name = $this->portableText('name', $this->name);

        if ($name === null) {
            $this->unsupportedOutputOption('name', 'Form Definition');
        }

        $attributes = Html::normalizeTagAttributes($this->attributes);

        foreach (array_keys($attributes) as $attribute) {
            if (in_array(strtolower((string) $attribute), [
                'aria-describedby',
                'aria-labelledby',
                'aria-required',
                'checked',
                'disabled',
                'id',
                'indeterminate',
                'indeterminate-value',
                'name',
                'readonly',
                'required',
                'slot',
                'value',
            ], true)) {
                $this->unsupportedOutputOption("attributes.{$attribute}", 'Form Definition');
            }
        }

        $props = array_filter([
            'label' => $this->portableText('label', $this->label),
            'onLabel' => $this->portableText('onLabel', $this->onLabel),
            'offLabel' => $this->portableText('offLabel', $this->offLabel),
            'size' => $this->projectedSize(),
        ], fn (mixed $value): bool => $value !== null);

        return new FormElementData(
            type: static::formElementType(),
            name: $name,
            props: $props === [] ? null : $props,
            attributes: $attributes === [] ? null : $attributes,
        );
    }

    /** The effective indeterminate posting value, or `null` when not indeterminate. */
    private function effectiveIndeterminateValue(): ?string
    {
        if ($this->evaluate($this->on) || ! $this->evaluate($this->indeterminate)) {
            return null;
        }

        return (string) $this->evaluate($this->indeterminateValue);
    }

    private function projectedSize(): ?string
    {
        $size = $this->evaluate($this->size);

        if ($size instanceof Size) {
            $size = $size->value;
        }

        if ($size !== null && ! in_array($size, [Size::Small->value, Size::Medium->value], true)) {
            $this->unsupportedOutputOption('size', 'Form Definition');
        }

        return $size;
    }
}
