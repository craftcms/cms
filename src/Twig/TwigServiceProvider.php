<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig;

use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\ServiceProvider;

final class TwigServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->make(ExceptionHandler::class)->map(Exception::class, fn (Exception $e) => $this->app->make(TwigMapper::class)->map($e));
    }
}
