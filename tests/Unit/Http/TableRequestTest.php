<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Requests\TableRequest;

it('resolves URL and body pagination independently of the configured page trigger', function (string $method, string $url, array $body, int $page) {
    Cms::config()->pageTrigger = 'p';
    $request = TableRequest::create($url, $method, $body);

    expect($request->page())->toBe($page);
})->with([
    'GET uses the configured URL parameter' => ['GET', '/table?p=3&page=8', [], 3],
    'POST uses the standard body parameter' => ['POST', '/table?p=8', ['page' => 3], 3],
    'POST defaults to page one' => ['POST', '/table?p=8', [], 1],
]);
