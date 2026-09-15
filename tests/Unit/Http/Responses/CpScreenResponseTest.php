<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Responses\CpModalResponse;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Facades\InputNamespace;
use Illuminate\Http\Request;
use Twig\Markup;

it('accepts markup for html sections', function (string $method, string $property) {
    $markup = new Markup('<div>HTML</div>', 'UTF-8');
    $response = new CpScreenResponse;

    expect($response->$method($markup))->toBe($response)
        ->and($response->$property)->toBe($markup);
})->with([
    ['toolbarHtml', 'toolbarHtml'],
    ['additionalButtonsHtml', 'additionalButtonsHtml'],
    ['contentHtml', 'contentHtml'],
    ['metaSidebarHtml', 'metaSidebarHtml'],
    ['pageSidebarHtml', 'pageSidebarHtml'],
    ['noticeHtml', 'noticeHtml'],
    ['errorSummary', 'errorSummary'],
]);

it('restores the namespace after response preparation', function (string $class, string $prepare, ?string $outer, bool $throws) {
    InputNamespace::set($outer);
    $request = Request::create('/', server: ['HTTP_ACCEPT' => 'application/json', 'HTTP_X_CRAFT_CONTAINER_ID' => 'container']);
    $response = new $class;
    $namespace = null;
    $response->$prepare(function ($prepared, $containerId) use ($response, $throws, &$namespace) {
        expect($prepared)->toBe($response)->and($containerId)->toBe('container');
        $namespace = InputNamespace::get();
        expect($namespace)->toBeString()->toHaveLength(10);
        if ($throws) {
            throw new RuntimeException('Preparation failed');
        }
        $prepared->contentHtml('<input name="title">');
    });

    if ($throws) {
        expect(fn () => $response->toResponse($request))->toThrow(RuntimeException::class, 'Preparation failed');
    } else {
        $data = $response->toResponse($request)->getData(true);
        expect($data['namespace'])->toBe($namespace)
            ->and($data['content'])->toBe('<input name="'.$namespace.'[title]">');
    }
    expect(InputNamespace::get())->toBe($outer);
})->with([[CpScreenResponse::class, 'prepareScreen'], [CpModalResponse::class, 'prepareModal']])
    ->with([null, 'outer'])->with([false, true]);
