<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\CheckSchemaVersion;
use CraftCms\Cms\Shared\Models\Info;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->middleware = app(CheckSchemaVersion::class);
});

it('passes through when schema version is compatible', function () {
    $request = Request::create('foo');

    $result = $this->middleware->handle($request, fn () => 'passed');

    expect($result)->toBe('passed');
});

it('passes through for CP request when schema is compatible', function () {
    $request = Request::create('/'.Cms::config()->cpTrigger.'/dashboard');

    $result = $this->middleware->handle($request, fn () => 'passed');

    expect($result)->toBe('passed');
});

it('aborts 503 for site request when schema is incompatible', function () {
    $info = Info::fetch();
    $originalSchemaVersion = $info->schemaVersion;
    $info->schemaVersion = '999.0.0';
    $info->save();

    $request = Request::create('/site-page');

    try {
        $this->middleware->handle($request, fn () => 'passed');
    } finally {
        $info->schemaVersion = $originalSchemaVersion;
        $info->save();
    }
})->throws(HttpException::class);

it('throws RuntimeException for CP request when schema is incompatible', function () {
    $info = Info::fetch();
    $originalSchemaVersion = $info->schemaVersion;
    $info->schemaVersion = '999.0.0';
    $info->save();

    $request = Request::create('/'.Cms::config()->cpTrigger.'/dashboard');

    try {
        $this->middleware->handle($request, fn () => 'passed');
    } finally {
        $info->schemaVersion = $originalSchemaVersion;
        $info->save();
    }
})->throws(RuntimeException::class);
