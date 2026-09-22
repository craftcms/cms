<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\TestClasses\Field\ThumbnailField;

beforeEach(function () {
    ThumbnailField::$requests = [];
});

it('passes mode to direct layout fields', function (ImageTransformMode $mode) {
    $entry = Entry::factory()->createElement();
    $field = new class extends BaseField
    {
        public array $requests = [];

        public function attribute(): string
        {
            return 'title';
        }

        public function thumbHtml(ElementInterface $element, int $size, ImageTransformMode $mode = ImageTransformMode::Fit): string
        {
            $this->requests[] = [$element, $size, $mode];

            return '<b>Native field thumbnail</b>';
        }
    };
    $field->uid = Str::uuid()->toString();
    $layout = FieldLayout::make($entry::class)
        ->tab('Content', fn (FieldLayoutTab $tab) => $tab->add($field));

    expect($layout->getThumbHtmlForElement("layoutElement:{$field->uid}", $entry, 120, $mode))
        ->toBe('<b>Native field thumbnail</b>')
        ->and($field->requests)->toBe([[$entry, 120, $mode]]);
})->with([
    'crop' => ImageTransformMode::Crop,
    'fit' => ImageTransformMode::Fit,
    'stretch' => ImageTransformMode::Stretch,
    'letterbox' => ImageTransformMode::Letterbox,
]);

it('preserves custom field HTML and passes the normalized value and owner', function (ImageTransformMode $mode) {
    $entry = Entry::factory()->withField('thumbnail', ThumbnailField::class, value: '<b>Custom thumbnail</b>')
        ->createElementWithFields(save: false)->element;
    $layout = $entry->getFieldLayout();
    $field = $layout->getCustomFieldElements()[0];
    $layout->thumbFieldKey = "layoutElement:{$field->uid}";

    expect($entry->getThumbHtml(128, $mode))->toBe('<b>Custom thumbnail</b>')
        ->and(ThumbnailField::$requests)->toBe([['<b>Custom thumbnail</b>', $entry, 128, $mode]]);
})->with([
    'crop' => ImageTransformMode::Crop,
    'fit' => ImageTransformMode::Fit,
    'stretch' => ImageTransformMode::Stretch,
    'letterbox' => ImageTransformMode::Letterbox,
]);

it('changes ownership while preserving size and mode through content blocks', function (ImageTransformMode $mode) {
    $innerField = Field::factory()->create(['handle' => 'innerThumbnail', 'type' => ThumbnailField::class]);
    Fields::refreshFields();
    $innerUid = Str::uuid()->toString();
    $entry = Entry::factory()->withField('block', ContentBlock::class, [
        'fieldLayouts' => [
            Str::uuid()->toString() => [
                'tabs' => [[
                    'name' => 'Content',
                    'elements' => [[
                        'uid' => $innerUid,
                        'type' => CustomField::class,
                        'fieldUid' => $innerField->uid,
                    ]],
                ]],
            ],
        ],
    ], value: ['fields' => ['innerThumbnail' => '<b>Nested thumbnail</b>']])
        ->createElementWithFields(save: false)->element;
    $layout = $entry->getFieldLayout();
    $outerField = $layout->getCustomFieldElements()[0];
    $layout->thumbFieldKey = "contentBlock:{$outerField->uid}.layoutElement:{$innerUid}";
    $block = $entry->getFieldValue('block');

    expect($entry->getThumbHtml(120, $mode))->toBe('<b>Nested thumbnail</b>')
        ->and(ThumbnailField::$requests)->toBe([['<b>Nested thumbnail</b>', $block, 120, $mode]])
        ->and($block)->not->toBe($entry);
})->with([
    'crop' => ImageTransformMode::Crop,
    'fit' => ImageTransformMode::Fit,
    'stretch' => ImageTransformMode::Stretch,
    'letterbox' => ImageTransformMode::Letterbox,
]);

it('defaults custom field dispatch to fit and keeps invalid targets nullable', function () {
    $entry = Entry::factory()->withField('thumbnail', ThumbnailField::class, value: 'Thumbnail')
        ->createElementWithFields(save: false)->element;
    $layout = $entry->getFieldLayout();
    $field = $layout->getCustomFieldElements()[0];

    expect($layout->getThumbHtmlForElement("layoutElement:{$field->uid}", $entry, 120))->toBe('Thumbnail')
        ->and(ThumbnailField::$requests)->toBe([['Thumbnail', $entry, 120, ImageTransformMode::Fit]])
        ->and($layout->getThumbHtmlForElement('layoutElement:missing', $entry, 120, ImageTransformMode::Crop))->toBeNull()
        ->and($layout->getThumbHtmlForElement("contentBlock:{$field->uid}.layoutElement:missing", $entry, 120, ImageTransformMode::Crop))->toBeNull()
        ->and($layout->getThumbHtmlForElement('unknown:missing', $entry, 120, ImageTransformMode::Crop))->toBeNull();
});
