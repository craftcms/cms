<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Field\ContentBlock as ContentBlockField;
use CraftCms\Cms\Field\Elements\ContentBlock as ContentBlockElement;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Support\Facades\Fields;

function createContentBlockElement(): ContentBlockElement
{
    $field = Field::factory()->create([
        'type' => ContentBlockField::class,
    ]);

    Fields::refreshFields();

    $contentBlock = new ContentBlockElement;
    $contentBlock->fieldId = $field->id;

    return $contentBlock;
}

describe('Edge cases', function () {
    test('fieldId accepts null when not installed', function () {
        $contentBlock = createContentBlockElement();
        Cms::setIsInstalled(false);
        $contentBlock->fieldId = null;

        try {
            $contentBlock->validate(['fieldId']);
        } finally {
            Cms::setIsInstalled(true);
        }

        expect($contentBlock->errors()->has('fieldId'))->toBeFalse();
    });

    test('inherited base Element validation works', function () {
        $contentBlock = createContentBlockElement();
        $contentBlock->title = str_repeat('a', 256);

        $contentBlock->validate(['title']);

        expect($contentBlock->errors()->has('title'))->toBeTrue();
    });
});
