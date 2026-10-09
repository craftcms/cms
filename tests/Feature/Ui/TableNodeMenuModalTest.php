<?php

declare(strict_types=1);

use CraftCms\Cms\Ui\Nodes\Table;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use Symfony\Component\DomCrawler\Crawler;

function menuModalRows(): array
{
    return [[
        'id' => 1,
        'stock' => [
            'label' => '7',
            'items' => [
                ['label' => 'History', 'url' => 'stock/1/history'],
                [
                    'label' => 'Adjust',
                    'modalUrl' => 'stock/adjust-modal',
                    'actionUrl' => 'stock/adjust',
                    'params' => ['id' => 1],
                ],
            ],
        ],
    ]];
}

it('renders menu items that open a modal UI as plain labels without JavaScript', function () {
    $payload = app(UiResolver::class)->resolve(Ui::make([
        Table::make('stock')
            ->columns([['key' => 'stock', 'label' => 'Stock']])
            ->rows(menuModalRows()),
    ]), new UiContext);

    $cell = new Crawler(app(UiHtmlRenderer::class)->render($payload))->filter('tbody td');

    expect($cell->text())->toBe('History, Adjust')
        ->and($cell->filter('a'))->toHaveCount(1)
        ->and($cell->filter('a')->attr('href'))->toEndWith('stock/1/history');
});
