<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\App\HealthCheckController;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\get;

test('health check action route responds successfully', function () {
    Event::fake([DiagnosingHealth::class]);

    get(action(HealthCheckController::class))
        ->assertOk();

    Event::assertDispatched(DiagnosingHealth::class);
});

test('health check action route returns 500 when diagnosing health fails outside debug mode', function () {
    config()->set('app.debug', false);

    Event::listen(DiagnosingHealth::class, fn () => throw new RuntimeException('Health failed'));

    get(action(HealthCheckController::class))->assertInternalServerError()
        ->assertSee('Application experiencing problems');
});

test('health check action route rethrows exceptions in debug mode', function () {
    config()->set('app.debug', true);
    $this->withoutExceptionHandling();

    Event::listen(DiagnosingHealth::class, fn () => throw new RuntimeException('Health failed'));

    get(action(HealthCheckController::class));
})->throws(RuntimeException::class, 'Health failed');
