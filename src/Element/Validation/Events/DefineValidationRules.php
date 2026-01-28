<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Validation\Events;

use CraftCms\Cms\Validation\Contracts\Validatable;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event dispatched when an element's validation rules are being defined.
 *
 * Plugins can listen to this event to add custom validation rules.
 */
final class DefineValidationRules
{
    use Dispatchable;

    /**
     * @param  Validatable  $component  The component being validated
     * @param  array<string, array<mixed>>  $rules  The current validation rules
     */
    public function __construct(
        public readonly Validatable $component,
        public array $rules,
    ) {}

    /**
     * Add a rule for an attribute.
     *
     * @param  string  $attribute  The attribute name
     * @param  mixed  $rule  The rule to add
     */
    public function addRule(string $attribute, mixed $rule): void
    {
        if (! isset($this->rules[$attribute])) {
            $this->rules[$attribute] = [];
        }

        $this->rules[$attribute][] = $rule;
    }

    /**
     * Add multiple rules for an attribute.
     *
     * @param  string  $attribute  The attribute name
     * @param  array<mixed>  $rules  The rules to add
     */
    public function addRules(string $attribute, array $rules): void
    {
        if (! isset($this->rules[$attribute])) {
            $this->rules[$attribute] = [];
        }

        $this->rules[$attribute] = array_merge($this->rules[$attribute], $rules);
    }
}
