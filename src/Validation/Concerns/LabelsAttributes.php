<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Concerns;

use Illuminate\Validation\Validator;
use Override;

/**
 * Gives a rule the label of the attribute it's validating, for the
 * `{attribute}` placeholder in its messages. Craft's `t()` fills placeholders
 * only from the params it's given, so Laravel's own `:attribute` replacement
 * never reaches them.
 *
 * Use it in a rule that implements `ValidatorAwareRule`.
 *
 * @since 6.0.0
 */
trait LabelsAttributes
{
    private ?Validator $validator = null;

    #[Override]
    public function setValidator(Validator $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * The attribute's label: its custom attribute name if the validator has
     * one (wildcards included), otherwise the attribute itself.
     */
    protected function attributeLabel(string $attribute): string
    {
        return $this->validator?->getDisplayableAttribute($attribute) ?? $attribute;
    }
}
