<?php

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\SendPoweredByHeader;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

beforeEach(function () {
    $this->generalConfig = Cms::config();
    $this->middleware = app(SendPoweredByHeader::class);
});

it('will remove the header if the config is disabled', function () {
    $this->generalConfig->sendPoweredByHeader(false);

    $callback = function () {
        $response = new Response;
        $response->header('X-Powered-By', 'Foo');

        return $response;
    };

    $response = $this->middleware->handle(Request::create('/'), $callback);

    expect($response->headers->has('X-Powered-By'))->toBeFalse();
});

it('will add the header if the config is enabled', function () {
    $this->generalConfig->sendPoweredByHeader();

    $callback = (fn () => new Response);

    $response = $this->middleware->handle(Request::create('/'), $callback);

    expect($response->headers->has('X-Powered-By'))->toBeTrue();
    expect($response->headers->get('X-Powered-By'))->toBe('Craft CMS');
});

it('will append if the config is enabled and another value was set already', function () {
    $this->generalConfig->sendPoweredByHeader();

    $callback = function () {
        $response = new Response;
        $response->header('X-Powered-By', 'Foo');

        return $response;
    };

    $response = $this->middleware->handle(Request::create('/'), $callback);

    expect($response->headers->has('X-Powered-By'))->toBeTrue();
    expect($response->headers->get('X-Powered-By'))->toBe('Foo,Craft CMS');
});
