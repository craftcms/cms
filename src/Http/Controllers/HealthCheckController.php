<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Throwable;

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

        return response(View::file(base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/health-up.blade.php'), [
            'exception' => $exception,
        ]), status: $exception ? 500 : 200);
    }
}
