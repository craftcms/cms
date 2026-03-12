<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use craft\helpers\UrlHelper;
use CraftCms\Cms\Cms;
use Illuminate\Support\Collection;

readonly class Cp
{
    /**
     * @TODO Could/should all this data just be handled in an inertia middleware?
     * We'll need to render all legacy pages with inertia in that case.
     */
    public static function config(): Collection
    {
        $config = Cms::config();

        return collect($config)
            ->only([
                'cpTrigger',
                'actionTrigger',
                'csrfTokenName',
            ])
            ->merge([
                'csrfTokenValue' => csrf_token(),
                'actionUrl' => UrlHelper::actionUrl(),
                'cpUrl' => UrlHelper::cpUrl(),
                'baseUrl' => UrlHelper::url(),
            ]);
    }
}
