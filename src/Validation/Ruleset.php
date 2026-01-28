<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation;

use CraftCms\Cms\Element\Validation\Events\DefineValidationRules;
use CraftCms\Cms\Validation\Contracts\ValidatableWithRuleset;

/**
 * @template T of ValidatableWithRuleset
 */
abstract class Ruleset
{
    public function __construct(
        /** @var T */
        protected readonly ValidatableWithRuleset $component,
    ) {}

    /**
     * Returns the validation rules for the current scenario.
     *
     * This method combines the rules defined in defineRules() with any rules
     * added by event listeners, then filters them based on the active scenario.
     * If a scenario is active, only rules for attributes defined in that scenario
     * are returned.
     *
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
     * Returns custom validation error messages.
     *
     * Override this method to provide custom error messages for specific
     * validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->component->attributeLabels();
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

    /**
     * Define the validation rules for this ruleset.
     *
     * Override this method in subclasses to define the base validation rules
     * for the component. Rules can be modified by event listeners before
     * being returned by the rules() method.
     *
     * @return array<string, array>
     */
    protected function defineRules(): array
    {
        return [];
    }
}
