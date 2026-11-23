<?php

declare(strict_types=1);

use CraftCms\Cms\HttpCache\DefaultCachePolicy;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

it('serves cached responses to safe requests, with or without cookies', function (string $method, array $cookies, bool $expected) {
    $request = Request::create('/', $method, cookies: $cookies);

    expect(new DefaultCachePolicy()->isRequestCacheable($request))->toBe($expected);
})->with([
    'GET' => ['GET', [], true],
    'HEAD' => ['HEAD', [], true],
    'GET with a session cookie' => ['GET', ['laravel_session' => 'abc'], true],
    'POST' => ['POST', [], false],
]);

it('decides which responses may be stored', function (Closure $request, Closure $response, bool $expected) {
    expect(new DefaultCachePolicy()->isResponseCacheable($request(), $response()))->toBe($expected);
})->with([
    'public with a lifetime' => [
        fn () => Request::create('/'),
        fn () => new Response(headers: ['Cache-Control' => 'public, max-age=60']),
        true,
    ],
    'no cache headers set' => [
        fn () => Request::create('/'),
        fn () => new Response,
        false,
    ],
    'no-store' => [
        fn () => Request::create('/'),
        fn () => new Response(headers: ['Cache-Control' => 'public, max-age=60, no-store']),
        false,
    ],
    'no-cache' => [
        fn () => Request::create('/'),
        fn () => new Response(headers: ['Cache-Control' => 'public, max-age=60, no-cache']),
        false,
    ],
    'private' => [
        fn () => Request::create('/'),
        fn () => new Response(headers: ['Cache-Control' => 'private, max-age=60']),
        false,
    ],
    'server error' => [
        fn () => Request::create('/'),
        fn () => new Response(status: 500, headers: ['Cache-Control' => 'public, max-age=60']),
        false,
    ],
    'not found' => [
        fn () => Request::create('/'),
        fn () => new Response(status: 404, headers: ['Cache-Control' => 'public, max-age=60']),
        true,
    ],
    'POST request' => [
        fn () => Request::create('/', 'POST'),
        fn () => new Response(headers: ['Cache-Control' => 'public, max-age=60']),
        false,
    ],
    'cookie object' => [
        fn () => Request::create('/'),
        function () {
            $response = new Response(headers: ['Cache-Control' => 'public, max-age=60']);
            $response->headers->setCookie(Cookie::create('laravel_session', 'abc'));

            return $response;
        },
        false,
    ],
    'raw Set-Cookie header' => [
        fn () => Request::create('/'),
        fn () => new Response(headers: ['Cache-Control' => 'public, max-age=60', 'Set-Cookie' => 'ab=b; Path=/']),
        false,
    ],
    'max-age without public' => [
        fn () => Request::create('/'),
        fn () => new Response(headers: ['Cache-Control' => 'max-age=60']),
        false,
    ],
    's-maxage without public' => [
        fn () => Request::create('/'),
        fn () => new Response(headers: ['Cache-Control' => 's-maxage=60']),
        true,
    ],
]);
