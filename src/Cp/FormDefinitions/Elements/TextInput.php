<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Elements;

use Closure;
use CraftCms\Cms\Cp\FormDefinitions\Data\FormElementData;
use CraftCms\Cms\Support\Concerns\EvaluatesClosures;

class TextInput extends FormElement
{
    use EvaluatesClosures;

    private string|Closure|null $label = null;

    private string|Closure|null $instructions = null;

    private string|Closure|null $placeholder = null;

    private bool $readOnly = false;

    private function __construct(string $name)
    {
        parent::__construct($name);
    }

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function type(): string
    {
        return 'craft:text-input';
    }

    public function label(string|Closure|null $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function instructions(string|Closure|null $instructions): self
    {
        $this->instructions = $instructions;

        return $this;
    }

    public function placeholder(string|Closure|null $placeholder): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function readOnly(bool $readOnly = true): self
    {
        $this->readOnly = $readOnly;

        return $this;
    }

    public function toData(): FormElementData
    {
        $fieldProps = array_filter([
            'label' => $this->resolvedText($this->label),
            'instructions' => $this->resolvedText($this->instructions),
            'readOnly' => $this->readOnly ?: null,
        ], fn (mixed $value): bool => $value !== null);

        $inputProps = array_filter([
            'placeholder' => $this->resolvedText($this->placeholder),
        ], fn (mixed $value): bool => $value !== null);

        return new FormElementData(
            type: 'craft:field',
            width: $this->width,
            props: $fieldProps === [] ? null : $fieldProps,
            children: [new FormElementData(
                type: $this->type(),
                name: $this->name,
                props: $inputProps === [] ? null : $inputProps,
                attributes: $this->elementAttributes === [] ? null : $this->elementAttributes,
            )],
        );
    }

    private function resolvedText(string|Closure|null $text): ?string
    {
        /** @var ?string */
        return $this->evaluate($text);
    }
}
