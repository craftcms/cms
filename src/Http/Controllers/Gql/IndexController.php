<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Gql;

use craft\helpers\UrlHelper;
use CraftCms\Cms\Cms;
use Illuminate\Http\RedirectResponse;

readonly class IndexController extends GqlController
{
    public function __invoke(): RedirectResponse
    {
        $this->ensureGqlEnabled();

        return redirect()->to(UrlHelper::cpUrl(
            Cms::config()->allowAdminChanges ? 'graphql/schemas' : 'graphql/tokens',
        ));
    }
}
