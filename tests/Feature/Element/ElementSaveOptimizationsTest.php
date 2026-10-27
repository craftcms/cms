<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Events\ElementSaved;
use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::factory()->admin()->create());

    $secondSite = Site::factory()->create(['handle' => 'second', 'name' => 'Second Site']);
    Sites::refreshSites();

    $textField = Field::factory()->create([
        'name' => 'Block Text',
        'handle' => 'blockText',
        'type' => PlainText::class,
    ]);
    Fields::refreshFields();

    $blockEntryType = EntryTypeModel::factory()
        ->withField($textField)
        ->create([
            'name' => 'Block',
            'handle' => 'block',
            'hasTitleField' => false,
            'titleFormat' => '{id}',
        ]);
    EntryTypes::refreshEntryTypes();

    $matrixField = Field::factory()->create([
        'name' => 'Blocks',
        'handle' => 'blocks',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$blockEntryType->id]],
    ]);
    Fields::refreshFields();

    $this->pageEntryType = EntryTypeModel::factory()
        ->withField($matrixField)
        ->create([
            'name' => 'Page',
            'handle' => 'page',
            'hasTitleField' => true,
        ]);
    EntryTypes::refreshEntryTypes();

    $this->section = Section::factory()
        ->withEntryTypes($this->pageEntryType)
        ->withSites($secondSite)
        ->create(['type' => SectionType::Channel]);
    Sections::refreshSections();
});

/**
 * @param  list<string>  $texts
 */
function optimizationsOwner(array $texts): EntryElement
{
    $model = EntryModel::factory()
        ->forSection(test()->section)
        ->forEntryType(test()->pageEntryType)
        ->create();

    $owner = optimizationsReloadOwner($model->id);
    $owner->title = 'Owner';
    $owner->setFieldValue('blocks', collect($texts)
        ->mapWithKeys(fn (string $text, int $i) => ['new'.($i + 1) => [
            'type' => 'block',
            'fields' => ['blockText' => $text],
        ]])
        ->all());

    expect(Elements::saveElement($owner))->toBeTrue();

    return optimizationsReloadOwner($owner->id);
}

function optimizationsReloadOwner(int $id): EntryElement
{
    return EntryElement::find()->id($id)->siteId(Sites::getPrimarySite()->id)->status(null)->one();
}

/**
 * @return list<EntryElement>
 */
function optimizationsBlocks(EntryElement $owner, ?int $siteId = null): array
{
    return EntryElement::find()
        ->ownerId($owner->id)
        ->siteId($siteId ?? Sites::getPrimarySite()->id)
        ->status(null)
        ->orderBy('sortOrder')
        ->all();
}

/**
 * @return list<array{int, int}> [elementId, sortOrder] pairs, ordered by sort order
 */
function optimizationsOwnershipRows(EntryElement $owner): array
{
    return DB::table(Table::ELEMENTS_OWNERS)
        ->where('ownerId', $owner->id)
        ->orderBy('sortOrder')
        ->get(['elementId', 'sortOrder'])
        ->map(fn (object $row) => [(int) $row->elementId, (int) $row->sortOrder])
        ->all();
}

/**
 * Returns the number of queries that look up a single element’s type by its ID, executed within the callback.
 *
 * Pass an element ID to only count lookups of that element’s type. A save can also look up other elements’ types,
 * such as a revision’s, when it makes one.
 */
function optimizationsTypeQueryCount(callable $callback, ?int $elementId = null): int
{
    // Compiled by the connection’s own grammar, so it’s quoted and prefixed the way it will run
    $sql = DB::table(Table::ELEMENTS)->select('type')->where('id', 0)->toSql();
    $count = 0;

    DB::listen(function ($query) use ($sql, $elementId, &$count) {
        if (str_starts_with($query->sql, $sql) && ($elementId === null || (int) $query->bindings[0] === $elementId)) {
            $count++;
        }
    });

    $callback();

    // Read now, so lookups after the callback don't count
    return $count;
}

function optimizationsSetElementType(int $elementId, string $type): void
{
    DB::table(Table::ELEMENTS)->where('id', $elementId)->update(['type' => $type]);
}

it('looks up a nested element’s owner type once per save, across all sites', function () {
    $owner = optimizationsOwner(['First', 'Second', 'Third']);
    $owner->setFieldValue('blocks', collect(optimizationsBlocks($owner))
        ->mapWithKeys(fn (EntryElement $block) => [$block->id => [
            'type' => 'block',
            'fields' => ['blockText' => $block->getFieldValue('blockText').' (edited)'],
        ]])
        ->all());

    $count = optimizationsTypeQueryCount(fn () => expect(Elements::saveElement($owner))->toBeTrue(), $owner->id);

    // 3 nested entries × 2 sites used to look up the owner’s type separately
    expect($count)->toBeLessThanOrEqual(1);

    foreach (Sites::getAllSiteIds() as $siteId) {
        expect(collect(optimizationsBlocks($owner, $siteId))->map(fn (EntryElement $block) => $block->getFieldValue('blockText'))->all())
            ->toBe(['First (edited)', 'Second (edited)', 'Third (edited)']);
    }
});

it('doesn’t memoize element types outside of element saves', function () {
    $owner = optimizationsOwner([]);

    expect(optimizationsTypeQueryCount(function () use ($owner) {
        Elements::getElementTypeById($owner->id);
        Elements::getElementTypeById($owner->id);
    }))->toBe(2);
});

it('forgets memoized element types once the outermost save ends', function (string $event, bool $fail) {
    $owner = optimizationsOwner([]);
    $other = optimizationsOwner([]);

    Event::listen($event, function (ElementSaved|ElementSaving $event) use ($owner, $other, $fail) {
        if ($event->element->id !== $owner->id) {
            return;
        }

        expect(Elements::getElementTypeById($other->id))->toBe(EntryElement::class);

        if ($fail) {
            throw new RuntimeException('Save failed');
        }
    });

    if ($fail) {
        expect(fn () => Elements::saveElement($owner))->toThrow(RuntimeException::class, 'Save failed');
    } else {
        expect(Elements::saveElement($owner))->toBeTrue();
    }

    optimizationsSetElementType($other->id, UserElement::class);
    expect(Elements::getElementTypeById($other->id))->toBe(UserElement::class);
})->with([
    'successful save' => [ElementSaved::class, false],
    'failed save' => [ElementSaving::class, true],
]);

it('forgets memoized element types when an element is hard-deleted during a save', function () {
    $owner = optimizationsOwner([]);
    $doomed = optimizationsOwner([]);
    $before = $after = 'not checked';

    Event::listen(ElementSaved::class, function (ElementSaved $event) use ($owner, $doomed, &$before, &$after) {
        // (The owner is saved for each site it propagates to)
        if ($event->element->id !== $owner->id || $before !== 'not checked') {
            return;
        }

        $before = Elements::getElementTypeById($doomed->id);
        Elements::deleteElement($doomed, true);
        $after = Elements::getElementTypeById($doomed->id);
    });

    expect(Elements::saveElement($owner))->toBeTrue()
        ->and($before)->toBe(EntryElement::class)
        ->and($after)->toBeNull();
});

it('keeps nested elements’ ownership rows in sync when they’re resaved directly', function () {
    $owner = optimizationsOwner(['First', 'Second']);
    [$first, $second] = optimizationsBlocks($owner);

    expect(optimizationsOwnershipRows($owner))->toBe([[$first->id, 1], [$second->id, 2]]);

    // Changing the sort order updates the existing row
    $first->setSortOrder(5);
    expect(Elements::saveElement($first))->toBeTrue()
        ->and(optimizationsOwnershipRows($owner))->toBe([[$second->id, 2], [$first->id, 5]]);

    // A missing row gets restored
    DB::table(Table::ELEMENTS_OWNERS)
        ->where('elementId', $second->id)
        ->where('ownerId', $owner->id)
        ->delete();

    $second->setSortOrder(2);
    expect(Elements::saveElement($second))->toBeTrue()
        ->and(optimizationsOwnershipRows($owner))->toBe([[$second->id, 2], [$first->id, 5]]);
});
