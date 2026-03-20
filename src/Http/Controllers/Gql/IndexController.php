<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Gql;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\URL;
use Illuminate\Http\RedirectResponse;

readonly class IndexController extends GqlController
{
    public function __invoke(): RedirectResponse
    {
        $this->ensureGqlEnabled();

        return redirect()->to(URL::cpUrl(
            Cms::config()->allowAdminChanges ? 'graphql/schemas' : 'graphql/tokens',
        ));
    }
}
