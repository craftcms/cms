<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\HandleActionRequest;
use CraftCms\Cms\Http\Routing\ActionRoute;
use Illuminate\Http\Request;

beforeEach(function () {
    Cms::config()->cpTrigger = 'admin';
    Cms::config()->actionTrigger = 'actions';
});

it('rebinds rewritten action requests as the current request', function () {
    $request = Request::create('/admin/utilities/query', 'POST', [
        'action' => 'query/execute',
    ]);
    app()->instance('request', $request);

    $handledRequest = app(HandleActionRequest::class)->handle(
        $request,
        fn (Request $request) => $request,
    );

    expect($handledRequest->path())->toBe('admin/actions/query/execute')
        ->and(request())->toBe($handledRequest)
        ->and($handledRequest->attributes->get(ActionRoute::class))->toBeInstanceOf(ActionRoute::class);
});

it('does not rebind action requests that already use the normalized action uri', function () {
    $request = Request::create('/admin/actions/query/execute', 'POST');
    app()->instance('request', $request);

    $handledRequest = app(HandleActionRequest::class)->handle(
        $request,
        fn (Request $request) => $request,
    );

    expect($handledRequest)->toBe($request)
        ->and(request())->toBe($request);
});

it('does not rebind non-action requests', function () {
    $request = Request::create('/admin/utilities/query');
    app()->instance('request', $request);

    app(HandleActionRequest::class)->handle(
        $request,
        fn (Request $request) => $request,
    );

    expect(request())->toBe($request);
});
