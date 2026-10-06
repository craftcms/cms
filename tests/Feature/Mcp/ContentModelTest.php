<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Mcp\Capabilities\ContentModel;
use CraftCms\Cms\Mcp\ContentModel\Check;
use CraftCms\Cms\Mcp\ContentModel\CheckRegistry;
use CraftCms\Cms\Section\Models\Section;
use Mcp\Schema\Content\TextContent;

it('reports and limits content-model findings', function () {
    $section = Section::factory()->create([
        'name' => 'Empty MCP Section',
        'handle' => 'emptyMcpSection',
    ]);
    Section::factory()->create([
        'name' => 'Another Empty MCP Section',
        'handle' => 'anotherEmptyMcpSection',
    ]);

    $contentModel = app(ContentModel::class);
    $result = $contentModel->audit(['empty-sections']);
    $limited = $contentModel->audit(['empty-sections'], limit: 1);

    expect($result['summary']['empty-sections'])->toBeGreaterThanOrEqual(2)
        ->and(collect($result['findings']['empty-sections'])->pluck('id'))->toContain($section->id)
        ->and($limited['findings']['empty-sections'])->toHaveCount(1)
        ->and($limited['summary']['empty-sections'])->toBe($result['summary']['empty-sections']);
});

it('groups numbered Unicode field names without conflating distinct letters', function () {
    Field::factory()->create(['name' => 'Ångström', 'handle' => 'angstrom']);
    Field::factory()->create(['name' => 'ÅNGSTRÖM 2', 'handle' => 'angstromCopy']);
    Field::factory()->create(['name' => 'Öngström', 'handle' => 'ongstrom']);
    app(Fields::class)->refreshFields();

    $result = app(ContentModel::class)->audit(['duplicate-field-candidates']);
    $groups = collect($result['findings']['duplicate-field-candidates'])
        ->map(fn (array $group): array => collect($group['fields'])->pluck('handle')->sort()->values()->all());

    expect($groups)->toContain(['angstrom', 'angstromCopy'])
        ->not->toContain(['angstrom', 'angstromCopy', 'ongstrom']);
});

it('focuses its audit prompt on matching checks', function () {
    $messages = app(ContentModel::class)->auditPrompt('fields');

    expect($messages)->toHaveCount(1)
        ->and($messages[0]->content)->toBeInstanceOf(TextContent::class)
        ->and($messages[0]->content->text)->toContain('"checks":["unused-fields","duplicate-field-candidates"]');
});

it('runs checks registered by plugins', function () {
    app(CheckRegistry::class)->register(TestContentModelCheck::class);

    $result = app(ContentModel::class)->audit([TestContentModelCheck::id()], limit: 1);

    expect($result)->toMatchArray([
        'checks' => ['test-plugin-check'],
        'summary' => ['test-plugin-check' => 2],
        'findings' => ['test-plugin-check' => [['source' => 'plugin']]],
    ]);
});

class TestContentModelCheck implements Check
{
    public static function id(): string
    {
        return 'test-plugin-check';
    }

    public function run(int $limit): array
    {
        return [
            'count' => 2,
            'findings' => array_slice([
                ['source' => 'plugin'],
                ['source' => 'plugin'],
            ], 0, $limit),
        ];
    }
}
