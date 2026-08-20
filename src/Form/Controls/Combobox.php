<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\Components\Combobox as ComboboxComponent;
use CraftCms\Cms\Form\ControlPayload;
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

    private bool $requireOptionMatch = false;

    private bool $showAllOnEmpty = false;

    private bool $showSelectedHint = false;

    private ?string $dir = null;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        return ComboboxComponent::make()
            ->id($attributes['id'])
            ->name($attributes['name'])
            ->value($value === null ? null : (string) $value)
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

    public function component(): string
    {
        return 'craft:combobox';
    }

    /** @param list<array<string, mixed>> $options */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
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
            'requireOptionMatch' => $this->requireOptionMatch ?: null,
            'showAllOnEmpty' => $this->showAllOnEmpty ?: null,
            'showSelectedHint' => $this->showSelectedHint ?: null,
            'dir' => $this->dir,
        ]);
    }
}
