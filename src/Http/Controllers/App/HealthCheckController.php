<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\App;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use ReflectionClass;
use Throwable;

/**
 * @since 6.0.0
 */
class HealthCheckController
{
    public function __invoke(): Response
    {
        $exception = null;

        try {
            Event::dispatch(new DiagnosingHealth);

        } catch (Throwable $e) {
            if (app()->hasDebugModeEnabled()) {
                throw $e;
            }

            report($e);

            $exception = $e->getMessage();
        }

        $view = dirname((string) new ReflectionClass(Application::class)->getFileName()).'/resources/health-up.blade.php';

        return response(View::file($view, [
            'exception' => $exception,
        ]), status: $exception ? 500 : 200);
    }
}
