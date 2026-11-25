<?php

declare(strict_types=1);

use CraftCms\Cms\Ui\Nodes\MarkdownContent;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use Symfony\Component\DomCrawler\Crawler;

it('renders Markdown content in a pane only when displayInPane is enabled', function () {
    $ui = Ui::make([
        MarkdownContent::make('in-pane', '**Boxed**')->width(50),
        MarkdownContent::make('no-pane', '**Plain**')->displayInPane(false),
    ]);
    $payload = app(UiResolver::class)->resolve($ui, new UiContext);
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));

    $inPane = $crawler->filter('[data-ui-node="in-pane"]');
    $noPane = $crawler->filter('[data-ui-node="no-pane"]');

    expect($inPane->nodeName())->toBe('craft-pane')
        ->and($inPane->attr('appearance'))->toBe('outline-fill')
        ->and(explode(' ', $inPane->attr('class')))->toContain('prose', 'width-50')
        ->and($inPane->filter('strong')->text())->toBe('Boxed')
        ->and($noPane->nodeName())->toBe('div')
        ->and(explode(' ', $noPane->attr('class')))->toContain('prose')
        ->and($noPane->filter('strong')->text())->toBe('Plain');
});
