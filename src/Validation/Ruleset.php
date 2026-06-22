<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation;

use CraftCms\Cms\Validation\Contracts\Validatable;
use CraftCms\Cms\Validation\Events\ValidationRulesResolving;
use Illuminate\Validation\Validator;
use Override;

/**
 * @template T of Validatable
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
    protected function validationRules(): array
    {
        $rules = parent::validationRules();

        event($event = new ValidationRulesResolving($this->resolveSubject(), $this, $rules));

        return $event->rules;
    }

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
