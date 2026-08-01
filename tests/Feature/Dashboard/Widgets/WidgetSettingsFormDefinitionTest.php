<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Widgets\Feed;
use CraftCms\Cms\Dashboard\Widgets\MyDrafts;
use CraftCms\Cms\Dashboard\Widgets\NewUsers;
use CraftCms\Cms\Dashboard\Widgets\QuickPost;
use CraftCms\Cms\Dashboard\Widgets\RecentEntries;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\UserGroup;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::find()->one());
});

it('projects every native widget setting in its authored order', function () {
    UserGroup::factory()->create(['name' => 'Editors']);
    $article = EntryType::factory()->create(['name' => 'Article']);
    $news = Section::factory()->withEntryTypes($article)->create([
        'name' => 'News',
        'type' => SectionType::Channel,
    ]);

    $definitions = [
        Feed::class => ['url', 'title', 'limit'],
        MyDrafts::class => ['limit'],
        NewUsers::class => ['dateRange', 'userGroupId'],
        QuickPost::class => ['section', "sections.{$news->id}.entryType", 'customTitle'],
        RecentEntries::class => ['section', 'limit'],
    ];

    foreach ($definitions as $widgetClass => $expectedNames) {
        $definition = new $widgetClass()->getSettingsFormDefinition(false)?->toArray();

        expect(widgetSettingNames($definition))->toBe($expectedNames);
    }
});

it('projects widget settings as read only', function () {
    $definition = new Feed()->getSettingsFormDefinition(true)?->toArray();

    expect($definition['elements'] ?? [])->toHaveCount(3);

    foreach ($definition['elements'] ?? [] as $field) {
        expect($field['props']['readOnly'] ?? false)->toBeTrue();
    }
});

it('projects quick post entry types with reversible section visibility', function () {
    $article = EntryType::factory()->create(['name' => 'Article']);
    $news = Section::factory()->withEntryTypes($article)->create([
        'name' => 'News',
        'type' => SectionType::Channel,
    ]);
    $definition = new QuickPost(['section' => $news->id])->getSettingsFormDefinition(false)?->toArray();
    $fields = collect($definition['elements'] ?? [])->keyBy(
        fn (array $field): string => $field['children'][0]['name'],
    );

    expect($fields["sections.{$news->id}.entryType"]['visibleWhen'])->toBe([
        'name' => 'section',
        'operator' => 'equals',
        'value' => $news->id,
    ]);
});

it('projects settings for an unsaved quick post', function () {
    $article = EntryType::factory()->create(['name' => 'Article']);
    $news = Section::factory()->withEntryTypes($article)->create([
        'name' => 'News',
        'type' => SectionType::Channel,
    ]);
    $widget = new QuickPost;

    expect($widget->getSettingsFormValues())->toMatchArray([
        'section' => null,
        'entryType' => null,
        'sections' => [
            $news->id => ['entryType' => $article->id],
        ],
    ]);
});

it('omits an unavailable quick post instead of showing an empty settings form', function () {
    Sections::shouldReceive('getAllSections')->twice()->andReturn(collect());

    expect(QuickPost::isSelectable())->toBeFalse()
        ->and(new QuickPost()->getSettingsFormDefinition(false))->toBeNull();
});

function widgetSettingNames(?array $definition): array
{
    return array_map(
        fn (array $field): string => $field['children'][0]['name'],
        $definition['elements'] ?? [],
    );
}
