<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Support\ServiceProvider;
use Override;
use Throwable;
use Twig\Loader\LoaderInterface;

/**
 * @since 6.0.0
 */
class TwigServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->bind(LoaderInterface::class, TemplateLoader::class);

        $handler = $this->app->make(ExceptionHandler::class);

        if ($handler instanceof Handler) {
            $handler->map(Throwable::class, fn (Throwable $e) => $this->app->make(TwigExceptionMapper::class)->map($e));
        }
    }
}
