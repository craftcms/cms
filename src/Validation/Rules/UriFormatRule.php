<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Rules;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\ElementHelper;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Validation\Validator;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class UriFormatRule implements ValidationRule, ValidatorAwareRule
{
    private ?Validator $validator = null;

    public function __construct(
        /**
         * @var bool Whether we should ensure that "{slug}" is used within the URI format.
         */
        public bool $requireSlug = false,

        /**
         * @var bool Whether to ensure that the URI format doesn’t begin with the actionTrigger or cpTrigger.
         */
        public bool $disallowTriggers = true,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $label = $this->validator?->getDisplayableAttribute($attribute) ?? $attribute;

        if (is_string($value)) {
            $value = trim($value, '/');
        }

        if ($this->requireSlug && ! ElementHelper::doesUriFormatHaveSlugTag($value)) {
            $fail(t('{attribute} must contain “{slug}”', [
                'attribute' => $label,
            ]));

            return;
        }

        if (! $this->disallowTriggers) {
            return;
        }

        $generalConfig = Cms::config();
        $firstSeg = explode('/', (string) $value, 2)[0];

        if ($firstSeg === $generalConfig->actionTrigger) {
            $fail(t('{attribute} cannot start with the {setting} config setting.', [
                'attribute' => $label,
                'setting' => 'actionTrigger',
            ]));

            return;
        }

        if ($generalConfig->cpTrigger && $firstSeg === $generalConfig->cpTrigger) {
            $fail(t('{attribute} cannot start with the {setting} config setting.', [
                'attribute' => $label,
                'setting' => 'cpTrigger',
            ]));
        }
    }

    public function setValidator(Validator $validator): static
    {
        $this->validator = $validator;

        return $this;
    }
}
