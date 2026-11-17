<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

/** @return list<array<string, mixed>> */
function trackedFieldReferenceRows(): array
{
    return DB::table(Table::FIELDREFERENCES)
        ->select(['fieldId', 'fieldInstanceUid', 'sourceId', 'sourceSiteId', 'targetId'])
        ->orderBy('sourceId')
        ->get()
        ->map(fn (object $row) => (array) $row)
        ->all();
}

it('fills field references for upgraded link field values', function () {
    $target = EntryModel::factory()->createElement();
    $result = EntryModel::factory()
        ->withField('link', Link::class, ['types' => ['entry']], value: ['type' => 'entry', 'value' => "{entry:$target->id@$target->siteId:url}"])
        ->createElementWithFields();
    $source = $result->element;
    $field = $result->field('link');
    $draft = app(Drafts::class)->createDraft($source, User::findOne()->id);
    app(Revisions::class)->createRevision($source, force: true);

    // The rows saving the source and its draft wrote
    $expected = trackedFieldReferenceRows();
    expect(array_column($expected, 'sourceId'))->toBe([$source->id, $draft->id]);

    // Craft 5 tracked the link in `relations`, and nothing filled `fieldreferences`.
    DB::table(Table::FIELDREFERENCES)->delete();
    DB::table(Table::RELATIONS)->insert([
        'fieldId' => $field->id,
        'sourceId' => $source->id,
        'targetId' => $target->id,
        'sortOrder' => 1,
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => Str::uuid()->toString(),
    ]);

    $migrations = dirname(__DIR__, 4).'/src/Database/Migrations';
    (require "$migrations/2026_06_02_230524_link_references.php")->up();
    (require "$migrations/2026_11_17_000000_update_field_references.php")->up();

    expect(DB::table(Table::RELATIONS)->where('fieldId', $field->id)->exists())->toBeFalse()
        ->and(trackedFieldReferenceRows())->toBe($expected);
});
