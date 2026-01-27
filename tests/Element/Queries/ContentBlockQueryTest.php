<?php

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Queries\ContentBlockQuery;
use CraftCms\Cms\Field\Elements\ContentBlock;
use CraftCms\Cms\Field\Models\Field;
use Illuminate\Support\Facades\DB;

it('queries content blocks', function () {
    $field = Field::factory()->create();

    DB::table(Table::CONTENTBLOCKS)
        ->insert([
            'primaryOwnerId' => 1,
            'fieldId' => $field->id,
        ]);

    expect(new ContentBlockQuery()->count())->toBe(1);
    expect(new ContentBlockQuery()->first())->toBeInstanceOf(ContentBlock::class);
});
