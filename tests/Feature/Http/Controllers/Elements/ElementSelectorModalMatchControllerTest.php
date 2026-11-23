<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Conditions\EntryCondition;
use CraftCms\Cms\Entry\Conditions\TypeConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Controllers\Elements\ElementSelectorModalMatchController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    $this->matches = fn (Entry $entry, string $operator, array $criteria = []) => postJson(
        action(ElementSelectorModalMatchController::class),
        [
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

it('holds elements to the selector’s criteria too', function () {
    $entry = EntryModel::factory()->createElement();

    expect(($this->matches)($entry, 'in', ['slug' => 'something-else']))->toBeFalse();
});
