<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Elements;

use Closure;
use CraftCms\Cms\Cp\FormDefinitions\Data\FormElementData;
use CraftCms\Cms\Support\Concerns\EvaluatesClosures;

abstract class InputElement extends FormElement
{
    use EvaluatesClosures;

    private string|Closure|null $label = null;

    private string|Closure|null $instructions = null;

    private string|Closure|null $tip = null;

    private string|Closure|null $warning = null;

    private bool $required = false;

    private bool $readOnly = false;

    final protected function __construct(string $name)
    {
        parent::__construct($name);
    }

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function label(string|Closure|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function instructions(string|Closure|null $instructions): static
    {
        $this->instructions = $instructions;

        return $this;
    }

    public function tip(string|Closure|null $tip): static
    {
        $this->tip = $tip;

        return $this;
    }

    public function warning(string|Closure|null $warning): static
    {
        $this->warning = $warning;

        return $this;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    public function readOnly(bool $readOnly = true): static
    {
        $this->readOnly = $readOnly;

        return $this;
    }

    #[\Override]
    public function toData(): FormElementData
    {
        $fieldProps = array_filter([
            'label' => $this->resolvedText($this->label),
            'instructions' => $this->resolvedText($this->instructions),
            'tip' => $this->resolvedText($this->tip),
            'warning' => $this->resolvedText($this->warning),
            'required' => $this->required ?: null,
            'readOnly' => $this->readOnly ?: null,
        ], fn (mixed $value): bool => $value !== null);
        $inputProps = $this->props();

        return new FormElementData(
            type: 'craft:field',
            key: $this->elementKey,
            width: $this->width,
            props: $fieldProps === [] ? null : $fieldProps,
            children: [new FormElementData(
                type: static::type(),
                name: $this->name,
                props: $inputProps === [] ? null : $inputProps,
                attributes: $this->elementAttributes === [] ? null : $this->elementAttributes,
            )],
            visibleWhen: $this->visibleWhen?->toData(),
        );
    }

    private function resolvedText(string|Closure|null $text): ?string
    {
        /** @var ?string */
        return $this->evaluate($text);
    }
}
