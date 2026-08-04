<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation;

use CraftCms\Cms\Validation\Contracts\Validatable;
use Override;

/**
 * A ruleset which takes its validation rules,
 * attributes and messages from the subject.
 *
 * @extends Ruleset<Validatable>
 */
class ValidatableRules extends Ruleset
{
    public function rules(): array
    {
        return $this->subject->getRules();
    }

    #[Override]
    public function attributes(): array
    {
        return $this->subject->attributeLabels();
    }

    #[Override]
    public function messages(): array
    {
        return $this->subject->getMessages();
    }

    #[Override]
    protected function passedValidation(): void
    {
        $this->subject->passedValidation();
    }
}
