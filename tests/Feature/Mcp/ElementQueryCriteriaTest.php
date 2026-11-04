<?php

declare(strict_types=1);

use CraftCms\Cms\Edition;
use CraftCms\Cms\Mcp\ElementQueryCriteria;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Models\User;
use Laravel\Passport\Passport;

it('orders by each column and direction without logging a deprecation', function () {
    $query = entryQuery();

    app(ElementQueryCriteria::class)->apply($query, ['orderBy' => 'postDate DESC, title']);
    $query->applyBeforeQueryCallbacks();

    expect($query->getQuery()->orders)
        ->toContain(['column' => 'entries.postDate', 'direction' => 'desc'])
        ->toContain(['column' => 'elements_sites.title', 'direction' => 'asc'])
        ->and(Deprecator::getRequestLogs())->toBe([]);
});

it('declares items for every array in tool input schemas', function () {
    Edition::set(Edition::Pro);
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:admin'], 'craft-mcp');

    // Strict MCP clients, such as VS Code, reject tools whose input schemas declare arrays without items
    $arraysWithoutItems = function (array $schema, string $path) use (&$arraysWithoutItems): array {
        $type = (array) ($schema['type'] ?? []);
        $missing = in_array('array', $type, true) && ! isset($schema['items']) ? [$path] : [];

        foreach ($schema as $key => $value) {
            if (is_array($value)) {
                $missing = [...$missing, ...$arraysWithoutItems($value, "$path/$key")];
            }
        }

        return $missing;
    };

    $tools = McpRequest::tools($this);

    expect($tools)->not->toBeEmpty()
        ->and(collect($tools)->flatMap(fn (array $tool) => $arraysWithoutItems($tool['inputSchema'], $tool['name']))->all())->toBe([]);
});
