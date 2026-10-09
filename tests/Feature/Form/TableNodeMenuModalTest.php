<?php

declare(strict_types=1);

use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Table;
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

it('passes menu items that open a modal Form through to the table', function () {
    $props = Table::make('stock')
        ->columns([['key' => 'stock', 'label' => 'Stock']])
        ->rows(menuModalRows())
        ->props();

    expect($props['rows'][0]['stock']['items'][1])->toBe([
        'label' => 'Adjust',
        'modalUrl' => 'stock/adjust-modal',
        'actionUrl' => 'stock/adjust',
        'params' => ['id' => 1],
    ]);
});

it('renders menu items that open a modal Form as plain labels without JavaScript', function () {
    $payload = app(FormResolver::class)->resolve(Form::make([
        Table::make('stock')
            ->columns([['key' => 'stock', 'label' => 'Stock']])
            ->rows(menuModalRows()),
    ]), new FormContext);

    $cell = new Crawler(app(FormHtmlRenderer::class)->render($payload))->filter('tbody td');

    expect($cell->text())->toBe('History, Adjust')
        ->and($cell->filter('a'))->toHaveCount(1)
        ->and($cell->filter('a')->attr('href'))->toEndWith('stock/1/history');
});
