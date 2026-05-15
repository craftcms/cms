<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Url;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Collection;
use stdClass;

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
                'runQueueAutomatically',
            ])
            ->merge([
                'translations' => I18N::getAllTranslationsForLocale(app()->getLocale()) ?: new stdClass,
                'csrfTokenValue' => csrf_token(),
                'actionUrl' => Url::actionUrl(),
                'cpUrl' => Url::cpUrl(),
                'baseUrl' => Url::url(),
            ]);
    }

    public static function vite(): Vite
    {
        return (clone app(Vite::class))
            ->useHotFile(Aliases::get('@resources/hot'))
            ->useBuildDirectory('vendor/craft/build');
    }

    public static function viteScripts(): Vite
    {
        return static::vite()->withEntryPoints([
            'resources/css/cp.css',
            'resources/js/cp.ts',
        ]);
    }
}
