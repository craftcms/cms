<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Jobs\LocalizeRelations;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Queue\Job;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use Illuminate\Database\QueryException;
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

it('can be retried after localizing a relation fails', function () {
    $field = Field::factory()->create();
    $source = Entry::factory()->create();
    $target = Entry::factory()->create();
    $primarySite = Sites::getPrimarySite();
    $secondarySite = Site::factory()->create([
        'groupId' => $primarySite->groupId,
        'sortOrder' => 0,
    ]);
    $relationUid = Str::uuid();
    $now = now();

    DB::table(Table::RELATIONS)->insert([
        'fieldId' => $field->id,
        'sourceId' => $source->id,
        'sourceSiteId' => null,
        'targetId' => $target->id,
        'sortOrder' => 1,
        'uid' => $relationUid,
        'dateCreated' => $now,
        'dateUpdated' => $now,
    ]);

    $conflictingRelationId = DB::table(Table::RELATIONS)->insertGetId([
        'fieldId' => $field->id,
        'sourceId' => $source->id,
        'sourceSiteId' => $secondarySite->id,
        'targetId' => $target->id,
        'sortOrder' => 1,
        'uid' => Str::uuid(),
        'dateCreated' => $now,
        'dateUpdated' => $now,
    ]);

    $job = new LocalizeRelations(fieldId: $field->id);

    expect(fn () => $job->handle())->toThrow(QueryException::class);

    expect(DB::table(Table::RELATIONS)
        ->where('fieldId', $field->id)
        ->whereNull('sourceSiteId')
        ->exists())->toBeTrue();

    DB::table(Table::RELATIONS)->where('id', $conflictingRelationId)->delete();

    $job->handle();

    expect(DB::table(Table::RELATIONS)
        ->where('uid', $relationUid)
        ->value('sourceSiteId'))->toBe($primarySite->id);

    expect(DB::table(Table::RELATIONS)
        ->where('fieldId', $field->id)
        ->where('sourceId', $source->id)
        ->where('targetId', $target->id)
        ->orderBy('sourceSiteId')
        ->pluck('sourceSiteId')
        ->all())->toBe(Sites::getAllSiteIds()->sort()->values()->all());
});
