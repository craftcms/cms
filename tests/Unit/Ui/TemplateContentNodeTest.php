<?php

declare(strict_types=1);

use CraftCms\Cms\Ui\Nodes\TemplateContent;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use Symfony\Component\DomCrawler\Crawler;

it('preserves interactive HTML and inline SVG when template content is explicitly trusted', function () {
    $html = '<a href="https://example.test/support">Supporting URL</a>'
        .'<svg role="img" aria-label="SVG preview" viewBox="0 0 10 10"><path d="M0 0h10v10z" /></svg>'
        .'<button type="button" onclick="this.textContent = &quot;Downloaded&quot;">Download SVG</button>';
    $ui = Ui::make([TemplateContent::make('sidebar-content', $html)->trusted()]);
    $payload = app(UiResolver::class)->resolve($ui, new UiContext);
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));

    expect($crawler->filter('button')->count())->toBe(1)
        ->and($crawler->filter('button')->attr('onclick'))->toBe('this.textContent = "Downloaded"')
        ->and($crawler->filter('a')->attr('href'))->toBe('https://example.test/support')
        ->and($crawler->filter('svg[role="img"]')->attr('aria-label'))->toBe('SVG preview')
        ->and($crawler->filter('svg path')->attr('d'))->toBe('M0 0h10v10z')
        ->and($crawler->filter('[inert]'))->toHaveCount(0);
});
