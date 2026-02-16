<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Events\CreatingRevision;
use CraftCms\Cms\Element\Events\RevertedToRevision;
use CraftCms\Cms\Element\Events\RevertingToRevision;
use CraftCms\Cms\Element\Events\RevisionCreated;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->revisions = app(Revisions::class);

    actingAs(User::findOne());
});

it('can create a revision', function () {
    Event::fake([
        CreatingRevision::class,
        RevisionCreated::class,
    ]);

    Event::listen(CreatingRevision::class, fn () => true);
    Event::listen(RevisionCreated::class, fn () => true);

    Entry::factory()->create();
    $element = EntryElement::findOne();

    $revisionId = $this->revisions->createRevision(
        canonical: $element,
        notes: 'Some notes',
    );
    $revision = Craft::$app->elements->getElementById($revisionId);

    expect($revision)->toBeInstanceOf(EntryElement::class);
    expect($revision->getIsRevision())->toBeTrue();
    expect($revision->revisionNotes)->toBe('Some notes');

    Event::assertDispatchedOnce(CreatingRevision::class);
    Event::assertDispatchedOnce(RevisionCreated::class);
});

it('can revert an element to a revision', function () {
    Event::fake([
        RevertingToRevision::class,
        RevertedToRevision::class,
    ]);

    Event::listen(RevertingToRevision::class, fn () => true);
    Event::listen(RevertedToRevision::class, fn () => true);

    Entry::factory()->create();
    $element = EntryElement::findOne();

    $revisionId = $this->revisions->createRevision(
        canonical: $element,
        notes: 'Some notes',
    );
    $revision = Craft::$app->elements->getElementById($revisionId);

    $element = $this->revisions->revertToRevision($revision, 1);

    expect($element->getIsRevision())->toBeFalse();

    Event::assertDispatchedOnce(RevertingToRevision::class);
    Event::assertDispatchedOnce(RevertedToRevision::class);
});
