<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Elements;

class CheckboxSelectInput extends InputElement
{
    /** @var list<array{label: string, value: string|int|float|bool|null}> */
    private array $options = [];

    private string|int|float|bool|null $allOption = null;

    public static function type(): string
    {
        return 'craft:checkbox-select-input';
    }

    /** @param list<array{label: string, value: string|int|float|bool|null}> $options */
    public function options(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function allOption(string|int|float|bool|null $value): self
    {
        $this->allOption = $value;

        return $this;
    }

    #[\Override]
    protected function props(): array
    {
        return array_filter([
            'options' => $this->options,
            'allOption' => $this->allOption,
        ], fn (mixed $value): bool => $value !== null);
    }
}
