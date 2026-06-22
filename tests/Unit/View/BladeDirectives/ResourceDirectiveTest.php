<?php

declare(strict_types=1);

use CraftCms\Cms\View\BladeRenderer;
use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\TemplateMode;

beforeEach(function () {
    TemplateMode::set(TemplateMode::Site);
    app()->forgetScopedInstances();

    $this->renderer = app(BladeRenderer::class);
});

it('registers Blade resource directives', function () {
    $assets = app(HtmlStack::class);

    $this->renderer->renderString(<<<'BLADE'
@craftCss
body { color: red; }
@endCraftCss
@craftCss('html { color: blue; }')
@craftJs('console.log("inline");', ['position' => \CraftCms\Cms\View\Enums\Position::BodyEnd->value])
@craftJs
console.log("captured");
@endCraftJs
@craftHtml
<div id="blade-html">Blade HTML</div>
@endCraftHtml
@craftScript
{"name":"Blade"}
@endCraftScript
BLADE);

    $head = $assets->headHtml(clear: false);
    $body = $assets->bodyEndHtml(clear: false);

    expect($head)
        ->toContain('body { color: red; }')
        ->toContain('html { color: blue; }')
        ->and($body)
        ->toContain('console.log("inline");')
        ->toContain('console.log("captured");')
        ->toContain('<div id="blade-html">Blade HTML</div>')
        ->toContain('{"name":"Blade"}');
});
