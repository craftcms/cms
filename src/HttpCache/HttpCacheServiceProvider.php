<?php

declare(strict_types=1);

namespace CraftCms\Cms\HttpCache;

use FOS\HttpCache\TagHeaderFormatter\TagHeaderFormatter;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Override;

/**
 * @since 6.0.0
 */
class HttpCacheServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singletonIf(CachePolicy::class, DefaultCachePolicy::class);
        $this->app->singleton(HttpCacheManager::class, fn (Application $app) => new HttpCacheManager($app));

        $this->app->scoped(ResponseTagger::class, fn (Application $app) => new ResponseTagger(
            $this->tagHeaderFormatter($app),
        ));
    }

    private function tagHeaderFormatter(Application $app): ?TagHeaderFormatter
    {
        /** @var class-string<TagHeaderFormatter>|null $formatter */
        $formatter = config('craft.http-cache.tags.formatter');

        return $formatter === null
            ? null
            : $app->make($formatter, config('craft.http-cache.tags.formatter_options', []));
    }
}
