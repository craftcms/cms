<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Jobs\LocalizeRelations;
use CraftCms\Cms\Queue\Job;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

it('extends Job', function () {
    $job = new LocalizeRelations(fieldId: 1);

    expect($job)->toBeInstanceOf(Job::class);
});

it('can be instantiated with field id', function () {
    $job = new LocalizeRelations(fieldId: 42);

    expect($job->fieldId)->toBe(42);
});

it('can be dispatched to the queue', function () {
    Queue::fake();

    $job = new LocalizeRelations(fieldId: 1);

    dispatch($job);

    Queue::assertPushed(LocalizeRelations::class);
});

it('provides a description', function () {
    $job = new LocalizeRelations(fieldId: 1);

    $description = $job->getDescription();

    expect($description)->toContain('Localizing relations');
});

it('handles case with no global relations', function () {
    $job = new LocalizeRelations(fieldId: 99999);

    $job->handle();

    expect(true)->toBeTrue();
});

it('localizes relations for a field', function () {
    // This test requires a valid field to exist in the database
    // Skip if no fields exist
    $field = DB::table(Table::FIELDS)->first();

    if (! $field) {
        $this->markTestSkipped('No fields exist in the database');
    }

    $fieldId = $field->id;

    // We need valid source and target elements
    $elements = DB::table(Table::ELEMENTS)->limit(2)->get();

    if ($elements->count() < 2) {
        $this->markTestSkipped('Not enough elements exist in the database');
    }

    $uid = \CraftCms\Cms\Support\Str::uuid();

    DB::table(Table::RELATIONS)->insert([
        'fieldId' => $fieldId,
        'sourceId' => $elements[0]->id,
        'sourceSiteId' => null,
        'targetId' => $elements[1]->id,
        'sortOrder' => 1,
        'uid' => $uid,
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);

    $job = new LocalizeRelations(fieldId: $fieldId);

    $job->handle();

    // Check that the relation now has a sourceSiteId
    $relation = DB::table(Table::RELATIONS)
        ->where('uid', $uid)
        ->first();

    expect($relation->sourceSiteId)->not->toBeNull();
});
