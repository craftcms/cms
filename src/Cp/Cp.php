<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use CraftCms\Cms\Cms;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;

final readonly class Cp
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
            ]);
    }

    /**
     * Returns page data for non-Inertia (legacy) pages so they can be
     * rendered inside the Inertia Vue app shell with the standard CP chrome.
     *
     * @return array{url: string, component: string, version: string|null, props: array<string, mixed>}
     */
    public static function nonInertiaPageData(): array
    {
        return [
            'url' => '/'.Request::path(),
            'component' => 'NonInertiaPage',
            'version' => inertia()->getVersion(),
            'props' => Inertia::getShared(),
        ];
    }
}
