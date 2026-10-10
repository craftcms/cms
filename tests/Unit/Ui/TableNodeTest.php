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

it('serializes keyed rows as a JSON list in their supplied order', function (bool $asCollection) {
    $rows = [
        'review' => ['id' => 20, 'name' => 'Editorial review'],
        'draft' => ['id' => 10, 'name' => 'Draft preparation'],
    ];
    $ui = Ui::make([
        Table::make('workflows')
            ->columns([['key' => 'name', 'label' => 'Name']])
            ->rows($asCollection ? collect($rows) : $rows),
    ]);
    $payload = app(UiResolver::class)->resolve($ui, new UiContext);
    $serializedRows = json_decode(json_encode($payload, JSON_THROW_ON_ERROR), flags: JSON_THROW_ON_ERROR)
        ->nodes[0]->props->rows;

    expect($serializedRows)->toBeArray()
        ->and(array_column($serializedRows, 'id'))->toBe([20, 10])
        ->and(array_column($serializedRows, 'name'))->toBe(['Editorial review', 'Draft preparation']);
})->with(['array' => false, 'collection' => true]);

it('resolves footer visibility from pagination and bulk actions unless overridden', function (Closure $configure, bool $visible) {
    $table = Table::make('workflows')
        ->columns([['key' => 'name', 'label' => 'Name']])
        ->rows([['id' => 1, 'name' => 'Editorial review']]);
    $payload = app(UiResolver::class)->resolve(Ui::make([$configure($table)]), new UiContext);

    expect($payload->nodes[0]->props['showFooter'])->toBe($visible);
})->with([
    'static list' => [fn (Table $table) => $table, false],
    'paginated list' => [fn (Table $table) => $table->dataUrl('/workflows/table-data'), true],
    'Inertia-paginated list' => [fn (Table $table) => $table->pagination([
        'total' => 1, 'per_page' => 100, 'current_page' => 1, 'last_page' => 1,
        'next_page_url' => null, 'prev_page_url' => null, 'from' => 1, 'to' => 1,
    ]), true],
    'bulk deletion' => [fn (Table $table) => $table->bulkDeletable('/workflows'), true],
    'bulk actions' => [fn (Table $table) => $table->bulkActions([['label' => 'Approve', 'url' => '/workflows/approve']]), true],
    'status actions' => [fn (Table $table) => $table->statusActions([['label' => 'Enable', 'url' => '/workflows/enable']]), true],
    'move between pages' => [fn (Table $table) => $table->moveToPageUrl('/workflows/move'), true],
    'row deletion' => [fn (Table $table) => $table->deletable(), false],
    'explicitly shown' => [fn (Table $table) => $table->showFooter(), true],
    'explicitly hidden for pagination' => [fn (Table $table) => $table->showFooter(false)->dataUrl('/workflows/table-data'), false],
    'explicitly hidden for bulk actions' => [fn (Table $table) => $table->bulkDeletable('/workflows')->showFooter(false), false],
    'returned to static rows' => [fn (Table $table) => $table->dataUrl('/workflows/table-data')->rows([]), false],
]);
