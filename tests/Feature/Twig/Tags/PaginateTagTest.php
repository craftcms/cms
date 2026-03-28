<?php

declare(strict_types=1);

use craft\db\Query;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Twig\TemplateRenderer;
use CraftCms\Cms\User\Models\User;
use Illuminate\Pagination\Paginator;

beforeEach(function () {
    $this->renderer = app(TemplateRenderer::class);
});

afterEach(function () {
    Paginator::currentPageResolver(fn (string $pageName = 'page') => app('request')->integer($pageName, 1));
});

it('paginates a query with two-variable syntax', function () {
    $result = $this->renderer->renderString(
        '{% paginate query as pageInfo, items %}{{ pageInfo.total }}:{{ items|length }}',
        ['query' => new Query()->from(Table::USERS)],
    );

    // The install creates 1 user
    expect(trim((string) $result))->toBe('1:1');
});

it('paginates with limit', function () {
    User::factory()->count(4)->create();

    $result = $this->renderer->renderString(
        '{% paginate query as pageInfo, items %}total:{{ pageInfo.total }},page:{{ items|length }}',
        ['query' => new Query()->from(Table::USERS)->limit(2)],
    );

    // 5 total users (1 from install + 4 created), limit 2 per page, page 1
    expect(trim((string) $result))->toContain('total:5')
        ->and(trim((string) $result))->toContain('page:2');
});

it('uses paginate as default info variable with single-variable syntax', function () {
    $result = $this->renderer->renderString(
        '{% paginate query as items %}{{ paginate.total }}',
        ['query' => new Query()->from(Table::USERS)],
    );

    expect(trim((string) $result))->toBe('1');
});

it('calculates totalPages correctly', function () {
    User::factory()->count(9)->create();

    $result = $this->renderer->renderString(
        '{% paginate query as pageInfo, items %}{{ pageInfo.totalPages }}',
        ['query' => new Query()->from(Table::USERS)->limit(5)],
    );

    // 10 total users, 5 per page = 2 pages
    expect(trim((string) $result))->toBe('2');
});

it('uses query-string pagination urls', function () {
    User::factory()->count(9)->create();
    swapUrlRequest('/users?p=2');

    $result = $this->renderer->renderString(
        '{% paginate query as pageInfo, items %}{{ pageInfo.prevUrl }}|{{ pageInfo.nextUrl }}|{{ pageInfo.firstUrl }}|{{ pageInfo.lastUrl }}',
        ['query' => new Query()->from(Table::USERS)->limit(3)],
    );

    expect(trim((string) $result))
        ->toContain('https://localhost/users')
        ->toContain('https://localhost/users?p=3')
        ->toContain('https://localhost/users?p=4');
});

it('uses the configured pageTrigger query param in twig pagination', function () {
    User::factory()->count(9)->create();
    Cms::config()->pageTrigger = '?page=';
    swapUrlRequest('/users?page=2');

    $result = $this->renderer->renderString(
        '{% paginate query as pageInfo, items %}{{ pageInfo.nextUrl }}',
        ['query' => new Query()->from(Table::USERS)->limit(3)],
    );

    expect(trim((string) $result))->toContain('https://localhost/users?page=3');
});

it('does not treat old path-style urls as paginated requests', function () {
    User::factory()->count(4)->create();
    swapUrlRequest('/users/p2');

    $result = $this->renderer->renderString(
        '{% paginate query as pageInfo, items %}{{ pageInfo.currentPage }}',
        ['query' => new Query()->from(Table::USERS)->limit(2)],
    );

    expect(trim((string) $result))->toBe('1');
});

it('uses the paginator current page resolver', function () {
    User::factory()->count(4)->create();
    swapUrlRequest('/users?page=1');
    Paginator::currentPageResolver(fn () => 2);

    $result = $this->renderer->renderString(
        '{% paginate query as pageInfo, items %}{{ pageInfo.currentPage }}:{{ items|length }}',
        ['query' => new Query()->from(Table::USERS)->limit(2)],
    );

    expect(trim((string) $result))->toBe('2:2');
});
