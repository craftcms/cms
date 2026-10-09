<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use CraftCms\Cms\Http\Middleware\RequireToken;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Request;

it('throws if no token is found', function () {
    $this->expectExceptionMessage('Valid token required');

    app(RequireToken::class)->handle(Request::create('foo'), fn () => 'bar');
});

it('returns next if token is found', function () {
    Context::addHidden(HandleTokenRequest::TOKEN_KEY, 'token');

    expect(app(RequireToken::class)->handle(Request::create('foo'), fn () => 'bar'))->toBe('bar');
});
