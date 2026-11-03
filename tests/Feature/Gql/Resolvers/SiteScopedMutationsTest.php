<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\Resolvers\Mutations\Asset as AssetMutationResolver;
use CraftCms\Cms\Gql\Resolvers\Mutations\Entry as EntryMutationResolver;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->siteA = Site::first();
    $this->siteB = Site::factory()->create();
    Sites::refreshSites();

    $entryType = EntryType::factory()->create();
    $this->section = Section::factory()
        ->withEntryTypes($entryType)
        ->withSites($this->siteA, $this->siteB)
        ->create();
    EntryTypes::refreshEntryTypes();

    $this->entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($entryType)
        ->createElement();

    // Make sure the entry exists in both sites
    DB::table(Table::ELEMENTS_SITES)->insertOrIgnore([
        'elementId' => $this->entry->id,
        'siteId' => $this->siteB->id,
        'uid' => (string) str()->uuid(),
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);

    $this->setSchema = function (array $sites, string $action, array $extraScope = [], ?string $resource = null): void {
        $resource ??= "sections.{$this->section->uid}";

        app(Gql::class)->flushCaches();
        app(Gql::class)->setActiveSchema(new GqlSchema([
            'scope' => [
                "$resource:read",
                "$resource:$action",
                ...array_map(fn (Site $site) => "sites.$site->uid:read", $sites),
                ...$extraScope,
            ],
        ]));
    };
});

function siteScopedElementSiteCount(int $elementId): int
{
    return DB::table(Table::ELEMENTS_SITES)
        ->join(Table::ELEMENTS, 'elements.id', '=', 'elements_sites.elementId')
        ->where('elementId', $elementId)
        ->whereNull('elements.dateDeleted')
        ->count();
}

it('refuses to delete an entry that exists in sites outside the schema', function (bool $withSiteId) {
    ($this->setSchema)([$this->siteA], 'delete');

    $arguments = ['id' => $this->entry->id];
    if ($withSiteId) {
        $arguments['siteId'] = $this->siteA->id;
    }

    expect(fn () => (new EntryMutationResolver)->deleteEntry(null, $arguments, null, Mockery::mock(ResolveInfo::class)))
        ->toThrow('Unable to perform the action.')
        ->and(siteScopedElementSiteCount($this->entry->id))->toBe(2);

    ($this->setSchema)([$this->siteA, $this->siteB], 'delete');

    expect((new EntryMutationResolver)->deleteEntry(null, $arguments, null, Mockery::mock(ResolveInfo::class)))->toBeTrue()
        ->and(siteScopedElementSiteCount($this->entry->id))->toBe(0);
})->with([
    'without siteId' => [false],
    'with siteId' => [true],
]);

it('refuses to delete an asset that exists in sites outside the schema', function () {
    config()->set('filesystems.disks.site-scoped-mutations', ['driver' => 'local', 'root' => storage_path('framework/testing/site-scoped-mutations')]);
    Storage::fake('site-scoped-mutations');

    $asset = AssetModel::factory()->createElement([
        'volumeId' => Volume::factory()->state([
            'fs' => 'site-scoped-mutations',
            'propagationMethod' => PropagationMethod::All,
        ]),
    ]);
    expect(Elements::saveElement($asset))->toBeTrue();
    $resource = "volumes.{$asset->getVolume()->uid}";

    expect(siteScopedElementSiteCount($asset->id))->toBe(2);

    ($this->setSchema)([$this->siteA], 'delete', resource: $resource);

    expect(fn () => (new AssetMutationResolver)->deleteAsset(null, ['id' => $asset->id], null, Mockery::mock(ResolveInfo::class)))
        ->toThrow('Unable to perform the action.')
        ->and(siteScopedElementSiteCount($asset->id))->toBe(2);

    ($this->setSchema)([$this->siteA, $this->siteB], 'delete', resource: $resource);

    expect((new AssetMutationResolver)->deleteAsset(null, ['id' => $asset->id], null, Mockery::mock(ResolveInfo::class)))->toBeTrue()
        ->and(siteScopedElementSiteCount($asset->id))->toBe(0);
});

it('refuses to publish a draft whose entry exists in sites outside the schema', function () {
    $draft = app(Drafts::class)->createDraft($this->entry, User::findOne()->id);

    ($this->setSchema)([$this->siteB], 'save', ['elements.drafts:read']);

    expect(fn () => (new EntryMutationResolver)->publishDraft(null, ['id' => $draft->draftId], null, Mockery::mock(ResolveInfo::class)))
        ->toThrow('Unable to perform the action.')
        ->and(EntryElement::find()->draftId($draft->draftId)->status(null)->site('*')->exists())->toBeTrue();

    ($this->setSchema)([$this->siteA, $this->siteB], 'save', ['elements.drafts:read']);

    expect((new EntryMutationResolver)->publishDraft(null, ['id' => $draft->draftId], null, Mockery::mock(ResolveInfo::class)))
        ->toBe($this->entry->id);
});

it('refuses to create a draft for an entry outside the schema sites', function () {
    $siteC = Site::factory()->create();
    Sites::refreshSites();

    ($this->setSchema)([$siteC], 'save');

    expect(fn () => (new EntryMutationResolver)->createDraft(null, ['id' => $this->entry->id], null, Mockery::mock(ResolveInfo::class)))
        ->toThrow('Unable to perform the action.');

    ($this->setSchema)([$this->siteB], 'save');

    expect((new EntryMutationResolver)->createDraft(null, ['id' => $this->entry->id], null, Mockery::mock(ResolveInfo::class)))
        ->not->toBeNull();
});
