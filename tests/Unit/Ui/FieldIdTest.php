<?php

declare(strict_types=1);

use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use Symfony\Component\DomCrawler\Crawler;

function renderField(string $path = 'title'): Crawler
{
    $payload = app(UiResolver::class)->resolve(
        Ui::make([Field::make('Title', Text::make($path))]),
        new UiContext,
    );

    return new Crawler(app(UiHtmlRenderer::class)->render($payload));
}

it('gives the field the id derived from the control path', function () {
    expect(renderField()->filter('craft-field')->attr('id'))->toBe('ui-title');
});

it('puts the input inside on a distinct id of its own', function () {
    // The label points `for` at this, and Handle's generator targets it by id.
    $input = renderField()->filter('craft-input input');

    expect($input->attr('id'))->toBe('ui-title-input')
        ->and($input->attr('name'))->toBe('title');
});
