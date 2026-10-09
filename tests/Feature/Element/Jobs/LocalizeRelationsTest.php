<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Jobs\LocalizeRelations;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('provides a description', function () {
    $job = new LocalizeRelations(fieldId: 1);

    $description = $job->getDescription();

    expect($description)->toContain('Localizing relations');
});

it('leaves global relations for other fields untouched', function () {
    $field = Field::factory()->create();
    $otherField = Field::factory()->create();
    $now = now();

    $otherRelationId = DB::table(Table::RELATIONS)->insertGetId([
        'fieldId' => $otherField->id,
        'sourceId' => Entry::factory()->create()->id,
        'sourceSiteId' => null,
        'targetId' => Entry::factory()->create()->id,
        'sortOrder' => 1,
        'uid' => Str::uuid(),
        'dateCreated' => $now,
        'dateUpdated' => $now,
    ]);

    new LocalizeRelations(fieldId: $field->id)->handle();

    expect(DB::table(Table::RELATIONS)->where('fieldId', $otherField->id)->pluck('sourceSiteId', 'id')->all())
        ->toBe([$otherRelationId => null]);
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
