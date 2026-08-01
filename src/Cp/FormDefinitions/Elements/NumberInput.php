<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Elements;

class NumberInput extends InputElement
{
    private int|float|null $min = null;

    private int|float|null $max = null;

    private int|float|null $step = null;

    public static function type(): string
    {
        return 'craft:number-input';
    }

    public function min(int|float|null $min): self
    {
        $this->min = $min;

        return $this;
    }

    public function max(int|float|null $max): self
    {
        $this->max = $max;

        return $this;
    }

    public function step(int|float|null $step): self
    {
        $this->step = $step;

        return $this;
    }

    #[\Override]
    protected function props(): array
    {
        return array_filter([
            'min' => $this->min,
            'max' => $this->max,
            'step' => $this->step,
        ], fn (mixed $value): bool => $value !== null);
    }
}
