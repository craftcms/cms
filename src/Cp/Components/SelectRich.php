<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Support\Html;
use Illuminate\Contracts\Support\Htmlable;

/**
 * PHP counterpart to the `<craft-select-rich>` web component: a single-choice
 * select whose options can carry an icon and a hint.
 *
 * @since 6.0.0
 */
class SelectRich extends ViewComponent
{
    use HasDisabled;
    use HasId;

    protected ?string $name = null;

    protected string|int|null $value = null;

    /** @var list<array{label: string|Htmlable, value: string|int, icon?: string|null, iconColor?: Color|string|null, hint?: string|null, disabled?: bool}> */
    protected array $options = [];

    protected ?string $label = null;

    protected bool $labelSrOnly = false;

    protected bool $small = false;

    protected function tagName(): string
    {
        return 'craft-select-rich';
    }

    public function name(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function value(string|int|null $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * A string label is encoded; pass an `Htmlable` for markup.
     *
     * @param  list<array{label: string|Htmlable, value: string|int, icon?: string|null, iconColor?: Color|string|null, hint?: string|null, disabled?: bool}>  $options
     */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function labelSrOnly(bool $labelSrOnly = true): static
    {
        $this->labelSrOnly = $labelSrOnly;

        return $this;
    }

    public function small(bool $small = true): static
    {
        $this->small = $small;

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->name,
            'label' => $this->label,
            'label-sr-only' => $this->labelSrOnly,
            'small' => $this->small,
            'disabled' => $this->isDisabled(),
        ];
    }

    #[\Override]
    protected function renderSlots(): string
    {
        $options = '';

        foreach ($this->options as $option) {
            $icon = $option['icon'] ?? null;
            $content = $icon !== null && $icon !== ''
                ? Icon::make()->name($icon)->color($option['iconColor'] ?? null)->toHtml().' '
                : '';
            $content .= $this->renderContent($option['label']);

            $options .= Html::tag('craft-option', $content, [
                'choice-value' => (string) $option['value'],
                'checked' => $this->value !== null && (string) $option['value'] === (string) $this->value,
                'disabled' => $option['disabled'] ?? false,
                'hint' => $option['hint'] ?? null,
            ]);
        }

        return $options.parent::renderSlots();
    }
}
