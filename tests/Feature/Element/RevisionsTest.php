<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Events\ElementRevertingToRevision;
use CraftCms\Cms\Element\Events\RevertedToRevision;
use CraftCms\Cms\Element\Events\RevisionCreated;
use CraftCms\Cms\Element\Events\RevisionCreating;
use CraftCms\Cms\Element\Models\Element as ElementModel;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Tests\TestClasses\Element\TestRevisionableElement;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->revisions = app(Revisions::class);

    actingAs(User::findOne());
});

it('can create a revision', function () {
    Event::fake([
        RevisionCreating::class,
        RevisionCreated::class,
    ]);

    Event::listen(RevisionCreating::class, fn () => true);
    Event::listen(RevisionCreated::class, fn () => true);

    Entry::factory()->create();
    $element = EntryElement::findOne();

    $revisionId = $this->revisions->createRevision(
        canonical: $element,
        notes: 'Some notes',
    );
    $revision = Elements::getElementById($revisionId);

    expect($revision)->toBeInstanceOf(EntryElement::class);
    expect($revision->getIsRevision())->toBeTrue();
    expect($revision->revisionNotes)->toBe('Some notes');

    Event::assertDispatchedOnce(RevisionCreating::class);
    Event::assertDispatchedOnce(RevisionCreated::class);
});

it('reuses the last revision when the element hasn’t changed since', function () {
    Entry::factory()->create();
    $element = EntryElement::findOne();

    $revisionId = $this->revisions->createRevision($element);

    expect($this->revisions->createRevision($element))->toBe($revisionId)
        ->and($this->revisions->createRevision($element, force: true))->not->toBe($revisionId);
});

it('can revert an element to a revision', function () {
    Event::fake([
        ElementRevertingToRevision::class,
        RevertedToRevision::class,
    ]);

    Event::listen(ElementRevertingToRevision::class, fn () => true);
    Event::listen(RevertedToRevision::class, fn () => true);

    Entry::factory()->create();
    $element = EntryElement::findOne();

    $revisionId = $this->revisions->createRevision(
        canonical: $element,
        notes: 'Some notes',
    );
    $revision = Elements::getElementById($revisionId);

    $element = $this->revisions->revertToRevision($revision, 1);

    expect($element->getIsRevision())->toBeFalse();

    Event::assertDispatchedOnce(ElementRevertingToRevision::class);
    Event::assertDispatchedOnce(RevertedToRevision::class);
});

it('saves revisions for any element type that has revisions', function () {
    Date::setTestNow('2026-10-01 12:00:00');
    $model = ElementModel::factory()->title('Original title')->create(['type' => TestRevisionableElement::class]);
    $element = TestRevisionableElement::find()->id($model->id)->one();

    Date::setTestNow('2026-10-01 13:00:00');
    $element->title = 'Updated title';
    expect(Elements::saveElement($element))->toBeTrue();

    $revisions = TestRevisionableElement::find()
        ->revisionOf($element)
        ->status(null)
        ->orderBy('num')
        ->all();

    expect($revisions)->toHaveCount(2)
        ->and($revisions[0]->title)->toBe('Original title')
        ->and($revisions[0]->revisionNotes)->toStartWith('Revision from ')
        ->and($revisions[1]->title)->toBe('Updated title');
});
