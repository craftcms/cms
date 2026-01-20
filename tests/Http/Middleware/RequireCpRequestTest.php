<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\RequireCpRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->middleware = app(RequireCpRequest::class);
});

it('passes through for CP request', function () {
    $request = Request::create('/'.Cms::config()->cpTrigger.'/dashboard');

    $result = $this->middleware->handle($request, fn () => 'passed');

    expect($result)->toBe('passed');
});

it('aborts 401 for non-CP request', function () {
    $request = Request::create('/site-page');

    $this->middleware->handle($request, fn () => 'passed');
})->throws(HttpException::class);

it('passes through for CP root request', function () {
    $request = Request::create('/'.Cms::config()->cpTrigger);

    $result = $this->middleware->handle($request, fn () => 'passed');

    expect($result)->toBe('passed');
});
