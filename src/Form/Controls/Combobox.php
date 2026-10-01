<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\Components\Combobox as ComboboxComponent;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\Controls\Combobox\CreateOption as ComboboxCreateOption;
use CraftCms\Cms\Form\FormHtmlRenderer;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class Combobox extends Control
{
    /** @var list<array<string, mixed>> */
    private array $options = [];

    private ?string $placeholder = null;

    private ?int $limit = null;

    private bool $clearable = false;

    private bool $multiple = false;

    private bool $requireOptionMatch = false;

    private bool $showAllOnEmpty = false;

    private bool $showSelectedHint = false;

    private ?string $dir = null;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        return static::htmlComponent($control)
            ->id($attributes['id'])
            ->name($attributes['name'])
            ->multiple((bool) ($control->props['multiple'] ?? false))
            ->value(($control->props['multiple'] ?? false) ? (array) $value : ($value === null ? null : (string) $value))
            ->options($control->props['options'])
            ->placeholder($control->props['placeholder'] ?? null)
            ->limit($control->props['limit'] ?? 150)
            ->clearable((bool) ($control->props['clearable'] ?? false))
            ->requireOptionMatch((bool) ($control->props['requireOptionMatch'] ?? false))
            ->showAllOnEmpty((bool) ($control->props['showAllOnEmpty'] ?? false))
            ->showSelectedHint((bool) ($control->props['showSelectedHint'] ?? false))
            ->orientation($control->props['dir'] ?? null)
            ->disabled($attributes['disabled'])
            ->readOnly($attributes['readonly'])
            ->required($attributes['required'])
            ->describedBy($attributes['aria']['describedby'] ?? null)
            ->attributes([
                'aria' => ['invalid' => $attributes['aria']['invalid'] ?? null],
            ])
            ->toHtml();
    }

    protected static function htmlComponent(ControlPayload $_control): ComboboxComponent
    {
        return ComboboxComponent::make();
    }

    public function component(): string
    {
        return 'craft:combobox';
    }

    /**
     * Cast option values to strings so numeric IDs match the selected values.
     *
     * @param  list<array<string, mixed>|ComboboxCreateOption>  $options
     */
    public function options(array $options): static
    {
        $this->options = array_map(self::stringifyOptionValue(...), $options);

        return $this;
    }

    /** @param array<string, mixed>|ComboboxCreateOption $option */
    private static function stringifyOptionValue(array|ComboboxCreateOption $option): array
    {
        if ($option instanceof ComboboxCreateOption) {
            $option = $option->jsonSerialize();
        }

        if (($option['type'] ?? null) === 'optgroup') {
            return [...$option, 'options' => array_map(self::stringifyOptionValue(...), $option['options'] ?? [])];
        }

        return array_key_exists('value', $option)
            ? [...$option, 'value' => (string) $option['value']]
            : $option;
    }

    public function placeholder(?string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function limit(?int $limit): static
    {
        if ($limit !== null && $limit < 1) {
            throw new InvalidArgumentException('Combobox limits must be at least 1.');
        }

        $this->limit = $limit;

        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function clearable(bool $clearable = true): static
    {
        $this->clearable = $clearable;

        return $this;
    }

    public function requireOptionMatch(bool $requireOptionMatch = true): static
    {
        $this->requireOptionMatch = $requireOptionMatch;

        return $this;
    }

    public function showAllOnEmpty(bool $showAllOnEmpty = true): static
    {
        $this->showAllOnEmpty = $showAllOnEmpty;

        return $this;
    }

    public function showSelectedHint(bool $showSelectedHint = true): static
    {
        $this->showSelectedHint = $showSelectedHint;

        return $this;
    }

    public function dir(?string $dir): static
    {
        $this->dir = $dir;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return Arr::whereNotNull([
            'options' => $this->options,
            'placeholder' => $this->placeholder,
            'limit' => $this->limit,
            'clearable' => $this->clearable ?: null,
            'multiple' => $this->multiple ?: null,
            'requireOptionMatch' => $this->requireOptionMatch ?: null,
            'showAllOnEmpty' => $this->showAllOnEmpty ?: null,
            'showSelectedHint' => $this->showSelectedHint ?: null,
            'dir' => $this->dir,
        ]);
    }
}
