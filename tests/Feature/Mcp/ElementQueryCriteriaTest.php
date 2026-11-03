<?php

declare(strict_types=1);

use CraftCms\Cms\Mcp\ElementQueryCriteria;
use CraftCms\Cms\Support\Facades\Deprecator;

it('orders by each column and direction without logging a deprecation', function () {
    $query = entryQuery();

    app(ElementQueryCriteria::class)->apply($query, ['orderBy' => 'postDate DESC, title']);
    $query->applyBeforeQueryCallbacks();

    expect($query->getQuery()->orders)
        ->toContain(['column' => 'entries.postDate', 'direction' => 'desc'])
        ->toContain(['column' => 'elements_sites.title', 'direction' => 'asc'])
        ->and(Deprecator::getRequestLogs())->toBe([]);
});
