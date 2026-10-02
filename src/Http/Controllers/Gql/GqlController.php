<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Gql;

use CraftCms\Cms\Cms;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
abstract readonly class GqlController
{
    protected function ensureGqlEnabled(): void
    {
        abort_unless(Cms::config()->enableGql, 404, t('Page not found.'));
    }
}
