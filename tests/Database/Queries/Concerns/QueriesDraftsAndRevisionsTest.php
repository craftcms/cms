<?php

use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\User\Elements\User;

test('drafts', function () {
    EntryModel::factory()->create();

    $entry = EntryModel::factory()->create();
    $element = Craft::$app->getElements()->getElementById($entry->id);
    $draft = app(Drafts::class)->createDraft($element);

    expect(entryQuery()->drafts()->count())->toBe(1);
    expect(entryQuery()->drafts(null)->count())->toBe(3);
    expect(entryQuery()->drafts(false)->count())->toBe(2);
    expect(entryQuery()->drafts()->pluck('id'))->toContain($draft->id);
    expect(entryQuery()->draftId($element->draftId)->first())->not()->toBeNull();
});

test('draftOf', function () {
    $entry = EntryModel::factory()->create();

    $element = Craft::$app->getElements()->getElementById($entry->id);

    app(Drafts::class)->createDraft($element);

    expect(entryQuery()->draftOf($element->id)->count())->toBe(1);
    expect(entryQuery()->draftOf([$element->id])->count())->toBe(1);
    expect(entryQuery()->draftOf([$element])->count())->toBe(1);
    expect(entryQuery()->draftOf($element)->count())->toBe(1);
    expect(entryQuery()->draftOf(999)->count())->toBe(0);

    $this->expectException(InvalidArgumentException::class);
    entryQuery()->draftOf('foo')->count();
});

test('draftCreator', function () {
    $user = User::find()->firstOrFail();

    $entry = EntryModel::factory()->create();
    $element = Craft::$app->getElements()->getElementById($entry->id);
    app(Drafts::class)->createDraft($element, $user->id);

    expect(entryQuery()->draftCreator($user->id)->count())->toBe(1);
    expect(entryQuery()->draftCreator($user)->count())->toBe(1);
    expect(entryQuery()->draftCreator(999)->count())->toBe(0);
});

test('provisionalDrafts', function () {
    $entry = EntryModel::factory()->create();
    $element = Craft::$app->getElements()->getElementById($entry->id);
    app(Drafts::class)->createDraft($element, provisional: true);

    expect(entryQuery()->drafts()->count())->toBe(0);
    expect(entryQuery()->drafts()->provisionalDrafts()->count())->toBe(1);
    expect(entryQuery()->drafts()->provisionalDrafts(null)->count())->toBe(1);
});

test('canonicalsOnly', function () {
    $entry = EntryModel::factory()->create();
    $element = Craft::$app->getElements()->getElementById($entry->id);
    app(Drafts::class)->createDraft($element);

    expect(entryQuery()->canonicalsOnly()->count())->toBe(1);
    expect(entryQuery()->drafts(null)->canonicalsOnly()->count())->toBe(1);
    expect(entryQuery()->drafts()->canonicalsOnly()->count())->toBe(0);
});

test('savedDraftsOnly', function () {
    $entry = EntryModel::factory()->create();
    $element = Craft::$app->getElements()->getElementById($entry->id);
    app(Drafts::class)->createDraft($element);

    expect(entryQuery()->savedDraftsOnly()->count())->toBe(1);
});

test('revisions', function () {
    EntryModel::factory()->create();

    $entry = EntryModel::factory()->create();
    $element = Craft::$app->getElements()->getElementById($entry->id);
    $revision = app(Revisions::class)->createRevision($element);

    expect(entryQuery()->revisions()->count())->toBe(1);
    expect(entryQuery()->revisions(null)->count())->toBe(3);
    expect(entryQuery()->revisions(false)->count())->toBe(2);
    expect(entryQuery()->revisions()->pluck('id'))->toContain($revision);
    expect(entryQuery()->revisionId($element->revisionId)->first())->not()->toBeNull();
    expect(entryQuery()->revisionOf($element)->first())->not()->toBeNull();
});

test('revisionCreator', function () {
    $user = User::find()->firstOrFail();

    $entry = EntryModel::factory()->create();
    $element = Craft::$app->getElements()->getElementById($entry->id);
    app(Revisions::class)->createRevision($element, $user->id);

    expect(entryQuery()->revisionCreator($user->id)->count())->toBe(1);
    expect(entryQuery()->revisionCreator($user)->count())->toBe(1);
    expect(entryQuery()->revisionCreator(999)->count())->toBe(0);
});
