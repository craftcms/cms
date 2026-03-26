<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Middleware\EnforceLicenses;
use CraftCms\Cms\License\License;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

beforeEach(function () {
    $this->middleware = app(EnforceLicenses::class);

    TemplateMode::set(TemplateMode::Cp);
});

it('passes through if no user is authenticated', function () {
    $request = Request::create('foo');
    $request->setUserResolver(fn () => null);

    $result = $this->middleware->handle($request, fn () => 'bar');

    expect($result)->toBe('bar');
});

it('passes through if edition can test', function () {
    $request = Request::create('foo');
    $request->setUserResolver(fn () => new class {});

    $result = $this->middleware->handle($request, fn () => 'bar');

    expect($result)->toBe('bar');
});

it('passes through if no license issues', function () {
    putenv('CRAFT_NO_TRIALS=true');

    $mockLicense = Mockery::mock(License::class)->makePartial();
    $mockLicense->shouldReceive('issues')->with(false)->andReturn([]);
    $middleware = new EnforceLicenses($mockLicense);

    $request = Request::create('foo');
    $request->setUserResolver(fn () => new class {});

    $result = $middleware->handle($request, fn () => 'bar');

    expect($result)->toBe('bar');

    putenv('CRAFT_NO_TRIALS');
});

it('shows licensing screen when license issues exist', function () {
    putenv('CRAFT_NO_TRIALS=true');

    $licenseIssues = [['Issue 1', 'Description 1', 'sku1']];
    $hash = 'abc123';

    $mockLicense = Mockery::mock(License::class)->makePartial();
    $mockLicense->shouldReceive('issues')->andReturn($licenseIssues);
    $mockLicense->shouldReceive('issuesHash')->with($licenseIssues)->andReturn($hash);
    $mockLicense->shouldReceive('shunCookieName')->andReturn('craft_license_shun');
    $middleware = new EnforceLicenses($mockLicense);

    $request = Request::create('foo');
    $request->setUserResolver(fn () => new class {});

    $result = $middleware->handle($request, fn () => new Response);

    expect($result)->toBeInstanceOf(Response::class);
    expect($result->getStatusCode())->toBe(402);
    expect($result->headers->has('Cache-Control'))->toBeTrue();

    putenv('CRAFT_NO_TRIALS');
});
