<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Html;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\call;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->app->instance('env', 'local');

    Route::middleware('web')->post('forgery-check', fn () => 'ok');
    Route::middleware('web')->get('csrf-input', fn () => Html::csrfInput());
});

afterEach(function () {
    PreventRequestForgery::useOriginOnly(false);
});

it('accepts token-less requests whose Origin matches the host when Sec-Fetch-Site is missing', function (bool $originOnly) {
    PreventRequestForgery::useOriginOnly($originOnly);

    call('POST', 'https://localhost/forgery-check', server: ['HTTP_ORIGIN' => 'https://localhost'])
        ->assertOk();
})->with(['default mode' => false, 'origin-only mode' => true]);

it('rejects token-less requests without a same-origin Origin', function (array $headers) {
    call('POST', 'https://localhost/forgery-check', server: $headers)
        ->assertStatus(419);
})->with([
    'no Origin' => [[]],
    'other host' => [['HTTP_ORIGIN' => 'https://evil.test']],
    'other scheme' => [['HTTP_ORIGIN' => 'http://localhost']],
    'opaque Origin' => [['HTTP_ORIGIN' => 'null']],
    'cross-site Sec-Fetch-Site' => [['HTTP_ORIGIN' => 'https://localhost', 'HTTP_SEC_FETCH_SITE' => 'cross-site']],
]);

it('prints a CSRF input unless only origins are checked', function () {
    get('csrf-input')->assertOk()->assertSee('name="_token"', false);

    PreventRequestForgery::useOriginOnly();

    get('csrf-input')->assertOk()->assertDontSee('name="_token"', false);
});
