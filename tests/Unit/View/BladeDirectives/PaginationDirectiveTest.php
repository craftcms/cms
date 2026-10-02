<?php

declare(strict_types=1);

use CraftCms\Cms\Blade\BladeRenderer;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

beforeEach(function () {
    TemplateMode::set(TemplateMode::Site);
    app()->forgetScopedInstances();

    $this->renderer = app(BladeRenderer::class);
});

it('assigns pagination variables from a query', function () {
    $query = new class implements Builder
    {
        public function paginate(?int $perPage = null, array|string $columns = ['*'], string $pageName = 'page', ?int $page = null): LengthAwarePaginator
        {
            return new LengthAwarePaginator(
                ['first', 'second'],
                5,
                2,
                1,
                ['path' => '/entries', 'pageName' => $pageName],
            );
        }
    };

    $output = $this->renderer->renderString('@craftPaginate($query){{ $paginate->total }}:{{ count($paginatedItems) }}', [
        'query' => $query,
    ]);

    expect($output)->toBe('5:2');
});
