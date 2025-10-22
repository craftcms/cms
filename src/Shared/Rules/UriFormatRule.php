<?php

namespace CraftCms\Cms\Shared\Rules;

use Closure;
use craft\helpers\ElementHelper;
use CraftCms\Cms\Cms;
use Illuminate\Contracts\Validation\ValidationRule;

use function CraftCms\Cms\t;

final class UriFormatRule implements ValidationRule
{
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
        if (is_string($value)) {
            $value = trim($value, '/');
        }

        if ($this->requireSlug && ! ElementHelper::doesUriFormatHaveSlugTag($value)) {
            $fail(t('{attribute} must contain “{slug}”', [
                'attribute' => $attribute,
            ]));

            return;
        }

        if (! $this->disallowTriggers) {
            return;
        }

        $generalConfig = Cms::config();
        $firstSeg = explode('/', $value, 2)[0];

        if ($firstSeg === $generalConfig->actionTrigger) {
            $fail(t('{attribute} cannot start with the {setting} config setting.', [
                'attribute' => $attribute,
                'setting' => 'actionTrigger',
            ]));

            return;
        }

        if ($generalConfig->cpTrigger && $firstSeg === $generalConfig->cpTrigger) {
            $fail(t('{attribute} cannot start with the {setting} config setting.', [
                'attribute' => $attribute,
                'setting' => 'cpTrigger',
            ]));
        }
    }
}
