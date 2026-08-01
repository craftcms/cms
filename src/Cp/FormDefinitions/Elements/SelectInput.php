<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Elements;

class SelectInput extends InputElement
{
    /** @var list<array{label: string, value: string|int|float|bool|null}> */
    private array $options = [];

    public static function type(): string
    {
        return 'craft:select-input';
    }

    /** @param list<array{label: string, value: string|int|float|bool|null}> $options */
    public function options(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    #[\Override]
    protected function props(): array
    {
        return ['options' => $this->options];
    }
}
