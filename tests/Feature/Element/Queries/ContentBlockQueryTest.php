<?php

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element as ElementModel;
use CraftCms\Cms\Element\Queries\ContentBlockQuery;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\ContentBlock as ContentBlockField;
use CraftCms\Cms\Field\Elements\ContentBlock;
use CraftCms\Cms\Field\Models\Field;
use Illuminate\Support\Facades\DB;

it('queries content blocks', function () {
    $field = Field::factory()->create([
        'type' => ContentBlockField::class,
    ]);
    $owner = Entry::factory()->create();
    $contentBlock = ElementModel::factory()->create([
        'type' => ContentBlock::class,
    ]);

    DB::table(Table::CONTENTBLOCKS)
        ->insert([
            'id' => $contentBlock->id,
            'primaryOwnerId' => $owner->id,
            'fieldId' => $field->id,
        ]);

    expect(new ContentBlockQuery()->count())->toBe(1);
    expect(new ContentBlockQuery()->first())->toBeInstanceOf(ContentBlock::class);
});
