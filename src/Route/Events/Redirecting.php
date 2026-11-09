<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route\Events;

use CraftCms\Cms\Route\RedirectRule;

/**
 * Dispatched before a request that would otherwise 404 is redirected by a `config/redirects.php` rule.
 *
 * @since 6.0.0
 */
readonly class Redirecting
{
    public function __construct(
        public RedirectRule $rule,
    ) {}
}
