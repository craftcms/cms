<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Elements;

use Closure;
use CraftCms\Cms\Support\Concerns\EvaluatesClosures;

class Tab extends FormContainer
{
    use EvaluatesClosures;

    private bool $hasErrors = false;

    /**
     * @param  string  $key  Stable sibling reconciliation key.
     * @param  string|Closure  $label  Resolved tab label.
     * @param  list<FormElement>  $elements  Ordered tab elements.
     */
    protected function __construct(
        string $key,
        private readonly string|Closure $label,
        array $elements,
    ) {
        parent::__construct($elements);

        $this->elementKey = $key;
    }

    /**
     * @param  string  $key  Stable sibling reconciliation key.
     * @param  string|Closure  $label  Resolved tab label.
     * @param  list<FormElement>  $elements  Ordered tab elements.
     */
    public static function make(string $key, string|Closure $label, array $elements): self
    {
        return new self($key, $label, $elements);
    }

    public static function type(): string
    {
        return 'craft:tab';
    }

    public function hasErrors(bool $hasErrors = true): static
    {
        $this->hasErrors = $hasErrors;

        return $this;
    }

    /** @return array<string, mixed> */
    #[\Override]
    protected function props(): array
    {
        /** @var string $label */
        $label = $this->evaluate($this->label);

        return array_filter([
            'label' => $label,
            'hasErrors' => $this->hasErrors ?: null,
        ], fn (mixed $value): bool => $value !== null);
    }
}
