<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

use function Pest\Laravel\call;

beforeEach(function () {
    $this->app->instance('env', 'local');

    Route::middleware('web')->post('forgery-check', fn () => 'ok');
});

it('accepts token-less requests whose Origin matches the host when Sec-Fetch-Site is missing', function () {
    call('POST', 'https://localhost/forgery-check', server: ['HTTP_ORIGIN' => 'https://localhost'])
        ->assertOk();
});

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
