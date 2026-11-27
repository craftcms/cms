<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Structure\Models\Structure;
use CraftCms\Cms\Structure\Models\StructureElement;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\View\TemplateManager;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\travelTo;

beforeEach(function () {
    travelTo(Date::parse('2026-01-01 09:30:00'));

    $this->section = Section::factory()->create();

    // The render marker shows whether the output came from the cache
    $this->render = fn (string $expression, string $render) => trim((string) app(TemplateManager::class)->renderString(
        "{% cache globally using key \"entries\" %}{{ render }}:{{ $expression }}{% endcache %}",
        ['sectionId' => $this->section->id, 'render' => $render],
    ));
});

function liveEntryExpressions(): array
{
    return [
        'all' => ['craft.entries.sectionId(sectionId).all()|length', '0', '1'],
        'count' => ['craft.entries.sectionId(sectionId).count()', '0', '1'],
        'exists' => ['craft.entries.sectionId(sectionId).exists() ? "yes" : "no"', 'no', 'yes'],
        'ids' => ['craft.entries.sectionId(sectionId).ids()|length', '0', '1'],
        'max' => ['craft.entries.sectionId(sectionId).max("elements.id") ? "yes" : "no"', 'no', 'yes'],
    ];
}

it('refreshes a cached block when an entry it queries is saved', function (string $expression, string $without, string $with) {
    $entry = EntryModel::factory()->forSection($this->section)->createElement();
    $entry->enabled = false;
    Elements::saveElement($entry);

    expect(($this->render)($expression, 'first'))->toBe("first:$without")
        ->and(($this->render)($expression, 'second'))->toBe("first:$without");

    $entry->enabled = true;
    Elements::saveElement($entry);

    expect(($this->render)($expression, 'third'))->toBe("third:$with");
})->with(liveEntryExpressions());

it('refreshes a cached block when a pending entry it would list goes live', function (string $expression, string $before, string $after) {
    EntryModel::factory()->forSection($this->section)->create(['postDate' => Date::parse('2026-01-01 10:00:30')]);

    expect(($this->render)($expression, 'first'))->toBe("first:$before");

    travelTo(Date::parse('2026-01-01 09:59:59'));
    expect(($this->render)($expression, 'second'))->toBe("first:$before");

    // Entry queries treat entries as live from the start of their post date’s minute
    travelTo(Date::parse('2026-01-01 10:00:00'));
    expect(($this->render)($expression, 'third'))->toBe("third:$after");
})->with([
    ...liveEntryExpressions(),
    'pending' => ['craft.entries.sectionId(sectionId).status("pending").count()', '1', '0'],
    'offset' => ['craft.entries.sectionId(sectionId).offset(1).limit(10).ids()|length', '0', '0'],
]);

it('refreshes a cached block when an entry it would list expires', function (string $expression, string $whenExpired, string $whenLive) {
    EntryModel::factory()->forSection($this->section)->create([
        'postDate' => Date::parse('2026-01-01 09:00:00'),
        'expiryDate' => Date::parse('2026-01-01 10:00:30'),
    ]);

    expect(($this->render)($expression, 'first'))->toBe("first:$whenLive");

    travelTo(Date::parse('2026-01-01 09:59:59'));
    expect(($this->render)($expression, 'second'))->toBe("first:$whenLive");

    // Entry queries treat entries as expired from the start of their expiry date’s minute
    travelTo(Date::parse('2026-01-01 10:00:00'));
    expect(($this->render)($expression, 'third'))->toBe("third:$whenExpired");
})->with([
    ...liveEntryExpressions(),
    'expired' => ['craft.entries.sectionId(sectionId).status("expired").count()', '1', '0'],
]);

it('refreshes a cached block when a pending entry it would list expires', function (string $expression, string $before, string $after) {
    EntryModel::factory()->forSection($this->section)->create([
        'postDate' => Date::parse('2026-01-01 10:00:30'),
        'expiryDate' => Date::parse('2026-01-01 11:00:30'),
    ]);

    expect(($this->render)($expression, 'first'))->toBe("first:$before");

    // Going live doesn’t change these results, so the block stays cached until the entry expires
    travelTo(Date::parse('2026-01-01 10:59:59'));
    expect(($this->render)($expression, 'second'))->toBe("first:$before");

    travelTo(Date::parse('2026-01-01 11:00:00'));
    expect(($this->render)($expression, 'third'))->toBe("third:$after");
})->with([
    'live or pending' => ['craft.entries.sectionId(sectionId).status(["live", "pending"]).count()', '1', '0'],
    'expired' => ['craft.entries.sectionId(sectionId).status("expired").count()', '0', '1'],
]);

it('refreshes a cached block that uses structure params when a pending entry it would list goes live', function (string $expression) {
    $structure = Structure::factory()->create();
    $structure->structureElements()->delete();
    $root = EntryModel::factory()->create(['postDate' => Date::parse('2026-01-01 09:00:00')]);
    $child = EntryModel::factory()->create(['postDate' => Date::parse('2026-01-01 10:00:30')]);
    $rootElement = new StructureElement(['structureId' => $structure->id, 'elementId' => $root->id]);
    $rootElement->makeRoot();
    new StructureElement(['structureId' => $structure->id, 'elementId' => $child->id])->appendTo($rootElement);

    $render = fn (string $marker) => trim((string) app(TemplateManager::class)->renderString(
        "{% cache globally using key \"structure\" %}{{ marker }}:{{ $expression }}{% endcache %}",
        ['structureId' => $structure->id, 'rootId' => $root->id, 'marker' => $marker],
    ));

    expect($render('first'))->toBe('first:0');

    travelTo(Date::parse('2026-01-01 09:59:59'));
    expect($render('second'))->toBe('first:0');

    travelTo(Date::parse('2026-01-01 10:00:00'));
    expect($render('third'))->toBe('third:1');
})->with([
    'descendantOf' => ['craft.entries.structureId(structureId).descendantOf(rootId).count()'],
    'level' => ['craft.entries.structureId(structureId).level(1).count()'],
]);

it('refreshes a cached block that searches when a pending entry it would list goes live', function () {
    $entry = EntryModel::factory()->forSection($this->section)->indexed()->title('Zebra crossing')->create();
    DB::table(Table::ENTRIES)->where('id', $entry->id)->update([
        'postDate' => Query::prepareDateForDb(Date::parse('2026-01-01 10:00:30')),
    ]);
    $expression = 'craft.entries.sectionId(sectionId).search("zebra").count()';

    expect(($this->render)($expression, 'first'))->toBe('first:0');

    travelTo(Date::parse('2026-01-01 09:59:59'));
    expect(($this->render)($expression, 'second'))->toBe('first:0');

    travelTo(Date::parse('2026-01-01 10:00:00'));
    expect(($this->render)($expression, 'third'))->toBe('third:1');
});
