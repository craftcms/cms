<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation;

use Closure;
use CraftCms\Cms\Validation\Contracts\Validatable;
use CraftCms\Cms\Validation\Events\ValidationRulesResolving;
use Illuminate\Validation\Validator;
use Override;

/**
 * @template T of Validatable
 *
 * @extends \CraftCms\RulesetValidation\Ruleset<T>
 *
 * @property T $subject
 */
abstract class Ruleset extends \CraftCms\RulesetValidation\Ruleset
{
    #[Override]
    protected function runValidation(bool $throw = true): bool
    {
        $subject = $this->resolveSubject();

        if ($subject instanceof Validatable) {
            $subject->prepareForValidation();
        }

        return parent::runValidation($throw);
    }

    #[Override]
    /**
     * @return array<string, array<mixed>>
     */
    protected function validationRules(): array
    {
        $rules = parent::validationRules();

        event($event = new ValidationRulesResolving($this->resolveSubject(), $this, $rules));

        return $event->rules;
    }

    /**
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        if (! method_exists($this->subject, 'afterValidate')) {
            return [];
        }

        return [
            function (Validator $validator) {
                $this->subject->afterValidate($validator);
            },
        ];
    }
}
