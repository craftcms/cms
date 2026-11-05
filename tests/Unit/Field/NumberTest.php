<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Number;

test('getPreviewHtml returns unformatted numbers as strings', function (int|float $value, string $expected) {
    $field = new Number(['previewFormat' => Number::FORMAT_NONE]);

    expect($field->getPreviewHtml($value, new Entry))->toBe($expected);
})->with([
    'int' => [1889, '1889'],
    'float' => [1.5, '1.5'],
]);
