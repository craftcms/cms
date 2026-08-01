<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Elements;

use Closure;

class ComboboxInput extends InputElement
{
    /** @var list<array<string, mixed>> */
    private array $options = [];

    private string|Closure|null $placeholder = null;

    private bool $allowAliases = false;

    public static function type(): string
    {
        return 'craft:combobox-input';
    }

    /** @param list<array<string, mixed>> $options */
    public function options(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function placeholder(string|Closure|null $placeholder): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function allowAliases(bool $allowAliases = true): self
    {
        $this->allowAliases = $allowAliases;

        return $this;
    }

    #[\Override]
    protected function props(): array
    {
        /** @var ?string $placeholder */
        $placeholder = $this->evaluate($this->placeholder);

        return array_filter([
            'options' => $this->options,
            'placeholder' => $placeholder,
            'allowAliases' => $this->allowAliases ?: null,
        ], fn (mixed $value): bool => $value !== null);
    }
}
