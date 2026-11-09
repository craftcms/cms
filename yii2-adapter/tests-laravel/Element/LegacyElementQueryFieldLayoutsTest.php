<?php

declare(strict_types=1);

use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Tag;
use CraftCms\Cms\Asset\Elements\Asset as NativeAsset;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Models\FieldLayout as FieldLayoutModel;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;

uses(DatabaseTestCase::class);

it('includes the field layouts of the element type when no source is specified', function(string $elementType, string $layoutType) {
    $layout = FieldLayoutModel::factory()->create(['type' => $layoutType]);
    Fields::refreshFields();

    expect(array_map(fn($layout) => $layout->id, $elementType::find()->getFieldLayouts()))->toBe([$layout->id]);
})->with([
    'assets' => [Asset::class, NativeAsset::class],
    'categories' => [Category::class, Category::class],
    'tags' => [Tag::class, Tag::class],
]);

it('applies custom field params to legacy asset queries', function() {
    $field = new PlainText(['name' => 'Code', 'handle' => 'code']);
    expect(Fields::saveField($field))->toBeTrue();
    FieldLayoutModel::factory()->forField($field)->create(['type' => NativeAsset::class]);
    Fields::refreshFields();
    AssetModel::factory()->create();

    expect(Asset::find()->code('missing')->ids())->toBe([]);
});
