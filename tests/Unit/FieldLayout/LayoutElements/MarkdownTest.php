<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
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
    $node = $element->uiNode(new FieldLayoutElementContext(null, $context));
    $payload = app(UiResolver::class)->resolve(Ui::make([$node]), $context);

    expect(app(UiHtmlRenderer::class)->render($payload))
        ->toContain('<code>&lt;b&gt;</code>')
        ->not->toContain('&amp;lt;b&amp;gt;');
});

// https://github.com/craftcms/cms/issues/20408
it('translates the content with the site translation category', function () {
    Cms::config()->translationDebugOutput();

    $element = new Markdown([
        'uid' => 'markdown-test',
        'content' => 'Hello world',
    ]);
    $context = new UiContext;
    $node = $element->uiNode(new FieldLayoutElementContext(null, $context));
    $payload = app(UiResolver::class)->resolve(Ui::make([$node]), $context);

    expect(app(UiHtmlRenderer::class)->render($payload))->toContain('$Hello world$');
});
