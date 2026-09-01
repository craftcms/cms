<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Cp\Concerns\HasSize;
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
class Lightswitch extends ViewComponent
{
    use HasDisabled;
    use HasId;
    use HasSize;

    protected const string DEFAULT_VALUE = '1';

    protected const string DEFAULT_INDETERMINATE_VALUE = '-';

    protected bool $on = false;

    protected bool $indeterminate = false;

    protected ?string $name = null;

    /** @var array<string, mixed> Additional attributes for the switch button. */
    protected array $buttonAttributes = [];

    protected string $value = self::DEFAULT_VALUE;

    protected string $indeterminateValue = self::DEFAULT_INDETERMINATE_VALUE;

    protected ?string $label = null;

    protected ?string $onLabel = null;

    protected ?string $offLabel = null;

    protected ?string $toggle = null;

    protected ?string $reverseToggle = null;

    protected ?string $labelledBy = null;

    protected ?string $describedBy = null;

    protected string|Stringable|null $instructions = null;

    protected function tagName(): string
    {
        return 'craft-switch';
    }

    public function on(bool $on = true): static
    {
        $this->on = $on;

        return $this;
    }

    /** Mixed state; only meaningful while the switch is off. */
    public function indeterminate(bool $indeterminate = true): static
    {
        $this->indeterminate = $indeterminate;

        return $this;
    }

    /** Renders a hidden input posting the switch state under this name. */
    public function name(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /** The value posted when the switch is on. */
    public function value(string|int|float $value): static
    {
        $this->value = is_int($value) || is_float($value) ? (string) $value : $value;

        return $this;
    }

    /** The value posted when the switch is indeterminate. */
    public function indeterminateValue(string $indeterminateValue): static
    {
        $this->indeterminateValue = $indeterminateValue;

        return $this;
    }

    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /** State label rendered after the control; omitted when equal to the label. */
    public function onLabel(?string $onLabel): static
    {
        $this->onLabel = $onLabel;

        return $this;
    }

    /** State label rendered before the control. */
    public function offLabel(?string $offLabel): static
    {
        $this->offLabel = $offLabel;

        return $this;
    }

    /** Selector (or element id) of a container to reveal while the switch is on. */
    public function toggle(?string $toggle): static
    {
        $this->toggle = $toggle;

        return $this;
    }

    /** Selector (or element id) of a container to reveal while the switch is off. */
    public function reverseToggle(?string $reverseToggle): static
    {
        $this->reverseToggle = $reverseToggle;

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

    /** Help text below the switch; supports markdown. */
    public function instructions(string|Stringable|null $instructions): static
    {
        $this->instructions = $instructions;

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        $onLabel = $this->onLabel ?? $this->label;

        return [
            'checked' => $this->on,
            'indeterminate' => $this->effectiveIndeterminateValue() !== null,
            'disabled' => $this->isDisabled(),
            'size' => $this->getSize(),
            'value' => $this->value !== self::DEFAULT_VALUE ? $this->value : null,
            'indeterminate-value' => $this->indeterminateValue !== self::DEFAULT_INDETERMINATE_VALUE ? $this->indeterminateValue : null,
            'label' => $this->label,
            'on-label' => $onLabel !== null && $onLabel !== $this->label ? $onLabel : null,
            'off-label' => $this->offLabel,
        ];
    }

    #[\Override]
    protected function renderSlots(): string
    {
        $instructions = (string) ($this->instructions ?? '');

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
        $this->buttonAttributes = Arr::merge(
            static::normalizeClasses($this->buttonAttributes),
            static::normalizeClasses($attributes),
        );

        return $this;
    }

    protected function switchButtonHtml(): string
    {
        $indeterminate = $this->effectiveIndeterminateValue() !== null;

        return Html::tag('craft-switch-button', '', Arr::merge(
            [
                'slot' => 'input',
                'id' => $this->getId(),
                'role' => 'switch',
                'size' => $this->getSize() ?? 'medium',
                'checked' => $this->on,
                'indeterminate' => $indeterminate,
                'disabled' => $this->isDisabled(),
                'class' => array_filter([
                    $this->toggle || $this->reverseToggle ? 'fieldtoggle' : null,
                ]),
                'data' => [
                    'tag-name' => 'craft-switch-button',
                    'target' => $this->toggle ?: null,
                    'reverse-target' => $this->reverseToggle ?: null,
                ],
                'aria' => [
                    'checked' => $this->on ? 'true' : ($indeterminate ? 'mixed' : 'false'),
                    'labelledby' => $this->labelledBy,
                    'describedby' => $this->describedBy,
                ],
            ],
            $this->buttonAttributes,
        ));
    }

    protected function hiddenInputHtml(): string
    {
        if ($this->name === null) {
            return '';
        }

        return (string) Html::hiddenInput($this->name, $this->postedValue(), [
            'disabled' => $this->isDisabled(),
            'slot' => 'hidden-input',
        ]);
    }

    protected function postedValue(): string
    {
        if ($this->on) {
            return $this->value;
        }

        return $this->effectiveIndeterminateValue() ?? '';
    }

    /** The effective indeterminate posting value, or `null` when not indeterminate. */
    private function effectiveIndeterminateValue(): ?string
    {
        if ($this->on || ! $this->indeterminate) {
            return null;
        }

        return $this->indeterminateValue;
    }
}
