<?php

declare(strict_types=1);

use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\LayoutElements\Markdown;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;

it('uses the pre-encoded flavor for encoded layout markdown', function () {
    $element = new Markdown([
        'uid' => 'markdown-test',
        'content' => '`<b>`',
    ]);
    $context = new UiContext;
    $node = $element->formNode(new FieldLayoutElementContext(null, $context));
    $payload = app(UiResolver::class)->resolve(Ui::make([$node]), $context);

    expect(app(UiHtmlRenderer::class)->render($payload))
        ->toContain('<code>&lt;b&gt;</code>')
        ->not->toContain('&amp;lt;b&amp;gt;');
});
