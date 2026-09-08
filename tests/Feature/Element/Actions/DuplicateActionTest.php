<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Actions\Duplicate;
use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Controllers\Elements\PerformElementActionController;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Structure\Models\Structure;
use CraftCms\Cms\Support\Facades\Structures;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

it('duplicates entries via the Laravel perform-action route', function () {
    $entry = EntryModel::factory()->createElement();
    $beforeCount = DB::table(Table::ELEMENTS)->count();

    postJson(action(PerformElementActionController::class), [
        'context' => 'index',
        'source' => '*',
        'viewState' => [
            'mode' => 'table',
            'static' => false,
        ],
        'elementType' => Entry::class,
        'elementAction' => Duplicate::class,
        'elementIds' => [$entry->id],
    ])->assertOk();

    expect(DB::table(Table::ELEMENTS)->count())->toBeGreaterThan($beforeCount);
});

it('deep duplicates descendants once in their original hierarchy', function (bool $asDrafts, bool $overlap, ?string $skip = null) {
    $structure = Structure::factory()->create();
    $section = Section::factory()->create([
        'type' => SectionType::Structure,
        'structureId' => $structure->id,
    ]);
    $entries = collect(['Root', 'Child', 'Grandchild', 'Sibling'])->map(
        fn (string $title) => EntryModel::factory()->forSection($section)->title($title)->createElement(),
    );
    [$root, $child, $grandchild, $sibling] = $entries->all();
    Structures::appendToRoot($structure->id, $root);
    Structures::append($structure->id, $child, $root);
    Structures::append($structure->id, $grandchild, $child);
    Structures::append($structure->id, $sibling, $root);

    if ($skip === 'unauthorized') {
        Gate::before(fn ($user, $ability, $arguments) => $ability === 'duplicate' && $arguments[0]->id === $sibling->id ? false : null);
    } elseif ($skip === 'invalid') {
        Event::listen(ElementSaving::class, function ($event) {
            if ($event->isNew && $event->element->title === 'Sibling') {
                $event->isValid = false;
            }
        });
    }

    $action = new Duplicate(['deep' => true, 'asDrafts' => $asDrafts]);
    expect($action->performAction(Entry::find()->id($overlap ? $entries->pluck('id')->all() : [$root->id])))->toBeTrue();

    $duplicates = Entry::find()->sectionId($section->id)->drafts($asDrafts)->status(null)
        ->id(['not', ...$entries->pluck('id')])->orderBy('structureelements.lft')->all();
    expect(array_column($duplicates, 'title'))->toBe($skip ? ['Root', 'Child', 'Grandchild'] : ['Root', 'Child', 'Grandchild', 'Sibling'])
        ->and(array_column($duplicates, 'level'))->toBe($skip ? [1, 2, 3] : [1, 2, 3, 2])
        ->and($duplicates[0]->lft)->toBe(Entry::find()->id($root->id)->one()->rgt + 1)
        ->and($action->getMessage())->toBe($skip === 'invalid'
            ? 'Could not duplicate all elements due to validation errors.'
            : 'Elements duplicated.');
})->with([[false, false], [false, true], [true, true], [false, false, 'unauthorized'], [false, false, 'invalid']]);
