<?php

declare(strict_types=1);

use craft\base\ElementInterface;
use craft\elements\Entry as EntryElement;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Events\ApplyingDraft;
use CraftCms\Cms\Element\Events\CreatingDraft;
use CraftCms\Cms\Element\Events\DraftApplied;
use CraftCms\Cms\Element\Events\DraftCreated;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->drafts = app(Drafts::class);
});

it('can get editable drafts', function () {
    Entry::factory()->create();

    $element = EntryElement::findOne();

    $this->drafts->createDraft($element, User::find()->firstOrFail()->id);

    expect($this->drafts->getEditableDrafts($element))->toBeEmpty();

    actingAs(User::find()->firstOrFail());

    expect($this->drafts->getEditableDrafts($element))->not()->toBeEmpty();
});

it('can create a draft', function () {
    Event::fake([
        CreatingDraft::class,
        DraftCreated::class,
    ]);

    Event::listen(CreatingDraft::class, fn () => true);
    Event::listen(DraftCreated::class, fn () => true);

    Entry::factory()->create();
    $element = EntryElement::findOne();

    expect($draft = $this->drafts->createDraft(
        canonical: $element,
        name: 'My draft',
        notes: 'Some notes',
    ))->toBeInstanceOf(ElementInterface::class);

    expect($draft->draftName)->toBe('My draft');
    expect($draft->draftNotes)->toBe('Some notes');

    Event::assertDispatchedOnce(CreatingDraft::class);
    Event::assertDispatchedOnce(DraftCreated::class);
});

it('can generate unique draft names', function () {
    expect($this->drafts->generateDraftName(1))->toBe('Draft 1');

    DB::table(Table::DRAFTS)->insert([
        'canonicalId' => 1,
        'name' => 'Draft 1',
    ]);

    expect($this->drafts->generateDraftName(1))->toBe('Draft 2');
});

it('can save an element as draft', function () {
    Entry::factory()->create();
    $element = EntryElement::findOne();

    expect($element->getIsDraft())->toBeFalse();

    $this->drafts->saveElementAsDraft($element, User::find()->firstOrFail()->id);

    expect($element->getIsDraft())->toBeTrue();
});

it('can apply a draft', function () {
    Event::fake([
        ApplyingDraft::class,
        DraftApplied::class,
    ]);

    Event::listen(ApplyingDraft::class, fn () => true);
    Event::listen(DraftApplied::class, fn () => true);

    $entry = Entry::factory()->create();
    $element = EntryElement::findOne();
    $entry->section->entryTypes()->attach($entry->entryType, ['sortOrder' => 1]);

    $draft = $this->drafts->createDraft(
        canonical: $element,
        name: 'My draft',
        notes: 'Some notes',
    );

    expect(DB::table(Table::DRAFTS)->count())->toBe(1);

    $element = $this->drafts->applyDraft($draft);

    expect(DB::table(Table::DRAFTS)->count())->toBe(0);

    expect($element->revisionNotes)->toBe('Some notes');

    Event::assertDispatchedOnce(ApplyingDraft::class);
    Event::assertDispatchedOnce(DraftApplied::class);
});

it('can remove draft data from an element', function () {
    Entry::factory()->create();
    $element = EntryElement::findOne();

    $draft = $this->drafts->createDraft(
        canonical: $element,
        name: 'My draft',
        notes: 'Some notes',
    );

    expect($draft->draftId)->not()->toBeNull();

    $this->drafts->removeDraftData($draft);

    expect($draft->draftId)->toBeNull();
});
