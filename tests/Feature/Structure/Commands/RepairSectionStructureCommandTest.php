<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Models\SectionSiteSettings;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Structure\Events\StructureElementInserted;
use CraftCms\Cms\Structure\Models\Structure;
use CraftCms\Cms\Structure\Models\StructureElement;
use CraftCms\Cms\Support\Facades\Sections;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->structure = Structure::factory()->create();
    $this->section = Section::factory()->create([
        'type' => SectionType::Structure,
        'structureId' => $this->structure->id,
    ]);
    $root = $this->structure->structureElements()->first();
    $this->entries = collect();

    foreach (range(1, 3) as $number) {
        $entry = Entry::factory()->forSection($this->section)->title("Repair entry $number")->create();
        $node = new StructureElement([
            'structureId' => $this->structure->id,
            'elementId' => $entry->id,
        ]);
        $node->appendTo($root);
        $this->entries->push($entry);
    }
});

it('previews the same repaired hierarchy that it persists', function (array $levels, ?int $maxLevels, array $expectedLevels, array $expectedOutput, ?string $scenario = null) {
    if (DB::getDriverName() === 'mysql' && in_array(-1, $levels, true)) {
        $this->markTestSkipped('MySQL stores structure levels as unsigned integers.');
    }

    $this->structure->update(['maxLevels' => $maxLevels]);

    if ($scenario === 'sites') {
        $site = Site::factory()->create();
        $this->section->update(['propagationMethod' => PropagationMethod::None]);
        SectionSiteSettings::factory()->create(['sectionId' => $this->section->id, 'siteId' => $site->id]);
        DB::table(Table::ELEMENTS_SITES)->whereIn('elementId', $this->entries->skip(1)->pluck('id'))->update(['siteId' => $site->id]);
        Sections::refreshSections();
    }

    if ($scenario === 'provisional') {
        $canonicalId = $this->entries[0]->id;
        $draftId = DB::table(Table::DRAFTS)->insertGetId(['canonicalId' => $canonicalId, 'provisional' => true, 'name' => 'Test draft']);
        $this->entries[1]->element->update(['draftId' => $draftId, 'canonicalId' => $canonicalId]);
    }

    foreach ($levels as $index => $level) {
        $node = StructureElement::query()->where('elementId', $this->entries[$index]->id);

        if ($level === null) {
            $node->delete();
        } else {
            $node->update(['level' => $level]);
        }
    }

    $original = $this->structure->structureElements()->get()->toArray();
    $arguments = ['handle' => $this->section->handle, '--no-interaction' => true];

    expect(Artisan::call('craft:utils:repair:section-structure', [...$arguments, '--dry-run' => true]))->toBe(0);
    $preview = collect(preg_split('/\R/', Artisan::output()))->filter(fn (string $line) => str_contains($line, 'Repair entry'))->values()->all();

    expect($this->structure->structureElements()->get()->toArray())->toBe($original);
    expect($preview)->toBe($expectedOutput);

    expect(Artisan::call('craft:utils:repair:section-structure', $arguments))->toBe(0);
    $live = collect(preg_split('/\R/', Artisan::output()))->filter(fn (string $line) => str_contains($line, 'Repair entry'))->values()->all();

    expect($live)->toBe($preview);
    expect($this->entries->map(fn (Entry $entry) => entryQuery()->id($entry->id)->site('*')->drafts(null)->provisionalDrafts(null)->structureId($this->structure->id)->one()?->level)->all())
        ->toBe($expectedLevels);
})->with([
    'jumping levels' => [[1, 4, 5], null, [1, 2, 3], [
        '✔ Repair entry 1',
        '  ∟ ✖ Repair entry 2 - had unexpected level (4)',
        '      ∟ ✖ Repair entry 3 - had unexpected level (5)',
    ]],
    'missing node' => [[1, 2, null], null, [1, 2, 1], [
        '✔ Repair entry 1',
        '  ∟ ✔ Repair entry 2',
        '✖ Repair entry 3 - was missing from structure',
    ]],
    'zero level' => [[1, 0, 3], null, [1, 1, 2], [
        '✔ Repair entry 1',
        '✖ Repair entry 2 - was missing from structure',
        '  ∟ ✖ Repair entry 3 - had unexpected level (3)',
    ]],
    'negative level' => [[1, -1, 3], null, [1, 1, 2], [
        '✔ Repair entry 1',
        '✖ Repair entry 2 - had unexpected level (-1)',
        '  ∟ ✖ Repair entry 3 - had unexpected level (3)',
    ]],
    'incompatible sites' => [[1, 2, 3], null, [1, 1, 2], [
        '✔ Repair entry 1',
        '✖ Repair entry 2 - no supported sites in common with parent',
        '  ∟ ✖ Repair entry 3 - had unexpected level (3)',
    ], 'sites'],
    'removed provisional draft before a child' => [[1, 1, 2], null, [1, null, 2], [
        '✔ Repair entry 1',
        '* Repair entry 2 (provisional draft) - removed',
        '  ∟ ✔ Repair entry 3',
    ], 'provisional'],
    'retained provisional draft' => [[1, 2, 3], null, [1, 2, 3], [
        '✔ Repair entry 1',
        '  ∟ ✔ Repair entry 2 (provisional draft)',
        '      ∟ ✔ Repair entry 3',
    ], 'provisional'],
    'maximum depth' => [[1, 2, 4], 2, [1, 2, 2], [
        '✔ Repair entry 1',
        '  ∟ ✔ Repair entry 2',
        '  ∟ ✖ Repair entry 3 - exceeded the max level (2)',
    ]],
]);

it('rolls back the repair when an insertion fails', function (bool $throws) {
    $original = $this->structure->structureElements()->get()->toArray();
    $transactionLevel = DB::transactionLevel();
    $failedElementId = $this->entries[1]->id;

    Event::listen(StructureElementInserted::class, function (StructureElementInserted $event) use ($failedElementId, $throws) {
        if ($event->element->id !== $failedElementId) {
            return;
        }

        if ($throws) {
            throw new RuntimeException('Insertion failed.');
        }

        $event->isValid = false;
    });

    expect(fn () => Artisan::call('craft:utils:repair:section-structure', [
        'handle' => $this->section->handle,
        '--no-interaction' => true,
    ]))->toThrow(RuntimeException::class, $throws
        ? 'Insertion failed.'
        : "Could not place element $failedElementId in structure {$this->structure->id}.");

    expect($this->structure->structureElements()->get()->toArray())->toBe($original);
    expect(DB::transactionLevel())->toBe($transactionLevel);
    expect(Artisan::output())->not->toContain('✔ Repair entry 2');
})->with(['exception' => true, 'veto' => false]);
