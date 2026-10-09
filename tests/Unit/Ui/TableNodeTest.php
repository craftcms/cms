<?php

declare(strict_types=1);

use CraftCms\Cms\Ui\Nodes\Table;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use Symfony\Component\DomCrawler\Crawler;

it('renders dated cells as readable times and missing dates as empty cells without JavaScript', function () {
    $ui = Ui::make([
        Table::make('tokens')
            ->columns([['key' => 'expires', 'label' => 'Expires']])
            ->rows([
                ['expires' => ['date' => '2026-12-31T12:00:00+00:00']],
                ['expires' => null],
            ]),
    ]);
    $payload = app(UiResolver::class)->resolve($ui, new UiContext);
    $cells = new Crawler(app(UiHtmlRenderer::class)->render($payload))->filter('tbody td');

    expect($cells->eq(0)->filter('time')->text())->toBe('December 31, 2026')
        ->and($cells->eq(0)->filter('time')->attr('datetime'))->toBe('2026-12-31T12:00:00+00:00')
        ->and($cells->eq(1)->text())->toBe('');
});
