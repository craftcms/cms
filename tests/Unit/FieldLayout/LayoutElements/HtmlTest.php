<?php

declare(strict_types=1);

use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\LayoutElements\Html;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use Symfony\Component\DomCrawler\Crawler;

it('renders sanitized non-interactive HTML content', function () {
    $element = new Html('<p>Direct HTML</p><form><input value="unsafe"></form><script>alert(1)</script>', [
        'uid' => 'html-test',
    ]);
    $context = new UiContext;
    $node = $element->uiNode(new FieldLayoutElementContext(null, $context));
    $payload = app(UiResolver::class)->resolve(Ui::make([$node]), $context);
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));

    expect($crawler->filter('[data-ui-node="html-test"][inert]'))->toHaveCount(1)
        ->and($crawler->filter('p')->text())->toBe('Direct HTML')
        ->and($crawler->filter('form, input, script'))->toHaveCount(0);
});
