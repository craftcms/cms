<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Widgets\QuickPost;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

it('titles itself after its section, and colors its tile after the entry type', function () {
    actingAs(User::find()->one());
    $entryType = EntryType::factory()->create(['name' => 'Work', 'icon' => 'palette', 'color' => 'violet']);
    $section = Section::factory()->withEntryTypes($entryType)->create([
        'name' => 'Collection',
        'type' => SectionType::Channel,
    ]);
    $widget = new QuickPost(['section' => $section->id]);

    expect($widget->getTitle())->toBe('New Collection Entry')
        ->and($widget->props()['entryType'])->toBe(['icon' => 'palette', 'color' => 'violet']);
});
