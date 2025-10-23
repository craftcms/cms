<?php

namespace CraftCms\Cms\Shared\Rules;

use Closure;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Contracts\Validation\ValidationRule;

use function CraftCms\Cms\t;

final readonly class SiteIdRule implements ValidationRule
{
    public function __construct(
        /**
         * @var ?bool $allowDisabled Whether to allow disabled sites.
         */
        private ?bool $allowDisabled = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value && ! Sites::getAllSiteIds($this->allowDisabled)->contains($value)) {
            $fail(t('Your system isn’t set up to save content for the site “{site}”.', ['site' => $value]));
        }
    }
}
