<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Conditions\HasUrlConditionRule;
use CraftCms\Cms\Entry\Conditions\PostDateConditionRule;
use CraftCms\Cms\Entry\Conditions\SectionConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\ProjectConfig\ProjectConfig as ProjectConfigPaths;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Conditions\AdminConditionRule;
use CraftCms\Cms\User\Models\User;
use Laravel\Passport\Passport;

beforeEach(function (): void {
    Edition::set(Edition::Pro);
    config()->set('passport.public_key', openssl_pkey_get_details(passportKey())['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:admin'], 'craft-mcp');
    $entryType = EntryType::factory()->create();
    $this->news = Section::factory()->withEntryTypes($entryType)->create(['name' => 'News', 'handle' => 'news']);
    $this->blog = Section::factory()->withEntryTypes($entryType)->create(['name' => 'Blog', 'handle' => 'blog']);
    $this->about = Section::factory()->withEntryTypes($entryType)->create(['name' => 'About', 'handle' => 'about', 'type' => SectionType::Single]);
    Sections::refreshSections();
    EntryTypes::refreshEntryTypes();
    $this->call = fn (string $tool, array $arguments): array => McpRequest::send($this, 'tools/call', [
        'name' => $tool,
        'arguments' => $arguments,
    ])->assertOk()->json('result');
    $this->structured = function (string $tool, array $arguments): array {
        $result = ($this->call)($tool, $arguments);
        expect($result['isError'] ?? false)->toBeFalse(json_encode($result));

        return $result['structuredContent'];
    };
});

it('saves an element type’s whole sidebar and reads it back', function (): void {
    $read = ($this->structured)('element_sources_get', ['elementType' => 'entries']);
    $newsKey = "section:{$this->news->uid}";

    expect($read['multiPage'])->toBeTrue()
        ->and($read['customized'])->toBeFalse()
        ->and(array_column($read['pages'][0]['sources'], 'key'))->toContain('*', $newsKey);

    $document = ($this->structured)('element_sources_save', [
        'elementType' => 'entries',
        'expectedRevision' => $read['revision'],
        'pages' => [
            ['name' => 'Content', 'icon' => 'newspaper', 'sources' => [
                ['type' => 'native', 'key' => '*', 'columns' => ['section', 'postDate'], 'defaultSort' => ['title', 'asc'], 'defaultViewMode' => 'cards'],
                ['type' => 'heading', 'heading' => 'Press'],
                ['type' => 'custom', 'label' => 'Recent news', 'columns' => [], 'condition' => ['conditionRules' => [
                    ['class' => SectionConditionRule::class, 'operator' => 'in', 'values' => [$this->news->uid]],
                    ['class' => HasUrlConditionRule::class, 'value' => true],
                    ['class' => PostDateConditionRule::class, 'rangeType' => 'past30Days', 'periodType' => 'daysAgo', 'periodValue' => null, 'startDate' => null, 'endDate' => null],
                ]], 'userGroups' => []],
            ]],
            ['name' => 'Archive', 'icon' => 'box-archive', 'sources' => [
                ['type' => 'native', 'key' => $newsKey, 'enabled' => false],
                ['type' => 'native', 'key' => "section:{$this->about->uid}"],
            ]],
        ],
    ]);

    [$all, $heading, $custom] = $document['pages'][0]['sources'];
    $rules = $custom['condition']['conditionRules']['rules'];

    expect(array_column($document['pages'], 'name'))->toBe(['Content', 'Archive'])
        ->and(array_column($document['pages'], 'icon'))->toBe(['newspaper', 'box-archive'])
        ->and($all)->toMatchArray(['columns' => ['section', 'postDate'], 'defaultSort' => ['title', 'asc'], 'defaultViewMode' => 'cards'])
        ->and($heading['heading'])->toBe('Press')
        ->and($heading['key'])->toStartWith('heading:')
        ->and($custom['key'])->toStartWith('custom:')
        ->and($custom)->toMatchArray(['label' => 'Recent news', 'columns' => [], 'userGroups' => [], 'sites' => null])
        ->and($custom['condition']['elementType'])->toBe(Entry::class)
        ->and($rules[0]['values'])->toBe([$this->news->uid])
        ->and($rules[0]['uid'])->not->toBeEmpty()
        ->and([$rules[1]['value'], $rules[2]['rangeType']])->toBe([true, 'past30Days'])
        ->and($document['pages'][1]['sources'][0]['enabled'])->toBeFalse()
        ->and($document['customized'])->toBeTrue()
        ->and(array_column($document['addedNativeSources'], 'key'))->toBe(["section:{$this->blog->uid}"])
        ->and(array_column(array_slice($document['pages'][0]['sources'], 3), 'heading'))->toBe([''])
        ->and($document['pages'][0]['sources'][4]['key'])->toBe("section:{$this->blog->uid}");

    $resaved = ($this->structured)('element_sources_save', [
        'elementType' => 'entries',
        'expectedRevision' => $document['revision'],
        'pages' => [['name' => 'Content', 'sources' => [
            ['type' => 'native', 'key' => '*', 'defaultViewMode' => null],
            ['type' => 'custom', 'key' => $custom['key']],
        ]]],
    ]);
    [$all, $resavedCustom] = $resaved['pages'][0]['sources'];

    expect($all['columns'])->toBe(['section', 'postDate'])
        ->and($all['defaultViewMode'])->toBeNull()
        ->and($resavedCustom['condition'])->toBe($custom['condition'])
        ->and($resavedCustom['label'])->toBe('Recent news')
        ->and(array_column($resaved['pages'], 'name'))->toBe(['Content'])
        ->and($resaved['pages'][0]['icon'])->toBe('newspaper');

    $reset = ($this->structured)('element_sources_save', ['elementType' => 'entries', 'reset' => true]);

    expect($reset['customized'])->toBeFalse()
        ->and(array_column($reset['pages'], 'name'))->toBe(['Entries'])
        ->and(ProjectConfig::get(ProjectConfigPaths::PATH_ELEMENT_SOURCES.'.'.Entry::class))->toBeNull();
});

it('refuses invalid sidebars without changing anything', function (array $arguments, string $message): void {
    $revision = ($this->structured)('element_sources_get', ['elementType' => 'entries'])['revision'];
    $arguments = json_decode(strtr(json_encode($arguments), ['{news}' => $this->news->uid]), true);

    $result = ($this->call)('element_sources_save', ['elementType' => 'entries', ...$arguments]);

    expect($result['isError'] ?? false)->toBeTrue()
        ->and($result['content'][0]['text'])->toContain($message)
        ->and(($this->structured)('element_sources_get', ['elementType' => 'entries'])['revision'])->toBe($revision)
        ->and(ProjectConfig::get(ProjectConfigPaths::PATH_ELEMENT_SOURCES.'.'.Entry::class))->toBeNull();
})->with(function (): array {
    $page = fn (array ...$sources): array => ['pages' => [['name' => 'Entries', 'sources' => [['type' => 'native', 'key' => '*'], ...$sources]]]];
    $custom = fn (array $rule): array => ['type' => 'custom', 'label' => 'Probe', 'condition' => ['conditionRules' => [$rule]]];

    return [
        'unknown column' => [$page(['type' => 'native', 'key' => 'section:{news}', 'columns' => ['status', 'nope']]), 'nope'],
        'unknown sort' => [$page(['type' => 'native', 'key' => 'section:{news}', 'defaultSort' => ['structure', 'asc']]), 'structure'],
        'unknown native source' => [$page(['type' => 'native', 'key' => 'section:missing']), 'section:missing'],
        'rule value outside options' => [$page($custom(['class' => SectionConditionRule::class, 'operator' => 'in', 'values' => ['missing-uid']])), 'missing-uid'],
        'unknown rule setting' => [$page($custom(['class' => SectionConditionRule::class, 'operator' => 'in', 'sections' => []])), 'sections'],
        'operator on a rule without operators' => [$page($custom(['class' => HasUrlConditionRule::class, 'operator' => '=', 'value' => true])), 'Omit operator'],
        'invalid operator' => [$page($custom(['class' => SectionConditionRule::class, 'operator' => '=', 'values' => []])), 'operator'],
        'rule of another element type' => [$page($custom(['class' => AdminConditionRule::class, 'value' => true])), 'isn’t available'],
        'pages that only differ in case' => [['pages' => [
            ['name' => 'Entries', 'sources' => [['type' => 'native', 'key' => '*']]],
            ['name' => 'entries', 'sources' => [['type' => 'native', 'key' => 'section:{news}']]],
        ]], 'same page'],
        'sources instead of pages' => [['sources' => [['type' => 'native', 'key' => '*']]], 'use pages'],
        'outdated revision' => [[...$page(), 'expectedRevision' => 'outdated'], 'has changed'],
    ];
});

it('offers the sidebar tools to admins, and only the read tools without admin changes', function (): void {
    $tools = fn (): array => array_column(McpRequest::tools($this), 'name');

    expect($tools())->toContain('element_sources_get', 'element_sources_save', 'condition_schema');

    Cms::config()->allowAdminChanges(false);

    expect($tools())->toContain('element_sources_get', 'condition_schema')->not->toContain('element_sources_save');

    Passport::actingAs(User::factory()->withPermissions(['accessCp'])->create(['admin' => false]), ['mcp:admin'], 'craft-mcp');

    expect($tools())->not->toContain('element_sources_get', 'condition_schema');
});
