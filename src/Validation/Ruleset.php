<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation;

use CraftCms\Cms\Element\Validation\Events\DefineValidationRules;
use CraftCms\Cms\Validation\Contracts\ValidatesWithScenarios;

/**
 * @template T of ValidatesWithScenarios
 */
abstract class Ruleset
{
    public function __construct(
        /** @var T */
        protected readonly ValidatesWithScenarios $component,
    ) {}

    /**
     * @return array<string, array>
     */
    final public function rules(): array
    {
        $rules = $this->defineRules();

        event($event = new DefineValidationRules($this->component, $rules));

        $attributes = $this->component->scenarios()[$this->component->getScenario()] ?? null;

        return collect($event->rules)
            ->unless(
                is_null($attributes),
                fn ($rules) => $rules->filter(
                    fn ($rule, string $attribute) => in_array($attribute, $attributes, true),
                )
            )
            ->filter()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Prepare the component for validation.
     *
     * Use this method to normalize or transform attribute values before
     * validation runs (e.g., trimming strings, normalizing slugs).
     *
     * @param  array<string>|null  $attributeNames  The attributes being validated, or null for all
     */
    public function prepareForValidation(?array $attributeNames = null): void {}

    protected function defineRules(): array
    {
        return [];
    }
}
