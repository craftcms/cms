<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Jobs\PruneRevisions;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Queue\Job;
use Illuminate\Support\Facades\Queue;

it('extends Job', function () {
    $job = new PruneRevisions(
        elementType: EntryElement::class,
        canonicalId: 1,
        siteId: 1,
    );

    expect($job)->toBeInstanceOf(Job::class);
});

it('can be instantiated with required parameters', function () {
    $job = new PruneRevisions(
        elementType: EntryElement::class,
        canonicalId: 42,
        siteId: 1,
    );

    expect($job->elementType)->toBe(EntryElement::class)
        ->and($job->canonicalId)->toBe(42)
        ->and($job->siteId)->toBe(1);
});

it('can be instantiated with custom max revisions', function () {
    $job = new PruneRevisions(
        elementType: EntryElement::class,
        canonicalId: 1,
        siteId: 1,
        maxRevisions: 5,
    );

    expect($job->maxRevisions)->toBe(5);
});

it('has null max revisions by default', function () {
    $job = new PruneRevisions(
        elementType: EntryElement::class,
        canonicalId: 1,
        siteId: 1,
    );

    expect($job->maxRevisions)->toBeNull();
});

it('can be dispatched to the queue', function () {
    Queue::fake();

    $job = new PruneRevisions(
        elementType: EntryElement::class,
        canonicalId: 1,
        siteId: 1,
    );

    dispatch($job);

    Queue::assertPushed(PruneRevisions::class);
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

it('returns early when maxRevisions is not configured', function () {
    Cms::config()->maxRevisions(null);

    $job = new PruneRevisions(
        elementType: EntryElement::class,
        canonicalId: 99999,
        siteId: 1,
    );

    $job->handle();

    expect(true)->toBeTrue();
});

it('handles case with no extra revisions', function () {
    Entry::factory()->create();
    $entry = EntryElement::find()->one();

    Cms::config()->maxRevisions(50);

    $job = new PruneRevisions(
        elementType: EntryElement::class,
        canonicalId: $entry->id,
        siteId: $entry->siteId,
        maxRevisions: 50,
    );

    $job->handle();

    expect(true)->toBeTrue();
});
