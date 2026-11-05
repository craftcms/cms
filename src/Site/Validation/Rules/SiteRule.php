<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Validation\Rules;

use Closure;
use CraftCms\Cms\Shared\Enums\ModelIdentifier;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Validation\Rules\ModelReferenceRule;
use Override;

use function CraftCms\Cms\t;

/**
 * Validates that a value references an existing site by ID, UID or handle.
 *
 * @since 6.0.0
 */
readonly class SiteRule extends ModelReferenceRule
{
    /**
     * @param  ModelIdentifier[]|null  $by
     * @param  Closure(string): string|string|null  $message
     */
    public function __construct(
        ?array $by = null,
        private ?bool $allowDisabled = null,
        Closure|string|null $message = null,
    ) {
        parent::__construct($by, $message);
    }

    #[Override]
    protected function resolve(mixed $value): ?object
    {
        return Sites::resolveSite($value, $this->by, $this->allowDisabled);
    }

    #[Override]
    protected function defaultMessage(string $value): string
    {
        return t('No site found for “{site}”.', ['site' => $value]);
    }
}
