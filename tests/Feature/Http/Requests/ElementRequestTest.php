<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\RequestedSite;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;

use function CraftCms\Cms\currentUser;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
});

it('prefers the current users provisional draft when resolving by canonical element id', function () {
    $entry = EntryModel::factory()->createElement([
        'title' => 'Canonical Title',
    ]);

    $draft = app(Drafts::class)->createDraft($entry, auth()->id(), provisional: true);
    $draft->title = 'Edited Title';
    Elements::saveElement($draft);

    $request = ElementRequest::create('/', 'POST');
    $request->setUserResolver(fn () => currentUser());

    app()->instance('request', $request);
    app(RequestedSite::class)->reset();

    $element = $request->element(['id' => $entry->id], checkForProvisionalDraft: true);

    expect($element)->not()->toBeNull()
        ->and($element->id)->toBe($draft->id)
        ->and($element->draftId)->toBe($draft->draftId)
        ->and($element->isProvisionalDraft)->toBeTrue();
});

it('resolves unpublished drafts by id when no canonical element exists', function () {
    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->create();

    $draft = app(Entry::class);
    $draft->siteId = Sites::getPrimarySite()->id;
    $draft->sectionId = $section->id;
    $draft->typeId = $entryType->id;
    $draft->title = 'Unpublished Draft';
    $draft->slug = 'unpublished-draft';
    $draft->setAuthorIds([auth()->id()]);

    app(Drafts::class)->saveElementAsDraft($draft, auth()->id(), markAsSaved: false);

    $request = ElementRequest::create('/', 'POST');
    $request->setUserResolver(fn () => currentUser());

    app()->instance('request', $request);
    app(RequestedSite::class)->reset();

    $element = $request->element(['id' => $draft->id]);

    expect($element)->not()->toBeNull()
        ->and($element->id)->toBe($draft->id)
        ->and($element->draftId)->toBe($draft->draftId)
        ->and($element->getIsUnpublishedDraft())->toBeTrue();
});

it('does not redirect when a non-strict request resolves the element in the requested site', function () {
    $entry = EntryModel::factory()->createElement([
        'title' => 'Kitchen Sink',
    ]);

    // Mirrors an Inertia navigation: Accept: text/html (not JSON), so the
    // controller resolves the element with strictSite disabled.
    $request = ElementRequest::create('/', 'GET');
    $request->setUserResolver(fn () => currentUser());

    app()->instance('request', $request);
    app(RequestedSite::class)->reset();

    $element = $request->element(['id' => $entry->id], strictSite: false);

    expect($element)->toBeInstanceOf(Entry::class)
        ->and($element->id)->toBe($entry->id);
});

it('resolves unpublished drafts by uid when no canonical element exists', function () {
    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->create();

    $draft = app(Entry::class);
    $draft->siteId = Sites::getPrimarySite()->id;
    $draft->sectionId = $section->id;
    $draft->typeId = $entryType->id;
    $draft->title = 'UID Draft';
    $draft->slug = 'uid-draft';
    $draft->setAuthorIds([auth()->id()]);

    app(Drafts::class)->saveElementAsDraft($draft, auth()->id(), markAsSaved: false);

    $request = ElementRequest::create('/', 'POST', [
        'elementUid' => $draft->uid,
    ]);
    $request->setUserResolver(fn () => currentUser());

    app()->instance('request', $request);
    app(RequestedSite::class)->reset();

    $element = $request->element();

    expect($element)->not()->toBeNull()
        ->and($element->id)->toBe($draft->id)
        ->and($element->uid)->toBe($draft->uid)
        ->and($element->draftId)->toBe($draft->draftId)
        ->and($element->getIsUnpublishedDraft())->toBeTrue();
});
