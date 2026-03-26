<?php

declare(strict_types=1);

use CraftCms\Cms\FieldLayout\LayoutElements\Markdown;

it('uses the pre-encoded flavor for encoded layout markdown', function () {
    $element = new Markdown([
        'content' => '`<b>`',
    ]);

    expect($element->formHtml())
        ->toContain('<code>&lt;b&gt;</code>')
        ->not->toContain('&amp;lt;b&amp;gt;');
});
