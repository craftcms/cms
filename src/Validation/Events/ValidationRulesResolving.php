<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Events;

use CraftCms\Cms\Validation\Contracts\Validatable;
use CraftCms\Cms\Validation\Ruleset;
use CraftCms\RulesetValidation\Contracts\ValidatesWithRuleset;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

/**
 * Event dispatched when validation rules are being defined.
 *
 * Plugins can listen to this event to add custom validation rules:
 *
 * ```php
 * use CraftCms\Cms\Validation\Events\ValidationRulesResolving;
 * use CraftCms\Cms\Entry\Elements\Entry;
 *
 * Event::listen(function (ValidationRulesResolving $event) {
 *     // Suppose we’re only interested in entries:
 *     if (! $event->subject instanceof Entry) {
 *         return;
 *     }
 *
 *     // Ignore nested entries:
 *     if ($event->subject->ownerId !== null) {
 *         return;
 *     }
 *
 *     // Enforce short slugs:
 *     $event->addRule('slug', 'max:40');
 * });
 * ```
 */
class ValidationRulesResolving
{
    use Dispatchable;

    /**
     * @param  ValidatesWithRuleset|Request  $subject  The object being validated
     * @param  Ruleset<Validatable>  $ruleset  The ruleset that produced the attached {@see $rules}
     * @param  array<string, array<mixed>>  $rules  The current validation rules
     */
    public function __construct(
        public readonly ValidatesWithRuleset|Request $subject,
        public readonly Ruleset $ruleset,
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
        $this->rules[$attribute] ??= [];

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
        $this->rules[$attribute] ??= [];

        $this->rules[$attribute] = array_merge($this->rules[$attribute], $rules);
    }
}
