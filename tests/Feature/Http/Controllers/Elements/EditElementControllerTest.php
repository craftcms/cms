<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Address\Models\Address as AddressModel;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout as FieldLayoutModel;
use CraftCms\Cms\Http\Controllers\Elements\EditElementController;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes as EntryTypesFacade;
use CraftCms\Cms\Support\Facades\Fields as FieldsFacade;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

dataset('editElementEntryRoutes', [
    'entries route' => [
        fn (Entry $entry) => cp_url(sprintf(
            'entries/%s/%d-%s',
            $entry->getSection()->handle,
            $entry->id,
            $entry->slug,
        )),
    ],
    'content route' => [
        fn (Entry $entry) => cp_url(sprintf(
            'content/entries/%s/%d-%s',
            $entry->getSection()->handle,
            $entry->id,
            $entry->slug,
        )),
    ],
    'entries route without slug' => [
        fn (Entry $entry) => cp_url(sprintf(
            'entries/%s/%d',
            $entry->getSection()->handle,
            $entry->id,
        )),
    ],
    'content route without slug' => [
        fn (Entry $entry) => cp_url(sprintf(
            'content/entries/%s/%d',
            $entry->getSection()->handle,
            $entry->id,
        )),
    ],
]);

beforeEach(function () {
    actingAs(User::findOne());

    config()->set('filesystems.disks.edit-element-controller-test', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/edit-element-controller-test'),
    ]);

    $layout = FieldLayout::make(Entry::class)
        ->tab('Content', fn (FieldLayoutTab $tab) => $tab->add(new EntryTitleField(['uid' => 'entry-title'])));
    $config = $layout->getConfig();
    $config['tabs'][0]['uid'] = 'entry-content';
    $layout = FieldLayoutModel::factory()->create(['type' => Entry::class, 'config' => $config]);
    $this->entryType = EntryType::factory()->create(['fieldLayoutId' => $layout->id]);
    $this->section = Section::factory()->withEntryTypes($this->entryType)->create([
        'handle' => 'news',
        'enableVersioning' => true,
    ]);
    $this->volume = Volume::factory()->create(['fs' => 'edit-element-controller-test']);
    $this->folder = VolumeFolderModel::factory()->create(['volumeId' => $this->volume->id]);
});

it('requires login for each entry control panel edit route', function (Closure $route) {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Current Title',
            'slug' => 'current-title',
        ]);

    Auth::logout();

    get($route($entry))->assertRedirectContains('login');
})->with('editElementEntryRoutes');

it('requires login for the asset control panel edit route', function () {
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $this->volume->id,
        'folderId' => $this->folder->id,
    ]);

    Auth::logout();

    get($asset->getCpEditUrl())->assertRedirectContains('login');
});

it('requires authentication for the action route', function () {
    Auth::logout();

    postJson(action(EditElementController::class), [
        'elementType' => Entry::class,
    ], [
        'X-Craft-Container-Id' => 'slideout',
    ])->assertUnauthorized();
});

it('renders the current entry edit screen for each control panel route', function (Closure $route) {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Current Title',
            'slug' => 'current-title',
        ]);

    get($route($entry))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('elements/Edit')
            ->where('title', 'Current Title')
            ->where('elementId', $entry->id)
            ->where('readOnly', false)
            ->where('saveUrl', fn (string $url) => str_contains($url, 'entries/save-entry'))
            ->has('form.nodes')
            ->has('sidebarForm.nodes')
        );
})->with('editElementEntryRoutes');

it('renders the asset edit screen', function () {
    $this->withoutExceptionHandling();
    Queue::fake();
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $this->volume->id,
        'folderId' => $this->folder->id,
        'filename' => 'featured-image.jpg',
    ]);

    get($asset->getCpEditUrl())
        ->assertOk()
        ->assertSee(sprintf('"elementId":%d', $asset->id), false);
});

it('returns responses resolved by the element request', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);

    post(action(EditElementController::class), [
        'elementType' => $entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
        'draftId' => 999999,
    ])->assertRedirect($entry->getCpEditUrl());
});

it('returns 400 when no element is identified by the request', function () {
    postJson(action(EditElementController::class), [
        'elementType' => Entry::class,
        'siteId' => Sites::getPrimarySite()->id,
    ], [
        'X-Craft-Container-Id' => 'slideout',
    ])->assertBadRequest();
});

it('renders an address through the generic editor without editor registration', function () {
    $owner = UserModel::factory()->createElement();
    $address = AddressModel::factory()->withOwnedElement($owner, 1)->createElement([
        'countryCode' => 'US',
    ]);

    $response = getJson(action(EditElementController::class, [
        'elementType' => Address::class,
        'elementId' => $address->id,
        'siteId' => $address->siteId,
        'ownerId' => $owner->id,
    ]), ['X-Inertia' => 'true'])->assertOk();

    expect($response->json('component'))->toBe('elements/Edit')
        ->and($response->json('props.elementId'))->toBe($address->id)
        ->and($response->json('props.saveUrl'))->toContain('elements/save')
        ->and($response->json('props.saveParams.elementType'))->toBe(Address::class)
        ->and($response->json('props.nestedContext.ownerId'))->toBe($owner->id);
});

it('returns a json editor payload for the current element', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Current Title',
            'slug' => 'current-title',
        ]);

    getJson(action(EditElementController::class, [
        'elementType' => $entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
    ]), [
        'X-Craft-Container-Id' => 'slideout',
    ])
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('action', 'elements/save')
            ->where('notice', null)
            ->where('content', fn (string $content) => str_contains($content, 'craft-entry-field-layout-ui')
                && str_contains($content, 'elements/save'))
            ->where('deltaNames', fn ($names) => collect($names)
                ->doesntContain(fn (string $name) => str_ends_with($name, '[title]')))
            ->where('bodyHtml', fn (string $html) => str_contains($html, sprintf('"elementId":%d', $entry->id))
                && str_contains($html, sprintf('"canonicalId":%d', $entry->id))
                && str_contains($html, '"isStatic":false')
                && str_contains($html, '"isProvisionalDraft":false')
                && str_contains($html, '"isUnpublishedDraft":false'))
            ->has('headHtml')
            ->has('bodyHtml')
            ->has('deltaNames')
            ->has('initialDeltaValues')
            ->etc()
        );
});

it('renders the generic asset editor in an Inertia slideout', function () {
    Queue::fake();
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $this->volume->id,
        'folderId' => $this->folder->id,
        'filename' => 'prop-delivery.jpg',
    ]);

    $response = getJson(action(EditElementController::class, [
        'elementType' => $asset::class,
        'elementId' => $asset->id,
        'siteId' => $asset->siteId,
    ]), [
        'X-Inertia' => 'true',
        'X-Craft-Container-Id' => 'slideout-1',
    ])->assertOk();

    expect($response->json('component'))->toBe('elements/Edit')
        ->and($response->json('props.elementId'))->toBe($asset->id)
        ->and($response->json('props.readOnly'))->toBeFalse()
        ->and($response->json('props.saveParams.elementId'))->toBe($asset->id);
});

it('renders the entry editor for an entry opened in an Inertia slideout', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement(['title' => 'Slideout Entry', 'slug' => 'slideout-entry']);

    $response = getJson(action(EditElementController::class, [
        'elementType' => $entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
    ]), [
        'X-Inertia' => 'true',
        'X-Craft-Container-Id' => 'slideout-1',
    ])->assertOk();

    expect($response->json('component'))->toBe('elements/Edit')
        ->and($response->json('props.elementId'))->toBe($entry->id)
        ->and($response->json('props.saveUrl'))->toContain('entries/save-entry')
        ->and($response->json('props.saveParams'))->toMatchArray([
            'entryId' => $entry->id,
            'siteId' => $entry->siteId,
        ])
        ->and($response->json('props.nestedContext'))->toBeNull();
});

it('renders a nested entry through the owner it was opened from', function () {
    ['field' => $field, 'ownerDraft' => $ownerDraft, 'block' => $block] = createEditElementMatrixFixture();

    $response = getJson(action(EditElementController::class, [
        'elementId' => $block->id,
        'siteId' => $block->siteId,
        'fieldId' => $field->id,
        'ownerId' => $ownerDraft->id,
    ]), [
        'X-Inertia' => 'true',
        'X-Craft-Container-Id' => 'slideout-1',
    ])->assertOk();

    expect($response->json('component'))->toBe('elements/Edit')
        ->and($response->json('props.saveUrl'))->toContain('elements/save')
        ->and($response->json('props.saveForDerivativeUrl'))->toContain('elements/save-nested-element-for-derivative')
        ->and($response->json('props.saveParams'))->toMatchArray([
            'elementType' => Entry::class,
            'elementId' => $block->id,
        ])
        ->and($response->json('props.nestedContext'))->toBe([
            'fieldId' => $field->id,
            'ownerId' => $ownerDraft->id,
        ]);
});

it('prevalidates enabled live elements and returns an error summary', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Current Title',
            'slug' => 'current-title',
        ]);

    postJson(action(EditElementController::class), [
        'elementType' => $entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
        'prevalidate' => 1,
        'title' => '',
    ], [
        'X-Craft-Container-Id' => 'slideout',
    ])
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('action', 'elements/save')
            ->where('errorSummary', fn (?string $summary) => is_string($summary)
                && str_contains($summary, 'The title field is required.')
                && str_contains($summary, 'field-error-key'))
            ->etc()
        );
});

it('merges canonical changes into outdated drafts before rendering', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);
    /** @var Entry $draft */
    $draft = app(Drafts::class)->createDraft($entry, auth()->id(), name: 'Working Draft');

    $entry->title = 'Updated Canonical Title';
    Elements::saveElement($entry);

    get(cp_url(sprintf(
        'entries/%s/%d-%s?draftId=%d',
        $entry->getSection()->handle,
        $entry->id,
        $entry->slug,
        $draft->draftId,
    )))
        ->assertOk()
        ->assertSeeText('Recent changes to the Current revision have been merged into this draft.');
});

it('uses the editor’s own site crumb rather than the shared one', function () {
    $other = Site::factory()->create();
    $section = Section::factory()->withSites($other)->create();
    $entry = EntryModel::factory()->forSection($section)->createElement();

    get(cp_url(sprintf('entries/%s/%d-%s', $section->handle, $entry->id, $entry->slug)))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $props = $page->toArray()['props'];

            // The editor lists only the sites the element propagates to, so it
            // keeps its own crumb and never opts into the shared one — two
            // site pickers otherwise.
            expect($props['crumbs'][0]['id'])->toBe('site-crumb');
            expect($props['craft']['siteCrumb'])->toBeNull();

            return true;
        });
});

/** @return array{field: FieldInterface, owner: Entry, ownerDraft: Entry, block: Entry} */
function createEditElementMatrixFixture(): array
{
    $blockType = EntryType::factory()
        ->withField(Field::factory()->create(['handle' => 'innerText', 'type' => PlainText::class]))
        ->create(['handle' => 'matrixBlock', 'hasTitleField' => true]);

    $matrixField = Field::factory()->create([
        'handle' => 'matrixField',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$blockType->id]],
    ]);

    $ownerType = EntryType::factory()
        ->withField($matrixField)
        ->create(['handle' => 'owner', 'hasTitleField' => true]);

    $section = Section::factory()->withEntryTypes($ownerType)->create(['handle' => 'owners']);

    $owner = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($ownerType)
        ->createElement(['title' => 'Owner Entry', 'slug' => 'owner-entry']);

    EntryTypesFacade::refreshEntryTypes();
    FieldsFacade::invalidateCaches();

    /** @var Entry $owner */
    $owner = Entry::find()->id($owner->id)->status(null)->one();
    $owner->setFieldValueFromRequest('matrixField', [
        'entries' => [
            'new:1' => [
                'type' => $blockType->handle,
                'title' => 'Block 1',
                'enabled' => true,
            ],
        ],
        'sortOrder' => ['new:1'],
    ]);
    expect(Elements::saveElement($owner))->toBeTrue();

    $owner = Entry::find()->id($owner->id)->status(null)->one();
    /** @var Entry $ownerDraft */
    $ownerDraft = app(Drafts::class)->createDraft($owner, auth()->id(), name: 'Owner Draft');

    return [
        'field' => FieldsFacade::getFieldById($matrixField->id),
        'owner' => $owner,
        'ownerDraft' => $ownerDraft,
        'block' => $owner->getFieldValue('matrixField')->status(null)->one(),
    ];
}

it('names the owner’s draft in a nested entry’s owner crumb', function () {
    ['ownerDraft' => $ownerDraft, 'block' => $block] = createEditElementMatrixFixture();

    $block->setOwner($ownerDraft);

    expect(last($block->getCrumbs())->html)->toContain('Owner Draft');
});

it('renders a nested entry’s own edit page with the entry editor', function () {
    ['block' => $block] = createEditElementMatrixFixture();

    expect($block->getCpEditUrl())->toContain("edit/{$block->id}");

    get($block->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('elements/Edit')
            ->where('elementId', $block->id)
            ->where('cpEditUrl', $block->getCpEditUrl())
        );
});
