<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Data\EagerLoadPlan;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Data\LinkData;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\LinkTypes;
use CraftCms\Cms\Field\LinkTypes\BaseElementLinkType;
use CraftCms\Cms\Field\LinkTypes\Entry as EntryLinkType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use Illuminate\Support\Facades\DB;

/**
 * @param  list<array<string, string>>  $values
 * @return list<int>
 */
function linkOwnerIds(array $values): array
{
    $first = EntryModel::factory()
        ->withField('link', Link::class, ['types' => ['entry', 'url']], value: $values[0])
        ->createElementWithFields()
        ->element;
    $ids = [$first->id];

    foreach (array_slice($values, 1) as $value) {
        $owner = Elements::duplicateElement($first);
        $owner->setFieldValue('link', $value);
        Elements::saveElement($owner);
        $ids[] = $owner->id;
    }

    BaseElementLinkType::forgetFetchedElements();

    return $ids;
}

function entryLink(Entry $target): array
{
    return ['type' => 'entry', 'value' => "{entry:$target->id@$target->siteId:url}"];
}

it('loads the linked elements of all queried elements together', function () {
    [$live, $disabled, $deleted] = [EntryModel::factory()->createElement(), EntryModel::factory()->createElement(), EntryModel::factory()->createElement()];
    $disabled->enabled = false;
    Elements::saveElement($disabled);
    $ids = linkOwnerIds([entryLink($live), entryLink($disabled), ['type' => 'url', 'value' => 'https://example.com'], entryLink($deleted)]);
    Elements::deleteElement($deleted, true);
    $links = Entry::find()->id($ids)->fixedOrder()->all();

    expect($links[0]->link->getElement()->id)->toBe($live->id);

    DB::enableQueryLog();

    expect($links[1]->link->getElement()->id)->toBe($disabled->id)
        ->and($links[1]->link->getUrl(anyStatus: false))->toBe('')
        ->and((string) $links[2]->link)->toBe('https://example.com')
        ->and($links[3]->link->getElement())->toBeNull()
        ->and($links[3]->link->getUrl())->toBe('')
        ->and($links[0]->link->getLabel())->toBe($live->title)
        ->and(DB::getQueryLog())->toBe([]);
});

it('eager-loads linked elements and their nested elements with()', function () {
    $targets = [EntryModel::factory()->createElement(), EntryModel::factory()->createElement()];
    $ids = linkOwnerIds(array_map(entryLink(...), $targets));
    $owners = Entry::find()->id($ids)->fixedOrder()->with(['link.authors'])->all();

    DB::enableQueryLog();

    expect($owners[0]->link)->toBeInstanceOf(LinkData::class)
        ->and(array_map(fn (Entry $owner) => $owner->link->getElement()->id, $owners))->toBe(array_column($targets, 'id'))
        ->and(array_map(fn (Entry $owner) => $owner->link->getUrl(), $owners))->toBe(array_map(fn (Entry $target) => $target->getUrl() ?? '', $targets))
        ->and($owners[1]->link->getElement()->getAuthors())->toBe([])
        ->and(DB::getQueryLog())->toBe([]);
});

it('eager-loads links to specific and current sites together', function () {
    $targets = [EntryModel::factory()->createElement(), EntryModel::factory()->createElement()];
    $ids = linkOwnerIds([entryLink($targets[0]), ['type' => 'entry', 'value' => "{entry:{$targets[1]->id}:url}"]]);
    $owners = Entry::find()->id($ids)->fixedOrder()->with(['link'])->all();

    DB::enableQueryLog();

    expect(array_map(fn (Entry $owner) => $owner->link->getElement()?->id, $owners))->toBe(array_column($targets, 'id'))
        ->and(DB::getQueryLog())->toBe([]);
});

it('only loads the links of the same field together', function () {
    $targets = [EntryModel::factory()->createElement(), EntryModel::factory()->createElement()];
    $ids = [
        ...linkOwnerIds([entryLink($targets[0]), entryLink($targets[0])]),
        ...linkOwnerIds([entryLink($targets[1]), entryLink($targets[1])]),
    ];
    $owners = Entry::find()->id($ids)->fixedOrder()->all();

    expect($owners[0]->link->getElement()->id)->toBe($targets[0]->id)
        ->and($owners[2]->link->getElement()?->id)->toBe($targets[1]->id);
});

it('leaves links alone when eager-loading plans could load other elements', function () {
    $target = EntryModel::factory()->createElement();
    $ids = linkOwnerIds([entryLink($target), entryLink($target)]);
    $filtered = Entry::find()->id($ids)->with([['link', ['slug' => 'not-a-target']]])->all();
    $aliased = Entry::find()->id($ids)->with(['link as linkedEntry'])->all();

    expect($filtered[0]->link->getElement()->id)->toBe($target->id)
        ->and($aliased[0]->getEagerLoadedElements('linkedEntry'))->toBeNull()
        ->and($aliased[0]->link)->toBeInstanceOf(LinkData::class);
});

it('eager-loads only links to a specific site for plans limited to some sites', function () {
    $site = Site::factory()->create();
    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->withSites($site)->create();
    $targets = [
        EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement(),
        EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement(),
    ];
    array_walk($targets, Elements::saveElement(...));
    $ids = linkOwnerIds([
        ['type' => 'entry', 'value' => "{entry:{$targets[0]->id}@$site->id:url}"],
        ['type' => 'entry', 'value' => "{entry:{$targets[1]->id}:url}"],
    ]);
    $owners = Entry::find()->id($ids)->fixedOrder()->with([new EagerLoadPlan(handle: 'link', alias: 'link', siteIds: [$site->id])])->all();

    DB::enableQueryLog();

    expect($owners[0]->link->getElement()->id)->toBe($targets[0]->id)
        ->and(DB::getQueryLog())->toBe([])
        ->and($owners[1]->link->getElement()->siteId)->toBe($targets[1]->siteId);
});

it('serializes links that haven’t been read', function () {
    $target = EntryModel::factory()->createElement();
    $ids = linkOwnerIds([entryLink($target), entryLink($target)]);
    $link = Entry::find()->id($ids)->fixedOrder()->all()[0]->link;

    expect(unserialize(serialize($link))->getElement()->id)->toBe($target->id);
});

it('leaves link types with their own element query to them', function () {
    app(LinkTypes::class)->register(SlugScopedEntryLinkType::class);
    $target = EntryModel::factory()->createElement();
    $owner = EntryModel::factory()
        ->withField('link', Link::class, ['types' => ['slugScopedEntry']], value: ['type' => 'slugScopedEntry', 'value' => "{entry:$target->id@$target->siteId:url}"])
        ->createElementWithFields()
        ->element;
    BaseElementLinkType::forgetFetchedElements();

    expect(Entry::find()->id($owner->id)->with(['link'])->one()->link->getElement())->toBeNull();

    app(LinkTypes::class)->remove(SlugScopedEntryLinkType::class);
});

class SlugScopedEntryLinkType extends EntryLinkType
{
    public static function id(): string
    {
        return 'slugScopedEntry';
    }

    protected static function referencedElementsQuery(array $ids, ?int $siteId): ElementQueryInterface
    {
        return parent::referencedElementsQuery($ids, $siteId)->slug('not-a-target');
    }
}
