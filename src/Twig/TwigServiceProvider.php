<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig;

use CraftCms\Cms\Deprecator\Deprecator;
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

    public function boot(): void
    {
        // Twig reports what will break in Twig 4 with trigger_deprecation(). Log those to the deprecator too.
        $previous = set_error_handler(function (int $level, string $message) use (&$previous): bool {
            if ($level === E_USER_DEPRECATED && str_starts_with($message, 'Since twig/twig ')) {
                $traces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
                $i = array_find_key($traces, fn (array $trace) => $trace['function'] === 'trigger_deprecation');
                $this->app->make(Deprecator::class)->logTwigDeprecation($message, array_slice($traces, $i ?? 1));
            }

            return $previous !== null && $previous(...func_get_args()) !== false;
        });
    }
}
