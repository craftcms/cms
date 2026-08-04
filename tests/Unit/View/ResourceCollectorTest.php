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
    $this->collector->begin($this->context);

    $this->htmlStack->js('console.log("hello")', Position::Head, 'js-key');
    $this->htmlStack->script('window.__craft = true', Position::BodyEnd, ['type' => 'module'], 'script-key');
    $this->htmlStack->css('body { color: red }', ['media' => 'screen'], 'css-key');
    $this->htmlStack->jsFile('/app.js', ['defer' => true, 'position' => Position::BodyEnd->value], 'file-key');
    $this->htmlStack->metaTag(['name' => 'description', 'content' => 'cached'], 'meta-key');
    $this->htmlStack->jsImport('alpine', '/alpine.js');

    $payload = $this->collector->end($this->context);

    expect($payload['js'][Position::Head->value]['js-key'])->toContain('console.log("hello")')
        ->and($payload['css']['css-key'][0])->toContain('body { color: red }')
        ->and($payload['metaTags']['meta-key'])->toBe([
            'name' => 'description',
            'content' => 'cached',
        ]);

    $this->htmlStack->clear();
    $this->collector->apply($payload, $this->context);

    $head = $this->htmlStack->headHtml();
    $body = $this->htmlStack->bodyEndHtml();

    expect($head)->toContain('body { color: red }')
        ->toContain('console.log("hello");')
        ->toContain('name="description"')
        ->toContain('alpine')
        ->and($body)->toContain('/app.js')
        ->toContain('type="module"');
});
