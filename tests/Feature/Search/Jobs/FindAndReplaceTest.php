<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Queue\BatchedJob;
use CraftCms\Cms\Search\Jobs\FindAndReplace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

it('extends BatchedJob', function () {
    $job = new FindAndReplace(
        find: 'foo',
        replace: 'bar',
    );

    expect($job)->toBeInstanceOf(BatchedJob::class);
});

it('can be instantiated with find and replace strings', function () {
    $job = new FindAndReplace(
        find: 'old text',
        replace: 'new text',
    );

    expect($job->find)->toBe('old text')
        ->and($job->replace)->toBe('new text');
});

it('can be dispatched to the queue', function () {
    Queue::fake();

    $job = new FindAndReplace(
        find: 'search',
        replace: 'replace',
    );

    dispatch($job);

    Queue::assertPushed(FindAndReplace::class);
});

it('provides a description with find and replace values', function () {
    $job = new FindAndReplace(
        find: 'old',
        replace: 'new',
    );

    $description = $job->getDescription();

    expect($description)->toContain('old')
        ->and($description)->toContain('new');
});

it('handles case with no matching content', function () {
    $job = new FindAndReplace(
        find: 'nonexistent-text-that-does-not-exist-anywhere',
        replace: 'replacement',
    );

    $job->handle();

    expect(true)->toBeTrue();
});

it('replaces text in element titles', function () {
    Entry::factory()->create();
    $entry = EntryElement::find()->one();

    // Update the title to contain our search text
    DB::table(Table::ELEMENTS_SITES)
        ->where('elementId', $entry->id)
        ->update(['title' => 'Hello World Title']);

    $job = new FindAndReplace(
        find: 'World',
        replace: 'Universe',
    );

    $job->handle();

    // Verify the replacement occurred
    $updated = DB::table(Table::ELEMENTS_SITES)
        ->where('elementId', $entry->id)
        ->first();

    expect($updated->title)->toBe('Hello Universe Title');
});

it('handles empty find string gracefully', function () {
    $job = new FindAndReplace(
        find: '',
        replace: 'something',
    );

    $job->handle();

    expect(true)->toBeTrue();
});

it('can replace with empty string', function () {
    Entry::factory()->create();
    $entry = EntryElement::find()->one();

    // Update the title to contain our search text
    DB::table(Table::ELEMENTS_SITES)
        ->where('elementId', $entry->id)
        ->update(['title' => 'Remove This Word']);

    $job = new FindAndReplace(
        find: ' This',
        replace: '',
    );

    $job->handle();

    // Verify the replacement occurred
    $updated = DB::table(Table::ELEMENTS_SITES)
        ->where('elementId', $entry->id)
        ->first();

    expect($updated->title)->toBe('Remove Word');
});
