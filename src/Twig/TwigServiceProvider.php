<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig;

use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Support\ServiceProvider;
use Override;

final class TwigServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $handler = $this->app->make(ExceptionHandler::class);

        if ($handler instanceof Handler) {
            $handler->map(Exception::class, fn (Exception $e) => $this->app->make(TwigMapper::class)->map($e));
        }
    }
}
