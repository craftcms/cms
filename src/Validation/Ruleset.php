<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation;

use CraftCms\Cms\Element\Validation\Events\DefineValidationRules;
use CraftCms\Cms\Validation\Contracts\Validatable;

/**
 * @template T of Validatable
 *
 * @property T $subject
 */
abstract class Ruleset extends \CraftCms\RulesetValidation\Ruleset
{
    #[\Override]
    protected function validationRules(): array
    {
        $rules = parent::validationRules();

        event($event = new DefineValidationRules($this->subject, $rules));

        return $event->rules;
    }
}
