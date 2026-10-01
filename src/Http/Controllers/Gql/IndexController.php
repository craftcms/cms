<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Gql;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\RedirectResponse;

/**
 * @since 6.0.0
 */
readonly class IndexController extends GqlController
{
    public function __invoke(): RedirectResponse
    {
        $this->ensureGqlEnabled();

        return redirect()->to(Url::cpUrl(
            Cms::config()->allowAdminChanges ? 'graphql/schemas' : 'graphql/tokens',
        ));
    }
}
