<?php

declare(strict_types=1);

use craft\db\Query;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Twig\TemplateRenderer;
use CraftCms\Cms\User\Models\User;

beforeEach(function () {
    $this->renderer = app(TemplateRenderer::class);
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
