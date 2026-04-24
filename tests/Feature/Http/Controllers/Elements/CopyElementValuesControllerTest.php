<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Http\Controllers\Elements\CopyElementValuesController;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Models\SectionSiteSettings;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    $this->secondarySite = Site::factory()->create([
        'handle' => 'secondary',
    ]);

    $this->field = Field::factory()->create([
        'name' => 'Copy Field',
        'handle' => 'copyField',
        'type' => PlainText::class,
        'translationMethod' => 'site',
    ]);
    $this->entryType = EntryType::factory()->withField($this->field)->create();
    $this->section = Section::factory()
        ->withSites($this->secondarySite)
        ->withEntryTypes($this->entryType)
        ->create([
            'handle' => 'blog',
        ]);
});

it('requires authentication', function () {
    Auth::logout();

    postJson(action(CopyElementValuesController::class), [
        'elementType' => Entry::class,
    ])->assertUnauthorized();
});

it('returns responses resolved by the element request', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);

    post(action(CopyElementValuesController::class), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'draftId' => 999999,
        'siteId' => $entry->siteId,
    ])->assertRedirect($entry->getCpEditUrl());
});

it('returns 400 when no element is identified by the request', function () {
    postJson(action(CopyElementValuesController::class), [
        'elementType' => Entry::class,
        'elementId' => 999999,
        'siteId' => Sites::getPrimarySite()->id,
    ])->assertBadRequest();
});

it('returns 400 for revisions', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);
    $revisionId = app(Revisions::class)->createRevision($entry, auth()->id());

    postJson(action(CopyElementValuesController::class), [
        'elementType' => Entry::class,
        'revisionId' => $revisionId,
        'siteId' => $entry->siteId,
    ])->assertBadRequest();
});

it('validates the request payload', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);

    postJson(action(CopyElementValuesController::class), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['fromSiteId', 'layoutElementUid']);
});

it('returns 400 for invalid source site ids', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);

    postJson(action(CopyElementValuesController::class), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
        'fromSiteId' => 999999,
        'layoutElementUid' => Str::uuid()->toString(),
    ])->assertBadRequest();
});

it('forbids copying values from a site the user cannot edit', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);

    $viewer = UserModel::factory()
        ->withPermissions([
            'accessCp',
            sprintf('editSite:%s', Sites::getPrimarySite()->uid),
            sprintf('viewEntries:%s', $this->section->uid),
            sprintf('viewPeerEntries:%s', $this->section->uid),
        ])
        ->createElement();

    actingAs($viewer);

    postJson(action(CopyElementValuesController::class), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
        'fromSiteId' => $this->secondarySite->id,
        'layoutElementUid' => customFieldUid($entry),
    ])->assertForbidden();
});

it('returns 400 for invalid layout element uuids', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);
    localizeEntry($entry, $this->secondarySite->id);

    postJson(action(CopyElementValuesController::class), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
        'fromSiteId' => $this->secondarySite->id,
        'layoutElementUid' => Str::uuid()->toString(),
    ])->assertBadRequest();
});

it('copies a title field value from another site and returns updated field html', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Primary Title',
            'slug' => 'primary-title',
        ]);
    localizeEntry($entry, $this->secondarySite->id);

    /** @var Entry $secondaryEntry */
    $secondaryEntry = Entry::find()
        ->id($entry->id)
        ->siteId($this->secondarySite->id)
        ->status(null)
        ->one();
    $secondaryEntry->setFieldValue('copyField', 'Secondary field value');
    $secondaryEntry->setAuthorIds([auth()->id()]);
    Elements::saveElement($secondaryEntry);

    postJson(action(CopyElementValuesController::class), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
        'fromSiteId' => $this->secondarySite->id,
        'layoutElementUid' => customFieldUid($entry),
        'namespace' => 'copyNamespace',
    ])->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('message', t('Field value copied.'))
            ->where('modelName', 'element')
            ->where('fieldHtml', fn (string $html) => $html !== ''
                && str_contains($html, 'data-layout-element="'.customFieldUid($entry).'"'))
            ->has('headHtml')
            ->has('bodyHtml')
            ->etc()
        );
});

function customFieldUid(Entry $entry): string
{
    $layoutElement = $entry->getFieldLayout()->getCustomFieldElements()[0] ?? null;

    expect($layoutElement?->uid)->not->toBeNull();

    return $layoutElement->uid;
}

function localizeEntry(Entry $entry, int $siteId): void
{
    SectionSiteSettings::query()->firstOrCreate([
        'sectionId' => $entry->sectionId,
        'siteId' => $siteId,
    ], [
        'uid' => (string) Str::uuid(),
        'hasUrls' => true,
    ]);

    EntryModel::query()->findOrFail($entry->id)->element->siteSettings()->firstOrCreate([
        'siteId' => $siteId,
    ]);
}
