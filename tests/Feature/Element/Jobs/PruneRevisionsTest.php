<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Jobs\PruneRevisions;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;

beforeEach(function () {
    Cms::config()->maxRevisions(null);

    $this->entry = Entry::factory()->createElement();
    $this->revisionIds = collect(range(1, 3))
        ->map(fn () => app(Revisions::class)->createRevision($this->entry, force: true))
        ->all();

    $this->remainingRevisionIds = fn () => EntryElement::find()
        ->revisionOf($this->entry->id)
        ->siteId($this->entry->siteId)
        ->status(null)
        ->orderBy('elements.id')
        ->ids();
});

it('has null max revisions by default', function () {
    $job = new PruneRevisions(
        elementType: EntryElement::class,
        canonicalId: 1,
        siteId: 1,
    );

    expect($job->maxRevisions)->toBeNull();
});

it('provides a description', function () {
    $job = new PruneRevisions(
        elementType: EntryElement::class,
        canonicalId: 1,
        siteId: 1,
    );

    $description = $job->getDescription();

    expect($description)->toContain('Pruning');
});

it('keeps all revisions when maxRevisions is not configured', function () {
    new PruneRevisions(
        elementType: EntryElement::class,
        canonicalId: $this->entry->id,
        siteId: $this->entry->siteId,
    )->handle();

    expect(($this->remainingRevisionIds)())->toBe($this->revisionIds);
});

it('deletes the oldest revisions beyond the configured maximum', function () {
    Cms::config()->maxRevisions(2);

    new PruneRevisions(
        elementType: EntryElement::class,
        canonicalId: $this->entry->id,
        siteId: $this->entry->siteId,
    )->handle();

    expect(($this->remainingRevisionIds)())->toBe(array_slice($this->revisionIds, 1));
});

it('prefers the job maximum over the configured maximum', function () {
    Cms::config()->maxRevisions(50);

    new PruneRevisions(
        elementType: EntryElement::class,
        canonicalId: $this->entry->id,
        siteId: $this->entry->siteId,
        maxRevisions: 1,
    )->handle();

    expect(($this->remainingRevisionIds)())->toBe([end($this->revisionIds)]);
});
