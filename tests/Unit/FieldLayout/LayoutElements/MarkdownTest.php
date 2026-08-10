<?php

declare(strict_types=1);

use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\LayoutElements\Markdown;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;

it('uses the pre-encoded flavor for encoded layout markdown', function () {
    $element = new Markdown([
        'uid' => 'markdown-test',
        'content' => '`<b>`',
    ]);
    $context = new FormContext;
    $node = $element->formNode(new FieldLayoutElementContext(null, $context));
    $payload = app(FormResolver::class)->resolve(Form::make([$node]), $context);

    expect(app(FormHtmlRenderer::class)->render($payload))
        ->toContain('<code>&lt;b&gt;</code>')
        ->not->toContain('&amp;lt;b&amp;gt;');
});
