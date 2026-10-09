<?php

declare(strict_types=1);

use CraftCms\Cms\View\CacheCollectors\ResourceCollector;
use CraftCms\Cms\View\Data\TemplateCacheContext;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Cms\View\HtmlStack;

beforeEach(function () {
    app()->forgetScopedInstances();

    $this->collector = app(ResourceCollector::class);
    $this->htmlStack = app(HtmlStack::class);
    $this->htmlStack->clear();
    $this->context = new TemplateCacheContext(
        cacheKey: 'test-cache',
        global: true,
        resources: true,
    );
});

it('captures and reapplies buffered html stack resources', function () {
    $registerResources = function (HtmlStack $htmlStack): void {
        $htmlStack->js('console.log("hello")', Position::Head, 'js-key');
        $htmlStack->script('window.__craft = true', Position::BodyEnd, ['type' => 'module'], 'script-key');
        $htmlStack->css('body { color: red }', ['media' => 'screen', 'data' => ['theme' => 'dark']], 'css-key');
        $htmlStack->jsFile('/app.js', [
            'defer' => true,
            'condition' => 'IE 9',
            'noscript' => true,
            'data' => ['module' => 'app'],
            'position' => Position::BodyEnd->value,
        ], 'file-key');
        $htmlStack->cssFile('/app.css', ['media' => 'print'], 'css-file-key');
        $htmlStack->metaTag(['name' => 'description', 'content' => 'cached'], 'meta-key');
        $htmlStack->jsImport('alpine', '/alpine.js');
        $htmlStack->css('body { color: blue }', ['nonce' => 'updated'], 'css-key');
    };

    $registerResources($this->htmlStack);
    $expectedHead = $this->htmlStack->headHtml();
    $expectedBody = $this->htmlStack->bodyEndHtml();

    app()->forgetScopedInstances();
    $this->collector = app(ResourceCollector::class);
    $this->htmlStack = app(HtmlStack::class);

    $this->collector->begin($this->context);
    $registerResources($this->htmlStack);

    $payload = $this->collector->end($this->context);

    expect($payload['js'][Position::Head->value]['js-key'])->toContain('console.log("hello")')
        ->and($payload['scripts'][Position::BodyEnd->value]['script-key'])->toBe([
            'window.__craft = true',
            ['type' => 'module'],
        ])
        ->and($payload['css']['css-key'])->toBe([
            'body { color: blue }',
            ['nonce' => 'updated'],
        ])
        ->and($payload['jsFiles'][Position::BodyEnd->value]['file-key'])->toBe([
            '/app.js',
            [
                'defer' => true,
                'condition' => 'IE 9',
                'noscript' => true,
                'data' => ['module' => 'app'],
            ],
        ])
        ->and($payload['cssFiles']['css-file-key'])->toBe([
            '/app.css',
            ['media' => 'print'],
        ])
        ->and($payload['metaTags']['meta-key'])->toBe([
            'name' => 'description',
            'content' => 'cached',
        ]);

    $this->htmlStack->clear();
    $this->collector->apply($payload, $this->context);

    expect($this->htmlStack->headHtml())->toBe($expectedHead)
        ->and($this->htmlStack->bodyEndHtml())->toBe($expectedBody);
});

it('returns an empty payload when no resources were buffered', function () {
    $this->collector->begin($this->context);

    expect($this->collector->end($this->context))->toBe([]);
});
