<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Elements;

use Closure;

class TextInput extends InputElement
{
    private string|Closure|null $placeholder = null;

    public static function type(): string
    {
        return 'craft:text-input';
    }

    public function placeholder(string|Closure|null $placeholder): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    #[\Override]
    protected function props(): array
    {
        /** @var ?string $placeholder */
        $placeholder = $this->evaluate($this->placeholder);

        return array_filter([
            'placeholder' => $placeholder,
        ], fn (mixed $value): bool => $value !== null);
    }
}
