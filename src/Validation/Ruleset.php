<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation;

use CraftCms\Cms\Element\Validation\Events\ValidationRulesResolving;
use CraftCms\Cms\Validation\Contracts\Validatable;
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
    protected function validationRules(): array
    {
        $rules = parent::validationRules();

        event($event = new ValidationRulesResolving($this->subject, $rules));

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
