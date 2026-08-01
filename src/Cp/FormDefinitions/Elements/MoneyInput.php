<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Elements;

class MoneyInput extends InputElement
{
    private int $fractionDigits = 2;

    public static function type(): string
    {
        return 'craft:money-input';
    }

    public function fractionDigits(int $fractionDigits): self
    {
        $this->fractionDigits = $fractionDigits;

        return $this;
    }

    #[\Override]
    protected function props(): array
    {
        return ['fractionDigits' => $this->fractionDigits];
    }
}
