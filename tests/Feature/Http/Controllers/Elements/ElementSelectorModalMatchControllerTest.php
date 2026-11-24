<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Conditions\EntryCondition;
use CraftCms\Cms\Entry\Conditions\TypeConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Http\Controllers\Elements\ElementSelectorModalMatchController;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    $this->matches = fn (Entry $entry, string $operator, array $criteria = [], array $payload = []) => postJson(
        action(ElementSelectorModalMatchController::class),
        [
            'context' => ElementSources::CONTEXT_MODAL,
            'elementType' => Entry::class,
            'elementId' => $entry->id,
            'siteId' => $entry->siteId,
            'criteria' => $criteria,
            'selectionCondition' => [
                'class' => EntryCondition::class,
                'elementType' => Entry::class,
                'conditionRules' => [
                    ['class' => TypeConditionRule::class, 'operator' => $operator, 'values' => [$entry->getType()->uid]],
                ],
            ],
            ...$payload,
        ],
    )->assertOk()->json('matches');
});

it('tells whether an element meets the selection condition', function () {
    $entry = EntryModel::factory()->createElement();

    expect(($this->matches)($entry, 'in'))->toBeTrue()
        ->and(($this->matches)($entry, 'ni'))->toBeFalse();
});

it('counts disabled elements, which selector modals list', function () {
    $entry = EntryModel::factory()->createElement();
    DB::table(Table::ELEMENTS)->where('id', $entry->id)->update(['enabled' => false]);

    expect(($this->matches)($entry, 'in'))->toBeTrue();
});

it('counts saved unpublished drafts, which selector modals list', function () {
    $entry = EntryModel::factory()->createElement();
    expect(app(Drafts::class)->saveElementAsDraft($entry, User::findOne()->id))->toBeTrue();

    expect(($this->matches)($entry, 'in'))->toBeTrue();
});

it('holds elements to the selector’s criteria too', function () {
    $entry = EntryModel::factory()->createElement();

    expect(($this->matches)($entry, 'in', ['slug' => 'something-else']))->toBeFalse()
        ->and(($this->matches)($entry, 'in', ['id' => ['not', $entry->id]]))->toBeFalse();
});

it('holds elements to the selector’s sources', function () {
    $allowed = Section::factory()->withEntryTypes($entryType = EntryType::factory()->create())->create();
    $other = Section::factory()->withEntryTypes($otherType = EntryType::factory()->create())->create();
    Sections::refreshSections();
    EntryTypes::refreshEntryTypes();

    $entry = EntryModel::factory()->forSection($allowed)->forEntryType($entryType)->createElement();
    $elsewhere = EntryModel::factory()->forSection($other)->forEntryType($otherType)->createElement();
    $sources = ['sources' => ["section:$allowed->uid"]];

    expect(($this->matches)($entry, 'in', payload: $sources))->toBeTrue()
        ->and(($this->matches)($elsewhere, 'in', payload: $sources))->toBeFalse();
});
