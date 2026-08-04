<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

class Number extends Text
{
    #[\Override]
    protected string $inputType = 'number';

    private ?int $size = null;

    #[\Override]
    public function component(): string
    {
        return 'craft:number';
    }

    public function size(?int $size): static
    {
        $this->size = $size;

        return $this;
    }

    #[\Override]
    public function props(): array
    {
        return array_filter([
            ...parent::props(),
            'size' => $this->size,
        ], fn (mixed $value): bool => $value !== null);
    }
}
